<?php

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username   = $settings['database_postgresql']['username'];
$password   = $settings['database_postgresql']['password'];
$dbname     = $settings['database_postgresql']['database'];
$servername = $settings['database_postgresql']['host'];
$serverport = $settings['database_postgresql']['port'];


if(!isset($_REQUEST["gateway"])) {
  echo "No gateway ID specified.";
  die();
}


$gateway = urldecode($_REQUEST["gateway"]);
$startdate = 0;
$enddate = time();


if(!isset($_REQUEST["startdate"]) or $_REQUEST["startdate"]=="") {
  $startdate = 0; // 1970-01-01
}
else {
  $startdate = strtotime($_REQUEST["startdate"]);
  if($enddate === false) {
    echo '{"error": true, "error_message": "Could not parse startdate"}';
    die();
  }
}


if(!isset($_REQUEST["enddate"]) or $_REQUEST["enddate"]=="") {
  // End of today's server time
  $enddate = strtotime("today") + 24*60*60;
}
else {
  $enddate = strtotime($_REQUEST["enddate"]);
  if($enddate === false) {
    echo '{"error": true, "error_message": "Could not parse enddate"}';
    die();
  }

  // When only a date is given the time part will be set to all 0's in the parsed timestamp.
  // Increment by one day to include the speicified day's data.
  $date = new DateTime();
  $date->setTimestamp($enddate);

  // If someone types a date with the time part set to all 0, we will select an extra day. 
  // The chance of this happening is small enough for us to accept this bug.
  // We handle the case with Y-m-d and Ymd later on.
  if( $date->format('H') == "00" 
    and $date->format('i') == "00" 
    and $date->format('s') == "00") {

    // If the time part was not specified as 0
    if (strpos($_REQUEST["enddate"], '00:00:00') === false 
      and strpos($_REQUEST["enddate"], '00:00') === false )
    {
      // Include this day's data
      $enddate = $enddate + 24*60*60;
    }
  }
}


try {

  $startDateObj = new DateTime();
  $startDateObj->setTimestamp($startdate);

  $endDateObj = new DateTime();
  $endDateObj->setTimestamp($enddate);

  $startDateStr = $startDateObj->format('Y-m-d H:i:s');
  $endDateStr = $endDateObj->format('Y-m-d H:i:s');

} catch  (Exception $e) {
  echo '{"error": true, "error_message": "Could not parse datetime"}';
  die();
}


$points = array();

try 
{
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
SELECT packets.id as id, time, gateway_id as gwaddr, 
latitude as lat, longitude as lon, altitude as alt, accuracy_meters as accuracy, 
rssi, snr, dev_id, app_id,
'SF' || dr.spreading_factor || 'BW' || dr.bandwidth/1000 as datarate 
FROM packets
JOIN antennas a on packets.antenna_id = a.id
JOIN devices d on packets.device_id = d.id
JOIN data_rates dr on packets.data_rate_id = dr.id
-- WHERE (a.network_id = 'NS_TTS_V3://eu1.cloud.thethings.network' OR a.network_id = 'NS_TTS_V3://ttn.eu1.cloud.thethings.network')
WHERE a.gateway_id = :gateway
-- AND NOT (latitude = 0.0 AND longitude = 0.0)
AND time > :startdate
AND time < :enddate
AND packets.experiment_id IS NULL
-- ORDER BY time DESC 
LIMIT 50000
SQL;

  $stmt = $conn->prepare($query);
  $stmt->bindParam(':gateway', $gateway, PDO::PARAM_STR);
  $stmt->bindParam(':startdate', $startDateStr, PDO::PARAM_STR);
  $stmt->bindParam(':enddate', $endDateStr, PDO::PARAM_STR);
  $stmt->execute();

  $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $lineNr=>$row) { 
    $point = array();

    foreach ($row as $key => $value) {
      $point[$key] = $value;
    }

    $points[] = $point;
  }
  
  $json_response = array("points" => $points, "error" => false);
  echo json_encode($json_response);

}
catch(PDOException $e)
{
  echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
}
$conn = null;


?>