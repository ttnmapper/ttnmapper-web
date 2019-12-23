<!DOCTYPE html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
  if(isset($_REQUEST["date"]) and $_REQUEST["date"]=="today")
  {
    $_REQUEST["date"] = date("Y-m-d");
  }

  if(isset($_REQUEST["date"]) and $_REQUEST["date"]=="yesterday")
  {
    $_REQUEST["date"] = date("Y-m-d",strtotime("-1 days"));
  }

  if(!isset($_REQUEST["date"]))
  {
    $_REQUEST["date"] = date("Y-m-d");
  }
?>
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

  <!--favicons-->
  <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/favicons/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/favicons/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/favicons/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/favicons/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/favicons/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/favicons/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/favicons/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/favicons/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/favicons/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
  <link rel="manifest" href="/favicons/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/favicons/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">

  <!-- Leaflet -->
  <link rel="stylesheet" href="/libs/leaflet/leaflet.css" />
  <link rel="stylesheet" href="/libs/Leaflet.markercluster/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="/libs/Leaflet.markercluster/dist/MarkerCluster.Default.css" />
  <link rel="stylesheet" href="/libs/leaflet.measure/leaflet.measure.css" />
    
  <style>
  body {
    padding: 0;
    margin: 0;
  }
  html, body, #map {
    height: 100%;
  }
  body,td,th {
  //  font-family: Arial, Helvetica, sans-serif;
  //  color: #000000;
  //  font-weight: normal;
  }
  a:link {
          text-decoration: none;
          color: #000010;
  }
  a:visited {
          text-decoration: none;
          color: #000020;
  }
  a:hover {
          text-decoration: underline;
          color: #000070;
  }
  a:active {
          text-decoration: none;
          color: #000070;
  }
  #leftcontainer
  {
    position: absolute;
    left: 20px;
    bottom: 30px;
    //max-width: 190px;
    z-index: 500;
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
    z-index: 500;
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

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <script>
  $.ajaxSetup({ dataType: 'json' });
  </script>

  <script>L_PREFER_CANVAS = true;</script>
  <script src="/libs/leaflet/v1.0.3/leaflet.js"></script>
  <!--<script src='//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js'></script>-->
  <script src="/libs/leaflet.geojsoncss.min.js"></script>
  <script src="/libs/oms.min.js"></script>
  <script src="/libs/Leaflet.draw/dist/leaflet.draw.js"></script>
  <script src="/libs/Leaflet.MeasureControl/leaflet.measurecontrol.js"></script>
  <script src="/libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.js"></script>
  <script src="/libs/leaflet-grayscale-master/TileLayer.Grayscale.js"></script>
  <script src="/libs/Leaflet.label-master/dist/leaflet.label.js"></script>
  <script src='/libs/turf.min.js'></script>
  <script type="text/javascript" src="/libs/Map.SelectArea.min.js"></script>
  <script src="/libs/Leaflet.EasyButton-master/src/easy-button.js"></script>
  <script>
    function swap_layers(){}
    function showHideMenu() {}

    //Create a map that remembers where it was zoomed to
    function boundsChanged () {
      swap_layers();
      localStorage.setItem('bounds', JSON.stringify(map.getBounds()));
      default_zoom = false;
    }


    function isInsideBounds(marker, bounds) {
      if(marker._latlng.lng > bounds._southWest.lng
      && marker._latlng.lng < bounds._northEast.lng
      && marker._latlng.lat > bounds._southWest.lat
      && marker._latlng.lat < bounds._northEast.lat) {
        return true;
      }
      else {
        return false;
      }
    }

    function findGetParameter(parameterName) {
      var result = null;
      var tmp = [];
      var items = location.search.substr(1).split("&");
      for (var index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] === parameterName) {
          result = decodeURIComponent(tmp[1]);
        }
      }
      return result;
    }



    var map;
    var default_zoom = true;
    var zoom_override = false;

    if(findGetParameter("lat")!=null && findGetParameter("lon")!=null && findGetParameter("zoom")!=null) {
      map = L.map('map').setView([ findGetParameter("lat"), findGetParameter("lon") ], findGetParameter("zoom"));
      default_zoom = false;
      zoom_override = true;
    }
    else {
      b = JSON.parse(localStorage.getItem('bounds'));
      if (b == null)
      {
        map = L.map('map').setView([48.209661, 10.251494], 6);
      }
      else {
        map = L.map('map');
        try {
          map.fitBounds([[b._southWest.lat%90,b._southWest.lng%180],[b._northEast.lat%90,b._northEast.lng%180]]);
          default_zoom = false;
        } catch (err) {
          map.setView([48.209661, 10.251494], 6);
        }
      }
    }

    map.on('dragend', boundsChanged);
    map.on('zoomend', boundsChanged);

    //disable inertia because it is irritating and slow
    map.options.inertia=false;

    //var map = L.map('map').setView([0, 0], 6);
    L.Control.measureControl().addTo(map);

    <?php
    $date = date_create($_REQUEST['date']);
    date_sub($date, date_interval_create_from_date_string('1 days'));
    ?>
    L.easyButton('&lt;', function(btn, map){
        console.log("Go to previous day");
        window.location = location.protocol + '//' + location.host + location.pathname + "?date=" + <?php echo '"'.date_format($date, 'Y-m-d').'";'; ?>
    }).addTo(map);

    <?php
    $date = date_create($_REQUEST['date']);
    date_add($date, date_interval_create_from_date_string('1 days'));
    ?>
    L.easyButton('&gt;', function(btn, map){
        console.log("Go to next day");
        window.location = location.protocol + '//' + location.host + location.pathname + "?date=" + <?php echo '"'.date_format($date, 'Y-m-d').'";'; ?>
    }).addTo(map);

    // https: also suppported.
    var Esri_WorldImagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
      fadeAnimation: false
    });

    // https: also suppported.
    var Stamen_TonerLite = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}', {
      attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      subdomains: 'abcd',
      minZoom: 0,
      maxZoom: 20,
      ext: 'png',
      fadeAnimation: false
    }).addTo(map);

    var OpenStreetMap_HOT = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>',
      fadeAnimation: false
      });

    var OpenStreetMap_BlackAndWhite = L.tileLayer('https://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false
    });

    // var OpenMapSurfer_Grayscale = L.tileLayer('http://korona.geog.uni-heidelberg.de/tiles/roadsg/x={x}&y={y}&z={z}', {
    // maxZoom: 19,
    // attribution: 'Imagery from <a href="http://giscience.uni-hd.de/">GIScience Research Group @ University of Heidelberg</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    // });
    
    var OpenStreetMap_Mapnik_Grayscale = L.tileLayer.grayscale('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false
    });

    // https: also suppported.
    var Esri_WorldShadedRelief = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Source: Esri',
      maxZoom: 13,
      fadeAnimation: false
    });

    // https: also suppported.
    var OpenStreetMap_Mapnik = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false
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

    map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 2});

    //add popups to marker click action
    var popup = new L.Popup({"offset": [0, -25]});
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });

    // Listen for orientation changes
    window.addEventListener("orientationchange", showHideMenu(), false);
    window.onresize = showHideMenu;

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

    var gwMarkerIconSCG = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxOrange.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconNoData = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxGreen.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconOffline = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxRed.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconRound = L.icon({
      iconUrl: "/resources/gateway_dot.png",

      iconSize:     [10, 10], // size of the icon
      iconAnchor:   [5, 5], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [5, 5] // point from which the popup should open relative to the iconAnchor
    });

    map.on('click', function(e) {
      console.log("Clicked: " + e.latlng.lat + ", " + e.latlng.lng);
    });


    map.selectArea.enable();

    map.on('areaselected', (e) => {
      // var postData = e.bounds;
      // postData['date'] = <?php echo '"'.$_REQUEST["date"].'";'; ?>
      // console.log(postData);
      // $.ajax
      //   ({
      //     type: "POST",
      //     url: 'bbox-select.php',
      //     dataType: 'json',
      //     data: JSON.stringify(postData),
      //     success: function (data) {
      //       console.log(data);
      //       if (confirm('Delete '+data['count']+' packets?')) {
      //           data.packets.forEach( (val, idx) => {
      //             console.log("Should delete "+val);
      //             if(circles[val] !== undefined) {
      //               map.removeLayer(circles[val]);
      //             }
      //             if(lines[val] !== undefined) {
      //               map.removeLayer(lines[val]);
      //             }
      //           } );
      //       } else {
      //           console.log("Should keep");
      //       }
      //     }
      //   });
      var toDelete = [];
      for(var index in circles) {
        if(isInsideBounds(circles[index], e.bounds)) {
          console.log("Inside bounds");
          toDelete.push(index);
          // map.removeLayer(circles[index]);
          // map.removeLayer(lines[index])
          // delete circles[index];
          // delete lines[index];
        }
      }

      if (confirm('Delete '+toDelete.length+' packets?')) {
        toDelete.forEach( (val) => {
          console.log("Should delete "+val);
          if(circles[val] !== undefined) {
            map.removeLayer(circles[val]);
            delete circles[val];
          }
          if(lines[val] !== undefined) {
            map.removeLayer(lines[val]);
            delete lines[val];
          }
        } );
        $.ajax
        ({
          type: "POST",
          url: 'bbox-delete.php',
          dataType: 'json',
          data: JSON.stringify(toDelete),
          success: function (data) {
            console.log(data);
          }
        });
      } else {
          console.log("Should keep");
      }
    });
// // dragging will be enabled and you can  
// // start selecting with Ctrl key pressed 
// map.selectArea.setCtrlKey(true); 
 
// // box-zoom will be disabled and you can  
// // start selecting with Shift key pressed 
// map.selectArea.setCtrlKey(true); 

<?php

$gateways = array();


try {
    $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $stmt = $conn->prepare("SELECT * FROM packets WHERE time > :date AND time<DATE_ADD(:date, INTERVAL 1 DAY)");
    $stmt->bindParam(':date', $_REQUEST["date"]);

    $stmt->execute();

    echo '
    var markerArray = [];
    var circles = {};
    var lines = {};
      ';

    // set the resulting array to associative
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
    echo "//".$stmt->rowCount()."\n";
    foreach($stmt->fetchAll() as $k=>$v) {
      $packetId = $v["id"];
      $rssi = $v["rssi"];
      $snr = $v["snr"];

      if($snr<0) {
        $rssi = $rssi + $snr;
      }

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

      if(!array_key_exists($v['gwaddr'], $gateways))
      {
        $sqlloc = $conn->prepare("SELECT channels,description,lat,lon,last_heard as last_update FROM gateways_aggregated WHERE gwaddr=:gwaddr");
        // $sqlloc = $conn->prepare("SELECT datetime,gateway_updates.lat,gateway_updates.lon,last_update,description,channels FROM gateway_updates JOIN gateways_aggregated ON gateway_updates.gwaddr = gateways_aggregated.gwaddr WHERE gateways_aggregated.gwaddr=:gwaddr AND datetime < :date ORDER BY datetime DESC LIMIT 1");
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
        if($distance<1000) {
          continue;
        }

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
        lines['.$v['id'].'] = marker;
        ';

      }
      echo '
      markerOptions = {
          stroke: false,
          radius: 5,
          color: "'.$color.'",
          fillColor: "'.$color.'",
          fillOpacity: 0.8
      };
      marker = L.circleMarker(['.$v['lat'].', '.$v['lon'].'], markerOptions);
      marker.bindPopup("'.$v['time'].'<br /><b>Node:</b> '.$v['nodeaddr'].'<br /><b>Received by gateway:</b> <br />'.$v['gwaddr'].'<br /><b>Location accuracy:</b> '.$v['accuracy'].'<br /><b>Packet id:</b> '.$v['id'].'<br /><b>RSSI:</b> '.$v['rssi'].'dBm<br /><b>SNR:</b> '.$v['snr'].'dB<br /><b>Link cost:</b> '.($rssi*-1).'dB<br /><b>DR:</b> '.$v['datarate'].'<br /><b>Distance:</b> '.$distance.'m<br /><b>Altitude: </b>'.$v['alt'].'m");
      markerArray.push(marker);
      circles['.$v['id'].'] = marker;
      ';
      
    }

    echo '
    if(markerArray.length>0)
    {
      var group = L.featureGroup(markerArray).addTo(map);
      map.fitBounds(group.getBounds());
    }
    ';

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
      marker.desc = "'.$gwdescriptionHead.'<br /><br /><font color=\"red\">Offline.</font> Will be removed from the map in 5 days.<br />'.$gwdescription.'";
      ';
    }
    else
    {
      if($gateway['channel_count']==0)
      {
        //Single channel gateway
        echo '
        marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIconNoData});
        marker.desc = "'.$gwdescriptionHead.'<br /><br />Not mapped by TTN Mapper.<br />'.$gwdescription.'";
      ';
      }
      else if($gateway['channel_count']<3)
      {
        //Single channel gateway
        echo '
        marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIconSCG});
        marker.desc = "'.$gwdescriptionHead.'<br /><br />Likely a <font color=\"orange\">Single Channel Gateway.</font><br />'.$gwdescription.'";
      ';
      }
      else
      {
        //LoRaWAN gateway
        echo '
        marker = L.marker(['.$gateway['lat'].', '.$gateway['lon'].'], {icon: gwMarkerIcon});
        marker.desc = "'.$gwdescriptionHead.'<br />'.$gwdescription.'";
        ';
      }
    }

    echo '
      marker.addTo(map);
      oms.addMarker(marker);
    ';
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
