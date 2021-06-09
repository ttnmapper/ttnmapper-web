var getParameters = getJsonFromUrl();

var loadedGateways = {};
var gatewaysInView = [];

var default_zoom = true;
var zoom_override = false;

var gatewayMarkersNoCluster = L.featureGroup();

var map;

function initMap() {

  map = L.map('map');

  if(findGetParameter("lat")!=null && findGetParameter("lon")!=null && findGetParameter("zoom")!=null) {
    map.setView([ findGetParameter("lat"), findGetParameter("lon") ], findGetParameter("zoom"));
    default_zoom = false;
    zoom_override = true;
  }
  else {
    b = JSON.parse(localStorage.getItem('bounds'));
    if (b == null)
    {
      map.setView(initialCoords, initialZoom);
    }
    else {
      try {
        map.fitBounds([[b._southWest.lat%90,b._southWest.lng%180],[b._northEast.lat%90,b._northEast.lng%180]]);
        default_zoom = false;
      } catch (err) {
        map.setView(initialCoords, initialZoom);
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
}

function addBackgroundLayers() {
  // https: also suppported.
  var Esri_WorldImagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
    fadeAnimation: false
  });

  // https: also suppported.
  var Stamen_TonerLite = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}', {
    attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> | Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    subdomains: 'abcd',
    minZoom: 0,
    maxZoom: 20,
    ext: 'png',
    fadeAnimation: false
  });

  if(isTtnMapperOrg) {
    var OpenStreetMap_Mapnik_Grayscale = L.tileLayer('https://maps.iotdash.nl/osm/{z}/{x}/{y}.png', {
      maxZoom: 20,
      attribution: '<a href="https://www.skylab.nl" title="Powered by SkyLab B.V.">Powered by SkyLab B.V.</a> | <a href="https://www.openstreetmap.org" title="&copy; OpenStreetMap">&copy; OpenStreetMap</a>',
      fadeAnimation: false,
      className: 'toGrayscale'
    });
  } else {
    var OpenStreetMap_Mapnik_Grayscale = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 20,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false,
      className: 'toGrayscale'
    });
  }

  // https: also suppported.
  var Esri_WorldShadedRelief = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri',
    maxZoom: 13,
    fadeAnimation: false
  });

  // https: also suppported.
  // var OpenStreetMap_Mapnik = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  var OpenStreetMap_Mapnik = L.tileLayer('https://maps.iotdash.nl/osm/{z}/{x}/{y}.png', {
    maxZoom: 20,
    // attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    attribution: '<a href="https://www.skylab.nl" title="Powered by SkyLab B.V.">Powered by SkyLab B.V.</a> | <a href="https://www.openstreetmap.org" title="&copy; OpenStreetMap">&copy; OpenStreetMap</a>',
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

//Create a map that remembers where it was zoomed to
function boundsChanged () {
  boundsChangedCallback();
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
  if("gateway" in getParameters) {
    
    if(Array.isArray(getParameters['gateway'])) {
      gatewaysInView = getParameters['gateway'];
    } else {
      gatewaysInView = [getParameters['gateway']];
    }

    console.log("Is array of gateways");
    addGateways(gatewaysInView);

  } else {
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
        }
      });

    if(showTtnV3Gateways === "1") {
      loadTtnV3Gateways();
    }

    if (showChirpV3Gateways === "1") {
      loadChirpV3Gateways();
    }
  }
}

function addGateways(gateways)
{
  // First add the gateway markers, then do the layers. 
  // Because we only download layers for gateways in our bounding box = gatewaysInView

  var gatewaysToAdd = [];
  for (i in gateways) {
    if(!(gateways[i] in loadedGateways)) {
      // console.log(gateways[i]+" not loaded yet");
      gatewaysToAdd.push(gateways[i]);
    }
  }

  if(gatewaysToAdd.length > 0) {
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
        showOrHideLayers();
      },
      failure: function(errMsg) {
        console.log(errMsg);
        showOrHideLayers();
      }
    });
  } else {
    showOrHideLayers();
  }

}

function addGatewayMarker(gateway, data)
{
  if(gateway in loadedGateways) {
    console.log(gateway + " already added");
    return;
  } else {
    console.log("Adding "+gateway);

    if(data['lat'] === null || data['lon'] === null) {
      console.log("Gateway "+gateway+" location is null");
      return;
    }
    if(data['lat'] === undefined || data['lon'] === undefined) {
      console.log("Gateway "+gateway+" location is undefined");
      return;
    }
    if(Math.abs(data['lat']) < 1 && Math.abs(data['lon']) < 1) {
      console.log("Gateway "+gateway+" location is on null island");
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
        '<li><a href=\"/colour-radar/?gateway[]='+he.encode(gateway)+
            '\">radar</a><br>'+
        '<li><a href=\"/alpha-shapes/?gateway[]='+he.encode(gateway)+
            '\">alpha shape</a><br>'+
      '</ul>';

    if(data['last_heard'] < (Date.now()/1000)-(60*60*24*5)) //5 days
    {
      console.log(gateway + " offline for >5 days");
      if(typeof showOfflineGateways != "undefined") {
        marker = L.marker([data['lat'], data['lon']], {icon: gatewayMarkerOffline});
        marker.bindPopup(gwdescriptionHead+'<br /><br /><font color="red">Offline.</font> Will be removed from the map in 5 days.<br />'+gwdescription);        
      } else {
        // do not show on map
        index = gatewaysInView.indexOf(gateway);
        if(index != -1) {
          gatewaysInView.splice(index, 1);
        }
        return;
      }
    }
    else if(data['last_heard'] < (Date.now()/1000)-(60*60*1)) //1 hour
    {
      marker = L.marker([data['lat'], data['lon']], {icon: gatewayMarkerOffline});
      marker.bindPopup(gwdescriptionHead+'<br /><br /><font color="red">Offline.</font> Will be removed from the map in 5 days.<br />'+gwdescription);
    }
    else if(data['channels'] == 0)
    {
      // Online but not mapped
      if(showUnmappedGateways === "1") {
        marker = L.marker([data['lat'], data['lon']], {icon: gatewayMarkerOnlineNotMapped});
        marker.bindPopup(gwdescriptionHead+'<br /><br /><font color="green">Online but no coverage mapped yet.</font><br />'+gwdescription);
      }
      else {
        return;
      }
    }
    else if(data['channels']<3)
    {
      //Single channel gateway
      marker = L.marker([data['lat'], data['lon']], {icon: gatewayMarkerSingleChannel});
      marker.bindPopup(gwdescriptionHead+'<br /><br />Likely a <font color="orange">Single Channel Gateway.</font><br />'+gwdescription);
    }
    else
    {
      //LoRaWAN gateway
      marker = L.marker([data['lat'], data['lon']], {icon: gatewayMarkerOnline});
      marker.bindPopup(gwdescriptionHead+'<br />'+gwdescription);
    }

    if(clusterGateways === "1") {
      gatewayMarkers.addLayer(marker);
    }
    else{
      gatewayMarkersNoCluster.addLayer(marker);
      // marker.addTo(map);
    }

    loadedGateways[gateway] = marker;
  }
}

function formatTime(timestamp)
{
  if(timestamp === undefined) {
  	return "unknown";
  }
  var date = new Date(timestamp*1000);
  return date.toISOString();
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






// TTN V3 temporary added
function loadTtnV3Gateways() {
  $.ajax
  ({
    type: "GET",
    url: '/webapi/gwall_ttnv3.php',
    dataType: 'json',
    success: function (data) {
      v3gateways = data["gateways"];
      for(i in v3gateways) {
        gateway = v3gateways[i];
        console.log(gateway);

        var gwdescription = "<b>TTN V3 Gateway</b><br />";
        gwdescription += "TTN Mapper ID: "+gateway.id+"<br />";
        gwdescription += "TTN V3 ID: "+gateway.gateway_id+"<br />";
        gwdescription += "Gateway EUI: "+gateway.gateway_eui+"<br />";
        gwdescription += "Description: "+gateway.description+"<br />";
        gwdescription += "Last heard: "+gateway.last_heard+"<br />";
        gwdescription += "TTN Mapper ID: "+gateway.id+"<br />";
        gwdescription += "Coordinates: "+gateway.latitude+", "+gateway.longitude+"<br />";
        gwdescription += "Altitude: "+gateway.altitude+"<br />";
        gwdescription += "Location source: "+gateway.location_source+"<br />";
        gwdescription += "Location accuracy: "+gateway.location_accuracy+"<br />";
        gwdescription += "Network ID: "+gateway.network_id+"<br />";

        marker = L.marker([gateway['latitude'], gateway['longitude']], 
          {icon: gatewayMarkerV3});
        marker.bindPopup(gwdescription);
        marker.addTo(map);
      }
    }
  });
}


// Chirpstack V3 temporary added
function loadChirpV3Gateways() {
  $.ajax
  ({
    type: "GET",
    url: '/webapi/gwall_chirpv3.php',
    dataType: 'json',
    success: function (data) {
      v3gateways = data["gateways"];
      for(i in v3gateways) {
        gateway = v3gateways[i];
        console.log(gateway);

        var gwdescription = "<b>Chirpstack V3 Gateway</b><br />";
        gwdescription += "TTN Mapper ID: "+gateway.id+"<br />";
        gwdescription += "Gateway EUI: "+gateway.gateway_eui+"<br />";
        gwdescription += "Description: "+gateway.description+"<br />";
        gwdescription += "Last heard: "+gateway.last_heard+"<br />";
        gwdescription += "Coordinates: "+gateway.latitude+", "+gateway.longitude+"<br />";
        gwdescription += "Altitude: "+gateway.altitude+"<br />";
        gwdescription += "Location source: "+gateway.location_source+"<br />";
        gwdescription += "Location accuracy: "+gateway.location_accuracy+"<br />";
        gwdescription += "Network ID: "+gateway.network_id+"<br />";

        marker = L.marker([gateway['latitude'], gateway['longitude']], 
          {icon: gatewayMarkerChirpV3});
        marker.bindPopup(gwdescription);
        marker.addTo(map);
      }
    }
  });
}
