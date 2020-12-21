<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);






/**
 *  An example CORS-compliant method.  It will allow any GET, POST, or OPTIONS requests from any
 *  origin.
 *
 *  In a production environment, you probably want to be more restrictive, but this gives you
 *  the general idea of what is involved.  For the nitty-gritty low-down, read:
 *
 *  - https://developer.mozilla.org/en/HTTP_access_control
 *  - http://www.w3.org/TR/cors/
 *
 */
function cors() {

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}





cors();




$columns_blacklist = array("user_id", "mqtt_topic");

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];


if(!isset($_REQUEST["application"]) && !isset($_REQUEST["device"]) && !isset($_REQUEST["user"]) && !isset($_REQUEST["experiment"])) {
  echo "No filter specified";
  die();
}


$application = urldecode($_REQUEST["application"]);
$device = urldecode($_REQUEST["device"]);
$user = urldecode($_REQUEST["user"]);
$experiment = urldecode($_REQUEST["experiment"]);


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
  $query = "";
  if(isset($_REQUEST['experiment']) and $experiment!="") {
    addExperimentsData();
  }
  else {
    addExperimentsData();
    addPacketsData();
  }
  
  $json_response = array("points" => $points, "error" => false);
  echo json_encode($json_response);

}
catch(PDOException $e)
{
  echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
}
$conn = null;


function addExperimentsData() {
  global $conn;
  global $points;
  global $application;
  global $device;
  global $user;
  global $experiment;
  global $startDateStr;
  global $endDateStr;
  global $columns_blacklist;

  $query = "SELECT * FROM experiments WHERE ";

  if(isset($_REQUEST['application']) and $application!="") {
    $query = $query." appeui = \"".$application."\" AND";
  }
  if(isset($_REQUEST['device']) and $device!="") {
    $query = $query." nodeaddr = \"".$device."\" AND";
  }
  if(isset($_REQUEST['user']) and $user!="") {
    $query = $query." user_id = \"".$user."\" AND";
  }
  if(isset($_REQUEST['experiment']) and $experiment!="") {
    $query = $query." name=\"".$experiment."\" AND";
  }

  $query = $query." `time` > :startdate AND `time` < :enddate ORDER BY `fcount`, `id` ASC LIMIT 1000";
  if(isset($_REQUEST["page"])) {
    $page = $_REQUEST["page"] * 1000;
    $query = $query." OFFSET ".$page;
  }
//print($startDateStr);
//print($endDateStr);
//print($query);
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":startdate", $startDateStr);
  $stmt->bindParam(":enddate", $endDateStr);
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
}

function addPacketsData() {
  global $conn;
  global $points;
  global $application;
  global $device;
  global $user;
  global $startDateStr;
  global $endDateStr;
  global $columns_blacklist;

  $query = "SELECT * FROM packets WHERE";

  if(isset($_REQUEST['application']) and $application!="") {
    $query = $query." appeui = \"".$application."\" AND";
  }
  if(isset($_REQUEST['device']) and $device!="") {
    $query = $query." nodeaddr = \"".$device."\" AND";
  }
  if(isset($_REQUEST['user']) and $user!="") {
    $query = $query." user_id = \"".$user."\" AND";
  }

  $query = $query." `time` > :startdate AND `time` < :enddate ORDER BY `fcount`, `id` ASC LIMIT 1000";
  if(isset($_REQUEST["page"])) {
    $page = $_REQUEST["page"] * 1000;
    $query = $query." OFFSET ".$page;
  }
// print($query);
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":startdate", $startDateStr);
  $stmt->bindParam(":enddate", $endDateStr);
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
}

?>