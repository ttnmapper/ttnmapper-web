<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username   = $settings['database_postgresql']['username'];
$password   = $settings['database_postgresql']['password'];
$dbname     = $settings['database_postgresql']['database'];
$servername = $settings['database_postgresql']['host'];
$serverport = $settings['database_postgresql']['port'];

$lat = $_REQUEST["lat"];
$lon = $_REQUEST["lon"];
$zoom = 19;

$xtile = floor((($lon + 180) / 360) * pow(2, $zoom));
$ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
// var_dump($xtile, $ytile);

if($xtile > (pow(2, $zoom)-1)) {
  $xtile = $xtile - pow(2, $zoom);
}

$n = pow(2, $zoom);
$west = $xtile / $n * 360.0 - 180.0;
$north = rad2deg(atan(sinh(pi() * (1 - 2 * $ytile / $n))));
$east = ($xtile+1) / $n * 360.0 - 180.0;
$south = rad2deg(atan(sinh(pi() * (1 - 2 * ($ytile+1) / $n))));

// var_dump($north, $south, $west, $east);
// die();

// try 
// {
  $conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
        $servername, 
        $serverport,
        $dbname,
        $username,
        $password);

  $conn = new PDO($conStr);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Run data query
  $query = <<<SQL
SELECT d.id, d.app_id, d.dev_id 
FROM packets p
JOIN devices d on p.device_id = d.id
JOIN antennas a on p.antenna_id = a.id
JOIN grid_cells gc on a.id = gc.antenna_id
WHERE p.latitude>=:south
AND p.latitude<=:north
AND p.longitude>=:west
AND p.longitude<=:east
AND gc.x = :xtile AND gc.y = :ytile
GROUP BY d.id, d.app_id, d.dev_id 
SQL;
  
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":north", $north, PDO::PARAM_STR);
  $stmt->bindParam(":south", $south, PDO::PARAM_STR);
  $stmt->bindParam(":east", $east, PDO::PARAM_STR);
  $stmt->bindParam(":west", $west, PDO::PARAM_STR);
  $stmt->bindParam(":xtile", $xtile, PDO::PARAM_STR);
  $stmt->bindParam(":ytile", $ytile, PDO::PARAM_STR);
  $stmt->execute();

  $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  $rows = array();
  foreach($stmt->fetchAll() as $lineNr=>$row) { 
  	// var_dump($row);
    // echo ($row["id"] . ": " . $row["app_id"] . " - " . $row["dev_id"] . "\n");
    $rows[] = $row;
  }
  echo json_encode($rows);
  
// }
// catch(PDOException $e)
// {
//   echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
// }
$conn = null;

?>
