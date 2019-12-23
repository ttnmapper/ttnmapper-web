<?php
// http://ttnmapper.org/device/csv.php?device=oyster&startdate=2019-09-21&enddate=2019-09-21&gateways=on&gateways=on&gateways=on

$columns_blacklist = array("user_id", "mqtt_topic");

if(!isset($_REQUEST["device"])) {
  echo "No device ID specified.";
  die();
}

if(!isset($_REQUEST["startdate"]) or $_REQUEST["startdate"]=="") {
  $_REQUEST["startdate"] = "0"; // 1970-01-01
}

if(!isset($_REQUEST["enddate"]) or $_REQUEST["enddate"]=="") {
  $_REQUEST["enddate"] = time(); // today
}


$device = $_REQUEST["device"];
$startdate = $_REQUEST["startdate"];
$enddate = $_REQUEST["enddate"];

if($startdate == "today") {
  $dt = new DateTime();
  $startdate = $dt->format('Y-m-d');
}

if($enddate == "today") {
  $dt = new DateTime();
  $enddate = $dt->format('Y-m-d');
}

if($startdate == "yesterday") {
  $dt = new DateTime();
  $dt->sub(new DateInterval('P1D'));
  $startdate = $dt->format('Y-m-d');
}

if($enddate == "yesterday") {
  $dt = new DateTime();
  $dt->sub(new DateInterval('P1D'));
  $enddate = $dt->format('Y-m-d');
}

try {

  $startDateObj = new DateTime();
  try {
    $startDateObj = new DateTime($startdate);
  } catch  (Exception $e) {
    $startDateObj->setTimestamp($startdate);
  }

  $endDateObj = new DateTime();
  try {
    $endDateObj = new DateTime($enddate);
  } catch  (Exception $e) {
    $endDateObj->setTimestamp($enddate);
  }

  $testEndDateOnly = DateTime::createFromFormat("Y-m-d", $enddate);
  $testEndDateOnlyCompact = DateTime::createFromFormat("Ymd", $enddate);
  if ($testEndDateOnly || $testEndDateOnlyCompact) {
    // Only an end date was set, so we should include the end day
    $endDateObj->add(new DateInterval('P1D'));
  }

  $startDateStr = $startDateObj->format('Y-m-d H:i:s');
  $endDateStr = $endDateObj->format('Y-m-d H:i:s');

} catch  (Exception $e) {
  echo "Can not parse datetime";
  die;
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
    $stmt = $conn->prepare("SELECT * FROM packets WHERE nodeaddr=:device AND `time` > :startdate AND `time` < :enddate ORDER BY `time` DESC LIMIT 10000");
    $stmt->bindParam(':device', $device, PDO::PARAM_STR);
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