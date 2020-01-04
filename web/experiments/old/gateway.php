<!DOCTYPE html>
<html>
<head>
  <title>TTN Mapper</title>
  <meta charset="utf-8" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="description" content="Map the coverage for gateways of The Things Network by using an Android app to GPS tag packets received from a TTN node." />
  <meta name="keywords" content="ttn coverage, the things network coverage, gateway reception area, contribute to ttn" />
  <meta name="robots" content="index,follow" />
  <meta name="author" content="JP Meijers">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

  <link rel="stylesheet" href="../libs/leaflet/v0.7.7/leaflet.css" />
    <link rel="stylesheet" href="../libs/Leaflet.draw/dist/leaflet.draw.css" />
    <link rel="stylesheet" href="../libs/Leaflet.MeasureControl/leaflet.measurecontrol.css" />
    <link rel="stylesheet" href="http://code.ionicframework.com/ionicons/1.5.2/css/ionicons.min.css">
    <link rel="stylesheet" href="../libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.css">
    
  <style>
  body {
    padding: 0;
    margin: 0;
  }
  html, body, #map {
    height: 100%;
  }
  #leftcontainer
  {
    position: absolute;
    left: 20px;
    bottom: 30px;
    //max-width: 190px;
  }
/*  #map {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  }*/
  #menu
  {
    //position: absolute;
    //bottom: 230px;  /* adjust value accordingly */
    //left: 20px;  /* adjust value accordingly */
    //width: 140px;
    visibility: visible;
  }
  #legend
  {
    //position:absolute;
    //bottom: 30px;  /* adjust value accordingly */
    //left: 20px;  /* adjust value accordingly */
    //width: 140px;
    visibility: visible;
  }
  #stats
  {
    position:absolute;
    bottom: 30px;  /* adjust value accordingly */
    right: 20px;  /* adjust value accordingly */
    visibility: visible;
  }
  .leaflet-control.enabled a {
      background-color: yellow;
  }
  .dropSheet
  {
      background-color/**/: #FFFFFF;
//        background-image/**/: none;
//        opacity: 0.5;
//        filter: alpha(opacity=50);
      border-width: 2px; 
      border-style: solid; 
//      border-color: #AAAAAA; 
      border-color: #555555;
      padding: 5px;
  }
  </style>
</head>
<body>
  <div id="map">
  </div>
  <div id="leftcontainer">
    <div id="menu" class="dropSheet"><?php include("../menu.php");?></div>
    <div style="height: 30px"></div>
    <div id="legend" class="dropSheet"><?php include("../legend.html");?></div>
  </div>
  <div id="stats" class="dropSheet"><?php include("../stats.php");?></div>

  <!-- Google analytics-->
  <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-75921430-1', 'auto');
  ga('send', 'pageview');

  </script>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <script>
  $.ajaxSetup({ dataType: 'json' });
  </script>

  <script>L_PREFER_CANVAS = true;</script>
  <script src="../libs/leaflet/v0.7.7/leaflet.js"></script>
  <!--<script src='//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js'></script>-->
  <script src="../libs/leaflet.geojsoncss.min.js"></script>
  <script src="../libs/oms.min.js"></script>
  <script src="../libs/Leaflet.draw/dist/leaflet.draw.js"></script>
  <script src="../libs/Leaflet.MeasureControl/leaflet.measurecontrol.js"></script>
  <script src="../libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.js"></script>
  <script src="../libs/leaflet-grayscale-master/TileLayer.Grayscale.js"></script>
  <script>
    function showHideMenu()
    {
      if(window.innerWidth >= 800 && window.innerHeight >= 600){
        document.getElementById('leftcontainer').style.visibility = 'visible';
        document.getElementById('menu').style.visibility = 'visible';
        document.getElementById('legend').style.visibility = 'visible';
        document.getElementById('stats').style.visibility = 'visible';
      }
      else
      {
        document.getElementById('menu').style.visibility = 'hidden';
        document.getElementById('legend').style.visibility = 'hidden';
        document.getElementById('stats').style.visibility = 'hidden';
        document.getElementById('leftcontainer').style.visibility = 'hidden';
      }
    }

    //var map = L.map('map').setView([52.260742, 5.817245], 8);
    var map = L.map('map').setView([48.209661, 10.251494], 6);
    //var map = L.map('map').setView([0, 0], 6);
    L.Control.measureControl().addTo(map);

    // https: also suppported.
    var Esri_WorldImagery = L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
    });

    // https: also suppported.
    var Stamen_TonerLite = L.tileLayer('http://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}', {
      attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      subdomains: 'abcd',
      minZoom: 0,
      maxZoom: 20,
      ext: 'png'
    }).addTo(map);

    var OpenStreetMap_HOT = L.tileLayer('http://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>'
      });
    var OpenStreetMap_BlackAndWhite = L.tileLayer('http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    // var OpenMapSurfer_Grayscale = L.tileLayer('http://korona.geog.uni-heidelberg.de/tiles/roadsg/x={x}&y={y}&z={z}', {
    // maxZoom: 19,
    // attribution: 'Imagery from <a href="http://giscience.uni-hd.de/">GIScience Research Group @ University of Heidelberg</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    // });
    var OpenStreetMap_Mapnik_Grayscale = L.tileLayer.grayscale('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });

    // https: also suppported.
    var Esri_WorldShadedRelief = L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Source: Esri',
      maxZoom: 13
    });

    // https: also suppported.
    var OpenStreetMap_Mapnik = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });



    L.control.layers({
      "Stamen TonerLite": Stamen_TonerLite,
      //"OSM B&W": OpenStreetMap_BlackAndWhite, 
      "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
      "Terrain": Esri_WorldShadedRelief, 
      "OSM Mapnik": OpenStreetMap_Mapnik,
      "Satellite": Esri_WorldImagery
    })
    .addTo(map);
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 2});

    //add popups to marker click action
    var popup = new L.Popup();
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });

    // Listen for orientation changes
    window.addEventListener("orientationchange", showHideMenu(), false);
    window.onresize = showHideMenu;

    // map.locate({setView: true, maxZoom: 16});
    // function onLocationFound(e) {
    //     var radius = e.accuracy / 2;

    //     L.marker(e.latlng).addTo(map)
    //         .bindPopup("You are within " + radius + " meters from this point").openPopup();

        // L.circle(e.latlng, radius).addTo(map);

    // }

    // map.on('locationfound', onLocationFound);

    // function onLocationError(e) {
    //     alert(e.message);
    // }

    // map.on('locationerror', onLocationError);
    

<?php
try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt;
    if(isset($_REQUEST['gwall']) and $_REQUEST['gwall']==1)
    {
      $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates"); 
    }
    elseif(isset($_REQUEST['gwaddr']))
    {
      $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM `experiments` WHERE name = :name AND gwaddr = :gwaddr");
      $stmt->bindParam(':name', $_REQUEST['name']);
      $stmt->bindParam(':gwaddr', $_REQUEST['gwaddr']);
    }
    else
    {
      $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM `experiments` WHERE name = :name");
      $stmt->bindParam(':name', $_REQUEST['name']);
    }
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    foreach($stmt->fetchAll() as $k=>$v) { 
      $sqlloc = $conn->prepare("SELECT lat,lon FROM gateways_aggregated WHERE gwaddr=:gwaddr");
      $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
      $sqlloc->execute();
      $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
      $locresult = $sqlloc->fetch();

      if($locresult['lat']!=null and $locresult['lon']!=null)
      {
      
        echo '
          var gwMarkerIcon = L.AwesomeMarkers.icon({
            icon: "ion-load-b",
            prefix: "ion",
            markerColor: "blue"
          });

          var gwMarkerIcon = L.icon({
            iconUrl: "/resources/TTNraindropJPM45px.png",
            shadowUrl: "/resources/marker-shadow.png",

            iconSize:     [45, 45], // size of the icon
            shadowSize:   [46, 46], // size of the shadow
            iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
            shadowAnchor: [16, 46],  // the same for the shadow
            popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
          });

          marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIcon}).addTo(map);
          marker.desc = "'.$v['gwaddr'].'<br />show only this gateway\'s coverage as: <br><a href=\"?gateway='.$v['gwaddr'].'&type=radials\">radials</a><br><a href=\"?gateway='.$v['gwaddr'].'&type=points\">points</a><br><a href=\"?gateway='.$v['gwaddr'].'&type=radar\">radar</a><br><a href=\"?gateway='.$v['gwaddr'].'&type=alpha\">alpha shape</a><br>";
          oms.addMarker(marker);
        ';
      }
    }

    // $stmt = $conn->prepare("SELECT * FROM `packets` WHERE 1 ORDER BY `time` DESC LIMIT 1");
    // $stmt->execute();
    // $stmt->setFetchMode(PDO::FETCH_ASSOC);
    // $result = $stmt->fetch();
    // echo '
    //     var latestMarkerIcon = L.AwesomeMarkers.icon({
    //       icon: "ion-load-a",
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
    //       "Node: '.$result['nodeaddr'].'<br />"+
    //       "Gateway: '.$result['gwaddr'].'";
    //     oms.addMarker(latest);
    // ';

    echo '
    var markerArray = [];
      ';
    $time_start = microtime(true);
    if(isset($_REQUEST['datarate']))
    {
      $stmt = $conn->prepare("SELECT * FROM `experiments` WHERE name = :name AND datarate = :dr");
      $stmt->bindParam(':dr', $_REQUEST['datarate']);
      $stmt->bindParam(':name', $_REQUEST['name']);
    }
    elseif(isset($_REQUEST['gwaddr']))
    {
      $stmt = $conn->prepare("SELECT * FROM `experiments` WHERE name = :name AND gwaddr = :gwaddr");
      $stmt->bindParam(':gwaddr', $_REQUEST['gwaddr']);
      $stmt->bindParam(':name', $_REQUEST['name']);
    }
    else
    {
      $stmt = $conn->prepare("SELECT * FROM `experiments` WHERE name = :name");
      $stmt->bindParam(':name', $_REQUEST['name']);
    }

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    //execution time of the script
    echo '//Total Execution Time: '.$execution_time.' secs';
    
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 

      $rssi = $v["rssi"];
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

    $sqlloc = $conn->prepare("SELECT lat,lon FROM gateway_updates WHERE gwaddr=:gwaddr AND datetime<:packettime ORDER BY datetime DESC LIMIT 1");
    $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
    $sqlloc->bindParam(':packettime', $v['time']);
    $sqlloc->execute();
    $sqlloc->setFetchMode(PDO::FETCH_ASSOC);
    $locresult = $sqlloc->fetch();

    $distance = "unknown";

    if($locresult)
    {
      $distance = round(haversineGreatCircleDistance($v['lat'], $v['lon'], $locresult['lat'], $locresult['lon']), 2);
      echo '
      markerOptions = {
          radius: 10,
          color: "'.$color.'",
          fillColor: "'.$color.'",
          opacity: 0.1,
          weight: 2
      };
      marker = L.polyline([['.$v['lat'].', '.$v['lon'].'],['.$locresult['lat'].', '.$locresult['lon'].']], markerOptions);
      marker.bindPopup("'.$v['time'].'<br /><b>Node:</b> '.$v['nodeaddr'].'<br /><b>Received by gateway:</b> <br />'.$v['gwaddr'].'<br /><b>Location accuracy:</b> '.$v['accuracy'].'<br /><b>Packet id:</b> '.$v['id'].'<br /><b>RSSI:</b> '.$v['rssi'].'<br /><b>SNR:</b> '.$v['snr'].'<br /><b>DR:</b> '.$v['datarate'].'<br /><b>Distance:</b> '.$distance.'m<br /><b>Altitude: </b>'.$v['alt'].'m");
      markerArray.push(marker);
      ';
    }

    echo '
    marker = L.circleMarker(['.$v['lat'].', '.$v['lon'].'], {
        stroke: false,
        radius: 5,
        color: "'.$color.'",
        fillColor: "'.$color.'",
        fillOpacity: 0.8
    });
    marker.bindPopup("'.$v['time'].'<br /><b>Node:</b> '.$v['nodeaddr'].'<br /><b>Received by gateway:</b> <br />'.$v['gwaddr'].'<br /><b>Location accuracy:</b> '.$v['accuracy'].'<br /><b>Packet id:</b> '.$v['id'].'<br /><b>RSSI:</b> '.$v['rssi'].'<br /><b>SNR:</b> '.$v['snr'].'<br /><b>DR:</b> '.$v['datarate'].'<br /><b>Distance:</b> '.$distance.'m<br /><b>Altitude: </b>'.$v['alt'].'m");
    markerArray.push(marker);
  ';


    }

    echo '
      if(markerArray.length>0)
      {
        var group = L.featureGroup(markerArray).addTo(map);
        map.fitBounds(group.getBounds());
      }
    ';
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
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
