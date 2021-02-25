<?php

//include "payload_fields_parser.php";
//include "email_validator.php";
include "data_validator.php";
include "database.php";
include "responses.php";

/*

{
   "network_type":"NS_TTS_V3",
   "network_address":"eu.thethings.network",
   "app_id":"jpm_testing",
   "dev_id":"things_uno_jpm",
   "dev_eui":"0004A30B001C684F",
   "time":1610286173646000000,
   "port":1,
   "counter":7,
   "frequency":868100000,
   "modulation":"LORA",
   "bandwidth":125000,
   "spreading_factor":7,
   "coding_rate":"4/5",
   "gateways":[
      {
         "gtw_id":"eui-3133303748005c00",
         "gtw_eui":"3133303748005C00",
         "time":1610286171601000000,
         "timestamp":811266273,
         "channel":0,
         "rssi":-95.0,
         "snr":9.5
      }
   ],
   "latitude":-33.95471342555239,
   "longitude":22.434498233820108,
   "altitude":273.07464034416944,
   "accuracy_meters":6.0,
   "accuracy_source":"gps",
   "userid":"d2883904-9aa7-43f6-b7ee-e9506341291f",
   "useragent":"Android10 App21:2020.12.25"
}

*/

$values = array();
$logfile = 'log-'.date('Y-m-d').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received."\n\n" , FILE_APPEND | LOCK_EX);









if($received=="")
{
  header("Content-Type: text/plain");
  echo "Integration API for TTN Mapper App V4.";
  die();
}

$json_data = json_decode($received, $assoc = true, $depth = 5);

if($json_data==FALSE or $json_data==NULL)
{
  return_error("Can't parse JSON");
}


if($json_data["network_type"] != "NS_TTN_V2") {
  return_error("This API can only handle TTN v2 messages.");
}


if(isset($json_data["experiment"]) && $json_data["experiment"]!="") {
  $values["experiment"] = $json_data["experiment"];
}

// parse values
$values["time"] = $json_data["time"] / 1000000000; // ns to s


$values["app_id"] = $json_data["app_id"];
$values["dev_id"] = $json_data["dev_id"];

$values["modulation"] = $json_data["modulation"];
$values["data_rate"] = "SF".$json_data["spreading_factor"]."BW".($json_data["bandwidth"]/1000);
// bitrate not used
// coding rate not used

$values["frequency"] = round($json_data["frequency"] / 1000) / 1000;
$values["counter"] = $json_data["counter"];


$values['lat'] = $json_data["latitude"];
$values['lon'] = $json_data["longitude"];
$values['acc'] = $json_data["accuracy_meters"];
$values['alt'] = $json_data["altitude"];
$values['provider'] = $json_data["accuracy_source"];
$values['user_agent'] = $json_data["useragent"];
$values['user_id'] = $json_data["userid"];


$samples_total = 0;
$samples_success = 0;

foreach ($json_data["gateways"] as $gateway)
{
  $samples_total++;

  $values["gtw_id"] = $gateway["gtw_id"];

  // we should remove this at some stage and use TTN IDs
  if (strpos($values["gtw_id"], "eui-") === 0) {
    $values["gtw_id"] = substr($values["gtw_id"], 4);
    $values["gtw_id"] = strtoupper($values["gtw_id"]);
  }
  
  if ( isset($gateway['rssi']) ) {
    $values["rssi"] = $gateway["rssi"]; // snr might be missing
  } else {
    $values["rssi"] = null;
  }
  

  if ( isset($gateway['snr']) ) {
    $values["snr"] = $gateway["snr"]; // snr might be missing
  } else {
    $values["snr"] = null;
  }


  // validate values - not for experiments
  if(!isset($values["experiment"])) {
    $values = sanitize_data($values);
    if ( check_data($values) ) {
      //pass
    } else {
      return_error("Payload fields failed validation.");
    }
  }

  try
  {
    if(add_to_db($values)) {
      $samples_success++;
    }
  }
  catch (Exception $e)
  {
    $text = $e."\n".var_export($values, true)."\n\n";
    file_put_contents($logfile, $text , FILE_APPEND | LOCK_EX);
  }

}

// $text = var_export($values, true)."\n\n";
// file_put_contents($logfile, $text , FILE_APPEND | LOCK_EX);
return_success($samples_success." samples added to TTN Mapper.");

?>
