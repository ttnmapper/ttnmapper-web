<?php
/*
{"time":"2016-01-31T22:01:00.948Z","nodeaddr":"02031701","gwaddr":"FFFEB827EB686374","snr":10.8,"rssi":-79,"freq":868.3,"lat":52.24771038,"lon":6.85348461}
*/

function return_error($error_string)
{
  $arr = array('error' => True, 'message' => $error_string);
  echo json_encode($arr);
  die();
}

function return_success($success_string)
{
  $arr = array('error' => False, 'message' => $success_string);
  echo json_encode($arr);
  die();
}


$user_agent = "user_uploaded";
$logfile = 'log-index-'.date('Y-m-d').'.txt';

$received = file_get_contents('php://input');

file_put_contents($logfile, $received."\n\n" , FILE_APPEND | LOCK_EX);

if($received=="")
{
  header("Content-Type: text/plain");
  echo file_get_contents( "description.txt" );
  die();
}

$json_data = json_decode($received, $assoc = true, $depth = 2);

if($json_data==FALSE or $json_data==NULL)
{
  return_error("error");
}


if(isset($json_data["test"]) and $json_data["test"]==true)
{
  return_error("Test successful");
}
if(isset($json_data["Test"]) and $json_data["Test"]==true)
{
  return_error("Test successful");
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
      (datarate, freq, gwaddr, lat, lon, nodeaddr, 
       appeui, rssi, snr, time, alt, accuracy, 
       provider, mqtt_topic, user_agent, user_id, name) 
      VALUES 
      (:datarate, :freq, :gwaddr, :lat, :lon, :nodeaddr, 
       :appeui, :rssi, :snr, :time, :alt, :accuracy, 
       :provider, :mqtt_topic, :user_agent, :user_id, :name)");
      $stmt->bindParam(':name', $experiment);
      $experiment = $json_data["experiment"];
    }
    else
    {
      // prepare sql and bind parameters
      $stmt = $conn->prepare("INSERT INTO packets 
      (datarate, freq, gwaddr, lat, lon, nodeaddr, 
       appeui, rssi, snr, time, alt, accuracy, 
       provider, mqtt_topic, user_agent, user_id) 
      VALUES 
      (:datarate, :freq, :gwaddr, :lat, :lon, :nodeaddr, 
       :appeui, :rssi, :snr, :time, :alt, :accuracy, 
       :provider, :mqtt_topic, :user_agent, :user_id)");
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
    else{
      return_error("'time' not set");
    }

    if(isset($json_data["nodeaddr"]))
    {
      $nodeaddr = $json_data["nodeaddr"];
    }
    else{
      if(isset($json_data["devid"]))
      {
        $nodeaddr = $json_data["devid"];
      }
      else
      {
        return_error("'devid' not set");
      }
    }

    if(isset($json_data["appid"]))
    {
      $appeui = $json_data["appid"];
    }
    else{
      if(isset($json_data["appeui"]))
      {
        $appeui = $json_data["appeui"];
      }
      else
      {
        return_error("Neither 'appid' nor 'appeui' set");
      }
    }

    if(isset($json_data["gwaddr"])) {
      $gwaddr = $json_data["gwaddr"];
      //strip eui- from gateway
      if (0 === strpos($gwaddr, 'eui-')) {
        $gwaddr = strtoupper(substr($gwaddr, 4));
      }
    }
    else{
      return_error("'gwaddr' not set");
    }

    if(isset($json_data["datarate"]))
      $datarate = $json_data["datarate"];
    else{
      return_error("'datarate' not set");
    }

    if(isset($json_data["rssi"]))
      $rssi = $json_data["rssi"];
    else{
      return_error("'rssi' not set");
    }

    if(isset($json_data["snr"]))
      $snr = $json_data["snr"];
    else{
      return_error("'snr' not set");
    }

    if(isset($json_data["freq"]))
      $freq = $json_data["freq"];
    else{
      return_error("'freq' not set");
    }

    if(isset($json_data["lat"]))
      $lat = $json_data["lat"];
    else{
      return_error("'lat' not set");
    }

    if(isset($json_data["lon"]))
      $lon = $json_data["lon"];
    else{
      return_error("'lon' not set");
    }

    if(isset($json_data["alt"]))
      $alt = $json_data["alt"];
    else{

    }

    if(isset($json_data["accuracy"]))
    {
      $accuracy = $json_data["accuracy"];
      
      //if it is not an experiment, be strict about hdop
      if(!isset($json_data["experiment"]))
      {
        if($accuracy>10)
        {
          return_error("location 'accuracy' not good enough");
        }
      }
    }
    else{
      if(isset($json_data["hdop"]))
      {
        $accuracy = $json_data["hdop"];
        
        //if it is not an experiment, be strict about hdop
        if(!isset($json_data["experiment"]))
        {
          if($accuracy>5)
          {
            return_error("location 'hdop' not good enough");
          }
        }
      }
      else
      {
        return_error("Neither 'accuracy' nor 'hdop' set");
      }
    }
    
    if(isset($json_data["provider"]))
      $provider = $json_data["provider"];
    else{
      return_error("'provider' not set");
    }

    if(isset($json_data["user_id"]))
    {
      $user_id = $json_data["user_id"];

      //if it is not an experiment, authenticate the user
      if(!isset($json_data["experiment"]))
      {
        //check if it is a valid user
        $stmtUser = $conn->prepare("SELECT COUNT(*) as number FROM api_users WHERE email=:user_id");
        $stmtUser->bindParam(':user_id', $user_id);
        $stmtUser->execute();
        $row = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if($row["number"] < 1)
        {
          return_error("Your user_id is not registered. Unregistered users can only upload to experiments. Please e-mail api@ttnmapper.org to register your user_id.");
        }
      }
    }
    else{
      return_error("'user_id' not set");
    }

    if(isset($json_data["mqtt_topic"]))
      $mqtt_topic = $json_data["mqtt_topic"];
    else{

    }

    //check latlon bounds
    if($lat>90 or $lat<-90){
      return_error("'lat' not in the valid range");
    }
    if($lon>180 or $lon<-180){
      return_error("'lon' not in the valid range");
    }

    // if(isset($json_data["user_agent"]))
    //   $user_agent = $json_data["user_agent"];
    $stmt->execute();

    // Set it to time of the packet, not now. Otherwise it will be wrong when someone uploads historical data. But also only set it to time if the current value is less than time.
    /*
    $stmt = $conn->prepare("UPDATE `gateway_updates` SET `last_update`=NOW() WHERE gwaddr=:gwaddr");
    $stmt->bindParam(':gwaddr', $gwaddr);
    $stmt->execute();
    */
}
catch(PDOException $e)
{
    return_error("Error: " . $e->getMessage());
}
$conn = null;

return_success("Entry uploaded successfully");

?>
