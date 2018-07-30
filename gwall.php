<?php

$settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];


$gateways = array();

try 
{
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt;
  if(isset($_REQUEST['gwall']) and ($_REQUEST['gwall']==1 or $_REQUEST['gwall']=="on") )
  {
    $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates");
  }
  else if(isset($_REQUEST['gwall']) and $_REQUEST['gwall']=="online") {
    $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates WHERE `last_update` > (NOW() - INTERVAL 1 HOUR)"); 
  }
  else
  {
    $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateways_aggregated WHERE `last_heard` > (NOW() - INTERVAL 5 DAY)"); 
  }
  $stmt->execute();
  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $k=>$v) { 
    $gateways[] = $v['gwaddr'];
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