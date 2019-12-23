<?php 

$start_time = 0;
$name = "experiment_elzanaty2";
$data = array();

if(isset($_REQUEST["start_time"]))
{
  $start_time = $_REQUEST["start_time"];
}

if(isset($_REQUEST["name"]))
{
  $name = $_REQUEST["name"];
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
  if(isset($_REQUEST["limit"]))
  {
    $limit = intval($_REQUEST["limit"]);
    $stmt = $conn->prepare("SELECT *,UNIX_TIMESTAMP(`time`) AS 'unixtime' FROM experiments WHERE name = :name AND time>FROM_UNIXTIME(:datetime) ORDER BY time ASC LIMIT ".$limit);
    // $stmt->bindParam(':limitNr', $limit);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':datetime', $start_time);
  }
  else if(isset($_REQUEST["last"]) and isset($_REQUEST["time"]))
  {
    $stmt = $conn->prepare("SELECT `time` FROM experiments WHERE name = :name and time>FROM_UNIXTIME(:time) ORDER BY time ASC LIMIT 1");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':time', $_REQUEST["time"]);
    $stmt->execute();
    $row = $stmt->fetch();
    
    $stmt = $conn->prepare("SELECT *,UNIX_TIMESTAMP(`time`) AS 'unixtime' FROM experiments WHERE name = :name AND time>date_sub(:time, INTERVAL 10 SECOND) AND time<date_add(:time, INTERVAL 10 SECOND)");

    $stmt->bindParam(':time', $row[0]);
    $stmt->bindParam(':name', $name);
    $stmt->execute();
  }
  else if(isset($_REQUEST["last"]))
  {
    $stmt = $conn->prepare("SELECT max(`time`) FROM experiments WHERE name = :name");
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    $row = $stmt->fetch();
    
    $stmt = $conn->prepare("SELECT *,UNIX_TIMESTAMP(`time`) AS 'unixtime' FROM experiments WHERE name = :name AND time>date_sub(:time, INTERVAL 10 SECOND)");

    $stmt->bindParam(':time', $row[0]);
    $stmt->bindParam(':name', $name);
    $stmt->execute();
  }
  else
  {
    $stmt = $conn->prepare("SELECT *,UNIX_TIMESTAMP(`time`) AS 'unixtime' FROM experiments WHERE name = :name AND time>FROM_UNIXTIME(:datetime) ORDER BY time ASC");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':datetime', $start_time);
  }
  $stmt->execute();
  $packets = $stmt->fetchAll();

  foreach($packets as $k=>$v) { 
    $sqlloc = $conn->prepare("SELECT lat,lon,last_update FROM gateway_updates WHERE gwaddr=:gwaddr ORDER BY `datetime` DESC LIMIT 1");
    $sqlloc->bindParam(':gwaddr', $v["gwaddr"]);
    $sqlloc->execute();
    $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
    $v["gateway"] = $sqlloc->fetch();
    $data[] = $v;
  }
}
catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

echo json_encode(array("points"=>$data), JSON_PRETTY_PRINT);

?>
