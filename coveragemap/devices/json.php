<?php

$columns_blacklist = array("user_id", "mqtt_topic");

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];


if(!isset($_REQUEST["device"])) {
  echo "No device ID specified.";
  die();
}


$device = urldecode($_REQUEST["device"]);
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
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Run data query
  $stmt = $conn->prepare("SELECT * FROM packets WHERE nodeaddr=:device AND `time` > :startdate AND `time` < :enddate ORDER BY `time` DESC LIMIT 10000");
  $stmt->bindParam(':device', $device, PDO::PARAM_STR);
  $stmt->bindParam(':startdate', $startDateStr, PDO::PARAM_STR);
  $stmt->bindParam(':enddate', $endDateStr, PDO::PARAM_STR);
  $stmt->execute();

  $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $lineNr=>$row) { 
    $point = array();

    foreach ($row as $key => $value) {
      if(!in_array($key, $columns_blacklist)) {
        $point[$key] = $value;
      }
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