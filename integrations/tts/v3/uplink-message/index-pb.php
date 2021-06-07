<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../payload_fields_parser.php";
include "../email_validator.php";
include "../data_validator.php";
include "../database.php";
include "../responses.php";

$values = array();
$values["user_agent"] = "tti_http_integration_v3";
$logfile = 'logs/log-pb-'.date('c').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received , LOCK_EX);



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


$headers = apache_request_headers();
$text = "\n\n".var_export($headers, true);
file_put_contents($logfile, $text , FILE_APPEND | LOCK_EX);

?>
