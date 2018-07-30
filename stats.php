<?php
$settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];

try 
{
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT count(DISTINCT(packets.gwaddr)) as nr FROM `packets` WHERE 1"); 
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    foreach($stmt->fetchAll() as $k=>$v) { 
      echo 'Measured gateways: '.$v['nr']."<br />";
    }
    
    $stmt = $conn->prepare("SELECT COUNT( * ) as nr FROM packets"); 
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    foreach($stmt->fetchAll() as $k=>$v) { 
      echo 'Measurement points: '.$v['nr']."<br />";
    }
    
    $stmt = $conn->prepare("SELECT COUNT( DISTINCT(nodeaddr) ) as nr FROM packets"); 
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    foreach($stmt->fetchAll() as $k=>$v) { 
      echo 'Contributing users: '.$v['nr']."<br />";
    }
    
    $stmt = $conn->prepare("SELECT COUNT( DISTINCT(nodeaddr) ) as nr FROM packets WHERE `time`>curdate() AND `time`<DATE_ADD(curdate(), INTERVAL 1 DAY);"); 
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    foreach($stmt->fetchAll() as $k=>$v) { 
      echo 'Users today: '.$v['nr']."<br />";
    }
    
    $stmt = $conn->prepare("SELECT MAX(time) as nr FROM packets"); 
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    foreach($stmt->fetchAll() as $k=>$v) { 
      echo 'Last measurement: <br />'.$v['nr']." UTC<br />";
    }
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
