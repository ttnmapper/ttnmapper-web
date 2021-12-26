<?php
// http://ttnmapper.org/device/csv.php?device=oyster&startdate=2019-09-21&enddate=2019-09-21&gateways=on&gateways=on&gateways=on

/*
id, time, nodeaddr, appeui, gwaddr, modulation, datarate, snr, rssi, freq, fcount, lat, lon, alt, accuracy, hdop, sats, provider, user_agent
298682898, 2021-03-31 20:07:23, cricket_001, jpm_crickets, 647FDAFFFE007A1F, LORA, SF7BW125, 10.50, -42.00, 868.300, 15306, -33.936700, 18.871000, 109.8, 0.00, 0.0, 0, Cayenne LPP, http-ttnmapper/2.7.1
298682897, 2021-03-31 20:07:23, cricket_001, jpm_crickets, 60C5A8FFFE71A964, LORA, SF7BW125, 10.00, -79.00, 868.300, 15306, -33.936700, 18.871000, 109.8, 0.00, 0.0, 0, Cayenne LPP, http-ttnmapper/2.7.1
*/

$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username   = $settings['database_postgresql']['username'];
$password   = $settings['database_postgresql']['password'];
$dbname     = $settings['database_postgresql']['database'];
$servername = $settings['database_postgresql']['host'];
$serverport = $settings['database_postgresql']['port'];


if(!isset($_REQUEST["experiment"])) {
  echo "No experiment name specified.";
  die();
}


$experiment = urldecode($_REQUEST["experiment"]);
$startdate = 0;
$enddate = time();


if(!isset($_REQUEST["startdate"]) or $_REQUEST["startdate"]=="") {
  $startdate = 0; // 1970-01-01
}
else {
  $startdate = strtotime($_REQUEST["startdate"]);
  if($enddate === false) {
    echo '{"error": true, "error_message": "Could not parse startdate"}';
    die();
  }
}


if(!isset($_REQUEST["enddate"]) or $_REQUEST["enddate"]=="") {
  // End of today's server time
  $enddate = strtotime("today") + 24*60*60;
}
else {
  $enddate = strtotime($_REQUEST["enddate"]);
  if($enddate === false) {
    echo '{"error": true, "error_message": "Could not parse enddate"}';
    die();
  }

  // When only a date is given the time part will be set to all 0's in the parsed timestamp.
  // Increment by one day to include the speicified day's data.
  $date = new DateTime();
  $date->setTimestamp($enddate);

  // If someone types a date with the time part set to all 0, we will select an extra day. 
  // The chance of this happening is small enough for us to accept this bug.
  // We handle the case with Y-m-d and Ymd later on.
  if( $date->format('H') == "00" 
    and $date->format('i') == "00" 
    and $date->format('s') == "00") {

    // If the time part was not specified as 0
    if (strpos($_REQUEST["enddate"], '00:00:00') === false 
      and strpos($_REQUEST["enddate"], '00:00') === false )
    {
      // Include this day's data
      $enddate = $enddate + 24*60*60;
    }
  }
}


try {

  $startDateObj = new DateTime();
  $startDateObj->setTimestamp($startdate);

  $endDateObj = new DateTime();
  $endDateObj->setTimestamp($enddate);

  $startDateStr = $startDateObj->format('Y-m-d H:i:s');
  $endDateStr = $endDateObj->format('Y-m-d H:i:s');

} catch  (Exception $e) {
  echo '{"error": true, "error_message": "Could not parse datetime"}';
  die();
}


$output = fopen("php://output",'w') or die("Can't open php://output");
header("Content-Type: text/plain");
// header("Content-Type:application/csv"); 
// header("Content-Disposition:attachment;filename=packets.csv");

fputcsv($output, array('id', 'time', 'device_id', 'application_id', 'gateway_id', 'modulation', 'spreading factor', 'bandwidth', 'snr', 'rssi', 'frequency', 'f_port', 'f_cnt', 'latitude', 'longitude', 'altitude', 'accuracy meters', 'hdop', 'satellites', 'location provider', 'user agent', 'experiment name'));

try {
  $conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
        $servername, 
        $serverport,
        $dbname,
        $username,
        $password);

  $conn = new PDO($conStr);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Run data query
  $query = <<<SQL
SELECT packets.id, time, dev_id, app_id, gateway_id, modulation,
dr.spreading_factor, dr.bandwidth,
snr, rssi, herz, f_port, f_cnt,
latitude, longitude, altitude, accuracy_meters, hdop, satellites, accs.name as "accsrc", ua.name as "useragent", e.name as "experiment"
FROM packets
JOIN antennas a on packets.antenna_id = a.id
JOIN devices d on packets.device_id = d.id
JOIN data_rates dr on packets.data_rate_id = dr.id
JOIN frequencies f on packets.frequency_id = f.id
JOIN accuracy_sources accs on packets.accuracy_source_id = accs.id
JOIN user_agents ua on packets.user_agent_id = ua.id
JOIN experiments e on packets.experiment_id = e.id
WHERE e.name = :experiment
AND time > :startdate
AND time < :enddate
-- AND packets.experiment_id IS NULL
ORDER BY time ASC
LIMIT 50000
SQL;

  $stmt = $conn->prepare($query);
  $stmt->bindParam(':experiment', $experiment, PDO::PARAM_STR);
  $stmt->bindParam(':startdate', $startDateStr, PDO::PARAM_STR);
  $stmt->bindParam(':enddate', $endDateStr, PDO::PARAM_STR);
  $stmt->execute();

  $stmt->setFetchMode(PDO::FETCH_ASSOC); 

  $number_of_rows = $stmt->rowCount();
  $points = array();
  
  $prod = array();
  foreach($stmt->fetchAll() as $lineNr=>$row) {
    fputcsv($output, $row);
  }
  print "\nNumber of rows dumped: ".$number_of_rows;
}
catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
  echo "An error occured running the query.";
}

fclose($output) or die("Can't close php://output");
?>