<?php

$columns_blacklist = array("user_id", "mqtt_topic");

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];


if(!isset($_REQUEST["gateway"])) {
  echo "No gateway ID specified.";
  die();
}

if(!isset($_REQUEST["startdate"]) or $_REQUEST["startdate"]=="") {
  $_REQUEST["startdate"] = "0"; // 1970-01-01
}

if(!isset($_REQUEST["enddate"]) or $_REQUEST["enddate"]=="") {
  $_REQUEST["enddate"] = time(); // today
}


$gateway = $_REQUEST["gateway"];
$startdate = $_REQUEST["startdate"];
$enddate = $_REQUEST["enddate"];

try {

  $startDateObj = new DateTime();
  try {
    $startDateObj = new DateTime($startdate);
  } catch  (Exception $e) {
    $startDateObj->setTimestamp($startdate);
  }

  $endDateObj = new DateTime();
  try {
    $endDateObj = new DateTime($enddate);
  } catch  (Exception $e) {
    $endDateObj->setTimestamp($enddate);
  }

  $testEndDateOnly = DateTime::createFromFormat("Y-m-d", $enddate);
  $testEndDateOnlyCompact = DateTime::createFromFormat("Ymd", $enddate);
  if ($testEndDateOnly || $testEndDateOnlyCompact) {
    // Only an end date was set, so we should include the end day
    $endDateObj->add(new DateInterval('P1D'));
  }

  $startDateStr = $startDateObj->format('Y-m-d H:i:s');
  $endDateStr = $endDateObj->format('Y-m-d H:i:s');

} catch  (Exception $e) {
  echo "Can not parse datetime";
  die;
}


$points = array();

try 
{
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Run data query
  $stmt = $conn->prepare("SELECT * FROM packets WHERE gwaddr=:gateway AND `time` > :startdate AND `time` < :enddate ORDER BY `time` DESC LIMIT 10000");
  $stmt->bindParam(':gateway', $gateway, PDO::PARAM_STR);
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