<?php
/*
{"time":"2016-01-31T22:01:00.948Z","nodeaddr":"02031701","gwaddr":"FFFEB827EB686374","snr":10.8,"rssi":-79,"freq":868.3,"lat":52.24771038,"lon":6.85348461}
*/

$received = file_get_contents('php://input');
$json_data = json_decode($received, $assoc = true, $depth = 2);

if($json_data==FALSE or $json_data==NULL)
{
  echo "error";
  exit();
}


try {
    // $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    // $username = $settings['database_mysql']['username'];
    // $password = $settings['database_mysql']['password'];
    // $dbname = $settings['database_mysql']['database'];
    // $servername = $settings['database_mysql']['host'];

    // $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  
//     // set the PDO error mode to exception
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//     // prepare sql and bind parameters
//     $stmt = $conn->prepare("INSERT INTO packets 
//     (datarate, freq, gwaddr, lat, lon, nodeaddr, rssi, snr, time, alt, accuracy, provider) 
//     VALUES 
//     (:datarate, :freq, :gwaddr, :lat, :lon, :nodeaddr, :rssi, :snr, :time, :alt, :accuracy, :provider)");
//     $stmt->bindParam(':datarate', $datarate);
//     $stmt->bindParam(':freq', $freq);
//     $stmt->bindParam(':gwaddr', $gwaddr);
//     $stmt->bindParam(':lat', $lat);
//     $stmt->bindParam(':lon', $lon);
//     $stmt->bindParam(':nodeaddr', $nodeaddr);
//     $stmt->bindParam(':rssi', $rssi);
//     $stmt->bindParam(':snr', $snr);
//     $stmt->bindParam(':time', $time);
//     $stmt->bindParam(':alt', $alt);
//     $stmt->bindParam(':accuracy', $accuracy);
//     $stmt->bindParam(':provider', $provider);

//     // insert another row
//     if(isset($json_data["time"]))
//       $time = $json_data["time"];
//     if(isset($json_data["nodeaddr"]))
//       $nodeaddr = $json_data["nodeaddr"];
//     if(isset($json_data["gwaddr"]))
//       $gwaddr = $json_data["gwaddr"];
//     if(isset($json_data["datarate"]))
//       $datarate = $json_data["datarate"];
//     if(isset($json_data["rssi"]))
//       $rssi = $json_data["rssi"];
//     if(isset($json_data["snr"]))
//       $snr = $json_data["snr"];
//     if(isset($json_data["freq"]))
//       $freq = $json_data["freq"];
//     if(isset($json_data["lat"]))
//       $lat = $json_data["lat"];
//     if(isset($json_data["lon"]))
//       $lon = $json_data["lon"];
//     if(isset($json_data["alt"]))
//       $alt = $json_data["alt"];
//     if(isset($json_data["accuracy"]))
//       $accuracy = $json_data["accuracy"];
//     if(isset($json_data["provider"]))
//       $provider = $json_data["provider"];
//     $stmt->execute();

//     echo "New records created successfully";
    echo "success";
    }
catch(PDOException $e)
    {
    echo "Error: " . $e->getMessage();
    }
$conn = null;

?>
