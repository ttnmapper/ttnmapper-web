var loadedRadarLayers = {};
var visibleRadarLayers = {};

var loadedCircleLayers = {};
var visibleCircleLayers = {};

var loadedGateways = {};
var gatewayMarkers = L.markerClusterGroup({
  maxClusterRadius: 40
});
var pointMarkers = L.featureGroup();
var lineMarkers = L.featureGroup();

var gatewayData;
var pointData;

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
var loadingControl;

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

  loadingControl = L.Control.loading({
      separate: true
  });
  map.addControl(loadingControl);

  // Listen for orientation changes
  //window.addEventListener("orientationchange", showHideMenu(), false);
  //window.onresize = showHideMenu;

  addBackgroundLayers();
  if(findGetParameter("gateways")!="off") {
    gatewayMarkers.addTo(map);
  }
  if(findGetParameter("points")!="off") {
    pointMarkers.addTo(map);
  }
  if(findGetParameter("lines")!="off") {
    lineMarkers.addTo(map);
  }
  getData();
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
  }).addTo(map);


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


  L.control.layers({
    "Stamen TonerLite": Stamen_TonerLite,
    "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
    "Terrain": Esri_WorldShadedRelief, 
    "OSM Mapnik": OpenStreetMap_Mapnik,
    "Satellite": Esri_WorldImagery
  })
  .addTo(map);
}

function getData()
{
    loadingControl._showIndicator();
    $("div.spanner").addClass("show");
    $("div.overlay").addClass("show");

    $.getJSON('json.php', 
    {
      gateway: findGetParameter("gateway"),
      startdate: findGetParameter("startdate"),
      enddate: findGetParameter("enddate")
    }, 
    function(data) {

      pointData = data;

      var gateways = [];
      for(point in data['points']) {
        if( !(data['points'][point]['gwaddr'] in gateways) ) {
          gateways.push(data['points'][point]['gwaddr']);
        }
      }
      
      // loadingControl._hideIndicator();
      // $("div.spanner").addClass("hide");
      // $("div.overlay").addClass("hide");

      addGateways(gateways);
    });
}

function addPointsAndLines()
{
    for(point in pointData['points']) {
      var data = pointData['points'][point];
      var lat = Number(data['lat']);
      var lon = Number(data['lon']);

      var colour = getColour(data);
      
      var signal = Number(data['rssi']);
      if(Number(data['snr'])<0) {
        signal = signal + Number(data['snr']);
      }

      var distance = 0;
      if( data['gwaddr'] in gatewayData ) {
        var gwLat = Number(gatewayData[data['gwaddr']]['lat']);
        var gwLon = Number(gatewayData[data['gwaddr']]['lon']);

        if(gwLat == 0 && gwLon == 0) {
          break;
        }

        distance = Math.round(getDistance(lat, lon, gwLat, gwLon));

        // Line
        lineOptions = {
            radius: 10,
            color: colour,
            fillColor: colour,
            opacity: 0.3,
            weight: 2
        };
        marker = L.polyline([ [data['lat'], data['lon']], [gwLat, gwLon] ], lineOptions);
        marker.bindPopup(
          data['time']+
          '<br /><b>Node:</b> '+data['nodeaddr']+
          '<br /><b>Received by gateway:</b> <br />'+data['gwaddr']+
          '<br /><b>Location accuracy:</b> '+data['accuracy']+
          '<br /><b>Packet id:</b> '+data['id']+
          '<br /><b>RSSI:</b> '+data['rssi']+'dBm'+
          '<br /><b>SNR:</b> '+data['snr']+'dB'+
          '<br /><b>Signal:</b> '+signal+'dBm'+
          '<br /><b>DR:</b> '+data['datarate']+
          '<br /><b>Distance:</b> '+distance+'m'+
          '<br /><b>Altitude: </b>'+data['alt']+'m');
        lineMarkers.addLayer(marker);
      }

      // Point
      markerOptions = {
          stroke: false,
          radius: 5,
          color: colour,
          fillColor: colour,
          fillOpacity: 0.8
      };
      marker = L.circleMarker([data['lat'], data['lon']], markerOptions);
      marker.bindPopup(
        data['time']+
        '<br /><b>Node:</b> '+data['nodeaddr']+
        '<br /><b>Received by gateway:</b> <br />'+data['gwaddr']+
        '<br /><b>Location accuracy:</b> '+data['accuracy']+
        '<br /><b>Packet id:</b> '+data['id']+
        '<br /><b>RSSI:</b> '+data['rssi']+'dBm'+
        '<br /><b>SNR:</b> '+data['snr']+'dB'+
        '<br /><b>Signal:</b> '+signal+'dBm'+
        '<br /><b>DR:</b> '+data['datarate']+
        '<br /><b>Distance:</b> '+distance+'m'+
        '<br /><b>Altitude: </b>'+data['alt']+'m');
      pointMarkers.addLayer(marker);
    }

    // Zoom map to fit points and gateways
    map.fitBounds(lineMarkers.getBounds());
}

function getDistance(lat1, lon1, lat2, lon2) {
  Number.prototype.toRad = function() {
    return this * Math.PI / 180;
  }

  var R = 6371000; // metre
  //has a problem with the .toRad() method below.
  var x1 = lat2-lat1;
  var dLat = x1.toRad();  
  var x2 = lon2-lon1;
  var dLon = x2.toRad();  
  var a = Math.sin(dLat/2) * Math.sin(dLat/2) + 
                  Math.cos(lat1.toRad()) * Math.cos(lat2.toRad()) * 
                  Math.sin(dLon/2) * Math.sin(dLon/2);  
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
  var d = R * c;

  return d;
}

function getColour(data) {

  // TODO: if we use signal to determine colour

  var rssi = Number(data["rssi"]);
  var snr = Number(data["snr"]);

  if(snr<0) {
    rssi = rssi + snr;
  }

  colour = "#0000ff";
  if (rssi==0)
  {
    colour = "black";
  }
  else if (rssi<-120)
  {
    colour = "blue";
  }
  else if (rssi<-115)
  {
    colour = "cyan";
  }
  else if (rssi<-110)
  {
    colour = "green";
  }
  else if (rssi<-105)
  {
    colour = "yellow";
  }
  else if (rssi<-100)
  {
    colour = "orange";
  }
  else
  {
    colour = "red";
  }

  return colour;
}

//Create a map that remembers where it was zoomed to
function boundsChanged () {
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
  url = url.replace("%5B", "[");
  url = url.replace("%5D", "]");
  console.log(url);
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

  // loadingControl._showIndicator();
  // $("div.spanner").addClass("show");
  // $("div.overlay").addClass("show");

  $.ajax({
    type: "POST",
    url: "/webapi/gwdetailslist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": gatewaysToAdd }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){

      gatewayData = data;
      for(gateway in data) {
        addGatewayMarker(gateway, data[gateway]);
      }
      addPointsAndLines();

      loadingControl._hideIndicator();
      $("div.spanner").addClass("hide");
      $("div.overlay").addClass("hide");
    },
    failure: function(errMsg) {
      loadingControl._hideIndicator();
      $("div.spanner").addClass("hide");
      $("div.overlay").addClass("hide");

      console.log(errMsg);
    }
  });
}

function addGatewayMarker(gateway, data)
{
  if(gateway in loadedGateways) {
    return;
  } else {
    console.log("Adding gateway "+gateway);

    if (data['lat'] == null || data['lon'] == null) {
      return;
    }

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