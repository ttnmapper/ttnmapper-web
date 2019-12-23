<!DOCTYPE html>
<html>
<head>
  <title>TTN Mapper</title>
  <meta charset="utf-8" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="description" content="Map the coverage for gateways of The Things Network." />
  <meta name="keywords" content="ttn coverage, the things network coverage, gateway reception area, contribute to ttn" />
  <meta name="robots" content="index,follow" />
  <meta name="author" content="JP Meijers">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

  <link rel="stylesheet" href="libs/leaflet/v0.7.7/leaflet.css" />
    <link rel="stylesheet" href="libs/Leaflet.draw/dist/leaflet.draw.css" />
    <link rel="stylesheet" href="libs/Leaflet.MeasureControl/leaflet.measurecontrol.css" />
    <link rel="stylesheet" href="http://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.css">
    <link rel="stylesheet" href="libs/Leaflet.label-master/dist/leaflet.label.css">
    
  <style>
  body {
    padding: 0;
    margin: 0;
  }
  html, body, #map {
    height: 100%;
  }
  .leaflet-control.enabled a {
      background-color: yellow;
  }
  </style>
</head>
<body>
  <div id="map">
  </div>

  <!-- Google analytics-->
  <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  var GA_LOCAL_STORAGE_KEY = 'ga:clientId';

  if (window.localStorage) {
    ga('create', 'UA-75921430-1', {
      'storage': 'none',
      'clientId': localStorage.getItem(GA_LOCAL_STORAGE_KEY)
    });
    ga(function(tracker) {
      localStorage.setItem(GA_LOCAL_STORAGE_KEY, tracker.get('clientId'));
    });
  }
  else {
    ga('create', 'UA-75921430-1', 'auto');
  }

  ga('send', 'pageview');

  </script>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <script>
  $.ajaxSetup({ dataType: 'json' });
  </script>

  <script>L_PREFER_CANVAS = true;</script>
  <script src="libs/leaflet/v0.7.7/leaflet.js"></script>
  <!--<script src='//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js'></script>-->
  <script src="libs/leaflet.geojsoncss.min.js"></script>
  <script src="libs/oms.min.js"></script>
  <script src="libs/Leaflet.draw/dist/leaflet.draw.js"></script>
  <script src="libs/Leaflet.MeasureControl/leaflet.measurecontrol.js"></script>
  <script src="libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.js"></script>
  <script src="libs/leaflet-grayscale-master/TileLayer.Grayscale.js"></script>
  <script src="/libs/Leaflet.label-master/dist/leaflet.label.js"></script>
  <script src='libs/turf.min.js'></script>
  <script>


    var map;

<?php
    if (isset($_REQUEST['lat']) and isset($_REQUEST['lon']) and isset($_REQUEST['zoom']))
    {
      echo "map = L.map('map', { zoomControl:false, attributionControl: false }).setView([".$_REQUEST['lat'].",".$_REQUEST['lon']."],".$_REQUEST['zoom'].");";
    }
    else {
      echo "map = L.map('map', { zoomControl:false, attributionControl: false }).setView([48.209661, 10.251494], 6);";
    }
?>

    // https: also suppported.
    var Stamen_TonerLite = L.tileLayer('http://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}', {
      attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      subdomains: 'abcd',
      minZoom: 0,
      maxZoom: 20,
      ext: 'png'
    }).addTo(map);


    //map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 2});

    //add popups to marker click action
    var popup = new L.Popup({"offset": [0, -25]});
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });


    var gwMarkerIcon = L.AwesomeMarkers.icon({
      icon: "ion-load-b",
      prefix: "ion",
      markerColor: "blue"
    });

    var gwMarkerIcon = L.icon({
      iconUrl: "resources/TTNraindropJPM45px.png",
      shadowUrl: "resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconOffline = L.icon({
      iconUrl: "resources/TTNraindropJPM45pxRed.png",
      shadowUrl: "resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });
    

<?php
try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    

    if(isset($_REQUEST['gateways']) and $_REQUEST['gateways']!="off")
    {
      $stmt;
      if( $_REQUEST['gateways']=="all" )
      {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates"); 
      }
      else
      {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM 500udeg"); 
      }
      $stmt->execute();

      // set the resulting array to associative
      $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
      foreach($stmt->fetchAll() as $k=>$v) { 
        $sqlloc = $conn->prepare("SELECT lat,lon,last_update FROM gateway_updates WHERE gwaddr=:gwaddr ORDER BY datetime DESC LIMIT 1");
        $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
        $sqlloc->execute();
        $sqlloc->setFetchMode(PDO::FETCH_ASSOC); 
        $locresult = $sqlloc->fetch();
        
        if(strtotime($locresult['last_update']) < time()-(60*60*1)) //1 day
        {
          echo '//'.strtotime($locresult['last_update']).' < '.time().'-'.(60*24*1).'\n;';
          echo '
          marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconOffline}).addTo(map);
          marker.desc = "<font color=\"red\">Offline.</font> Will be removed from the map in 5 days.<br />'.$v['gwaddr'].'<br />Last heard at '.$locresult['last_update'].'<br />Show only this gateway\'s coverage as: <ul><li><a href=\"?gateway='.$v['gwaddr'].'&type=radials\">radials</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=circles\">circles</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=blocks\">blocks</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=radar\">radar</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=alpha\">alpha shape</a><br></ul>";
          ';
        }
        else
        {
          echo '
          marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIcon}).addTo(map);
          marker.desc = "'.$v['gwaddr'].'<br />Last heard at '.$locresult['last_update'].'<br />Show only this gateway\'s coverage as: <ul><li><a href=\"?gateway='.$v['gwaddr'].'&type=radials\">radials</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=circles\">circles</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=blocks\">blocks</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=radar\">radar</a><br><li><a href=\"?gateway='.$v['gwaddr'].'&type=alpha\">alpha shape</a><br></ul>";
          ';
        }

        echo '
          oms.addMarker(marker);
        ';
        
      }
    }


    $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) AS gwaddr FROM `radar`");
      
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      echo '
    $.getJSON("geojson/'.$v['gwaddr'].'/radar.geojson", function(data){
        geojsonBlue = L.geoJson(data, 
          {
            stroke: false, 
            fillOpacity: 0.5,
            fillColor: "blue",
            filter: function (feature) {
              if(feature.style.color=="blue") return true;
              else return false;

            }
          }
        );

        geojsonCyan = L.geoJson(data, 
          {
            stroke: false, 
            fillOpacity: 0.5,
            fillColor: "cyan",
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
            fillColor: "green",
            filter: function (feature) {
              if(feature.style.color=="green") return true;
              else return false;

            }
          }
        );

        geojsonYellow = L.geoJson(data, 
          {
            stroke: false, 
            fillOpacity: 0.5,
            fillColor: "yellow",
            filter: function (feature) {
              if(feature.style.color=="yellow") return true;
              else return false;

            }
          }
        );

        geojsonOrange = L.geoJson(data, 
          {
            stroke: false, 
            fillOpacity: 0.5,
            fillColor: "orange",
            filter: function (feature) {
              if(feature.style.color=="orange") return true;
              else return false;

            }
          }
        );

        geojsonRed = L.geoJson(data, 
          {
            stroke: false, 
            fillOpacity: 0.5,
            fillColor: "red",
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
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>
  </script>
</body>
</html>
