<?php
// http://ttnmapper.org/device/csv.php?device=oyster&startdate=2019-09-21&enddate=2019-09-21&gateways=on&gateways=on&gateways=on

$columns_blacklist = array("user_id", "mqtt_topic");

if(!isset($_REQUEST["gateway"])) {
  echo "No gateway ID specified.";
  die();
}

$gateway = urldecode($_REQUEST["gateway"]);

if(substr($gateway, 0, 4) === "eui-") {
  $gateway = substr($gateway, 4);
  $gateway = strtoupper($gateway);
}

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



header("Content-Type: text/plain");
$columns = [];

  try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT `COLUMN_NAME` 
      FROM `INFORMATION_SCHEMA`.`COLUMNS` 
      WHERE `TABLE_SCHEMA`='ttnmapper' 
      AND `TABLE_NAME`='packets';");
    $stmt->execute();
    
    foreach($stmt->fetchAll() as $k=>$v) { 
      // Ignore blacklisted columns
      if(in_array($v[0], $columns_blacklist)) {
        continue;
      }
      $columns[] = $v[0];
    }

    // Print column headings
    $i = 0;
    foreach($columns as $col)
    {
      print $col;

      if($i!=count($columns)-1)
      {
        print ", ";
      }
      $i++;
    }
    print "\n";
    
    // Run data query
    $stmt = $conn->prepare("SELECT * FROM packets WHERE `time` > :startdate AND `time` < :enddate AND gwaddr=:gateway ORDER BY `time` DESC LIMIT 10000");
    $stmt->bindParam(':gateway', $gateway, PDO::PARAM_STR);
    $stmt->bindParam(':startdate', $startDateStr, PDO::PARAM_STR);
    $stmt->bindParam(':enddate', $endDateStr, PDO::PARAM_STR);
    $stmt->execute();

    $number_of_rows = $stmt->rowCount();
    
    foreach($stmt->fetchAll() as $k=>$v) { 
      $i = 0;
      foreach($columns as $col)
      {
        if($v[$i]!="")
        {
          print $v[$i];
        }
        else
        {
          print "null";
        }
        if($i!=count($columns)-1)
        {
          print ", ";
        }
        $i++;
      }
      print "\n";
    }
    print "\nNumber of rows dumped: ".$number_of_rows;
  }
  catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    echo "An error occured running the query.";
  }
?>