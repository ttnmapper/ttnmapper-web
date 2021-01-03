<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ?tile=12/2125/1348
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

//$cache_location = "/mnt/localssd/tiles";
if($settings['tms']['tile_cache_location'] === NULL or $settings['tms']['tile_cache_location'] === "") {
  $cache_location = getenv("TTNMAPPER_HOME")."/tms/tiles";
} else {
  $cache_location = $settings['tms']['tile_cache_location'];
}

$enable_caching = false;
$zoom_switchover = 18; // never switch over to raw packet mode as that creates too high db load

main();

/**
* Strong Blur
*
* @param resource $gdImageResource 
* @param int $blurFactor optional 
*  This is the strength of the blur
*  0 = no blur, 3 = default, anything over 5 is extremely blurred
* @return GD image resource
* @author Martijn Frazer, idea based on http://stackoverflow.com/a/20264482
*/
function blur($gdImageResource, $blurFactor = 3)
{
  // blurFactor has to be an integer
  $blurFactor = round($blurFactor);
  
  $originalWidth = imagesx($gdImageResource);
  $originalHeight = imagesy($gdImageResource);

  $smallestWidth = ceil($originalWidth * pow(0.5, $blurFactor));
  $smallestHeight = ceil($originalHeight * pow(0.5, $blurFactor));

  // for the first run, the previous image is the original input
  $prevImage = $gdImageResource;
  $prevWidth = $originalWidth;
  $prevHeight = $originalHeight;

  // scale way down and gradually scale back up, blurring all the way
  for($i = 0; $i < $blurFactor; $i += 1)
  {    
    // determine dimensions of next image
    $nextWidth = $smallestWidth * pow(2, $i);
    $nextHeight = $smallestHeight * pow(2, $i);

    // resize previous image to next size
    $nextImage = imagecreatetruecolor($nextWidth, $nextHeight);
    imagecopyresized($nextImage, $prevImage, 0, 0, 0, 0, 
      $nextWidth, $nextHeight, $prevWidth, $prevHeight);

    // apply blur filter
    imagefilter($nextImage, IMG_FILTER_GAUSSIAN_BLUR);

    // now the new image becomes the previous image for the next step
    $prevImage = $nextImage;
    $prevWidth = $nextWidth;
      $prevHeight = $nextHeight;
  }

  // scale back to original size and blur one more time
  imagecopyresized($gdImageResource, $nextImage, 
    0, 0, 0, 0, $originalWidth, $originalHeight, $nextWidth, $nextHeight);
  imagefilter($gdImageResource, IMG_FILTER_GAUSSIAN_BLUR);

  // clean up
  imagedestroy($prevImage);

  // return result
  return $gdImageResource;
}

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
  global $enable_caching;
  global $zoom_switchover;
  // if($z<$zoom_switchover)
  // {
  //   if( !$enable_caching or (time()-@filemtime($filename) > 48 * 3600) ) { //48 hour caching
  //     createTileAggregatedSamples($x, $y, $z);
  //     //createTileRawSamples($x, $y, $z);
  //   }
  // }
  // elseif($z<17)
  // {
  //   if( !$enable_caching or (time()-@filemtime($filename) > 48 * 3600) ) { //48 hour caching
  //     createTileRawSamples($x, $y, $z);
  //   }
  // }
  // elseif($z<18)
  // {
  //   if( !$enable_caching or (time()-@filemtime($filename) > 48 * 3600) ) { //48 hour caching
  //     createTileRawSamples($x, $y, $z);
  //   }
  // }
  // else
  // {
    createTileRawSamples($x, $y, $z);
  // }

  tileToBrowser($x, $y, $z);
  // logTile($x, $y, $z);
}

function logTile($x, $y, $z)
{
  try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("INSERT INTO `heatmap_usage`(`x`, `y`, `z`) VALUES (:x,:y,:z)");

    $stmt->bindParam(':x', $x);
    $stmt->bindParam(':y', $y);
    $stmt->bindParam(':z', $z);

    $stmt->execute();
  }
  catch(PDOException $e) {
      error_log("Caught $e");//$e->getMessage();
  }
}

function createTileAggregatedSamples($x, $y, $z)
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
  $red = imagecolorallocatealpha($image, 255, 0, 0, 255);

  try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT lat,lon,max(rssiavg) as rssi FROM 5mdeg WHERE lat>:lat_min_pad AND lon>:lon_min_pad AND lat<:lat_max_pad AND lon<:lon_max_pad GROUP BY lat,lon ORDER BY rssi ASC");

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

      if($v['rssi'] < -120)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $blue);
      }
      elseif($v['rssi'] < -115)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $cyan);
      }
      elseif($v['rssi'] < -110)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $green);
      }
      elseif($v['rssi'] < -105)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $yellow);
      }
      elseif($v['rssi'] < -100)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $orange);
      }
      elseif($v['rssi'] < 0)
      {
        imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $red);
      }
      else
      {
        //imagefilledrectangle( $image, $startx, $starty, $endx, $endy, $transparent);
      }
    }

    $conn=null; //closes the db connection

    global $cache_location;
    $filename = $cache_location."/".$z."/".$x."/".$y.".png";
    imagepng($image, $filename);
  }
  catch(PDOException $e) {
      error_log("Caught $e");//$e->getMessage();
  }
}

function createTileRawSamples($x, $y, $z)
{
  $max = pow(2, $z);

  $lon_min = $x / $max * 360.0 - 180.0;
  $lon_max = ($x+1) / $max * 360.0 - 180.0;
  $lat_max = rad2deg(atan(sinh(pi() * (1 - 2 * $y / $max))));
  $lat_min = rad2deg(atan(sinh(pi() * (1 - 2 * ($y+1) / $max))));

  $lon_width = $lon_max - $lon_min;
  $lat_height = $lat_max - $lat_min;

  // Select one tile width extra to all sides
  $lon_min_data = $lon_min - $lon_width;
  $lon_max_data = $lon_max + $lon_width;
  $lat_min_data = $lat_min - $lat_height;
  $lat_max_data = $lat_max + $lat_height;

  // echo $lon_min_data;
  // echo "<br />";
  // echo $lon_max_data;
  // echo "<br />";
  // echo $lat_min_data;
  // echo "<br />";
  // echo $lat_max_data;

  $conn=null; //closes the db connection

  //create the image from the array
  $image = @imagecreate(768, 768) or die("Cannot Initialize new GD image stream");
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

  // draw image content
  // try 
  // {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $pgusername = $settings['database_postgres']['username'];
    $pgpassword = $settings['database_postgres']['password'];
    $pgdbname = $settings['database_postgres']['database'];
    $pgservername = $settings['database_postgres']['host'];

    $connPG = new PDO("pgsql:host=$pgservername;dbname=$pgdbname", $pgusername, $pgpassword);
    $connPG->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    // $myusername = $settings['database_mysql']['username'];
    // $mypassword = $settings['database_mysql']['password'];
    // $mydbname = $settings['database_mysql']['database'];
    // $myservername = $settings['database_mysql']['host'];
    $myusername = "ttnmapper";
    $mypassword = "ttnmapperpassword";
    $mydbname = "ttnmapper";
    $myservername = "10.6.27.53";

    $connMY = new PDO("mysql:host=$myservername;dbname=$mydbname", $myusername, $mypassword);
    $connMY->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $stmt = $connMY->prepare("SELECT gweui FROM gateway_bbox 
      WHERE lat_max>:latmin AND lat_min<:latmax AND lon_max>:lonmin AND lon_min<:lonmax");
    $stmt->bindParam(':latmin', $lat_min);
    $stmt->bindParam(':lonmin', $lon_min);
    $stmt->bindParam(':latmax', $lat_max);
    $stmt->bindParam(':lonmax', $lon_max);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    
    $gateways = array();
    foreach($stmt->fetchAll() as $k=>$v) { 
      $gateways[] = $v['gweui'];
    }

    $data = array();
    global $zoom_switchover;

header( 'Content-type: text/html; charset=utf-8' );
    ob_end_flush();

    echo("Gateway count: ".sizeof($gateways)."<br />");

    foreach ($gateways as $key => $value) {

      $time_start = microtime(true);

      echo($value);

      // Get the time the gateway was installed at the current location
      $stmt = $connMY->prepare("SELECT datetime FROM gateway_updates WHERE gwaddr = :gwaddr ORDER BY datetime DESC LIMIT 1");
      $stmt->bindParam(':gwaddr', $value);
      $stmt->execute();
      $time = $stmt->fetch()["datetime"];

      $time_end = microtime(true);
      $execution_time = ($time_end - $time_start);
      echo '<br /><b>Last move:</b> '.$execution_time.'s ';
      echo($time);

      // Get the foreign key for this gateway
      $stmt = $connPG->prepare("SELECT id FROM ttnmapper_gateways WHERE gtw_id = :gwaddr");
      $stmt->bindParam(':gwaddr', $value);
      $stmt->execute();
      $gtw_pg_id = $stmt->fetch()["id"];

      $time_end = microtime(true);
      $execution_time = ($time_end - $time_start);
      echo '<br /><b>PG ID:</b> '.$execution_time.'s ';
      echo($gtw_pg_id);

      // Get the data for this gateway, for this block, since the last move
      $stmt = $connPG->prepare(
        "SELECT latitude,longitude,rssi FROM ttnmapper_packets_ts ".
        "WHERE gateway_id = :gtw_pg_id ".
        "AND   time > :time ".
        "AND   latitude>:lat_min_pad ".
        "AND   longitude>:lon_min_pad ".
        "AND   latitude<:lat_max_pad ".
        "AND   longitude<:lon_max_pad "
        //"ORDER BY time DESC LIMIT 1"
      );

      $stmt->bindParam(':lat_min_pad', $lat_min_data);
      $stmt->bindParam(':lon_min_pad', $lon_min_data);
      $stmt->bindParam(':lat_max_pad', $lat_max_data);
      $stmt->bindParam(':lon_max_pad', $lon_max_data);
      $stmt->bindParam(':gtw_pg_id', $gtw_pg_id);
      $stmt->bindParam(':time', $time);
      
      $stmt->execute();
      
      $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
      foreach($stmt->fetchAll() as $k => $v) { 
        $data[] = $v;
      }

      $time_end = microtime(true);
      $execution_time = ($time_end - $time_start);
      echo '<br /><b>Total Execution Time:</b> '.$execution_time.'s<br />';
      flush();
      //ob_flush();
    }

    usort($data, function ($item1, $item2) {
        return $item1['rssi'] <=> $item2['rssi'];
    });

    die();

    foreach ($data as $k => $v) {

      $cx = ($v['longitude']-$lon_min_data)/$lon_width * 256.0;
      $cy = 768-($v['latitude']-$lat_min_data)/$lat_height * 256.0;

      if($v['rssi'] < -120)
      {
        imagefilledellipse( $image, $cx, $cy, 40, 40, $blue);
      }
      elseif($v['rssi'] < -115)
      {
        imagefilledellipse( $image, $cx, $cy, 36, 36, $cyan);
      }
      elseif($v['rssi'] < -110)
      {
        imagefilledellipse( $image, $cx, $cy, 32, 32, $green);
      }
      elseif($v['rssi'] < -105)
      {
        imagefilledellipse( $image, $cx, $cy, 28, 28, $yellow);
      }
      elseif($v['rssi'] < -100)
      {
        imagefilledellipse( $image, $cx, $cy, 24, 24, $orange);
      }
      elseif($v['rssi'] < 0)
      {
        imagefilledellipse( $image, $cx, $cy, 20, 20, $red);
      }
      else
      {
        //imagefilledellipse( $image, $cx, $cy, 20, 20, $transparent);
      }
    }
  // }
  // catch(PDOException $e) {
  //     error_log("Caught $e");//$e->getMessage();
  // }

  // $image = blur($image, $blurFactor = 1);
  // imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
  // imagefilter($image, IMG_FILTER_SMOOTH);

  // crop image
  //create the image from the array
  $croppedimage = @imagecreate(256, 256) or die("Cannot Initialize new GD image stream");
  imagesavealpha($croppedimage, true);
  imagealphablending($croppedimage, false); //???
  $transparent = imagecolorallocatealpha($croppedimage, 0, 0, 0, 0);
  $transparentColour = imagecolortransparent ($croppedimage, $transparent );
  $blue = imagecolorallocatealpha($croppedimage, 0, 0, 255, 0);
  $cyan = imagecolorallocatealpha($croppedimage, 0, 255, 255, 0);
  $green = imagecolorallocatealpha($croppedimage, 0, 255, 0, 0);
  $yellow = imagecolorallocatealpha($croppedimage, 255, 255, 0, 0);
  $orange = imagecolorallocatealpha($croppedimage, 255, 127, 0, 0);
  $red = imagecolorallocatealpha($croppedimage, 255, 0, 0, 255);

  imagecopy( $croppedimage, $image, 0, 0, 256, 256, 256, 256);

  global $cache_location;
  $filename = $cache_location."/".$z."/".$x."/".$y.".png";
  imagepng($croppedimage, $filename);
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
