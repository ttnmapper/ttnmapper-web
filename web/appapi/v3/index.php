<?php

//include "payload_fields_parser.php";
//include "email_validator.php";
include "data_validator.php";
include "database.php";
include "responses.php";

/*

{
   "app_id":"jpm_ttgo",
   "counter":86707,
   "dev_id":"eui-d8a01dfffe404e54",
   "hardware_serial":"D8A01DFFFE404E54",
   "iid":"",
   "metadata":{
      "airtime":61696000,
      "coding_rate":"4/5",
      "data_rate":"SF7BW125",
      "frequency":867.5,
      "gateways":[
         {
            "channel":5,
            "gtw_id":"eui-00800000a00024f4",
            "rf_chain":0,
            "rssi":-33.0,
            "snr":6.25,
            "time":"2018-10-24T00:01:01.0101Z",
            "timestamp":3875360372
         },
         {
            "altitude":120.0,
            "channel":5,
            "gtw_id":"eui-1dee0574fa421df6",
            "latitude":-33.93622,
            "longitude":18.871649,
            "rf_chain":0,
            "rssi":-55.0,
            "snr":7.0,
            "time":"2018-10-22T22:11:16Z",
            "timestamp":3562536444
         },
         {
            "altitude":119.0,
            "channel":5,
            "gtw_id":"eui-b827ebfffe18d432",
            "latitude":-33.936626,
            "longitude":18.87096,
            "rf_chain":0,
            "rssi":-71.0,
            "snr":7.5,
            "time":"2018-10-22T22:11:16.333981Z",
            "timestamp":663504428
         }
      ],
      "modulation":"LORA",
      "time":"2018-10-24T00:01:01.0101Z"
   },
   "mqtt_topic":"jpm_ttgo/devices/eui-d8a01dfffe404e54/up",
   "payload_raw":"SGVsbG8sIHdvcmxk",
   "phone_alt":161.73412620368626,
   "phone_lat":-33.93661805869246,
   "phone_loc_acc":8.0,
   "phone_loc_provider":"gps",
   "phone_lon":18.871066798168044,
   "phone_time":"2018-10-22T22:11:16Z",
   "port":1,
   "user_agent":"Android8.0.0 App1:1.0"
}

*/

$values = array();
$logfile = 'log-'.date('Y-m-d').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received."\n\n" , FILE_APPEND | LOCK_EX);






// Also forward to new backend
$url = 'http://do.jpmeijers.com:8080/v1/android/v3';

$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header' => array(
            'Content-type: application/json'
        ),
        'content' => $received
    )
);

$context  = stream_context_create($opts);

$result = @file_get_contents($url, false, $context);
//print($result);







if($received=="")
{
  header("Content-Type: text/plain");
  echo "Integration API for TTN Mapper App V3.";
  die();
}

$json_data = json_decode($received, $assoc = true, $depth = 5);

if($json_data==FALSE or $json_data==NULL)
{
  return_error("Can't parse JSON");
}


if(isset($json_data["experiment"]) && $json_data["experiment"]!="") {
  $values["experiment"] = $json_data["experiment"];
}

// parse values
$values["app_id"] = $json_data["app_id"];
$values["dev_id"] = $json_data["dev_id"];
$values["port"] = $json_data["port"];
$values["time"] = $json_data["metadata"]["time"];
$values["frequency"] = $json_data["metadata"]["frequency"];
$values["modulation"] = $json_data["metadata"]["modulation"];
$values["data_rate"] = $json_data["metadata"]["data_rate"];
$values["coding_rate"] = $json_data["metadata"]["coding_rate"];

//SCG workaround
if($values["frequency"] > 1000000) {
  $values["frequency"] = $values["frequency"] / 1000000;
}

$values['lat'] = $json_data["phone_lat"];
$values['lon'] = $json_data["phone_lon"];
$values['acc'] = $json_data["phone_loc_acc"];
$values['alt'] = $json_data["phone_alt"];
$values['provider'] = $json_data["phone_loc_provider"];
$values['user_agent'] = $json_data["user_agent"];
$values['user_id'] = $json_data["iid"];


$samples_total = 0;
$samples_success = 0;

foreach ($json_data["metadata"]["gateways"] as $gateway)
{
  $samples_total++;

  $values["gtw_id"] = $gateway["gtw_id"];
  
  if ( isset($gateway['snr']) ) {
    $values["snr"] = $gateway["snr"]; // snr might be missing
  } else {
    $values["snr"] = null;
  }
  
  if ( isset($gateway['rssi']) ) {
    $values["rssi"] = $gateway["rssi"]; // snr might be missing
  } else {
    $values["rssi"] = null;
  }

  // we should remove this at some stage and use TTN IDs
  if (strpos($values["gtw_id"], "eui-") === 0) {
    $values["gtw_id"] = substr($values["gtw_id"], 4);
    $values["gtw_id"] = strtoupper($values["gtw_id"]);
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
