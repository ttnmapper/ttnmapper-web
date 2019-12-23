<?php

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

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

  $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates"); 
  $stmt->execute();

  // set the resulting array to associative
  $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  foreach($stmt->fetchAll() as $k=>$v) {

    $sqlloc = $conn->prepare("SELECT * FROM gateway_updates WHERE gwaddr=:gwaddr ORDER BY datetime ASC LIMIT 1");
    $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
    $sqlloc->execute();
    $result = $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
    
    foreach($sqlloc->fetchAll() as $k=>$v) { 
      $gateway = array();
      $gateway['gtwid'] = $v['gwaddr'];
      $gateway['last_heard'] = $v['last_update'];
      $gateway['lat'] = $v['lat'];
      $gateway['lon'] = $v['lon'];
    }
    $gateways[] = $gateway;
  
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
