<!DOCTYPE html>
<html>
<!--
  http://ttnmapper.org/device/map.php?device=&startdate=&enddate=&gateways=on&gateways=on&gateways=on
  http://ttnmapper.org/device/map.php?device=oyster&startdate=2019-09-21&enddate=2019-09-21&gateways=on&gateways=on&gateways=on
  http://ttnmapper.org/advanced_maps.php?device=s&startdate=2019-09-12&enddate=2019-09-12&gateways=on&gateways=on&gateways=on
  startdate and enddate can be empty, so use defaults
-->
<head>
  <?php
  include("../testAndOld/include_head.php");
  ?>
</head>
<body>
  <?php
  include("../testAndOld/include_body_top.php");
  ?>
  <script>
    function swap_layers(){};
  <?php
  include("../testAndOld/include_base_map.php");
  ?>

<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];


if(!isset($_REQUEST["gateway"])) {
  echo "No gateway ID specified.";
  die();
}

if(!isset($_REQUEST["startdate"]) or $_REQUEST["startdate"]=="") {
  $_REQUEST["startdate"] = "0"; // 1970-01-01
}

if(!isset($_REQUEST["enddate"]) or $_REQUEST["enddate"]=="") {
  $_REQUEST["enddate"] = time(); // today
}

if(!isset($_REQUEST["colour"]) or $_REQUEST["colour"]=="") {
  $_REQUEST["colour"] = "signal";
}

$gateway = $_REQUEST["gateway"];

if(substr($gateway, 0, 4) === "eui-") {
  $gateway = substr($gateway, 4);
  $gateway = strtoupper($gateway);
}

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




$gateways = array();

try 
{
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Run data query
    if(isset($_REQUEST["nolimit"])) {
      $stmt = $conn->prepare("SELECT * FROM packets WHERE `time` > :startdate AND `time` < :enddate AND gwaddr=:gateway ORDER BY `time` DESC");
    } else {
      $stmt = $conn->prepare("SELECT * FROM packets WHERE `time` > :startdate AND `time` < :enddate AND gwaddr=:gateway ORDER BY `time` DESC LIMIT 10000");
    }
    $stmt->bindParam(':gateway', $gateway, PDO::PARAM_STR);
    $stmt->bindParam(':startdate', $startDateStr, PDO::PARAM_STR);
    $stmt->bindParam(':enddate', $endDateStr, PDO::PARAM_STR);
    $stmt->execute();

    echo '
    var markerArray = [];
      ';

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    echo "//".$stmt->rowCount()."\n";

    $dbLines = $stmt->fetchAll();

    // Sort by data rate
    $drValue = array(
      "SF7BW125" => 1,
      "SF7BW250" => 1,
      "SF8BW125" => 2,
      "SF8BW500" => 2,
      "SF9BW125" => 3,
      "SF10BW125" => 4,
      "SF11BW125" => 5,
      "SF12BW125" => 6,
      NULL => 7,
    );
    if($_REQUEST["colour"] == "datarate") {
      usort($dbLines, function($a, $b)
      {
        global $drValue;
        return $drValue[$b['datarate']] - $drValue[$a['datarate']];
      });
    }

    foreach($dbLines as $k=>$v) {

      $rssi = $v["rssi"];
      $snr = $v["snr"];

      if($_REQUEST["colour"] == "datarate") {

        $dr = $drValue[$v['datarate']];

        switch ($dr) {
          case 1:
            $color = "#1b9e77";
            $radius = 8;
            break;
          case 2:
            $color = "#d95f02";
            $radius = 10;
            break;
          case 3:
            $color = "#7570b3";
            $radius = 12;
            break;
          case 4:
            $color = "#e7298a";
            $radius = 14;
            break;
          case 5:
            $color = "#66a61e";
            $radius = 16;
            break;
          case 6:
            $color = "#e6ab02";
            $radius = 18;
            break;
          default:
            $color = "#000000";
            $radius = 8;
        }

      } else {
        // signal

        if($snr<0) {
          $rssi = $rssi + $snr;
        }

        $color = "#0000ff";
        if ($rssi==0)
        {
          $color = "black";
          $radius = 8;
        }
        else if ($rssi<-120)
        {
          $color = "blue";
          $radius = 13;
        }
        else if ($rssi<-115)
        {
          $color = "cyan";
          $radius = 12;
        }
        else if ($rssi<-110)
        {
          $color = "green";
          $radius = 11;
        }
        else if ($rssi<-105)
        {
          $color = "yellow";
          $radius = 10;
        }
        else if ($rssi<-100)
        {
          $color = "orange";
          $radius = 9;
        }
        else
        {
          $color = "red";
          $radius = 8;
        }
      }

      if(!array_key_exists($v['gwaddr'], $gateways))
      {
        $sqlloc = $conn->prepare("SELECT channels,description,lat,lon,last_heard as last_update FROM gateways_aggregated WHERE gwaddr=:gwaddr");
        $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
        $sqlloc->execute();
        $sqlloc->setFetchMode(PDO::FETCH_ASSOC);
        $locresult = $sqlloc->fetch();

        $distance = "unknown";

        if($locresult)
        {
            $gateways[$v['gwaddr']] = array(
              'lat' => $locresult['lat'],
              'lon' => $locresult['lon'],
              'channel_count' => $locresult["channels"],
              'description' => $locresult["description"],
              'last_update' => $locresult['last_update']
            );
        }
      }
          
      if(isset($gateways[$v['gwaddr']]))
      {
        $distance = round(haversineGreatCircleDistance($v['lat'], $v['lon'], $gateways[$v['gwaddr']]['lat'], $gateways[$v['gwaddr']]['lon']), 2);

        if(isset($_REQUEST["lines"]) and $_REQUEST["lines"] == "on") {
          echo '
          lineOptions = {
              radius: 10,
              color: "'.$color.'",
              fillColor: "'.$color.'",
              opacity: 0.3,
              weight: 2
          };
          marker = L.polyline([['.$v['lat'].', '.$v['lon'].'],['.$gateways[$v['gwaddr']]['lat'].', '.$gateways[$v['gwaddr']]['lon'].']], lineOptions);
          marker.bindPopup("'.$v['time'].'<br /><b>Node:</b> '.$v['nodeaddr'].'<br /><b>Received by gateway:</b> <br />'.$v['gwaddr'].'<br /><b>Location accuracy:</b> '.$v['accuracy'].'<br /><b>Packet id:</b> '.$v['id'].'<br /><b>RSSI:</b> '.$v['rssi'].'dBm<br /><b>SNR:</b> '.$v['snr'].'dB<br /><b>Link cost:</b> '.($rssi*-1).'dB<br /><b>DR:</b> '.$v['datarate'].'<br /><b>Distance:</b> '.$distance.'m<br /><b>Altitude: </b>'.$v['alt'].'m");
          markerArray.push(marker);
          ';
        }

      }

      if(isset($_REQUEST["points"]) and $_REQUEST["points"] == "on") {
        echo '
        markerOptions = {
            stroke: false,
            radius: "'.$radius.'",
            color: "'.$color.'",
            fillColor: "'.$color.'",
            fillOpacity: 0.8
        };
        marker = L.circleMarker(['.$v['lat'].', '.$v['lon'].'], markerOptions);
        marker.bindPopup("'.$v['time'].'<br /><b>Node:</b> '.$v['nodeaddr'].'<br /><b>Received by gateway:</b> <br />'.$v['gwaddr'].'<br /><b>Location accuracy:</b> '.$v['accuracy'].'<br /><b>Packet id:</b> '.$v['id'].'<br /><b>RSSI:</b> '.$v['rssi'].'dBm<br /><b>SNR:</b> '.$v['snr'].'dB<br /><b>Link cost:</b> '.($rssi*-1).'dB<br /><b>DR:</b> '.$v['datarate'].'<br /><b>Distance:</b> '.$distance.'m<br /><b>Altitude: </b>'.$v['alt'].'m");
        markerArray.push(marker);
        ';
      }
      
    }

    echo '
    if(markerArray.length>0)
    {
      var group = L.featureGroup(markerArray).addTo(map);
      map.fitBounds(group.getBounds());
    }
    ';

    if(isset($_REQUEST["gateways"]) and $_REQUEST["gateways"] == "on") {
      foreach($gateways as $gwaddr=>$gateway)
      {
        $gwdescriptionHead = "";

        if ($gateway['description'] != null) {
          $gwdescriptionHead = sprintf("<b>%s</b><br />%s",
            htmlentities($gateway['description']),
            htmlentities($gwaddr));
        } else {
          $gwdescriptionHead = sprintf("<b>%s</b>",
            htmlentities($gwaddr));
        }

        $gwdescription = 
        '<br />Last heard at '.$gateway['last_update'].
        '<br />Channels heard on: '.$gateway['channel_count'].
        '<br />Show only this gateway\'s coverage as: '.
        '<ul>'.
          '<li><a href=\"//ttnmapper.org/colour-radar/?gateway[]='.urlencode($gwaddr).
              '\">radar</a><br>'.
          '<li><a href=\"//ttnmapper.org/alpha-shapes/?gateway[]='.urlencode($gwaddr).
              '\">alpha shape</a><br>'.
        '</ul>';

        if(strtotime($gateway['last_update']) < time()-(60*60*1)) //1 hour
        {
          echo '
          marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIconOffline});
          marker.bindPopup("'.$gwdescriptionHead.'<br /><br /><font color=\"red\">Offline.</font> Will be removed from the map in 5 days.<br />'.$gwdescription.'");
          ';
        }
        else
        {
          if($gateway['channel_count']==0)
          {
            //Single channel gateway
            echo '
            marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIconNoData});
            marker.bindPopup("'.$gwdescriptionHead.'<br /><br />Not mapped by TTN Mapper.<br />'.$gwdescription.'");
          ';
          }
          else if($gateway['channel_count']<3)
          {
            //Single channel gateway
            echo '
            marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIconSCG});
            marker.bindPopup("'.$gwdescriptionHead.'<br /><br />Likely a <font color=\"orange\">Single Channel Gateway.</font><br />'.$gwdescription.'");
          ';
          }
          else
          {
            //LoRaWAN gateway
            echo '
            marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIcon});
            marker.bindPopup("'.$gwdescriptionHead.'<br />'.$gwdescription.'");
            ';
          }
        }

        echo '
          marker.addTo(map);
          //oms.addMarker(marker);
        ';
      }
    }
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if(isset($_REQUEST['lat']) and isset($_REQUEST['lon']) and isset($_REQUEST['zoom']))
{
  echo 'map.setView(['.$_REQUEST['lat'].', '.$_REQUEST['lon'].'], '.$_REQUEST['zoom'].');';
}
?>
  </script>
</body>
</html>
<?php

/**
 * Calculates the great-circle distance between two points, with
 * the Haversine formula.
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function haversineGreatCircleDistance(
  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  return $angle * $earthRadius;
}
?>
