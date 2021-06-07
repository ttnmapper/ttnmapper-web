<?php
/*
{"time":"2016-01-31T22:01:00.948Z","nodeaddr":"02031701","gwaddr":"FFFEB827EB686374","snr":10.8,"rssi":-79,"freq":868.3,"lat":52.24771038,"lon":6.85348461}
*/

$result_array = array();
$result_array["error"] = false;
$logfile = 'log-'.date('Y-m-d').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received."\n\n" , FILE_APPEND | LOCK_EX);




if (strpos($received, 'org.ttnmapper.ios.TTNMapper') !== false) {
    // $url = 'http://do.jpmeijers.com:8080/v1/ios/v2';
  $url = 'https://integrations.ttnmapper.org/ios/v2';
}
else
{
    $url = 'http://do.jpmeijers.com:8080/v1/android/v2';
}

// Also forward to new backend


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







$json_data = json_decode($received, $assoc = true, $depth = 5);

if($json_data==FALSE or $json_data==NULL)
{
  $result_array["error"] = true;
  $result_array["error_message"] = json_last_error_msg();
  echo json_encode($result_array);
  exit();
}

if(!isset($json_data["iid"]))
{
  $result_array["error"] = true;
  $result_array["error_message"] = "instance id not specified";
  echo json_encode($result_array);
  exit();
}
if(!isset($json_data["user_agent"]))
{
  $result_array["error"] = true;
  $result_array["error_message"] = "user agent not specified";
  echo json_encode($result_array);
  exit();
}


try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if(isset($json_data["experiment"]))
    {
      $stmt = $conn->prepare("INSERT INTO experiments 
      (datarate, freq, gwaddr, lat, lon, nodeaddr, appeui, rssi, snr, time, alt, accuracy, provider, mqtt_topic, user_agent, name, user_id) 
      VALUES 
      (:datarate, :freq, :gwaddr, :lat, :lon, :nodeaddr, :appeui, :rssi, :snr, :time, :alt, :accuracy, :provider, :mqtt_topic, :user_agent, :name, :user_id)");
      $stmt->bindParam(':name', $experiment);
      $experiment = $json_data["experiment"];
    }
    else
    {
      // prepare sql and bind parameters
      $stmt = $conn->prepare("INSERT INTO packets 
      (datarate, freq, gwaddr, lat, lon, nodeaddr, appeui, rssi, snr, time, alt, accuracy, provider, mqtt_topic, user_agent, user_id) 
      VALUES 
      (:datarate, :freq, :gwaddr, :lat, :lon, :nodeaddr, :appeui, :rssi, :snr, :time, :alt, :accuracy, :provider, :mqtt_topic, :user_agent, :user_id)");
    }
    $stmt->bindParam(':datarate', $datarate);
    $stmt->bindParam(':freq', $freq);
    $stmt->bindParam(':gwaddr', $gwaddr);
    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lon', $lon);
    $stmt->bindParam(':nodeaddr', $nodeaddr);
    $stmt->bindParam(':appeui', $appeui);
    $stmt->bindParam(':rssi', $rssi);
    $stmt->bindParam(':snr', $snr);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':alt', $alt);
    $stmt->bindParam(':accuracy', $accuracy);
    $stmt->bindParam(':provider', $provider);
    $stmt->bindParam(':mqtt_topic', $mqtt_topic);
    $stmt->bindParam(':user_agent', $user_agent);
    $stmt->bindParam(':user_id', $user_id);

    // insert another row
    if(isset($json_data["time"]))
    {
      $time = $json_data["time"];
      $time = substr($time, 0, strpos($time, '.'));
    }
    if(isset($json_data["nodeaddr"]))
      $nodeaddr = $json_data["nodeaddr"];
    if(isset($json_data["appeui"]))
      $appeui = $json_data["appeui"];
    if(isset($json_data["gwaddr"]))
      $gwaddr = $json_data["gwaddr"];
    if(isset($json_data["datarate"]))
      $datarate = $json_data["datarate"];
    if(isset($json_data["rssi"]))
      $rssi = $json_data["rssi"];
    if(isset($json_data["snr"]))
      $snr = $json_data["snr"];
    if(isset($json_data["freq"]))
    {
      $freq = $json_data["freq"];
      if($freq>1000000)
      {
        $freq = $freq / 1000000;
      }
    }
    if(isset($json_data["lat"]))
      $lat = $json_data["lat"];
    if(isset($json_data["lon"]))
      $lon = $json_data["lon"];
    if(isset($json_data["alt"]))
      $alt = $json_data["alt"];
    if(isset($json_data["accuracy"]))
      $accuracy = $json_data["accuracy"];
    if(isset($json_data["provider"]))
      $provider = $json_data["provider"];
    if(isset($json_data["mqtt_topic"]))
      $mqtt_topic = $json_data["mqtt_topic"];
    if(isset($json_data["user_agent"]))
      $user_agent = $json_data["user_agent"];
    if(isset($json_data["iid"]))
      $user_id = $json_data["iid"];

    //strip eui- from gateway
    if (0 === strpos($gwaddr, 'eui-')) {
      $gwaddr = strtoupper(substr($gwaddr, 4));
    }

    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO instanceids (iid, user_agent, created, last_update) VALUES (:iid, :user_agent, NOW(), NOW()) ON DUPLICATE KEY UPDATE user_agent=:user_agent, last_update=NOW();");
    $stmt->bindParam(':iid', $json_data["iid"]);
    $stmt->bindParam(':user_agent', $json_data["user_agent"]);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE `gateway_updates` SET `last_update`=NOW() WHERE gwaddr=:gwaddr");
    $stmt->bindParam(':gwaddr', $gwaddr);
    $stmt->execute();

    $result_array["error"] = false;
    $result_array["error_message"] = "New records created successfully";
    echo json_encode($result_array);
}
catch(PDOException $e)
{
    $result_array["error"] = true;
    $result_array["error_message"] = $e->getMessage();
    echo json_encode($result_array);
}
$conn = null;


?>
