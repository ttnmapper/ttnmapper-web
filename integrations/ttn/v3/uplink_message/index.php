<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../payload_fields_parser.php";
include "../email_validator.php";
include "../data_validator.php";
include "../database.php";
include "../responses.php";

$values = array();
$values["user_agent"] = "ttn_http_integration_v2";
$logfile = 'logs/log-'.date('Y-m-d').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received."\n\n" , FILE_APPEND | LOCK_EX);



if( !function_exists('apache_request_headers') ) {
///
  function apache_request_headers() {
    $arh = array();
    $rx_http = '/\AHTTP_/';
    foreach($_SERVER as $key => $val) {
      if( preg_match($rx_http, $key) ) {
        $arh_key = preg_replace($rx_http, '', $key);
        $rx_matches = array();
        // do some nasty string manipulations to restore the original letter case
        // this should work in most cases
        $rx_matches = explode('_', $arh_key);
        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
          foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
          $arh_key = implode('-', $rx_matches);
        }
        $arh[$arh_key] = $val;
      }
    }
    return( $arh );
  }
///
}

if($received=="")
{
  header("Content-Type: text/plain");
  echo "Integration API for TTN HTTP integration V3.";
  die();
}

$json_data = json_decode($received, $assoc = true);

if($json_data==FALSE or $json_data==NULL)
{
  return_error("Can't parse JSON");
}

// validate email address
$headers = apache_request_headers();
$text = var_export($headers, true)."\n\n";
file_put_contents($logfile, $text , FILE_APPEND | LOCK_EX);



if(isset($headers['Authorization'])){
  if( validateEmail($headers['Authorization']) ) {
    $values["user_id"] = $headers['Authorization'];
  } else {
    return_error("Authorization header doesn't contain a valid email address.");
  }
}
else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
  if( validateEmail($_SERVER['HTTP_AUTHORIZATION']) ) {
    $values["user_id"] = $_SERVER['HTTP_AUTHORIZATION'];
  } else {
    return_error("Authorization header doesn't contain a valid email address.");
  }
} else {
  return_error("Authorization header not set.");
}




// Custom header for extra commands and values
if(isset($headers["Ttnmapper-Extra"])) {
  // parse as if it was a url query string
  parse_str($headers["Ttnmapper-Extra"], $commands);

  foreach ($commands as $command => $value) {

    // Filter by port
    if($command == "port") {
      try {
        $port = @intval($value);
        if($port != $json_data["port"]) {
          return_success("Ignoring packet on port ".$json_data["port"]);
        }
      } catch (Exception $e) {
        return_error("Incorrect TTNMAPPER-EXTRA header value around port key.");
      }
    }

    // Provider can be set via header- overrides any detected by us
    if($command == "provider") {
      try {
        $values["providerHeader"] = $value;
      } catch (Exception $e) {
        return_error("Incorrect TTNMAPPER-EXTRA header value around provider key.");
      }
    }

    // Provider can be set via header
    if($command == "experiment") {
      try {
        $values["experiment"] = $value;
      } catch (Exception $e) {
        return_error("Incorrect TTNMAPPER-EXTRA header value around experiment key.");
      }
    }

  }
}

if(isset($json_data["experiment"]) && $json_data["experiment"]!="") {
  $values["experiment"] = $json_data["experiment"];
}

// provider -> gps position source or device type

/*
{
   "end_device_ids":{
      "device_id":"cricket-001",
      "application_ids":{
         "application_id":"jpm-crickets"
      },
      "dev_addr":"26011CE4"
   },
   "correlation_ids":[
      "as:up:01E175D2K6EHZH7GGH9TWRCVBN",
      "gs:conn:01E16YPNYG4HEXHYJ7VFYKH2EW",
      "gs:uplink:01E175D2AYR39QT12BY0ESMPP7",
      "ns:uplink:01E175D2AZPJF4RDZH7A5EP2BS",
      "rpc:/ttn.lorawan.v3.GsNs/HandleUplink:01E175D2AYJYFSCZ6NMXKJ2QWQ"
   ],
   "received_at":"2020-02-16T14:10:59.302096081Z",
   "uplink_message":{
      "f_port":1,
      "f_cnt":527,
      "frm_payload":"AIj60lkC4SQAMY8=",
      "decoded_payload":{
         "gps_0":{
            "altitude":126.87000274658203,
            "latitude":-33.93669891357422,
            "longitude":18.870800018310547
         }
      },
      "rx_metadata":[
         {
            "gateway_ids":{
               "gateway_id":"pisupply-shield",
               "eui":"B827EBFFFED88375"
            },
            "timestamp":2732493451,
            "rssi":-72,
            "channel_rssi":-72,
            "snr":9.8,
            "uplink_token":"Ch0KGwoPcGlzdXBwbHktc2hpZWxkEgi4J+v//tiDdRCLlfqWCg=="
         }
      ],
      "settings":{
         "data_rate":{
            "lora":{
               "bandwidth":125000,
               "spreading_factor":7
            }
         },
         "data_rate_index":5,
         "coding_rate":"4/5",
         "frequency":"868100000",
         "timestamp":2732493451
      },
      "received_at":"2020-02-16T14:10:59.039048589Z"
   }
}

*/


// parse values
$values["app_id"] = $json_data["end_device_ids"]["application_ids"]["application_id"];
$values["dev_id"] = $json_data["end_device_ids"]["device_id"];
$values["port"] = $json_data["uplink_message"]["f_port"];
$values["time"] = $json_data["received_at"];
$values["frequency"] = $json_data["uplink_message"]["settings"]["frequency"];

if(isset($json_data["uplink_message"]["settings"]["data_rate"]["lora"])) {
  $values["modulation"] = "LORA";
  $bw = $json_data["uplink_message"]["settings"]["data_rate"]["lora"]["bandwidth"];
  $sf = $json_data["uplink_message"]["settings"]["data_rate"]["lora"]["spreading_factor"];

  $bw = round($bw / 1000); // 125000 to 125

  $values["data_rate"] = "SF".$sf."BW".$bw; // SF7BW125

} else if(isset($json_data["uplink_message"]["settings"]["data_rate"]["fsk"])) {
  $values["modulation"] = "FSK";
  $values["data_rate"] = "";

} else {
  $values["modulation"] = "";
  $values["data_rate"] = "";

}

$values["coding_rate"] = $json_data["uplink_message"]["settings"]["coding_rate"];

//SCG workaround
if($values["frequency"] > 1000000) {
  $values["frequency"] = $values["frequency"] / 1000000;
}

// if ( isset($json_data["metadata"]['latitude']) 
//   && isset($json_data["metadata"]['longitude']) 
//   && isset($json_data["metadata"]['altitude']) )
// {
//   // First using coordinates sent by TTN

//   $values["provider"] = "registry";
//   $values["latitude"] = $json_data["metadata"]["latitude"];
//   $values["longitude"] = $json_data["metadata"]["longitude"];
//   $values["altitude"] = $json_data["metadata"]["altitude"];
// }
// else

if ( isset($json_data['uplink_message']['decoded_payload']) )
{
  // Provider referes to where the location accuracy comes from
  $values["provider"] = "payload_fields";
  // will be overwritten by hdop or accuracy values

  // otherwise try using the payload fields
  $result = parse_payload_fields($json_data['uplink_message']['decoded_payload']);

  if ( isset($result["lat"]) 
    && isset($result["lon"]) )
  {
    $values = array_merge($values, $result);
  }
  else
  {
    return_error("No location data in decoded_payload");
  }
}
else
{
  // if that fails, try parsing the raw payload - not yet
  return_error("No decoded_payload");
}


// use collos for musti balloon
if(isset($values["experiment"])) {
  if ($values["experiment"] == "test-b3" or $values["experiment"] == "microclimate-flight") {
    $values["provider"] = "collos";
    $values["lat"] = $json_data["metadata"]["latitude"];
    $values["lon"] = $json_data["metadata"]["longitude"];
    $values["alt"] = $json_data["metadata"]["altitude"];
  }
}

//override any auto detected providers
if(isset($values["providerHeader"]))
{
  $values["provider"] = $values["providerHeader"];
}

$samples_total = 0;
$samples_success = 0;

foreach ($json_data["uplink_message"]["rx_metadata"] as $gateway)
{
  $samples_total++;

/*
  {
    "gateway_ids":{
       "gateway_id":"pisupply-shield",
       "eui":"B827EBFFFED88375"
    },
    "timestamp":2732493451,
    "rssi":-72,
    "channel_rssi":-72,
    "snr":9.8,
    "uplink_token":"Ch0KGwoPcGlzdXBwbHktc2hpZWxkEgi4J+v//tiDdRCLlfqWCg=="
  }
*/
  // We prefer using the EUI as that is supposed to be globally uniwue,
  // while the gateway_id only needs to be unique per stack
  if( isset($gateway["gateway_ids"]["eui"]) ) {
    $values["gtw_id"] = $gateway["gateway_ids"]["eui"];
  } else if(isset($gateway["gateway_ids"]["eui"])) {
    $values["gtw_id"] = $gateway["gateway_ids"]["gateway_id"];
  }

  // we should remove this at some stage and use TTN IDs
  if (strpos($values["gtw_id"], "eui-") === 0) {
    $values["gtw_id"] = substr($values["gtw_id"], 4);
    $values["gtw_id"] = strtoupper($values["gtw_id"]);
  }


  if ( isset($gateway['snr']) ) {
    $values["snr"] = $gateway["snr"]; // snr might be missing
  } else {
    $values["snr"] = null;
  }
  
  if ( isset($gateway['rssi']) ) {
    $values["rssi"] = $gateway["rssi"];
  } else if( isset($gateway['channel_rssi']) ) {
    $values["rssi"] = $gateway["channel_rssi"];
  } else {
    $values["rssi"] = null;
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
