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
// var layerSwapZoomLevel = 10;
// var layerHideZoomLevel = 7;

var map;
var default_zoom = true;
var zoom_override = false;

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

var canvasRenderer;

var getParameters = getJsonFromUrl();

setUp();

function setUp() {

  map = L.map('map');

  map.createPane('semitransparent');
  map.getPane('semitransparent').style.opacity = '0.5';
  canvasRenderer = L.canvas({pane: 'semitransparent'});

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

  L.control.measure({
    position: 'topleft'
  }).addTo(map)

  map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");

  // Listen for orientation changes
  window.addEventListener("orientationchange", showHideMenu(), false);
  window.onresize = showHideMenu;

  addBackgroundLayers();
  addForegroundLayers();
  getGatewaysInView();

  //Clusterfier for gateway markers
  gatewayMarkers.addTo(map);
}

function addBackgroundLayers() {
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


  var OpenStreetMap_Mapnik_Grayscale = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    fadeAnimation: false,
    className: 'toGrayscale'
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
}


function addForegroundLayers() {
  var coveragetiles = L.tileLayer('/tms/index.php?tile={z}/{x}/{y}', {
    maxNativeZoom: 18,
    maxZoom: 20,
    zIndex: 10,
    opacity: 0.5
  });
  coveragetiles.addTo(map);
}


function showHideMenu()
{

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

function getJsonFromUrl(url) {
  if(!url) url = location.href;
  var question = url.indexOf("?");
  var hash = url.indexOf("#");
  if(hash==-1 && question==-1) return {};
  if(hash==-1) hash = url.length;
  var query = question==-1 || hash==question+1 ? url.substring(hash) : 
  url.substring(question+1,hash);
  var result = {};
  query.split("&").forEach(function(part) {
    if(!part) return;
    part = part.split("+").join(" "); // replace every + with space, regexp-free version
    var eq = part.indexOf("=");
    var key = eq>-1 ? part.substr(0,eq) : part;
    var val = eq>-1 ? decodeURIComponent(part.substr(eq+1)) : "";
    var from = key.indexOf("[");
    if(from==-1) result[decodeURIComponent(key)] = val;
    else {
      var to = key.indexOf("]",from);
      var index = decodeURIComponent(key.substring(from+1,to));
      key = decodeURIComponent(key.substring(0,from));
      if(!result[key]) result[key] = [];
      if(!index) result[key].push(val);
      else result[key][index] = val;
    }
  });
  return result;
}


function getGatewaysInView()
{

  var bounds = map.getBounds();

  $.ajax
    ({
      type: "POST",
      url: '/webapi/gwbbox.php',
      dataType: 'json',
      data: JSON.stringify(bounds),
      success: function (data) {
        gatewaysInView = data["gateways"];
        addGateways(gatewaysInView);
        console.log(gatewaysInView.length + " gateways in view");
        // showOrHideLayers();
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
    url: "/webapi/gwdetailslist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": gatewaysToAdd }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){
      for(gateway in data) {
        addGatewayMarker(gateway, data[gateway]);
      }
    },
    failure: function(errMsg) {
        console.log(errMsg);
    }
  });

  //showOrHideLayers(); // First add the gateway markers, then do the layers.
}

function addGatewayMarker(gateway, data)
{
  if(gateway in loadedGateways) {
    return;
  } else {
    // console.log("Adding "+gateway);
    var gwdescriptionHead = "";
    if (data['description'] != null) {
      gwdescriptionHead = "<b>"+he.encode(data['description'])+"</b><br />"+he.encode(gateway);
    } else {
      gwdescriptionHead = "<b>"+he.encode(gateway)+"</b>";
    }

    gwdescription = 
      '<br />Last heard at '+formatTime(data['last_heard'])+
      '<br />Channels heard on: '+data['channels']+
      '<br />Lat, Lon: '+data['lat'] +','+ data['lon']+
      '<br />Show only this gateway\'s coverage as: '+
      '<ul>'+
        '<li><a href=\"//ttnmapper.org/colour-radar/?gateway[]='+he.encode(gateway)+
            '\">radar</a><br>'+
        '<li><a href=\"//ttnmapper.org/alpha-shapes/?gateway[]='+he.encode(gateway)+
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

    gatewayMarkers.addLayer(marker);
    loadedGateways[gateway] = marker;
  }
}


function formatTime(timestamp)
{
  var date = new Date(timestamp*1000);
  return date.toISOString();
}