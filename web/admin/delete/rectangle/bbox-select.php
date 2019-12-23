<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$received = file_get_contents('php://input');
$json_data = json_decode($received, $assoc = true, $depth = 2);
$json_data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $received), true );

if($json_data==FALSE or $json_data==NULL)
{
  echo '{"error": true, "error_message": "' . json_last_error().'"}';
  exit();
}

#{"_southWest":{"lng":6.715497156312011,"lat":52.159068901046254},"_northEast":{"lng":7.0063341279171425,"lat":52.296423335685716}, "iid":12345}

$date_start = $json_data['date']." 00:00:00";
$date_end = $json_data['date']." 23:59:59";

$packets = array();

// try
// {
  $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

  $username = $settings['database_mysql']['username'];
  $password = $settings['database_mysql']['password'];
  $dbname = $settings['database_mysql']['database'];
  $servername = $settings['database_mysql']['host'];

  $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $conn->prepare("SELECT * FROM packets 
    WHERE lat>=:swlat AND lat<=:nelat AND lon>=:swlon AND lon<=:nelon AND `time`>=:date_start AND `time`<=:date_end");
  $stmt->bindParam(':swlat', $json_data['_southWest']['lat']);
  $stmt->bindParam(':nelat', $json_data['_northEast']['lat']);
  $stmt->bindParam(':swlon', $json_data['_southWest']['lng']);
  $stmt->bindParam(':nelon', $json_data['_northEast']['lng']);
  $stmt->bindParam(':date_start', $date_start);
  $stmt->bindParam(':date_end', $date_end);
  $stmt->execute();
  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
  
  foreach($stmt->fetchAll() as $k=>$v) {
    $packets[] = $v['id'];
  }
  
  $json_response = array("packets" => $packets, "error" => false, "count" => count($packets));
  echo json_encode($json_response);

// }
// catch(PDOException $e)
// {
//   echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
// }
$conn = null;


?>