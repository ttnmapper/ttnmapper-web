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
SELECT a.network_id, a.gateway_id
FROM grid_cells
JOIN antennas a on a.id = grid_cells.antenna_id
WHERE x=:xtile AND y=:ytile
GROUP BY a.network_id, a.gateway_id 
SQL;
  
  $stmt = $conn->prepare($query);
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
