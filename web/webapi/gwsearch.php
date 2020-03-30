<?php

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];

$gateways = array();

try 
{
  $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if(strlen($_REQUEST['q']) < 3) {
    echo '{"error": true, "error_message": "Query string too short"}';
    die();
  }

  $searchString = "".$_REQUEST['q']."%";

  $statement = $conn->prepare("SELECT * FROM `gateways_aggregated` WHERE gwaddr LIKE :gwsubstring LIMIT 20");
  $statement->bindParam(':gwsubstring', $searchString);
  $statement->execute();
  $results = $statement->fetchAll(PDO::FETCH_ASSOC);
  
  $json_response = array("results" => $results, "error" => false);
  echo json_encode($json_response);

}
catch(PDOException $e)
{
  echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
}
$conn = null;


?>