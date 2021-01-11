<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "payload_fields_parser.php";
include "email_validator.php";
include "data_validator.php";
include "database.php";
include "responses.php";

$values = array();
$values["user_agent"] = "ttn_http_integration_v2";
$logfile = 'logs/log-'.date('Y-m-d').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received."\n\n" , FILE_APPEND | LOCK_EX);






// Also forward to new backend
$url = 'https://test-integrations.ttnmapper.org/ttn/v2';

$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header' => array(
            'Content-type: application/json',
            'Authorization: '.$_SERVER['HTTP_AUTHORIZATION']
        ),
        'content' => $received,
	'timeout' => 1  //1 Second
    )
);

$context  = stream_context_create($opts);

$result = @file_get_contents($url, false, $context);
//print($result);


// Also forward to new backend
// $url = 'http://dev.ttnmapper.org:8080/v1/ttn/v2';

// $opts = array('http' =>
//     array(
//         'method'  => 'POST',
//         'header' => array(
//             'Content-type: application/json',
//             'Authorization: '.$_SERVER['HTTP_AUTHORIZATION']
//         ),
//         'content' => $received,
//         'timeout' => 1  //1 Second 
//     )
// );

// $context  = stream_context_create($opts);

// $result = @file_get_contents($url, false, $context);
//print($result);






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
  echo "Integration API for TTN HTTP integration V2.";
  die();
}

$json_data = json_decode($received, $assoc = true, $depth = 5);

if($json_data==FALSE or $json_data==NULL)
{
  return_error("Can't parse JSON");
}


$headers = apache_request_headers();
$text = var_export($headers, true)."\n\n";
file_put_contents($logfile, $text , FILE_APPEND | LOCK_EX);






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


// Use registry lcoation for device first
if ( isset($json_data["metadata"]['latitude']) 
  && isset($json_data["metadata"]['longitude']) 
  && isset($json_data["metadata"]['altitude']) )
{
  if(isset($json_data["metadata"]["location_source"])) {
    $values["provider"] = $json_data["metadata"]["location_source"];
  } else {
    $values["provider"] = "registry";
  }
  $values["lat"] = $json_data["metadata"]["latitude"];
  $values["lon"] = $json_data["metadata"]["longitude"];
  $values["alt"] = $json_data["metadata"]["altitude"];
}


// Then try payload fields
if ( isset($json_data['payload_fields']) )
{
  // Provider referes to where the location accuracy comes from
  $values["provider"] = "payload_fields";
  // will be overwritten by hdop or accuracy values

  // otherwise try using the payload fields
  $result = parse_payload_fields($json_data["payload_fields"]);

  if ( isset($result["lat"]) 
    && isset($result["lon"]) )
  {
    $values = array_merge($values, $result);
  }
}

if( !isset($values["lat"]) || !isset($values["lon"]) )
{
  // if that fails, try parsing the raw payload - not yet
  return_error("No location information");
}



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
