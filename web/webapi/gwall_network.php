<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username   = $settings['database_postgresql']['username'];
$password   = $settings['database_postgresql']['password'];
$dbname     = $settings['database_postgresql']['database'];
$servername = $settings['database_postgresql']['host'];
$serverport = $settings['database_postgresql']['port'];

$gateways = array();

$network_id = $_REQUEST['network_id'];

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

  $sql = "";

  if ($network_id == "thethingsnetwork.org") {
    $sql = "SELECT * FROM gateways  WHERE (gateways.network_id LIKE 'NS_TTS_V2://%' OR gateways.network_id LIKE 'thethingsnetwork.org') AND latitude != 0 AND longitude != 0";
    $stmt = $conn->prepare($sql);
  } else {
    $sql = "SELECT * FROM gateways  WHERE gateways.network_id = :network_id AND latitude != 0 AND longitude != 0";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':network_id', $network_id);
  }

  $stmt->execute();
  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $k=>$v) { 
    $gateways[] = $v;
  }
  
  $json_response = array("gateways" => $gateways, "error" => false);
  echo json_encode($json_response);

}
catch(PDOException $e)
{
  echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
}
$conn = null;


?>