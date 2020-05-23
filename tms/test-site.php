<!DOCTYPE html>
<html>
<head>
  <?php
  include("../web/testAndOld/include_head.php");
  ?>
</head>
<body>
  <?php
  include("../web/testAndOld/include_body_top.php");
  ?>
  <script>
  var geojsonLayer500udeg;
  var geojsonLayer5mdeg;
  var geojsonLayerCircles;
  
  function swap_layers()
  {
  }

<?php
include("../web/testAndOld/include_base_map.php");
?>
    

<?php
try {
  /*
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
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
        }
        else
        {
          $sqlloc = $conn->prepare("SELECT channels,lat,lon,last_heard as last_update FROM gateways_aggregated WHERE gwaddr=:gwaddr");
          $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
          $sqlloc->execute();
          $sqlloc->setFetchMode(PDO::FETCH_ASSOC);
          $locresult = $sqlloc->fetch();
        }

        $sqlloc = $conn->prepare("SELECT channels FROM gateways_aggregated WHERE gwaddr=:gwaddr");
        $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
        $sqlloc->execute();
        $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
        $channel_count = $sqlloc->fetch();
        $channel_count = $channel_count["channels"];

        $gwdescription = $v['gwaddr'].
        '<br />Last heard at '.$locresult['last_update'].
        '<br />Channels heard on: '.$channel_count.
        '<br />Show only this gateway\'s coverage as: '.
        '<ul>'.
          '<li> <a href=\"?gateway='.$v['gwaddr'].
                '&type=radials'.
                (isset($_REQUEST['hideothers'])?'&hideothers=on':'').
                '\">radials</a><br>'.
          '<li> <a href=\"?gateway='.$v['gwaddr'].
                '&type=circles'.
                (isset($_REQUEST['hideothers'])?'&hideothers=on':'').
                '\">circles</a><br>'.
          '<li><a href=\"?gateway='.$v['gwaddr'].
              '&type=blocks'.
              (isset($_REQUEST['hideothers'])?'&hideothers=on':'').
              '\">blocks</a><br>'.
          '<li><a href=\"?gateway='.$v['gwaddr'].
              '&type=radar'.(isset($_REQUEST['hideothers'])?'&hideothers=on':'').
              '\">radar</a><br>'.
          '<li><a href=\"?gateway='.$v['gwaddr'].
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
          if(strtotime($locresult['last_update']) < time()-(60*60*1)) //1 day
          {
            echo '//'.strtotime($locresult['last_update']).' < '.time().'-'.(60*24*1).'\n;';
            echo '
            marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconOffline});
            marker.desc = "<font color=\"red\">Offline.</font> Will be removed from the map in 5 days.<br />'.$gwdescription.'";
            ';
          }
          else
          {
            if($channel_count==0)
            {
              //Single channel gateway
              echo '
              marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconNoData});
              marker.desc = "Not mapped by TTN Mapper.<br />'.$gwdescription.'";
            ';
            }
            else if($channel_count<3)
            {
              //Single channel gateway
              echo '
              marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconSCG});
              marker.desc = "Likely a <font color=\"orange\">Single Channel Gateway.</font><br />'.$gwdescription.'";
            ';
            }
            else
            {
              //LoRaWAN gateway
              echo '
              marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIcon});
              marker.desc = "'.$gwdescription.'";
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
*/

}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}


  echo "
    //var coveragetiles = L.tileLayer('/tms/index-dev.php?tile={z}/{x}/{y}', {
    var coveragetiles = L.tileLayer('http://private.ttnmapper.org:8000/{z}/{x}/{y}', {
      maxNativeZoom: 18,
      maxZoom: 20,
      zIndex: 10,
      opacity: 0.5
    });
    coveragetiles.addTo(map);
  ";


?>
  </script>
</body>
</html>
