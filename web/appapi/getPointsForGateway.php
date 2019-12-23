<?php
ob_start("ob_gzhandler");
error_reporting(0);

$received = file_get_contents('php://input');
$json_data = json_decode($received, $assoc = true, $depth = 2);
$json_data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $received), true );

if($json_data==FALSE or $json_data==NULL)
{
  echo '{"error": true, "error_message": "' . json_last_error().'"}';
  exit();
}

#{'gateway': '0000000000000000', 'resolution': '0.005'}
#resolutions: 0.005, 0.0005

$points = array();

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


  if(strcmp($json_data['resolution'], "0.005")==0)
  {
    $stmt = $conn->prepare("SELECT * FROM 5mdeg WHERE gwaddr = :gwaddr");
  }
  else if(strcmp($json_data['resolution'], "0.0005")==0)
  {
    $stmt = $conn->prepare("SELECT * FROM 500udeg WHERE gwaddr = :gwaddr");
  }
  else
  {
    echo '{"error": true, "error_message": "incorrect resolution specified"}';
    exit();
  }
  
  $stmt->bindParam(':gwaddr', $json_data['gateway']);
  $stmt->execute();

  $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
  
  foreach($stmt->fetchAll() as $k=>$v) { 
    $points[] = array("last_agg"=>$v['last_update'],
     "lat"=>$v['lat'],
     "lon"=>$v['lon'],
     "samples"=>$v['samples'],
     "rssimin"=>$v['rssimin'],
     "rssimax"=>$v['rssimax'],
     "rssiavg"=>$v['rssiavg'],
     "snrmin"=>$v['snrmin'],
     "snrmax"=>$v['snrmax'],
     "snravg"=>$v['snravg']);
  }
  
  $json_response = array("gateway"=>$v['gwaddr'], "points" => $points, "error" => false);
  echo json_encode($json_response);

}
catch(PDOException $e)
{
  echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
}
$conn = null;

?>