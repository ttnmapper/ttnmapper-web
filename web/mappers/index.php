<!DOCTYPE html>
<html>
<head>
  <?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  include("../include_head.php");
  ?>
</head>
<body>
  <?php
  include("../include_body_top.php");
  ?>
  <script>
  var geojsonLayer500udeg;
  var geojsonLayer5mdeg;
  var geojsonLayerCircles;
  
  function swap_layers()
  {
    
  }

<?php
include("../include_base_map.php");
?>
    

<?php
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $stmt = $conn->prepare("SELECT DISTINCT(`nodeaddr`) FROM `packets` WHERE `time` > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      $stmtnode = $conn->prepare("SELECT * FROM `packets` WHERE nodeaddr=:nodeaddr AND `time` > DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY `time` DESC LIMIT 1");
      $stmtnode->bindParam(':nodeaddr', $v['nodeaddr']);
      $stmtnode->execute();
      $stmtnode->setFetchMode(PDO::FETCH_ASSOC);
      $result = $stmtnode->fetch();
      echo '
          var latestMarkerIcon = L.AwesomeMarkers.icon({
            icon: "ion-android-bicycle",
            prefix: "ion",
            markerColor: "purple"
          });

          latest = L.marker(new L.LatLng('.$result['lat'].', '.$result['lon'].'), {
              icon: latestMarkerIcon,
              radius: 10,
              color: "#ff7800",
              fillColor: "#ff7800",
              weight: 1,
              fillOpacity: 1,
              opacity: 1,
              riseOnHover: true
          });
          latest.desc = "<b>Active TTN Mapper</b><br />"+
            "Time: '.$result['time'].'<br />"+
            "Node: <a href=\"/special.php?node='.$result['nodeaddr'].'&type=placemarks&gateways=on\">'.$result['nodeaddr'].'</a><br />"+
            "Gateway: '.$result['gwaddr'].'";
      ';

      if(isset($_REQUEST['hideothers']) and $_REQUEST['hideothers'] == "on" )
      {
        //hidden
      }
      else
      {
        echo 'latest.addTo(map);';
        echo 'oms.addMarker(latest);';
      }
    }
?>
  </script>
</body>
</html>
