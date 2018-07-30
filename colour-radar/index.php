<!DOCTYPE html>
<html>
<head>
  <?php
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
    if(map)
    {
      if(map.getZoom() <= 6)
      {
        if(geojsonLayer500udeg)
        {
          map.removeLayer(geojsonLayer500udeg);
        }
        if(geojsonLayer5mdeg)
        {
          map.removeLayer(geojsonLayer5mdeg);
        }
        if(geojsonLayerCircles)
        {
          map.addLayer(geojsonLayerCircles);
        }
      }
      else if (map.getZoom() >= 12) {
        if(geojsonLayer500udeg)
        {
          map.addLayer(geojsonLayer500udeg);
        }
        if(geojsonLayer5mdeg)
        {
          map.removeLayer(geojsonLayer5mdeg);
        }
        if(geojsonLayerCircles)
        {
          map.removeLayer(geojsonLayerCircles);
        }
      } else {
        if(geojsonLayer500udeg)
        {
          map.removeLayer(geojsonLayer500udeg);
        }
        if(geojsonLayer5mdeg)
        {
          map.addLayer(geojsonLayer5mdeg);
        }
        if(geojsonLayerCircles)
        {
          map.removeLayer(geojsonLayerCircles);
        }
      }      
    }
  }

<?php
include("../include_base_map.php");
?>
    

<?php
try {
    $settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if(!isset($_REQUEST['gwall']) or (isset($_REQUEST['gwall']) and $_REQUEST['gwall']!="none"))
    {
      $stmt;
      if(isset($_REQUEST['gwall']) and ($_REQUEST['gwall']==1 or $_REQUEST['gwall']=="on") )
      {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates");
      }
      else if(isset($_REQUEST['gwall']) and $_REQUEST['gwall']=="online") {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates WHERE `last_update` > (NOW() - INTERVAL 1 HOUR)"); 
      }
      else
      {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateways_aggregated WHERE `last_heard` > (NOW() - INTERVAL 5 DAY)"); 
      }
      $stmt->execute();


      //Add gateway markers
      $gateway_array = [];
      if(isset($_REQUEST['gateway']))
      {
        if(is_array($_REQUEST['gateway']))
        {
          $gateway_array = $_REQUEST['gateway'];
        }
        else
        {
          $gateway_array[] = $_REQUEST['gateway'];
        }
      }

      // set the resulting array to associative
      $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
      foreach($stmt->fetchAll() as $k=>$v) {

        if(isset($_REQUEST['gwall']) and ($_REQUEST['gwall']==1 or $_REQUEST['gwall']=="on" or $_REQUEST['gwall']=="online") )
        {
          $sqlloc = $conn->prepare("SELECT lat,lon,last_update FROM gateway_updates WHERE gwaddr=:gwaddr ORDER BY datetime DESC LIMIT 1");
          $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
          $sqlloc->execute();
          $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
          $locresult = $sqlloc->fetch();

          $sqlchn = $conn->prepare("SELECT channels,description FROM gateways_aggregated WHERE gwaddr=:gwaddr");
          $sqlchn->bindParam(':gwaddr', $v['gwaddr']);
          $sqlchn->execute();
          $sqlchn->setFetchMode(PDO::FETCH_ASSOC); 
          $sqlchnresult = $sqlchn->fetch();
          $channel_count = $sqlchnresult["channels"];
          $description = $sqlchnresult["description"];
        }
        else
        {
          $sqlloc = $conn->prepare("SELECT channels,description,lat,lon,last_heard as last_update FROM gateways_aggregated WHERE gwaddr=:gwaddr");
          $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
          $sqlloc->execute();
          $sqlloc->setFetchMode(PDO::FETCH_ASSOC);
          $locresult = $sqlloc->fetch();
          $channel_count = $locresult["channels"];
          $description = $locresult["description"];
        }

        $gwdescriptionHead = "";

        if ($description != null) {
          $gwdescriptionHead = sprintf("<b>%s</b><br />%s",
            htmlentities($description),
            htmlentities($v['gwaddr']));
        } else {
          $gwdescriptionHead = sprintf("<b>%s</b>",
            htmlentities($v['gwaddr']));
        }

        $gwdescription = 
        '<br />Last heard at '.$locresult['last_update'].
        '<br />Channels heard on: '.$channel_count.
        '<br />Show only this gateway\'s coverage as: '.
        '<ul>'.
          // '<li> <a href=\"//ttnmapper.org/?gateway='.$v['gwaddr'].
          //       '&type=radials'.
          //       (isset($_REQUEST['hideothers'])?'&hideothers=on':'').
          //       '\">radials</a><br>'.
          // '<li> <a href=\"//ttnmapper.org/?gateway='.$v['gwaddr'].
          //       '&type=circles'.
          //       (isset($_REQUEST['hideothers'])?'&hideothers=on':'').
          //       '\">circles</a><br>'.
          // '<li><a href=\"//ttnmapper.org/?gateway='.$v['gwaddr'].
          //     '&type=blocks'.
          //     (isset($_REQUEST['hideothers'])?'&hideothers=on':'').
          //     '\">blocks</a><br>'.
          '<li><a href=\"//ttnmapper.org/colour-radar/?gateway='.$v['gwaddr'].
              '&type=radar'.(isset($_REQUEST['hideothers'])?'&hideothers=on':'').
              '\">radar</a><br>'.
          '<li><a href=\"//ttnmapper.org/alphashapes/?gateway='.$v['gwaddr'].
              '&type=alpha'.(isset($_REQUEST['hideothers'])?'&hideothers=on':'').
              '\">alpha shape</a><br>'.
        '</ul>';
        
        $showGWMarker = true;

        if(isset($_REQUEST['hideothers']) and $_REQUEST['hideothers'] == "on" )
        {
          //hidden
          $showGWMarker = false;
        }
        else
        {
          $showGWMarker = true;
        }
        if ( in_array($v['gwaddr'], $gateway_array) )
        {
          $showGWMarker = true;
        }

        if($showGWMarker == true)
        {
          if(strtotime($locresult['last_update']) < time()-(60*60*1)) //1 hour
          {
            echo '//'.strtotime($locresult['last_update']).' < '.time().'-'.(60*24*1).'\n;';
            echo '
            marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconOffline});
            marker.desc = "'.$gwdescriptionHead.'<br /><br /><font color=\"red\">Offline.</font> Will be removed from the map in 5 days.<br />'.$gwdescription.'";
            ';
          }
          else
          {
            if($channel_count==0)
            {
              //Single channel gateway
              echo '
              marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconNoData});
              marker.desc = "'.$gwdescriptionHead.'<br /><br />Not mapped by TTN Mapper.<br />'.$gwdescription.'";
            ';
            }
            else if($channel_count<3)
            {
              //Single channel gateway
              echo '
              marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconSCG});
              marker.desc = "'.$gwdescriptionHead.'<br /><br />Likely a <font color=\"orange\">Single Channel Gateway.</font><br />'.$gwdescription.'";
            ';
            }
            else
            {
              //LoRaWAN gateway
              echo '
              marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIcon});
              marker.desc = "'.$gwdescriptionHead.'<br />'.$gwdescription.'";
              ';
            }
          }

          echo '
            marker.addTo(map);
            oms.addMarker(marker);
          ';
        }
        
        if( in_array($v['gwaddr'], $gateway_array) )
        {
          echo '
          if(!zoom_override)
          {
            map.setView(new L.LatLng('.$locresult['lat'].', '.$locresult['lon'].'), 13);
          }
          ';
        }
      }
    }

    $stmt = $conn->prepare("SELECT DISTINCT(`nodeaddr`) FROM `packets` WHERE `time` > CURDATE()");
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      $stmtnode = $conn->prepare("SELECT * FROM `packets` WHERE nodeaddr=:nodeaddr ORDER BY `time` DESC LIMIT 1");
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

    // $stmt = $conn->prepare("SELECT * FROM `packets` WHERE 1 ORDER BY `time` DESC LIMIT 1");
    // $stmt->execute();
    // $stmt->setFetchMode(PDO::FETCH_ASSOC);
    // $result = $stmt->fetch();
    // echo '
    //     var latestMarkerIcon = L.AwesomeMarkers.icon({
    //       icon: "ion-android-bicycle",
    //       prefix: "ion",
    //       markerColor: "purple"
    //     });

    //     latest = L.marker(new L.LatLng('.$result['lat'].', '.$result['lon'].'), {
    //         icon: latestMarkerIcon,
    //         radius: 10,
    //         color: "#ff7800",
    //         fillColor: "#ff7800",
    //         weight: 1,
    //         fillOpacity: 1,
    //         opacity: 1,
    //         riseOnHover: true
    //     }).addTo(map);
    //     latest.desc = "<b>Latest measured point</b><br />"+
    //       "Time: '.$result['time'].'<br />"+
    //       "Node: <a href=\"http://ttnmapper.org/special.php?node='.$result['nodeaddr'].'&type=placemarks&gateways=on\">'.$result['nodeaddr'].'</a><br />"+
    //       "Gateway: '.$result['gwaddr'].'";
    //     oms.addMarker(latest);
    // ';
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if(isset($_REQUEST['pade']) and $_REQUEST['pade']=="1")
{
  echo '
  $.get("/geojson/pade.geojson", function(json) {
      L.geoJson.css(json).addTo(map);
      });
  ';
}

if(isset($_REQUEST['decentlab']) and $_REQUEST['decentlab']=="1")
{
  echo '
  $.get("/decentlab.geojson", function(json) {
      L.geoJson.css(json).addTo(map);
      });
  ';
}

if(isset($_REQUEST['gateway'])/* and ctype_alnum($_REQUEST['gateway'])*/)
{
  $gateway_array = [];
  if(is_array($_REQUEST['gateway']))
  {
    $gateway_array = $_REQUEST['gateway'];
  }
  else
  {
    $gateway_array[] = $_REQUEST['gateway'];
  }

  foreach($gateway_array as $gateway)
  {

    if(isset($_REQUEST['type']) and $_REQUEST['type']=="radials")
    {
      echo '
      $.get("/geojson/'.$gateway.'/'.$gateway.'_radials_rssi_0.0005sqdeg.geojson", function(json) {
          gatewaylayer = L.geoJson.css(json, {weight: 2})
          gatewaylayer.addTo(map);
          if(!zoom_override)
          {
            map.fitBounds(gatewaylayer.getBounds());
          }
          });
      ';
    }
    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="radar")
    {
      echo '
      $.get("/geojson/'.$gateway.'/radar.geojson", function(json) {
          gatewaylayer = L.geoJson.css(json, {stroke: false, fillOpacity: 0.5})
          gatewaylayer.addTo(map);
          if(!zoom_override)
          {
            map.fitBounds(gatewaylayer.getBounds());
          }
          });
      ';
    }
    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="radar-unfiltered")
    {
      echo '
      $.get("/geojson/'.$gateway.'/radar_unfiltered.geojson", function(json) {
          gatewaylayer = L.geoJson.css(json, {stroke: false, fillOpacity: 0.5})
          gatewaylayer.addTo(map);
          if(!zoom_override)
          {
            map.fitBounds(gatewaylayer.getBounds());
          }
          });
      ';
    }
    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="radar-star")
    {
      echo '
      $.get("/geojson/'.$gateway.'/radar-star.geojson", function(json) {
          gatewaylayer = L.geoJson.css(json, {stroke: false, fillOpacity: 0.5})
          gatewaylayer.addTo(map);
          if(!zoom_override)
          {
            map.fitBounds(gatewaylayer.getBounds());
          }
          });
      ';
    }
    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="alpha")
    {
      echo '
      $.get("/geojson/'.$gateway.'/alphashape.geojson", function(json) {
          gatewaylayer = L.geoJson.css(json, 
          {
            stroke: false, 
            fillOpacity: 0.25,
            fillColor: "#0000FF",
            zIndex: 25
          });
          gatewaylayer.addTo(map);
          if(!zoom_override)
          {
            map.fitBounds(gatewaylayer.getBounds());
          }
          });
      ';
    }
    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="blocks")
    {

      echo '
      var markerArray = [];
        ';
      if($gateway=="all")
      {
        $stmt = $conn->prepare("SELECT lat,lon,rssiavg FROM `500udeg`");
      }
      else
      {
        $stmt = $conn->prepare("SELECT lat,lon,rssiavg FROM `500udeg` WHERE gwaddr = :gwaddr");
        $stmt->bindParam(':gwaddr', $gateway);
      }
      
      $locations120 = array();
      $locations115 = array();
      $locations110 = array();
      $locations105 = array();
      $locations100 = array();
      $locations000 = array();

      $stmt->execute();
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      foreach($stmt->fetchAll() as $k=>$v) { 

        $rssi = $v["rssiavg"];
        $color = "#0000ff";
        if ($rssi==0)
        {
          $color = "black";
        }
        else if ($rssi<-120)
        {
          $color = "blue";
          $locations120[]=array(
            array((float)$v['lat']-0.00025, (float)$v['lon']-0.00025),
            array((float)$v['lat']-0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']-0.00025),
          );
        }
        else if ($rssi<-115)
        {
          $color = "cyan";
          $locations115[]=array(
            array((float)$v['lat']-0.00025, (float)$v['lon']-0.00025),
            array((float)$v['lat']-0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']-0.00025),
          );
        }
        else if ($rssi<-110)
        {
          $color = "green";
          $locations110[]=array(
            array((float)$v['lat']-0.00025, (float)$v['lon']-0.00025),
            array((float)$v['lat']-0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']-0.00025),
          );
        }
        else if ($rssi<-105)
        {
          $color = "yellow";
          $locations105[]=array(
            array((float)$v['lat']-0.00025, (float)$v['lon']-0.00025),
            array((float)$v['lat']-0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']-0.00025),
          );
        }
        else if ($rssi<-100)
        {
          $color = "orange";
          $locations100[]=array(
            array((float)$v['lat']-0.00025, (float)$v['lon']-0.00025),
            array((float)$v['lat']-0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']-0.00025),
          );
        }
        else
        {
          $color = "red";
          $locations000[]=array(
            array((float)$v['lat']-0.00025, (float)$v['lon']-0.00025),
            array((float)$v['lat']-0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']+0.00025),
            array((float)$v['lat']+0.00025, (float)$v['lon']-0.00025),
          );
        }

      }

      echo '
        marker = L.polygon('.json_encode($locations120).', {
            stroke: false,
            color: "blue",
            fillColor: "blue",
            fillOpacity: 0.8
        });
        markerArray.push(marker);';

      echo '
        marker2 = L.polygon('.json_encode($locations115).', {
            stroke: false,
            color: "cyan",
            fillColor: "cyan",
            fillOpacity: 0.8
        });
        markerArray.push(marker2);';

      echo '
        marker3 = L.polygon('.json_encode($locations110).', {
            stroke: false,
            color: "green",
            fillColor: "green",
            fillOpacity: 0.8
        });
        markerArray.push(marker3);';

      echo '
        marker4 = L.polygon('.json_encode($locations105).', {
            stroke: false,
            color: "yellow",
            fillColor: "yellow",
            fillOpacity: 0.8
        });
        markerArray.push(marker4);';

      echo '
        marker5 = L.polygon('.json_encode($locations100).', {
            stroke: false,
            color: "orange",
            fillColor: "orange",
            fillOpacity: 0.8
        });
        markerArray.push(marker5);';

      echo '
        marker6 = L.polygon('.json_encode($locations000).', {
            stroke: false,
            color: "red",
            fillColor: "red",
            fillOpacity: 0.8
        });
        markerArray.push(marker6);';

      echo '
        if(markerArray.length>0)
        {
          var group = L.featureGroup(markerArray).addTo(map);
          
          if(!zoom_override)
          {
            map.fitBounds(group.getBounds());
          }
        }
      ';

    }

    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="blocksdebug")
    {

      echo '
      var markerArray = [];
        ';
      // if($_REQUEST['gateway']=="all")
      // {
      //   $stmt = $conn->prepare("SELECT * FROM `500udeg` GROUP BY lat,lon ORDER BY lat, lon");
      // }
      // else
      // {
        $stmt = $conn->prepare("SELECT * FROM `500udeg` WHERE gwaddr = :gwaddr");
        $stmt->bindParam(':gwaddr', $gateway);
      // }
      
      $locations120 = array();
      $locations115 = array();
      $locations110 = array();
      $locations105 = array();
      $locations100 = array();
      $locations000 = array();

      echo 'var myIcon = L.divIcon({className: "my-div-icon"});
      ';

      $stmt->execute();
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      foreach($stmt->fetchAll() as $k=>$v) { 

        $rssi = $v["rssiavg"];
        $color = "#0000ff";
        if ($rssi==0)
        {
          $color = "black";
        }
        else if ($rssi<-120)
        {

        echo '
        marker = L.polygon([
        ['.($v['lat']-0.00025).','.($v['lon']-0.00025).'],
        ['.($v['lat']-0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']-0.00025).'],
        ], {
            stroke: false,
            color: "blue",
            fillColor: "blue",
            fillOpacity: 0.8,
            description: '.$v['samples'].'
        });
        marker.bindPopup("'.$v['samples'].'");
        markerArray.push(marker);';


        if($v['samples']!=1)
        {
          echo '
          L.marker(marker.getBounds().getCenter(), {
            icon: L.divIcon({
            html: "'.$v['samples'].'",
            iconSize: [0, 0]
            })
          }).addTo(map);
          ';
        }

        }
        else if ($rssi<-115)
        {

        echo '
        marker = L.polygon([
        ['.($v['lat']-0.00025).','.($v['lon']-0.00025).'],
        ['.($v['lat']-0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']-0.00025).'],
        ], {
            stroke: false,
            color: "cyan",
            fillColor: "cyan",
            fillOpacity: 0.8,
            description: '.$v['samples'].'
        });
        marker.bindPopup("'.$v['samples'].'");
        markerArray.push(marker);';


        if($v['samples']!=1)
        {
          echo '
          L.marker(marker.getBounds().getCenter(), {
            icon: L.divIcon({
            html: "'.$v['samples'].'",
            iconSize: [0, 0]
            })
          }).addTo(map);
          ';
        }
      }
        else if ($rssi<-110)
        {

        echo '
        marker = L.polygon([
        ['.($v['lat']-0.00025).','.($v['lon']-0.00025).'],
        ['.($v['lat']-0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']-0.00025).'],
        ], {
            stroke: false,
            color: "green",
            fillColor: "green",
            fillOpacity: 0.8,
            description: '.$v['samples'].'
        });
        marker.bindPopup("'.$v['samples'].'");
        markerArray.push(marker);';


        if($v['samples']!=1)
        {
          echo '
          L.marker(marker.getBounds().getCenter(), {
            icon: L.divIcon({
            html: "'.$v['samples'].'",
            iconSize: [0, 0]
            })
          }).addTo(map);
          ';
        }
        }
        else if ($rssi<-105)
        {

        echo '
        marker = L.polygon([
        ['.($v['lat']-0.00025).','.($v['lon']-0.00025).'],
        ['.($v['lat']-0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']-0.00025).'],
        ], {
            stroke: false,
            color: "yellow",
            fillColor: "yellow",
            fillOpacity: 0.8,
            description: '.$v['samples'].'
        });
        marker.bindPopup("'.$v['samples'].'");
        markerArray.push(marker);';


        if($v['samples']!=1)
        {
          echo '
          L.marker(marker.getBounds().getCenter(), {
            icon: L.divIcon({
            html: "'.$v['samples'].'",
            iconSize: [0, 0]
            })
          }).addTo(map);
          ';
        }
        }
        else if ($rssi<-100)
        {

        echo '
        marker = L.polygon([
        ['.($v['lat']-0.00025).','.($v['lon']-0.00025).'],
        ['.($v['lat']-0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']-0.00025).'],
        ], {
            stroke: false,
            color: "orange",
            fillColor: "orange",
            fillOpacity: 0.8,
            description: '.$v['samples'].'
        });
        marker.bindPopup("'.$v['samples'].'");
        markerArray.push(marker);';


        if($v['samples']!=1)
        {
          echo '
          L.marker(marker.getBounds().getCenter(), {
            icon: L.divIcon({
            html: "'.$v['samples'].'",
            iconSize: [0, 0]
            })
          }).addTo(map);
          ';
        }
        }
        else
        {

        echo '
        marker = L.polygon([
        ['.($v['lat']-0.00025).','.($v['lon']-0.00025).'],
        ['.($v['lat']-0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']+0.00025).'],
        ['.($v['lat']+0.00025).','.($v['lon']-0.00025).'],
        ], {
            stroke: false,
            color: "red",
            fillColor: "red",
            fillOpacity: 0.8,
            description: '.$v['samples'].'
        });
        marker.bindPopup("'.$v['samples'].'");
        markerArray.push(marker);';


        if($v['samples']!=1)
        {
          echo '
          L.marker(marker.getBounds().getCenter(), {
            icon: L.divIcon({
            html: "'.$v['samples'].'",
            iconSize: [0, 0]
            })
          }).addTo(map);
          ';
        }
        }

        // echo '
        //   marker = L.polygon([['.($v['lat']-0.00025).', '.($v['lon']-0.00025).'],['.($v['lat']-0.00025).', '.($v['lon']+0.00025).'],['.($v['lat']+0.00025).', '.($v['lon']+0.00025).'],['.($v['lat']+0.00025).', '.($v['lon']-0.00025).'],['.($v['lat']-0.00025).', '.($v['lon']-0.00025).']], {
        //       stroke: false,
        //       color: "'.$color.'",
        //       fillColor: "'.$color.'",
        //       fillOpacity: 0.8
        //   });
        //   markerArray.push(marker);
        // ';

      }

      echo '
        if(markerArray.length>0)
        {
          var group = L.featureGroup(markerArray).addTo(map);

          if(!zoom_override)
          {
            map.fitBounds(group.getBounds());
          }
        }
      ';

    }
    else if(isset($_REQUEST['type']) and $_REQUEST['type']=="circles")
    {
      echo '
      var markerArray = [];
        ';
      if($gateway=="all")
      {
        $stmt = $conn->prepare("SELECT lat,lon,rssiavg FROM `500udeg`");
      }
      else
      {
        $stmt = $conn->prepare("SELECT lat,lon,rssiavg FROM `500udeg` WHERE gwaddr = :gwaddr");
        $stmt->bindParam(':gwaddr', $gateway);
      }
      


      $stmt->execute();
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      foreach($stmt->fetchAll() as $k=>$v) { 

        $rssi = $v["rssiavg"];
        $color = "#0000ff";
        if ($rssi==0)
        {
          $color = "black";
        }
        else if ($rssi<-120)
        {
          $color = "blue";
        }
        else if ($rssi<-115)
        {
          $color = "cyan";
        }
        else if ($rssi<-110)
        {
          $color = "green";
        }
        else if ($rssi<-105)
        {
          $color = "yellow";
        }
        else if ($rssi<-100)
        {
          $color = "orange";
        }
        else
        {
          $color = "red";
        }

        echo '
        marker = L.circle(['.($v['lat']).', '.($v['lon']).'], 30, {
            stroke: false,
            radius: 5,
            color: "'.$color.'",
            fillColor: "'.$color.'",
            fillOpacity: 0.8
        });
        markerArray.push(marker);
      ';
      }

      echo '
        if(markerArray.length>0)
        {
          var group = L.featureGroup(markerArray).addTo(map);

          if(!zoom_override)
          {
            map.fitBounds(group.getBounds());
          }
        }
      ';
      
    }
  }
}

  //TODO: heatmap

else if(isset($_REQUEST['blocks']) and $_REQUEST['blocks']=="on")
{
  echo "
    var coveragetiles = L.tileLayer('tms/?tile={z}/{x}/{y}', {
      maxNativeZoom: 18,
      maxZoom: 20,
      zIndex: 10,
      opacity: 0.5
    });
    coveragetiles.addTo(map);
  ";
}

else if(isset($_REQUEST['blocks']) and $_REQUEST['blocks']=="test")
{
  echo "
    var coveragetiles = L.tileLayer('tms/colorbrewer2.php?tile={z}/{x}/{y}', {
      maxNativeZoom: 18,
      maxZoom: 20,
      zIndex: 10,
      opacity: 0.5
    });
    coveragetiles.addTo(map);
  ";
}

else if(isset($_REQUEST['areas']) and $_REQUEST['areas']=="on")
{

  // echo '
  // $.getJSON("geojson/concave_huls.geojson", function(data){
  //     geojsonLayerHuls = L.geoJson.css(turf.merge(data));
  //     geojsonLayerHuls.addTo(map);
  // });
  // ';


  $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) AS gwaddr FROM `gateways_aggregated`");
    
  $stmt->execute();
  $stmt->setFetchMode(PDO::FETCH_ASSOC);
  foreach($stmt->fetchAll() as $k=>$v) { 
    echo '
    $.getJSON("geojson/'.$v['gwaddr'].'/alphashape.geojson", function(data){
        geojson = L.geoJson(data, 
          {
            stroke: false, 
            fillOpacity: 0.25,
            fillColor: "#0000FF",
            zIndex: 25
          }
        );

        geojson.addTo(map);
    });
  ';
  }
}


else if(isset($_REQUEST['radartest']) and $_REQUEST['radartest']=="on")
{
  echo '
  if (turf.erase) turf.difference = turf.erase;

  $.getJSON("geojson/radar_blue.geojson", function(datablue){
    $.getJSON("geojson/radar_cyan.geojson", function(datacyan){
      $.getJSON("geojson/radar_green.geojson", function(datagreen){
        $.getJSON("geojson/radar_yellow.geojson", function(datayellow){
          $.getJSON("geojson/radar_orange.geojson", function(dataorange){
            $.getJSON("geojson/radar_red.geojson", function(datared){

        geojsonBlue = L.geoJson(turf.difference(turf.merge(datablue), turf.merge(datacyan)), 
          {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "blue"
          });
        geojsonBlue.addTo(map);

        geojson = L.geoJson(turf.difference(turf.merge(datacyan), turf.merge(datagreen)), 
          {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "cyan"
          });
        geojson.addTo(map);

        geojson = L.geoJson(turf.difference(turf.merge(datagreen), turf.merge(datayellow)), 
          {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "green"
          });
        geojson.addTo(map);

        geojson = L.geoJson(turf.difference(turf.merge(datayellow), turf.merge(dataorange)), 
          {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "yellow"
          });
        geojson.addTo(map);

        geojson = L.geoJson(turf.difference(turf.merge(dataorange), turf.merge(datared)), 
          {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "orange"
          });
        geojson.addTo(map);

        geojson = L.geoJson(turf.merge(datared), 
          {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "red"
          });
        geojson.addTo(map);

            });
          });
        });
      });
    });
  });
  ';
}

else if(isset($_REQUEST['radials']) and $_REQUEST['radials']=="on")
{
  echo '

  $.getJSON("geojson/circles_rssi_0.0005sqdeg.geojson", function(data){
    geojsonLayerCircles = L.geoJson(data, {
      pointToLayer: function (feature, latlng) {
        return L.circle(latlng, feature.properties.radius, {
          stroke: false,
          color: feature.style.color,
          fillColor: feature.style.color,
          fillOpacity: 0.8
        });
      }
    });
    //geojsonLayerCircles.addTo(map);
    swap_layers();
    //only set zoon once
    if(geojsonLayerCircles && default_zoom)
    {
      if(!zoom_override)
      {
        map.fitBounds(geojsonLayerCircles.getBounds());
      }
    }
  });

  $.getJSON("geojson/radials_rssi_0.005sqdeg.geojson", function(data){
      geojsonLayer5mdeg = L.geoJson.css(data, {weight: 2});
      //geojsonLayer5mdeg.addTo(map);
      swap_layers();
  });
  ';

  echo "

  ";

  echo '
  $.getJSON("geojson/radials_rssi_0.0005sqdeg.geojson", function(data){
      geojsonLayer500udeg = L.geoJson.css(data, {weight: 2});
      //geojsonLayer500udeg.addTo(map);
      swap_layers();
  });
  ';

  echo '
  ';


  if(isset($_REQUEST['areastoo']) and $_REQUEST['areastoo']=="on")
  {

    echo '
    $.getJSON("geojson/concave_huls.geojson", function(data){
        geojsonLayerHuls = L.geoJson.css(data);
        geojsonLayerHuls.addTo(map);
    });
    ';

  }

  if(isset($_REQUEST['radartoo']) and $_REQUEST['radartoo']=="on")
  {

    echo '
    $.getJSON("geojson/radar_blue.geojson", function(data){
        geojsonLayerHuls = L.geoJson.css(data, {stroke: false});
        geojsonLayerHuls.addTo(map);
    });
    ';

  }

}


else //if(isset($_REQUEST['radar']) and $_REQUEST['radar']="on")
{

  $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) AS gwaddr FROM `radar`");
    
  $stmt->execute();
  $stmt->setFetchMode(PDO::FETCH_ASSOC);
  foreach($stmt->fetchAll() as $k=>$v) { 
    echo '
  $.getJSON("/geojson/'.$v['gwaddr'].'/radar.geojson", function(data){
      geojsonBlue = L.geoJson(data, 
        {
          stroke: false, 
          fillOpacity: 0.25,
          fillColor: "#0000FF",
          zIndex: 25,
          filter: function (feature) {
            if(feature.style.color=="blue") return true;
            else return false;

          }
        }
      );

      geojsonCyan = L.geoJson(data, 
        {
          stroke: false, 
          fillOpacity: 0.35,
          fillColor: "#00FFFF",
          zIndex: 30,
          filter: function (feature) {
            if(feature.style.color=="cyan") return true;
            else return false;

          }
        }
      );

      geojsonGreen = L.geoJson(data, 
        {
          stroke: false, 
          fillOpacity: 0.5,
          fillColor: "#00FF00",
          zIndex: 35,
          filter: function (feature) {
            if(feature.style.color=="green") return true;
            else return false;

          }
        }
      );

      geojsonYellow = L.geoJson(data, 
        {
          stroke: false, 
          fillOpacity: 0.7,
          fillColor: "#FFFF00",
          zIndex: 40,
          filter: function (feature) {
            if(feature.style.color=="yellow") return true;
            else return false;

          }
        }
      );

      geojsonOrange = L.geoJson(data, 
        {
          stroke: false, 
          fillOpacity: 0.7,
          fillColor: "#FF7F00",
          zIndex: 45,
          filter: function (feature) {
            if(feature.style.color=="orange") return true;
            else return false;

          }
        }
      );

      geojsonRed = L.geoJson(data, 
        {
          stroke: false, 
          fillOpacity: 0.7,
          fillColor: "#FF0000",
          zIndex: 50,
          filter: function (feature) {
            if(feature.style.color=="red") return true;
            else return false;

          }
        }
      );

      geojsonBlue.addTo(map);
      geojsonCyan.addTo(map);
      geojsonGreen.addTo(map);
      geojsonYellow.addTo(map);
      geojsonOrange.addTo(map);
      geojsonRed.addTo(map);
  });
  ';
  }

  // echo '
  // $.getJSON("geojson/radar.geojson", function(data){
  //     // geojsonLayerHuls = L.geoJson.css(data, {stroke: false, fillOpacity: 0.5});
  //     // geojsonLayerHuls.addTo(map);
  //     geojsonBlue = L.geoJson(data, 
  //       {
  //         stroke: false, 
  //         fillOpacity: 0.5,
  //         fillColor: "blue",
  //         filter: function (feature) {
  //           if(feature.style.color=="blue") return true;
  //           else return false;

  //         }
  //       }
  //     );

  //     geojsonCyan = L.geoJson(data, 
  //       {
  //         stroke: false, 
  //         fillOpacity: 0.5,
  //         fillColor: "cyan",
  //         filter: function (feature) {
  //           if(feature.style.color=="cyan") return true;
  //           else return false;

  //         }
  //       }
  //     );

  //     geojsonGreen = L.geoJson(data, 
  //       {
  //         stroke: false, 
  //         fillOpacity: 0.5,
  //         fillColor: "green",
  //         filter: function (feature) {
  //           if(feature.style.color=="green") return true;
  //           else return false;

  //         }
  //       }
  //     );

  //     geojsonYellow = L.geoJson(data, 
  //       {
  //         stroke: false, 
  //         fillOpacity: 0.5,
  //         fillColor: "yellow",
  //         filter: function (feature) {
  //           if(feature.style.color=="yellow") return true;
  //           else return false;

  //         }
  //       }
  //     );

  //     geojsonOrange = L.geoJson(data, 
  //       {
  //         stroke: false, 
  //         fillOpacity: 0.5,
  //         fillColor: "orange",
  //         filter: function (feature) {
  //           if(feature.style.color=="orange") return true;
  //           else return false;

  //         }
  //       }
  //     );

  //     geojsonRed = L.geoJson(data, 
  //       {
  //         stroke: false, 
  //         fillOpacity: 0.5,
  //         fillColor: "red",
  //         filter: function (feature) {
  //           if(feature.style.color=="red") return true;
  //           else return false;

  //         }
  //       }
  //     );

  //     geojsonBlue.addTo(map);
  //     geojsonCyan.addTo(map);
  //     geojsonGreen.addTo(map);
  //     geojsonYellow.addTo(map);
  //     geojsonOrange.addTo(map);
  //     geojsonRed.addTo(map);
  // });
  // ';
}

?>
  </script>
</body>
</html>
