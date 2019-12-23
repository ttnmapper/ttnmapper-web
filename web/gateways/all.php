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
    function swap_layers() {}

    function showHideMenu()
    {
      if(window.innerWidth >= 800 && window.innerHeight >= 600){
        document.getElementById('leftcontainer').style.visibility = 'visible';
        document.getElementById('menu').style.visibility = 'visible';
        document.getElementById('legend').style.display = 'none';
        document.getElementById('leftpadding').style.display = 'none';
        document.getElementById('stats').style.visibility = 'visible';
      }
      else
      {
        document.getElementById('menu').style.visibility = 'hidden';
        document.getElementById('legend').style.display = 'none';
        document.getElementById('leftpadding').style.display = 'none';
        document.getElementById('stats').style.visibility = 'hidden';
        document.getElementById('leftcontainer').style.visibility = 'hidden';
      }
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
    }).addTo(map);

    
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
      "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
      "Terrain": Esri_WorldShadedRelief, 
      "OSM Mapnik": OpenStreetMap_Mapnik,
      "Satellite": Esri_WorldImagery
    })
    .addTo(map);

    map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 1});

    //add popups to marker click action
    var popup = new L.Popup({"offset": [0, 0]});
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });

    // Listen for orientation changes
    window.addEventListener("orientationchange", showHideMenu(), false);
    window.onresize = showHideMenu;

    var gwMarkerIconRoundBlue = L.icon({
      iconUrl: "/resources/gateway_dot.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconRoundGreen = L.icon({
      iconUrl: "/resources/gateway_dot_green.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });
    var gwMarkerIconRoundRed = L.icon({
      iconUrl: "/resources/gateway_dot_red.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });
    var gwMarkerIconRoundYellow = L.icon({
      iconUrl: "/resources/gateway_dot_yellow.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });
    
    $.get( "all-api.php", function( data ) {
      gateways = data["gateways"];

      for(var i=0; i<gateways.length; i++) {
        addGateway(gateways[i]);
      }
    });

function formatTime(timestamp)
{
  var date = new Date(timestamp*1000);
  return date.toISOString();
}

  function addGateway(gateway) {
  //{"gtwid":"00-08-00-4a-39-a6","lat":"-19.8733480","lon":"-43.9919900"}
      var gwdescriptionHead = "<b>"+gateway['gtwid']+"</b>";

      gwdescription = 
        '<br />Last heard at '+gateway['last_heard']+
        '<br />Lat, Lon: '+gateway['lat'] +','+ gateway['lon'];
        
      if(gateway['last_heard'] < (Date.now()/1000)-(60*60*1)) //1 hour
      {
        marker = L.marker([gateway['lat'], gateway['lon']], {icon: gwMarkerIconRoundBlue});
        marker.desc = gwdescriptionHead+'<br /><br /><font color="red">Offline.</font> Will be removed from the map in 5 days.<br />'+gwdescription;
      }
      else
      {
        //LoRaWAN gateway
        marker = L.marker([gateway['lat'], gateway['lon']], {icon: gwMarkerIconRoundBlue});
        marker.desc = gwdescriptionHead+'<br />'+gwdescription;
      }

      marker.addTo(map);
      oms.addMarker(marker);
  }

  </script>
</body>
</html>
