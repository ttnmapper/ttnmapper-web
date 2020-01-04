<!DOCTYPE html>
<html>
<head>
<?php
  include("../testAndOld/include_head.php");
?>
</head>
<body>
  <div id="map"></div>
  <div id="rightcontainer">
    <div id="stats" class="dropSheet"></div>
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
  <script src="/libs/turf.min.js"></script>
  <script src="/libs/he/he.js"></script>

  <script>
    function showHideMenu()
    {
      // if(window.innerWidth >= 800 && window.innerHeight >= 600){
      //   document.getElementById('leftcontainer').style.visibility = 'visible';
      //   document.getElementById('rightcontainer').style.visibility = 'visible';
      //   document.getElementById('menu').style.visibility = 'visible';
      //   document.getElementById('legend').style.visibility = 'visible';
      // }
      // else
      // {
      //   document.getElementById('menu').style.visibility = 'hidden';
      //   document.getElementById('legend').style.visibility = 'hidden';
      //   document.getElementById('leftcontainer').style.visibility = 'hidden';
      //   document.getElementById('rightcontainer').style.visibility = 'hidden';
      // }
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

  


    //Create a map that remembers where it was zoomed to
    function boundsChanged () {
      swap_layers();
      localStorage.setItem('bounds', JSON.stringify(map.getBounds()));
      default_zoom = false;
    }

    var map;
    var default_zoom = true;
    var zoom_override = false;

<?php
//give priority to url parameters for initial location
//and then fail over to cookie
//or else use defautl amsterdam zoom
    if (isset($_REQUEST['lat']) and isset($_REQUEST['lon']) and isset($_REQUEST['zoom']))
    {
      echo "
    map = L.map('map').setView([".$_REQUEST['lat'].",".$_REQUEST['lon']."],".$_REQUEST['zoom'].");
    default_zoom = false;
    zoom_override = true;
    ";
    }
    else {
      echo "
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
      ";
    }
?>


    map.on('dragend', boundsChanged);
    map.on('zoomend', boundsChanged);

    //disable inertia because it is irritating and slow
    map.options.inertia=false;

    //var map = L.map('map').setView([0, 0], 6);
    L.Control.measureControl().addTo(map);

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
    });

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


    switch (findGetParameter("layer")) {
      case "mapnik":
        OpenStreetMap_Mapnik.addTo(map);
        break;
      case "mapnik_grayscale":
        OpenStreetMap_Mapnik_Grayscale.addTo(map);
        break;
      case "terrain":
        Esri_WorldShadedRelief.addTo(map);
        break;
      case "satellite":
        Esri_WorldImagery.addTo(map);
        break;
      default:
        // use default layer
        Stamen_TonerLite.addTo(map);
    }



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


  var maximum_distance = 0;
  var maximum_altitude = 0;
  var max_dist_gw = 0, max_dist_time = 0, max_dist_id = 0;

  function swap_layers()
  {

  }
  
  function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
  }

  function comparePacketsByTime(a,b) {
    if (a.unixtime < b.unixtime)
      return -1;
    if (a.unixtime > b.unixtime)
      return 1;
    return 0;
  }

  function createDescription(point)
  {
    var distance = "-";
    if(point.gateway != false)
    {
      distance = getDistanceFromLatLon(point.lat, point.lon, point.gateway.lat, point.gateway.lon).toFixed(0)+"m";
    }
    return point.time+'<br /><b>Node:</b> '+point.nodeaddr+'<br /><b>Received by gateway:</b> <br />'+point.gwaddr+'<br /><b>Location accuracy:</b> '+point.accuracy+'<br /><b>Packet id:</b> '+point.id+'<br /><b>RSSI:</b> '+point.rssi+'<br /><b>SNR:</b> '+point.snr+'<br /><b>DR:</b> '+point.datarate+'<br /><b>Distance:</b> '+distance+'<br /><b>Altitude: </b>'+point.alt+'m';
  }
  
  var loaded_packets = [];
  var loaded_gateways = [];
  var busy_fetching_packets = false;
  var last_node_locations = [];
  var last_time = 0;
  var number_of_points = 0;



  var markers = [ [], [] ]; //lat, lon => 

  var latestMarkerIcon = L.AwesomeMarkers.icon({
      icon: "ion-android-bicycle",
      prefix: "ion",
      markerColor: "purple"
    });

  recordLineOptions = {
    radius: 10,
    color: "#d755b1",
    opacity: 1,
    stroke: true,
    weight: 10
  };
  recordLine = L.polyline([[0, 0],[0, 0]], recordLineOptions);
  recordLine.addTo(map);


  function updateRealtimeMarker(nodeaddr)
  {
    var len_node = loaded_packets[nodeaddr].length;
    var point = loaded_packets[nodeaddr][len_node-1];
    
    if(!(nodeaddr in last_node_locations))
    {
      last_node_locations[nodeaddr] = 
      L.marker(new L.LatLng(point.lat, point.lon), {
          icon: latestMarkerIcon,
          radius: 10,
          color: "#ff7800",
          fillColor: "#ff7800",
          weight: 1,
          fillOpacity: 1,
          opacity: 1,
          riseOnHover: true
      });
      last_node_locations[nodeaddr].desc = createDescription(point);
      last_node_locations[nodeaddr].addTo(map);
      oms.addMarker(last_node_locations[nodeaddr]);
    }
    else
    {
      last_node_locations[nodeaddr].desc = createDescription(point);
      last_node_locations[nodeaddr].setLatLng(new L.LatLng(point.lat, point.lon));
    }
  }

  function fetch_data()
  {
    if(busy_fetching_packets)
    {
      return;
    }
    busy_fetching_packets=true;
    if(last_time == 0)
    {
      last_time = getParameterByName('start_time');
      if(last_time == null)
      {
        last_time = 0;
      }
    }

    if(getParameterByName('timelapse')!=null)
    {
      var api_url = 'track_api.php?name='+getParameterByName('name')+'&start_time='+last_time+"&limit="+getParameterByName('timelapse');
    }
    else
    {
      var api_url = 'track_api.php?name='+getParameterByName('name')+'&start_time='+last_time;
    }

    $.get(api_url, function(data) {
      
      var i;
      console.log("Fetched "+data.points.length+" new packets");
      for(i=0; i<data.points.length; i++)
      {
        number_of_points++;
        var point = data.points[i];
        if(!(point.nodeaddr in loaded_packets))
        {
          loaded_packets[point.nodeaddr] = [];
        }
        loaded_packets[point.nodeaddr].push(point);
        //loaded_packets[point.nodeaddr].sort(comparePacketsByTime);
        
        if(point.unixtime>last_time)
        {
          last_time = point.unixtime;
        }

        // does not exist
        var rssi = point.rssi;

        var color = "#0000ff";
        if (rssi==0)
        {
          color = "black";
        }
        else if (rssi<-120)
        {
          color = "blue";
        }
        else if (rssi<-115)
        {
          color = "cyan";
        }
        else if (rssi<-110)
        {
          color = "green";
        }
        else if (rssi<-105)
        {
          color = "yellow";
        }
        else if (rssi<-100)
        {
          color = "orange";
        }
        else
        {
          color = "red";
        }

        //if(!point_exists)
        if(typeof markers[point.lat] === 'undefined' || typeof markers[point.lat][point.lon] === 'undefined')
        {
          marker = L.circleMarker([point.lat, point.lon], {
            stroke: false,
            radius: 5,
            color: color,
            fillColor: color,
            fillOpacity: 0.8
          });
          marker.bindPopup(createDescription(point));
          marker.addTo(map);

          if(typeof markers[point.lat] === 'undefined')
          {
            markers[point.lat] = [];
          }
          markers[point.lat][point.lon] = {"rssi": point.rssi, "marker": marker};

        }
        else {
          // does exist
          if(point.rssi<markers[point.lat][point.lon]["rssi"])
          {
            markers[point.lat][point.lon]["marker"].options.fillColor = color;
            markers[point.lat][point.lon]["rssi"] = point.rssi;

            map.removeLayer(markers[point.lat][point.lon]["marker"]);
            markers[point.lat][point.lon]["marker"].addTo(map);
          }
        }

        if(point.gateway != false)
        {
          if(getParameterByName('nogateways')==null)
          {
            if(!(point.gwaddr in loaded_gateways))
            {
              loaded_gateways[point.gwaddr] = point.gateway;
              marker = L.marker([point.gateway.lat, point.gateway.lon], 
                {icon: gwMarkerIcon});
              marker.desc = point.gwaddr;
              marker.addTo(map);
              oms.addMarker(marker);
            }
          }

          if(getParameterByName('nolines')==null)
          {
            markerOptions = {
              radius: 10,
              color: color,
              fillColor: color,
              opacity: 0.1,
              weight: 2
            };
            marker = L.polyline([[point.lat, point.lon],[point.gateway.lat, point.gateway.lon]], markerOptions);
            marker.bindPopup(createDescription(point));
            marker.addTo(map);
          }

          distance = getDistanceFromLatLon(point.lat, point.lon, point.gateway.lat, point.gateway.lon);
          if(maximum_distance<distance)
          {
            maximum_distance = distance;
            max_dist_gw = point.gwaddr;
            max_dist_time = point.time;
            max_dist_id = point.id;

            map.removeLayer(recordLine);
            recordLine = L.polyline([[point.lat, point.lon],[point.gateway.lat, point.gateway.lon]], recordLineOptions);
            recordLine.bindPopup("Record distance:<br />"+createDescription(point));
            recordLine.addTo(map);

            console.log("new distnace record: "+distance);
            console.log(point);
          }
        }
        loaded_packets[point.nodeaddr].sort(comparePacketsByTime);

        if( Number(point.alt) >= maximum_altitude )
        {
          maximum_altitude = Number(point.alt);
        }

        updateRealtimeMarker(point.nodeaddr);
      }

      if(data.points.length>0)
      {
        document.getElementById("stats").innerHTML = 
        "Last packet received: "+point.time+"<br />"+
        "Last altitude: "+point.alt+"m<br />"+
        "<br />"+
        "Number of points: "+number_of_points+"<br />"+
        "Number of devices: "+Object.keys(loaded_packets).length+"<br />"+
        "Number of gateways: "+Object.keys(loaded_gateways).length+"<br />" +
        "<br />"+
        "Maximum altitude: "+maximum_altitude+"m<br />" +
        "Maximum distance: "+(maximum_distance/1000).toFixed(3)+"km <br />" +
        "<small>GW: "+max_dist_gw+" @ "+max_dist_time+"<!-- - ID: "+max_dist_id+"--></small><br />";

        busy_fetching_packets=false;
        setTimeout(fetch_data, 1);
      }
      else
      {
        busy_fetching_packets=false;
        setTimeout(fetch_data, 1000);
      }
    });
  }

  //http://stackoverflow.com/questions/18883601/function-to-calculate-distance-between-two-coordinates-shows-wrong
  function getDistanceFromLatLon(lat1,lon1,lat2,lon2) {
    var R = 6371000; // Radius of the earth in km
    var dLat = deg2rad(lat2-lat1);  // deg2rad below
    var dLon = deg2rad(lon2-lon1); 
    var a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
      Math.sin(dLon/2) * Math.sin(dLon/2)
      ; 
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
    var d = R * c; // Distance in km
    return d;
  }

  function deg2rad(deg) {
    return deg * (Math.PI/180)
  }

  function startUpdating()
  {
    fetch_data();
    //var intervalID = setInterval(fetch_data, 100);
  }
  window.onload = startUpdating();
  </script>
</body>
</html>