<?php

$received = file_get_contents('php://input');
$json_data = json_decode($received, $assoc = true, $depth = 2);
$json_data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $received), true );

$gateways = $json_data['gateways'];

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];

$gwdetails = array();

$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
// set the PDO error mode to exception
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try
{
  foreach($gateways as $gwaddr) {
    $sqlloc = $conn->prepare("SELECT lat,lon,channels,description, UNIX_TIMESTAMP(last_heard) as last_heard FROM gateways_aggregated WHERE gwaddr=:gwaddr");
    $sqlloc->bindParam(':gwaddr', $gwaddr);
    $sqlloc->execute();
    $sqlloc->setFetchMode(PDO::FETCH_ASSOC);
    $locresult = $sqlloc->fetch();
    
    $details = array();
    if ($locresult['description'] != null) {
      $details['description'] = $locresult['description'];
    }
    if($locresult['lat'] != null) {
        $details['lat'] = floatval($locresult['lat']);
    }
    if($locresult['lon'] != null) {
        $details['lon'] = floatval($locresult['lon']);
    }
    if($locresult['last_heard'] != null) {
        $details['last_heard'] = intval($locresult['last_heard']);
    }
    $details['channels'] = intval($locresult['channels']);
    //$details['gwaddr'] = $_REQUEST['gwaddr'];

    // if ($details['lat'] == null or $details['lon'] == null) {
    //   $sqlloc = $conn->prepare("SELECT lat,lon,UNIX_TIMESTAMP(last_update) as last_update FROM gateway_updates WHERE gwaddr=:gwaddr ORDER BY datetime DESC LIMIT 1");
    //   $sqlloc->bindParam(':gwaddr', $gwaddr);
    //   $sqlloc->execute();
    //   $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
    //   $locresult = $sqlloc->fetch();
    //   if($locresult) {
    //     $details['lat'] = $locresult['lat'];
    //     $details['lon'] = $locresult['lon'];
    //     $details['last_heard'] = $locresult['last_update'];
    //   }
    // }

    $gwdetails[$gwaddr] = $details;
  }
  
  //$json_response = array("details" => $details, "error" => false);
  echo json_encode($gwdetails);

    //   switch (json_last_error()) {
    //     case JSON_ERROR_NONE:
    //         // echo ' - No errors';
    //     break;
    //     case JSON_ERROR_DEPTH:
    //         echo ' - Maximum stack depth exceeded';
    //     break;
    //     case JSON_ERROR_STATE_MISMATCH:
    //         echo ' - Underflow or the modes mismatch';
    //     break;
    //     case JSON_ERROR_CTRL_CHAR:
    //         echo ' - Unexpected control character found';
    //     break;
    //     case JSON_ERROR_SYNTAX:
    //         echo ' - Syntax error, malformed JSON';
    //     break;
    //     case JSON_ERROR_UTF8:
    //         echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
    //     break;
    //     default:
    //         echo ' - Unknown error';
    //     break;
    // }

}
catch(PDOException $e)
{
  echo '{"error": true, "error_message": "' . $e->getMessage().'"}';
}
$conn = null;


?>