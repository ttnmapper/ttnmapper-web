<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = array();
if(isset($_REQUEST["code"])) {
  $url = 'https://account.thethingsnetwork.org/users/token';
  $data = array('grant_type' => 'authorization_code', 'code' => $_REQUEST["code"]);
  // $options = array(
  //         'http' => array(
  //         'header'  => "Authorization: Basic app_secret\r\n".
  //                      "Content-type: application/json\r\n",
  //         'method'  => 'POST',
  //         'content' => json_encode($data),
  //     )
  // );

  // var_dump($options);
  // $context  = stream_context_create($options);
  // $result = file_get_contents($url, true, $context);
  // var_dump($result);
  // var_dump($http_response_header);

  $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

  $username = $settings['database_mysql']['username'];

  $request_headers = array();
  $request_headers[] = 'Authorization: Basic '.$settings['oauth']['app_secret'];
  $request_headers[] = 'Content-type: application/json';

  $ch = curl_init( $url );
  curl_setopt( $ch, CURLOPT_POST, 1);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt( $ch, CURLOPT_HTTPHEADER, $request_headers);
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

  $ttn_answer_string = curl_exec( $ch );
  $ttn_answer = json_decode($ttn_answer_string, true);

  if(isset($ttn_answer["access_token"])) {
    //This reponse contains a valid access token for TTN. Also provide a valid token to talk to TTN Mapper.
    $ttn_answer["ttn_mapper_expires_in"] = "1234";
    $ttn_answer["ttn_mapper_access_token"] = "1234";
    $ttn_answer["ttn_mapper_refresh_token"] = "1234";
  } else {
    //var_dump($ttn_answer);
  }
  $response = $ttn_answer;
}
elseif (isset($_REQUEST["error"])) {
  $response["error"] = $_REQUEST["error"];
  $response["error_description"] = $_REQUEST["error_description"];
}
else {
  $response["message"] = "Unknown state";
}

if(isset($_REQUEST["state"])) {
  $response["state"] = $_REQUEST["state"];
}

echo (json_encode($response));
?>