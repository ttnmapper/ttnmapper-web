<?php

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];


$received = file_get_contents('php://input');
$json_data = json_decode($received, $assoc = true, $depth = 2);
$json_data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $received), true );

if($json_data==FALSE or $json_data==NULL)
{
  echo '{"error": true, "error_message": "' . json_last_error().'"}';
  exit();
}

#{"_southWest":{"lng":6.715497156312011,"lat":52.159068901046254},"_northEast":{"lng":7.0063341279171425,"lat":52.296423335685716}, "iid":12345}

$gateways = array();

try 
{
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $conn->prepare("SELECT gweui FROM gateway_bbox 
    WHERE lat_max>:swlat AND lat_min<:nelat AND lon_max>:swlon AND lon_min<:nelon");
  $stmt->bindParam(':swlat', $json_data['_southWest']['lat']);
  $stmt->bindParam(':nelat', $json_data['_northEast']['lat']);
  $stmt->bindParam(':swlon', $json_data['_southWest']['lng']);
  $stmt->bindParam(':nelon', $json_data['_northEast']['lng']);
  $stmt->execute();
  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $k=>$v) { 
    $gateways[] = $v['gweui'];
  }

  // Also select all gateways without bounding boxes defined yet
  $stmt = $conn->prepare("SELECT gwaddr FROM gateways_aggregated WHERE lat>:swlat AND lat<:nelat AND lon>:swlon AND lon<:nelon");
  $stmt->bindParam(':swlat', $json_data['_southWest']['lat']);
  $stmt->bindParam(':nelat', $json_data['_northEast']['lat']);
  $stmt->bindParam(':swlon', $json_data['_southWest']['lng']);
  $stmt->bindParam(':nelon', $json_data['_northEast']['lng']);
  $stmt->execute();
  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

  foreach($stmt->fetchAll() as $k=>$v) { 
    $gwaddr = $v['gwaddr'];
    if(!in_array($gwaddr, $gateways)) {
      $gateways[] = $gwaddr;
    }
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