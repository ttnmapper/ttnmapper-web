var gatewayMarkers = L.featureGroup();
var pointMarkers = L.featureGroup();
var lineMarkers = L.featureGroup();

var reloadPoints = false; // force relaod historic points after gateway fetch

var allPoints = {}; // indexed by lat, then lon, point under that, marker under that
var allGateways = {};

var maxDistanceLine; // single line connecting point with gateway
var maxDistanceGateway;

var lastLocationTime;
var lastLocationFcount = 0;
var lastLocationMarker;

var locationsCount = 0;


// stats
var maximumDistance = 0;
var maximumDistanceGwId = "";
var maximumDistanceTime;
var maximumAltitude = 0;
var lastAltitude = 0;
var lastHeardTime = 0;

var gatewaysFirstFetch = true;


setUp();

function setUp() {
  $("#legend").load("/legend.html");
  $("#legend").css({ visibility: "visible"});
  $("#stats").css({ visibility: "visible"});

  pageWidth = $('#container').width();
  if(pageWidth < 800) {
    console.log("Too narrow");
  }

  initMap();

  addBackgroundLayers();
  if(findGetParameter("gateways")=="on") {
    gatewayMarkers.addTo(map);
    gatewayMarkersNoCluster.addTo(map);
  }
  if(findGetParameter("points")!="off") {
    pointMarkers.addTo(map);
  }
  if(findGetParameter("lines")=="on") {
    lineMarkers.addTo(map);
  }
  getData();
}

// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  // Callback not used
}

function showOrHideLayers() {
  // Callback when gateways have been downloaded and added
  if(reloadPoints == true) {
    reloadPoints = false;
    processNewData();
  }
}

function getData()
{
    subscribeToLiveData();

    var history = findGetParameter("history");

    if(findGetParameter("startdate") != null && history!="off") {
      // $("div.spanner").addClass("show");
      // $("div.overlay").addClass("show");

      getHistory(0);
    }
}

function getHistory(offset) {
      $.getJSON('//private.ttnmapper.org/track-history/json.php', 
      {
        application: findGetParameter("application"),
        device: findGetParameter("device"),
        experiment: findGetParameter("experiment"),
        user: findGetParameter("user"),
        startdate: findGetParameter("startdate"),
        page: offset
      }, 
      function(data) {
        console.log("Got historic data "+data.points.length);

        data.points.forEach(function(obj) { 
          // {"id":"13551510","time":"2020-04-08 15:43:14","nodeaddr":"cricket_001","appeui":"jpm_crickets",
          // "gwaddr":"60C5A8FFFE71A964","modulation":"LORA","datarate":"SF7BW125","snr":"10.00","rssi":"-43.00",
          // "freq":"868.300","lat":"-33.936600","lon":"18.870800","alt":"134.4","accuracy":"0.00","hdop":"0.0",
          // "sats":"0","provider":"Cayenne LPP","name":"testjpm","mqtt_topic":null,"user_agent":
          // "ttn-v2-integration","user_id":"ttn@jpmeijers.com", "fcount": 20}
          if(obj.lat === undefined || obj.lon === undefined) {
            return;
          }

          var signal = Number(obj.rssi);
          if(Number(obj.snr)<0) {
            signal = signal + Number(obj.snr);
          }

          if(allPoints[obj.lat] === undefined) {
            allPoints[obj.lat] = {};
          }
          if(allPoints[obj.lat][obj.lon] === undefined) {
            point = {};
            point.time = new Date(Date.parse(obj.time));
            point.dev_id = obj.nodeaddr;
            point.app_id = obj.appeui;
            point.latitude = obj.lat;
            point.longitude = obj.lon;
            point.altitude = obj.alt;
            point.rssi = obj.rssi;
            point.snr = obj.snr;
            point.signal = signal;
            console.log(obj.fcount);
            point.fcount = obj.fcount;
            point.gatewayId = obj.gwaddr;
            point.gateways = [];
            point.gateways.push(obj.gwaddr);
            point.datarate = obj.datarate;

            allPoints[obj.lat][obj.lon] = point;
          } else {
            point = allPoints[obj.lat][obj.lon];
            point.gateways.push(obj.gwaddr);
            if(signal > point.signal) {
              point.gatewayId = obj.gwaddr;
              point.rssi = obj.rssi;
              point.snr = obj.snr;
              point.signal = signal;
            }
            allPoints[obj.lat][obj.lon] = point;
          }

        }); // end foreach gateway
        processData();
        if(data.points.length > 0) {
          getHistory(offset+1);
        }
      }); // end ajax call

}

function getGatewayData(gateways)
{
  // console.log("Fetching gateways");
  // console.log(gateways);

  if(gateways.length > 0) {
    $.ajax({
      type: "POST",
      url: "//private.ttnmapper.org/webapi/gwdetailslist.php",
      // The key needs to match your method's input parameter (case-sensitive).
      data: JSON.stringify({ "gateways": gateways }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      success: function(data){
        for(gateway in data) {
          allGateways[gateway] = data[gateway];
        }
      }
    });
  }

  if(gatewaysFirstFetch) {
    gatewaysFirstFetch = false;
    processData();
  }

}

function subscribeToLiveData() {
  console.log("Subscribe to data stream");

  url = "wss://private.ttnmapper.org/ws?";

  application = findGetParameter("application");
  device = findGetParameter("device");
  user = findGetParameter("user");
  experiment = findGetParameter("experiment");

  if(application != null) {
    url += "application="+application+"&";
  }
  if(device != null) {
    url += "device="+device+"&";
  }
  if(user != null) {
    url += "user="+user+"&";
  }
  if(experiment != null) {
    url += "experiment="+experiment+"&";
  }

  url = url.substring(0, url.length - 1);

  console.log(url);

  if ("WebSocket" in window) {
    console.log("[WS] WebSocket is supported by your Browser!");
     
    // Let us open a web socket
    var ws = new ReconnectingWebSocket(url);


    ws.onopen = function() {
      // Web Socket is connected, send data using send()
      // ws.send("Message to send");
      console.log("[WS] Open");
    };

    ws.onmessage = function (evt) {
      var msg = evt.data;
      console.log("[WS] New data: ");
      console.log(msg);
      obj = JSON.parse(msg);


/*
{"app_id":"jpm_crickets","dev_id":"cricket_001","dev_eui":"00E150E95369A0B0","time":1586366252696336433,
"port":1,"counter":40364,"frequency":868099976,"modulation":"LORA","bandwidth":125000,"spreading_factor":7,
"coding_rate":"4/5","gateways":[
  {"gtw_id":"eui-b827ebfffed88375","gtw_eui":"B827EBFFFED88375","antenna_index":0, "time":1586366252000000000,"timestamp":2975870163,"rssi":-52,"snr":9.5,"latitude":-33.935970306396484,"longitude":18.870805740356445},
  {"gtw_id":"eui-7276ff00080e0176","gtw_eui":"7276FF00080E0176","antenna_index":0,"time":1586366252000000000,"timestamp":258504083,"rssi":-53,"snr":9.2},
  {"gtw_id":"eui-60c5a8fffe71a964","gtw_eui":"60C5A8FFFE71A964","antenna_index":0,"timestamp":2062606875,"rssi":-43,"snr":10.3}
  ],
  "latitude":-33.9367,"longitude":18.8708,"altitude":133.61,"accuracy_source":"Cayenne LPP","experiment":"testjpm","userid":"ttn@jpmeijers.com","useragent":"ttn-v2-integration"}
*/
      obj.gateways.forEach(function(gateway) {
        if(obj.latitude === undefined || obj.longitude === undefined) {
          return;
        }

        var signal = Number(gateway.rssi);
        if(Number(gateway.snr)<0) {
          signal = signal + Number(gateway.snr);
        }


        gatewayId = gateway.gtw_id;
        if(gateway.gtw_eui !== undefined) {
          gatewayId = gateway.gtw_eui;
        }

        if(allPoints[obj.latitude] === undefined) {
          allPoints[obj.latitude] = {};
        }
        if(allPoints[obj.latitude][obj.longitude] === undefined) {

          point = {};
          point.time = new Date(obj.time/1000000); //ms
          point.dev_id = obj.dev_id;
          point.app_id = obj.app_id;
          point.latitude = obj.latitude;
          point.longitude = obj.longitude;
          point.altitude = obj.altitude;
          point.rssi = gateway.rssi;
          point.snr = gateway.snr;
          point.signal = signal;
          point.fcount = obj.counter;
          point.gatewayId = gatewayId;
          point.gateways = [];
          point.gateways.push(gatewayId);
          point.datarate = "SF"+obj.spreading_factor+"BW"+(obj.bandwidth/1000);
        
          allPoints[obj.latitude][obj.longitude] = point;
        } else {
          point = allPoints[obj.latitude][obj.longitude];
          point.gateways.push(gatewayId);
          if(signal > point.signal) {
            point.gatewayId = gatewayId;
            point.rssi = gateway.rssi;
            point.snr = gateway.snr;
            point.signal = signal;
          }
          allPoints[obj.latitude][obj.longitude] = point;
        }
      }); // end gateway loop

      processData();
    }; // end on message

    ws.onclose = function() { 
      // websocket is closed.
      console.log("[WS] Connection is closed..."); 
};
  } else {
     // The browser doesn't support WebSocket
     console.log("[WS] WebSocket NOT supported by your Browser!");
  }
}


function processData() {

  gatewaysToFetch = [];

  locationsCount = 0;
  Object.keys(allPoints).forEach(function(lat) {
    Object.keys(allPoints[lat]).forEach(function(lon) {
      //console.log(lat+", "+lon);

      point = allPoints[lat][lon];

      // Number of packets
      locationsCount++;
      
      // Iterate gateways
      point.gateways.forEach(function(gwid) {
        // Make a list of gateways we've seen and need data for
        if(!gatewaysToFetch.includes(gwid)) {
          gatewaysToFetch.push(gwid);
        }

        if(allGateways[gwid] === undefined) {
          // Also add to global list of seen gateways - which will be updated with data later
          allGateways[gwid] = {};
        } else if(allGateways[gwid].lat !== undefined && allGateways[gwid].lon !== undefined) {
          
          // Otherwise we have the data to calculate a distance
          // Only once per point per gateway
          if(point.gwsProcessed === undefined) {
            point.gwsProcessed = [];
          } 

          if(!point.gwsProcessed.includes(gwid)) {
            point.gwsProcessed.push(gwid);

            var distance = 0;
            var gwLat = Number(allGateways[gwid].lat);
            var gwLon = Number(allGateways[gwid].lon);
            var pointLat = Number(point.latitude);
            var pointLon = Number(point.longitude);

            if(gwLat == 0 && gwLon == 0) {
              console.log("Gateway "+point.gatewayId+" on NULL island");
            } else {
              distance = Math.round(getDistance(pointLat, pointLon, gwLat, gwLon));
              if(point.distance === undefined) {
                point.distance = distance;
                point.maxDistGw = gwid;
              } else if (distance > point.distance) {
                point.distance = distance;
                point.maxDistGw = gwid;
              }
            }
          }
        }
      });

      // Add a circle marker
      point.marker = addUpdatePoint(point, point.marker);

      // Update the last location marker, depending on fcount if we can, otherwise time
      if(point.fcount != 0 && point.fcount>=lastLocationFcount) {
        lastLocationFcount = point.fcount;
        addUpdateLastMarker(point);
      }

      // no framecoutn yet, so use time
      if(lastLocationFcount === 0) {
      
        // Store the last time we heard anything
        if(lastLocationTime === undefined) {
          lastLocationTime = point.time;
          // Store the last altitude
          lastAltitude = point.altitude;
        } else if(point.time > lastLocationTime) {
          lastLocationTime = point.time;
          // Store the last altitude
          lastAltitude = point.altitude;
        }


      }

      // Update statistics
      updateStatistics(point);

    });
  });

  getGatewayData(gatewaysToFetch);

}

function addUpdatePoint(point, marker) {
  var lat = Number(point.latitude);
  var lon = Number(point.longitude);

  var colour = getColour(point);


  if(marker === undefined) {
    // console.log("Adding new marker");
    // Point
    markerOptions = {
        stroke: false,
        radius: 5,
        color: colour,
        fillColor: colour,
        fillOpacity: 0.8
    };
    marker = L.circleMarker([point.latitude, point.longitude], markerOptions);
    pointMarkers.addLayer(marker);
  } else {
    // console.log("Updating marker")
    marker.options.color = colour;
    marker.options.fillColor = colour;
  }
  marker.bindPopup(
      timeConverter(point['time'])+
      '<br /><b>AppID:</b> '+point.app_id+
      '<br /><b>DevID:</b> '+point.dev_id+
      '<br /><b>Gateway count:</b> <br />'+point.gateways.length+
      '<br /><b>Strongest gateway:</b> <br />'+point.gatewayId+
      '<br /><b>Furthest gateway:</b> <br />'+point.maxDistGw+
      '<br /><b>Max distance:</b> <br />'+(point.distance/1000).toFixed(3)+"km"+
      // '<br /><b>Location accuracy:</b> '+point['accuracy']+
      // '<br /><b>Packet id:</b> '+point['id']+
      '<br /><b>RSSI:</b> '+point.rssi+'dBm'+
      '<br /><b>SNR:</b> '+point.snr+'dB'+
      '<br /><b>Signal:</b> '+point.signal+'dBm'+
      '<br /><b>DR:</b> '+point.datarate+
      '<br /><b>Altitude: </b>'+point.altitude+'m');

  return marker;
}


// function autoZoom() {

//   var autozoom = findGetParameter("autozoom");
//   if(autozoom!="off") {
//     // Zoom map to fit points and gateways
//     if(pointMarkers.getBounds().isValid()) {
//       var bounds = pointMarkers.getBounds();

//       if(lineMarkers.getBounds().isValid()) {
//         bounds.extend(lineMarkers.getBounds());
//       }
//       if(gatewayMarkers.getBounds().isValid()) {
//         bounds.extend(gatewayMarkers.getBounds());
//       }
//       if(gatewayMarkersNoCluster.getBounds().isValid()) {
//         bounds.extend(gatewayMarkersNoCluster.getBounds());
//       }

//       map.fitBounds(bounds, {padding: [50, 50]});
//     }
//   }
// }


function addUpdateLastMarker(point) {

  if( lastLocationMarker === undefined ) {
    lastLocationMarker = L.marker([point.latitude, point.longitude]);
    lastLocationMarker.addTo(map);
  } else {
    var newLatLng = new L.LatLng(point.latitude, point.longitude);
    lastLocationMarker.setLatLng(newLatLng);
  }

  lastLocationMarker.bindPopup(
    timeConverter(point['time'])+
    '<br /><b>AppID:</b> '+point.app_id+
    '<br /><b>DevID:</b> '+point.dev_id+
    '<br /><b>Received by gateway:</b> <br />'+point.gatewayId+
    // '<br /><b>Location accuracy:</b> '+point['accuracy']+
    // '<br /><b>Packet id:</b> '+point['id']+
    '<br /><b>RSSI:</b> '+point.rssi+'dBm'+
    '<br /><b>SNR:</b> '+point.snr+'dB'+
    '<br /><b>Signal:</b> '+point.signal+'dBm'+
    '<br /><b>DR:</b> '+point.datarate+
    '<br /><b>Altitude: </b>'+point.altitude+'m');

  lastAltitude = point.altitude;
  lastLocationTime = point.time;
}


function updateMaxDistanceLine(point) {
  if(maxDistanceLine !== undefined) {
    map.removeLayer(maxDistanceLine);
  }

  recordLineOptions = {
    radius: 10,
    color: "#d755b1",
    opacity: 1,
    stroke: true,
    weight: 10
  };

  gwid = point.maxDistGw;
  gwLat = Number(allGateways[gwid].lat);
  gwLon = Number(allGateways[gwid].lon);

  maxDistanceLine = L.polyline([ [point.latitude, point.longitude], [gwLat, gwLon] ], recordLineOptions);

  maxDistanceLine.bindPopup("<b>Maximum distance</b><br />"+
    timeConverter(point['time'])+
    '<br /><b>Furthest gateway:</b> <br />'+point.maxDistGw+
    '<br /><b>Distance:</b> <br />'+(point.distance/1000).toFixed(3)+"km"+
    '<br /><b>RSSI:</b> '+point.rssi+'dBm'+
    '<br /><b>SNR:</b> '+point.snr+'dB'+
    '<br /><b>Signal:</b> '+point.signal+'dBm'+
    '<br /><b>DR:</b> '+point.datarate+
    '<br /><b>Altitude: </b>'+point.altitude+'m');

  maxDistanceLine.addTo(map);


  var gwdescriptionHead = "";
  if (allGateways[gwid].description != null) {
    gwdescriptionHead = "<b>"+he.encode(allGateways[gwid].description)+"</b><br />"+he.encode(gwid);
  } else {
    gwdescriptionHead = "<b>"+he.encode(gwid)+"</b>";
  }

  if(maxDistanceGateway === undefined) {
    //LoRaWAN gateway
    maxDistanceGateway = L.marker([gwLat, gwLon], {icon: gatewayMarkerOnline});
    maxDistanceGateway.addTo(map);
  } else {
    var newLatLng = new L.LatLng(gwLat, gwLon);
    maxDistanceGateway.setLatLng(newLatLng);
  }

  maxDistanceGateway.bindPopup(gwdescriptionHead);

}


function updateStatistics(point) {

  if(point.distance !== undefined) {
    if(point.distance > maximumDistance) {
      maximumDistance = point.distance;
      maximumDistanceGwId = point.maxDistGw;
      maximumDistanceTime = point.time;

      updateMaxDistanceLine(point);
    }
  }

  if(Number(point.altitude) > maximumAltitude) {
    maximumAltitude = Number(point.altitude);
    console.log("Max alt: "+maximumAltitude);
  }


  document.getElementById("stats").innerHTML = 
  "Last packet received:<br />"+timeConverter(lastLocationTime)+"<br />"+
  "Last altitude: "+lastAltitude+"m<br />"+
  "<br />"+
  "Number of packets: "+locationsCount+"<br />"+
  // "Number of devices: "+Object.keys(countPointsPerDevice).length+"<br />"+
  "Number of gateways: "+Object.keys(allGateways).length+"<br />" +
  "<br />"+
  "Maximum altitude: "+maximumAltitude+"m<br />" +
  "Maximum distance: "+(maximumDistance/1000).toFixed(3)+"km <br />" +
  "<small>GW: "+maximumDistanceGwId+"<br />@ "+timeConverter(maximumDistanceTime)+"</small><br />";
}

function timeConverter(a){
  try {
    a.getFullYear();
  } catch {
    return "";
  }
  var year = a.getFullYear();
  var month = a.getMonth();
  var date = a.getDate();
  var hour = a.getHours();
  var min = a.getMinutes();
  var sec = a.getSeconds();
  var time = year + '-';
  if(month<10) time += "0";
  time += month + '-';
  if(date<10) time+="0";
  time += date + ' ';
  if(hour<10) time+= "0";
  time += hour + ':';
  if(min<10) time += "0";
  time+= min + ':';
  if(sec<10) time+="0";
  time += sec + "UTC";
  return time;
}