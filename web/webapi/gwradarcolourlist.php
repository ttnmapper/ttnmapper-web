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

$geojsondir = getenv("TTNMAPPER_HOME")."/web/geojson/";

$gwdetails = array();

try 
{
  // foreach($gateways as $gwaddr) {
  //   $string = file_get_contents("geojson/".$gwaddr."/radar-single.geojson");
  //   $circle_json = json_decode($string, true);

  //   $gwdetails[$gwaddr] = $circle_json;
  // }

  foreach($gateways as $gwaddr) {
    if (file_exists($geojsondir.$gwaddr."/radar.geojson")) {
       $string = file_get_contents($geojsondir.$gwaddr."/radar.geojson");
       $circle_json = json_decode($string, true);

       $gwdetails[$gwaddr] = $circle_json;
    }
    else {
        $gwdetails[$gwaddr] = "";
    }
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