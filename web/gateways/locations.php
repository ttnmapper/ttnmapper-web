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

    map.on('click', function(e) {
      console.log("Clicked: " + e.latlng.lat + ", " + e.latlng.lng);
    });

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
    


    echo '
    var markerArray = [];
      ';
	$stmt = $conn->prepare("SELECT * FROM `gateway_updates` WHERE gwaddr = :gwaddr");
  $gwaddr = strtoupper($_REQUEST['gwaddr']);
	$stmt->bindParam(':gwaddr', $gwaddr);
    
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 

      echo '
      marker = L.circleMarker(['.$v['lat'].', '.$v['lon'].']);
      marker.bindPopup("'.$v['datetime'].'<br />'.$v['lat'].', '.$v['lon'].'");
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
