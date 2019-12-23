<!DOCTYPE html>
<html>
<head>
  <?php
  include("include_head.php");
  ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
</head>
<body>
  <?php
  include("include_body_top.php");
  ?>
  <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
  <script>

var loadedRadarLayers = {};
var visibleRadarLayers = {};

var loadedCircleLayers = {};
var visibleCircleLayers = {};

var loadedGateways = {};
var gatewaysInView = [];
var gatewayMarkers = L.markerClusterGroup({
  maxClusterRadius: 40
});

var previousZoom = 0;
var layerSwapGwCount = 300;
var layerHideGwCount = 2000;


function showHideMenu()
{
  document.getElementById('legend').style.display = 'none';
    
  if(window.innerWidth >= 800 && window.innerHeight >= 600){
    document.getElementById('leftcontainer').style.visibility = 'visible';
    document.getElementById('menu').style.visibility = 'visible';
    // document.getElementById('leftpadding').style.display = 'none';
    // document.getElementById('legend').style.display = 'none';


    document.getElementById('rightcontainer').style.visibility = 'visible';
    // document.getElementById('stats').style.display = 'none';
    // document.getElementById('rightpadding').style.display = 'none';
    // document.getElementById('shuttleworth').style.visibility = 'hidden';
  }
  else
  {
    document.getElementById('leftcontainer').style.visibility = 'hidden';
    document.getElementById('menu').style.visibility = 'hidden';
    // document.getElementById('leftpadding').style.display = 'none';
    // document.getElementById('legend').style.display = 'none';

    document.getElementById('rightcontainer').style.visibility = 'hidden';
    // document.getElementById('stats').style.display = 'none';
    // document.getElementById('rightpadding').style.display = 'none';
    // document.getElementById('shuttleworth').style.visibility = 'hidden';
  }
}

//Create a map that remembers where it was zoomed to
function boundsChanged () {
  // swap_layers();
  getGatewaysInView();
  localStorage.setItem('bounds', JSON.stringify(map.getBounds()));
  default_zoom = false;
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

map = L.map('map');

// var coveragePane = map.createPane('coverage');
// coveragePane.style.opacity = 0.2;
map.createPane('semitransparent');
map.getPane('semitransparent').style.opacity = '0.5';
var canvasRenderer = L.canvas({pane: 'semitransparent'});

if(findGetParameter("lat")!=null && findGetParameter("lon")!=null && findGetParameter("zoom")!=null) {
  map.setView([ findGetParameter("lat"), findGetParameter("lon") ], findGetParameter("zoom"));
  default_zoom = false;
  zoom_override = true;
}
else {
  b = JSON.parse(localStorage.getItem('bounds'));
  if (b == null)
  {
    map.setView([48.209661, 10.251494], 6);
  }
  else {
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
  "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
  "Terrain": Esri_WorldShadedRelief, 
  "OSM Mapnik": OpenStreetMap_Mapnik,
  "Satellite": Esri_WorldImagery
})
.addTo(map);

map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");

// Listen for orientation changes
window.addEventListener("orientationchange", showHideMenu(), false);
window.onresize = showHideMenu;

var gwMarkerIconRoundBlue = L.icon({
  iconUrl: "/resources/gateway_dot.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});

var gwMarkerIconRoundGreen = L.icon({
  iconUrl: "/resources/gateway_dot_green.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});
var gwMarkerIconRoundRed = L.icon({
  iconUrl: "/resources/gateway_dot_red.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});
var gwMarkerIconRoundYellow = L.icon({
  iconUrl: "/resources/gateway_dot_yellow.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});

getGatewaysInView(); //This gets the number of visible gateways which is used to show or hide radars and circles
// getGateways(); // This gets the gateway markers for all gateways
gatewayMarkers.addTo(map);


function hideAllLayers() {
    map.eachLayer(function (layer) {
      if ("feature" in layer) {
        if ("geometry" in layer.feature) {
          if ("type" in layer.feature.geometry) {
            if (layer.feature.geometry.type == "Point"
              || layer.feature.geometry.type == "Polygon") {
                map.removeLayer(layer);
            }
          }
        }
      }
    });
}

var prevState = "none";
function showOrHideLayers() {
  if(map)
  {
    if(gatewaysInView.length<layerSwapGwCount){
      //View radars
      if(prevState !== "radar") {
        hideAllLayers();
      }
      previousZoom = map.getZoom();
      prevState = "radar";
      loadRadarsInView();
      hideAllCircleViews();
    }
    else if(gatewaysInView.length<layerHideGwCount){
      //View circles
      if (prevState!=="circle") {
        hideAllLayers();
      }
      previousZoom = map.getZoom();
      prevState = "circle";
      loadCircleViews();
      hideAllRadarViews();
    }
    else {
      //Hide all and only show markers
      hideAllLayers();
      hideAllCircleViews();
      hideAllRadarViews();
      previousZoom = map.getZoom();
      prevState = "none";
    }
  }
}

function getGatewaysInView()
{
  var bounds = map.getBounds();

  $.ajax
    ({
      type: "POST",
      url: 'gwbbox.php',
      dataType: 'json',
      data: JSON.stringify(bounds),
      success: function (data) {
        gatewaysInView = data["gateways"];

        addGateways(gatewaysInView);

        console.log(gatewaysInView.length + " gateways in view");
        showOrHideLayers();
      }
    });
}

function addGateways(gateways)
{
  //gwdetailslist.php
  var gatewaysToAdd = [];
  for (i in gateways) {
    if(!(gateways[i] in loadedGateways)) {
      // console.log(gateways[i]+" not loaded yet");
      gatewaysToAdd.push(gateways[i]);
    }
  }

  $.ajax({
    type: "POST",
    url: "gwdetailslist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": gatewaysToAdd }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){
      for(gateway in data) {
        // console.log(data[gateway]);
        addGatewayMarker(gateway, data[gateway]);
      }
    },
    failure: function(errMsg) {
        cosole.log(errMsg);
    }
  });
}

function addGatewayMarker(gwaddr, data)
{
  // console.log("Adding "+gwaddr);
  var gwdescriptionHead = "";
  if (data['description'] != null) {
    gwdescriptionHead = "<b>"+he.encode(data['description'])+"</b><br />"+he.encode(gwaddr);
  } else {
    gwdescriptionHead = "<b>"+he.encode(gwaddr)+"</b>";
  }

  gwdescription = 
    '<br />Last heard at '+formatTime(data['last_heard'])+
    '<br />Channels heard on: '+data['channels']+
    '<br />Lat, Lon: '+data['lat'] +','+ data['lon']+
    '<br />Show only this gateway\'s coverage as: '+
    '<ul>'+
      '<li><a href=\"//ttnmapper.org/colour-radar/?gateway[]='+he.encode(gwaddr)+
          '\">radar</a><br>'+
      '<li><a href=\"//ttnmapper.org/alpha-shapes/?gateway[]='+he.encode(gwaddr)+
          '\">alpha shape</a><br>'+
    '</ul>';

  if(data['last_heard'] < (Date.now()/1000)-(60*60*1)) //1 hour
  {
    marker = L.marker([data['lat'], data['lon']], {icon: gwMarkerIconRoundRed});
    marker.bindPopup(gwdescriptionHead+'<br /><br /><font color="red">Offline.</font> Will be removed from the map in 5 days.<br />'+gwdescription);
  }
  else if(data['channels']<3)
  {
    //Single channel gateway
    marker = L.marker([data['lat'], data['lon']], {icon: gwMarkerIconRoundYellow});
    marker.bindPopup(gwdescriptionHead+'<br /><br />Likely a <font color="orange">Single Channel Gateway.</font><br />'+gwdescription);
  }
  else
  {
    //LoRaWAN gateway
    marker = L.marker([data['lat'], data['lon']], {icon: gwMarkerIconRoundBlue});
    marker.bindPopup(gwdescriptionHead+'<br />'+gwdescription);
  }

  if(gwaddr in loadedGateways) {
    return;
  } else {
    // marker.addTo(map);
    // oms.addMarker(marker);
    gatewayMarkers.addLayer(marker);
    loadedGateways[gwaddr] = marker;
  }
}

function formatTime(timestamp)
{
  // Create a new JavaScript Date object based on the timestamp
  // multiplied by 1000 so that the argument is in milliseconds, not seconds.
  var date = new Date(timestamp*1000);
  return date.toISOString();
}

function hideAllRadarViews()
{
  Object.keys(visibleRadarLayers).forEach(function(key) {
    map.removeLayer(visibleRadarLayers[key]);
    delete visibleRadarLayers[key];
  });
}

function hideAllCircleViews()
{
  Object.keys(visibleCircleLayers).forEach(function(key) {
    map.removeLayer(visibleCircleLayers[key]);
    delete visibleCircleLayers[key];
  });
}

function loadCircleViews()
{
  gwids = gatewaysInView;
  // console.log(gwids);

  // First hide layers that are not visible anymore
  Object.keys(visibleCircleLayers).forEach(function(key) {
    if($.inArray(key, gwids)!=-1) {
      // Keep showing the layer, or download a new one
    }
    else {
      map.removeLayer(visibleCircleLayers[key]);
      delete visibleCircleLayers[key];
    }
  });

  var newRadarsToDownload = [];

  for(var i=0; i<gwids.length; i++) {
    let gwid = gwids[i];

    // Add a marker on the map for the gateway
    // addGateway(gwid);
    
    // Layer download
    if(gwid in loadedCircleLayers) {
      //already downloaded this layer and drew it
      // Layer show/hide
      if(gwid in visibleCircleLayers) {
        // Layer already shown
      }
      else {
        loadedCircleLayers[gwid].addTo(map);
        visibleCircleLayers[gwid] = loadedCircleLayers[gwid];
      }
    }
    else {
      newRadarsToDownload.push(gwid);
    }
  }

  $.ajax({
    type: "POST",
    url: "gwcirclelist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": newRadarsToDownload }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){
      for(gwid in data) {
        let geojsonLayerCircles = L.geoJSON(data[gwid], {
          pointToLayer: function (feature, latlng) {
            return L.circle(latlng, feature.properties.radius, {
              stroke: false,
              fillOpacity: 0.8,
              fillColor: "#0000FF",
              renderer: canvasRenderer,
            });
          }
        });
        if(gwid in visibleCircleLayers) {
        } else {
          geojsonLayerCircles.addTo(map);
          visibleCircleLayers[gwid] = geojsonLayerCircles;
        }
        if(gwid in loadedCircleLayers) {
        } else {
          loadedCircleLayers[gwid] = geojsonLayerCircles;
        }
      }
    },
    failure: function(errMsg) {
        cosole.log(errMsg);
    }
  });

}

function loadRadarsInView()
{
  gwids = gatewaysInView;

  // First hide layers that are not visible anymore
  Object.keys(visibleRadarLayers).forEach(function(key) {
    if($.inArray(key, gwids)!=-1) {
      // Keep showing the layer, or download a new one
    }
    else {
      map.removeLayer(visibleRadarLayers[key]);
      delete visibleRadarLayers[key];
    }
  });

  var newRadarsToDownload = [];

  for(var i=0; i<gwids.length; i++) {
    let gwid = gwids[i];

    // Add a marker on the map for the gateway
    // addGateway(gwid);
    
    // Layer download
    if(gwid in loadedRadarLayers) {
      //already downloaded this layer and drew it
      // Layer show/hide
      if(gwid in visibleRadarLayers) {
        // Layer already shown
      }
      else {
        loadedRadarLayers[gwid].addTo(map);
        visibleRadarLayers[gwid] = loadedRadarLayers[gwid];
        // console.log("ReShowing "+gwid);
      }
    }
    else {
      newRadarsToDownload.push(gwid);
    }
  }

  $.ajax({
    type: "POST",
    url: "gwradarlist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": newRadarsToDownload }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){
      for(gwid in data) {
        // let polygon = L.polygon(latlngs,
        let polygon = L.geoJSON(data[gwid], 
          {
            stroke: false,
            // weight: 2,
            // color: "#000000",
            fillOpacity: 0.8,
            fillColor: "#0000FF",
            // zIndex: 25,
            renderer: canvasRenderer
          }
        );
        if(gwid in visibleRadarLayers) {
        } else {
          visibleRadarLayers[gwid] = polygon; // should add the layer to the map here and store the pointer to the layer
          polygon.addTo(map);
        }
        if(gwid in loadedRadarLayers) {
        } else {
          loadedRadarLayers[gwid] = polygon; // should add the geojson data to the dictionary here
        }
      }
    },
    failure: function(errMsg) {
        cosole.log(errMsg);
    }
  });
  
}

  </script>
</body>
</html>
