<?php
// ?tile=12/2125/1348
$cache_location = "/tmp/ttnmappertiles";

main();

function main()
{
  $parts = explode('/', $_REQUEST['tile']);

  if(count($parts)!=3)
  {
    echo "Invalid tile address";
    die();
  }

  $z = $parts[0];
  $x = $parts[1];
  $y = $parts[2];

  if($z > 18)
  {
    echo "Too high zoom level";
    die();
  }

  if($z < 0)
  {
    echo "Too low zoom level";
    die();
  }

  $max = pow(2, $z);

  if($x > $max or $y > $max or $x < 0 or $y < 0)
  {
    echo "Tile index out of bounds for zoom level";
    die();
  }


  //check if file exist in cache with an age less than a day
  global $cache_location;
  if (!file_exists($cache_location)) {
      mkdir($cache_location, 0777, true);
  }
  $directory = $cache_location."/".$z."/".$x."/";
  if (!file_exists($directory)) {
      mkdir($directory, 0777, true);
  }
  $filename = $directory.$y.".png";

  createTile($x, $y, $z);

  tileToBrowser($x, $y, $z);
}

function createTile($x, $y, $z)
{
  $max = pow(2, $z);

  $lon_min = $x / $max * 360.0 - 180.0;
  $lon_max = ($x+1) / $max * 360.0 - 180.0;
  $lat_max = rad2deg(atan(sinh(pi() * (1 - 2 * $y / $max))));
  $lat_min = rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $max))));

  $lon_width = $lon_max - $lon_min;
  $lat_height = $lat_max - $lat_min;

  $sample_width = (0.005/$lon_width)*256.0;
  $sample_height = (0.005/$lat_height)*256.0;

  //create the image from the array
  $image = @imagecreate(256, 256) or die("Cannot Initialize new GD image stream");
  imagesavealpha($image, true);
  imagealphablending($image, false); //???
  $transparent = imagecolorallocatealpha($image, 0, 0, 0, 0);
  $transparentColour = imagecolortransparent ($image, $transparent );
  $blue = imagecolorallocatealpha($image, 0, 0, 255, 0);
  $cyan = imagecolorallocatealpha($image, 0, 255, 255, 0);
  $green = imagecolorallocatealpha($image, 0, 255, 0, 0);
  $yellow = imagecolorallocatealpha($image, 255, 255, 0, 0);
  $orange = imagecolorallocatealpha($image, 255, 127, 0, 0);
  $red = imagecolorallocatealpha($image, 255, 0, 0, 0);
  $black = imagecolorallocatealpha($image, 0, 0, 0, 0);

  // try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT lat,lon,samples FROM 5mdeg WHERE lat>:lat_min_pad AND lon>:lon_min_pad AND lat<:lat_max_pad AND lon<:lon_max_pad");

    $lat_min_pad = $lat_min-0.005;
    $lon_min_pad = $lon_min-0.005;
    $lat_max_pad = $lat_max+0.005;
    $lon_max_pad = $lon_max+0.005;

    $stmt->bindParam(':lat_min_pad', $lat_min_pad);
    $stmt->bindParam(':lon_min_pad', $lon_min_pad);
    $stmt->bindParam(':lat_max_pad', $lat_max_pad);
    $stmt->bindParam(':lon_max_pad', $lon_max_pad);

    $stmt->execute();

    // echo "db entries: ".($stmt->rowCount());

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) {
      $cx = ($v['lon']-$lon_min)/$lon_width * 256.0;
      $cy = 256-($v['lat']-$lat_min)/$lat_height * 256.0;

      $startx = $cx;
      $starty = $cy - $sample_height;
      $endx = $cx + $sample_width;
      $endy = $cy;

      if($v['samples'] > 50000)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $red);
        imagefilledellipse( $image, $cx, $cy, 20, 20, $red);
      }
      elseif($v['samples'] > 10000)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $yellow);
      }
      elseif($v['samples'] > 1000)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $cyan);
      }
      elseif($v['samples'] > 0)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $blue);
      }
      else
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $black);
      }
    }

    $conn=null; //closes the db connection

    global $cache_location;
    $filename = $cache_location."/".$z."/".$x."/".$y.".png";
    imagepng($image, $filename);
  // }
  // catch(PDOException $e) {
  //     error_log("Caught $e");//$e->getMessage();
  // }
}

function tileToBrowser($x, $y, $z)
{
  global $cache_location;
  $filename = $cache_location."/".$z."/".$x."/".$y.".png";
  try
  {
    $image = imagecreatefrompng($filename);
    session_start(); 
    // header("Cache-Control: private, max-age=3600, pre-check=3600");
    // header("Pragma: private");
    // header("Expires: " . date(DATE_RFC822,strtotime(" 6 hour")));
    header("Content-Type: image/png");
    imagepng($image);
    imagedestroy($image);
  }
  catch(Exception $e) {
    error_log("Caught $e");
    $image = @imagecreate(256, 256) or die("Cannot Initialize new GD image stream");
    imagepng($image);
    imagedestroy($image);
  }
}

?>