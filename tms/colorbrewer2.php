<?php
// ?tile=12/2125/1348
$cache_location = "/tmp/ttnmappertiles-colorbrewer2";
$zoom_switchover = 13;

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

  /*
  http://codereview.stackexchange.com/questions/72020/local-image-cache-for-external-images

  Don't check whether your local file exists first; you don't have to. You can just prepend filemtime() by an @, which will keep it from outputting any error messages and still return 0 in case the file doesn't exist (which will simply be interpretes as the file being ancient).
  */
  global $zoom_switchover;
  if($z<$zoom_switchover)
  {
  //   if(time()-@filemtime($filename) > 24 * 3600){ //24 hour caching for high zoom levels
      createTile($x, $y, $z);
  //   }
  }
  else
  {
  //   if(time()-@filemtime($filename) > 6 * 3600){ //6 hour caching
      createTile($x, $y, $z);
    // }
  }

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

  global $zoom_switchover;
  if($z<$zoom_switchover)
  {
    $sample_width = (0.005/$lon_width)*256.0;
    $sample_height = (0.005/$lat_height)*256.0;
  }
  else
  {
    $sample_width = (0.0005/$lon_width)*256.0;
    $sample_height = (0.0005/$lat_height)*256.0;
  }

  // echo "bbox:<br />".$lat_min.",".$lon_min."<br />".$lat_max.",".$lon_max."<br>";

  $imagedata = array_fill(0, 256, array_fill(0, 256, 0));

  try {
      $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

      $username = $settings['database_mysql']['username'];
      $password = $settings['database_mysql']['password'];
      $dbname = $settings['database_mysql']['database'];
      $servername = $settings['database_mysql']['host'];

      $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      

      if($z<$zoom_switchover)
      {
        $stmt = $conn->prepare("SELECT lat,lon,max(rssiavg) as rssi FROM 5mdeg WHERE lat>:lat_min_pad AND lon>:lon_min_pad AND lat<:lat_max_pad AND lon<:lon_max_pad GROUP BY lat,lon ORDER BY rssi ASC");

        $lat_min_pad = $lat_min-0.005;
        $lon_min_pad = $lon_min-0.005;
        $lat_max_pad = $lat_max+0.005;
        $lon_max_pad = $lon_max+0.005;
      }
      else
      {
        $stmt = $conn->prepare("SELECT lat,lon,max(rssiavg) as rssi FROM 500udeg WHERE lat>:lat_min_pad AND lon>:lon_min_pad AND lat<:lat_max_pad AND lon<:lon_max_pad GROUP BY lat,lon ORDER BY rssi ASC");

        $lat_min_pad = $lat_min-0.0005;
        $lon_min_pad = $lon_min-0.0005;
        $lat_max_pad = $lat_max+0.0005;
        $lon_max_pad = $lon_max+0.0005;
      }

      $stmt->bindParam(':lat_min_pad', $lat_min_pad);
      $stmt->bindParam(':lon_min_pad', $lon_min_pad);
      $stmt->bindParam(':lat_max_pad', $lat_max_pad);
      $stmt->bindParam(':lon_max_pad', $lon_max_pad);


  // echo "pad:<br />".$lat_min_pad.",".$lon_min_pad."<br />".$lat_max_pad.",".$lon_max_pad."<br>";

      $stmt->execute();

      // echo "db entries: ".($stmt->rowCount());

      // set the resulting array to associative
      $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
      foreach($stmt->fetchAll() as $k=>$v) { 
        $pixel_width = ($v['lon']-$lon_min)/$lon_width * 256.0;
        $pixel_width_min = round($pixel_width - $sample_width/2);
        $pixel_width_max = round($pixel_width + $sample_width/2);

        $pixel_height = ($v['lat']-$lat_min)/$lat_height * 256.0;
        $pixel_height_min = round($pixel_height - $sample_height/2);
        $pixel_height_max = round($pixel_height + $sample_height/2);

        // echo $v['lat']."<br>";
        // echo $lat_min."<br>";

        // echo $pixel_height."<br>";
        // echo $pixel_height_min."<br>";
        // echo $pixel_height_max."<br>";

        if($pixel_width_min<0) $pixel_width_min=0;
        if($pixel_height_min<0) $pixel_height_min=0;
        if($pixel_width_max>255) $pixel_width_max=255;
        if($pixel_height_max>255) $pixel_height_max=255;

        for ($i=$pixel_height_min; $i<=$pixel_height_max; $i++) {
          for ($j=$pixel_width_min; $j<=$pixel_width_max; $j++){
            // echo ($i.",".$j." ");
            if($v['rssi']>$imagedata[$i][$j] or $imagedata[$i][$j]==0)
            {
              $imagedata[$i][$j] = $v['rssi'];
            }
          }
          // echo "<br>";
        }
      }

      $conn=null; //closes the db connection

      //create the image from the array
      $image = @imagecreate(256, 256) or die("Cannot Initialize new GD image stream");
      imagesavealpha($image, true);
      imagealphablending($image, false); //???
      $transparent = imagecolorallocatealpha($image, 0, 0, 0, 0);
      $transparentColour = imagecolortransparent ($image, $transparent );
      $blue = imagecolorallocatealpha($image, 255, 255, 178, 0);
      $cyan = imagecolorallocatealpha($image, 254, 217, 118, 0);
      $green = imagecolorallocatealpha($image, 254, 178, 76, 0);
      $yellow = imagecolorallocatealpha($image, 253, 151, 60, 0);
      $orange = imagecolorallocatealpha($image, 240, 59, 32, 0);
      $red = imagecolorallocatealpha($image, 189, 0, 38, 0);

      for($i=0; $i<256; $i++)
      {
        for($j=0; $j<256; $j++)
        {
          // echo $imagedata[$i][$j];
          if($imagedata[$i][$j] < -120)
          {
            imagesetpixel($image, $j, 255-$i, $blue);
            // echo "b";
          }
          elseif($imagedata[$i][$j] < -115)
          {
            imagesetpixel($image, $j, 255-$i, $cyan);
            // echo "c";
          }
          elseif($imagedata[$i][$j] < -110)
          {
            imagesetpixel($image, $j, 255-$i, $green);
            // echo "g";
          }
          elseif($imagedata[$i][$j] < -105)
          {
            imagesetpixel($image, $j, 255-$i, $yellow);
            // echo "y";
          }
          elseif($imagedata[$i][$j] < -100)
          {
            imagesetpixel($image, $j, 255-$i, $orange);
            // echo "o";
          }
          elseif($imagedata[$i][$j] < 0)
          {
            imagesetpixel($image, $j, 255-$i, $red);
            // echo "r";
          }
          else
          {
            imagesetpixel($image, $j, 255-$i, $transparent);
            // echo "-";
          }
        }
        // echo "<br>";
      }

      global $cache_location;
      $filename = $cache_location."/".$z."/".$x."/".$y.".png";
      imagepng($image, $filename);

  }
  catch(PDOException $e) {
      error_log("Caught $e");//$e->getMessage();
  }
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