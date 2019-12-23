<?php

$received = file_get_contents('php://input');
$json_data = json_decode($received, $assoc = true, $depth = 2);
$json_data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $received), true );

if($json_data==FALSE or $json_data==NULL)
{
  echo '{"error": true, "error_message": "' . json_last_error().'"}';
  exit();
}

#{"_sw":{"lng":6.715497156312011,"lat":52.159068901046254},"_ne":{"lng":7.0063341279171425,"lat":52.296423335685716}, "iid":12345}


$gateways = array();

try 
{
  $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

  $username = $settings['database_mysql']['username'];
  $password = $settings['database_mysql']['password'];
  $dbname = $settings['database_mysql']['database'];
  $servername = $settings['database_mysql']['host'];

  $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  //check if this iid is allowed to use the api
  $stmt = $conn->prepare("SELECT * FROM instanceids WHERE iid = :iid");
  $stmt->bindParam(':iid', $json_data['iid']);
  $stmt->execute();
  if($stmt->rowCount()==0)
  {
    echo '{"error": true, "error_message": "Unauthorized"}';
    exit();
  }


  $stmt = $conn->prepare("SELECT gweui FROM gateway_bbox 
    WHERE lat_max>:swlat AND lat_min<:nelat AND lon_max>:swlon AND lon_min<:nelon");
  $stmt->bindParam(':swlat', $json_data['_sw']['lat']);
  $stmt->bindParam(':nelat', $json_data['_ne']['lat']);
  $stmt->bindParam(':swlon', $json_data['_sw']['lng']);
  $stmt->bindParam(':nelon', $json_data['_ne']['lng']);
  $stmt->execute();
  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $k=>$v) { 
    $gateways[] = $v['gweui'];
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