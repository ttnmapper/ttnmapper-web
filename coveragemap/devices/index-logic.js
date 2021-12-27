var dataAlreadyAdded = false;

var pointMarkers = L.featureGroup();
var lineMarkers = L.featureGroup();

var gatewayData;
var pointData = {};

var showOfflineGateways = "1";

setUp();

function setUp() {
  $("#legend").load("/legend.html");
  $("#legend").css({ visibility: "visible"});

  initMap();

  addBackgroundLayers();
  if(findGetParameter("gateways")!="off") {
    gatewayMarkers.addTo(map);
    gatewayMarkersNoCluster.addTo(map);
  }
  if(findGetParameter("points")!="off") {
    pointMarkers.addTo(map);
  }
  if(findGetParameter("lines")!="off") {
    lineMarkers.addTo(map);
  }
  getData();
}

// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  //nothing to do
  //getGatewaysInView();
}

function showOrHideLayers() {
  if(!dataAlreadyAdded) {
    addPointsAndLines();
  }

  $("div.spanner").addClass("hide");
  $("div.overlay").addClass("hide");
}

function getData()
{
    $("div.spanner").addClass("show");
    $("div.overlay").addClass("show");


    $.getJSON('json-pg.php', 
    {
      application: findGetParameter("application"),
      device: findGetParameter("device"),
      startdate: findGetParameter("startdate"),
      enddate: findGetParameter("enddate")
    }, 
    function(data) {

      pointData = data;
      console.log(pointData);

      var gateways = [];
      for(point in data['points']) {
        if( !gateways.includes(data['points'][point]['gwaddr']) ) {
          var gwaddr = data['points'][point]['gwaddr'];
          // if(gwaddr.startsWith("eui-")) {
          //   gwaddr = gwaddr.substring(4);
          //   gwaddr = gwaddr.toUpperCase();
          // }
          console.log(gwaddr);
          if(gwaddr !== "packetbroker") {
            data['points'][point]['gwaddr'] = gwaddr;
            gateways.push(gwaddr);
          }
        }
      }

      // $.getJSON('json.php', 
      // {
      //   device: findGetParameter("device"),
      //   startdate: findGetParameter("startdate"),
      //   enddate: findGetParameter("enddate")
      // }, 
      // function(data) {

      //   if(pointData.hasOwnProperty('points')) {
      //     console.log("Concatenating points")
      //     pointData.points = pointData.points.concat(data.points);
      //   } else {
      //     console.log("Setting points")
      //     pointData.points = data.points;
      //   }
      //   console.log(pointData);

      //   var gateways = [];
      //   for(point in data['points']) {
      //     if( !gateways.includes(data['points'][point]['gwaddr']) ) {
      //       gateways.push(data['points'][point]['gwaddr']);
      //     }
      //   }

        addGateways(gateways);
      // });
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
      if( data['gwaddr'] in loadedGateways ) {
        var gwLat = Number(loadedGateways[data['gwaddr']].getLatLng().lat);
        var gwLon = Number(loadedGateways[data['gwaddr']].getLatLng().lng);

        if(gwLat == 0 && gwLon == 0) {
          console.log("Gateway "+data['gwaddr']+" on NULL island");
        } else {

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
            '<br /><b>AppID:</b> '+data['app_id']+
            '<br /><b>DevID:</b> '+data['dev_id']+
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
        '<br /><b>AppID:</b> '+data['app_id']+
        '<br /><b>DevID:</b> '+data['dev_id']+
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
    if(pointMarkers.getBounds().isValid()) {
      var bounds = pointMarkers.getBounds();

      if(lineMarkers.getBounds().isValid()) {
        bounds.extend(lineMarkers.getBounds());
      }
      if(gatewayMarkers.getBounds().isValid()) {
        bounds.extend(gatewayMarkers.getBounds());
      }
      if(gatewayMarkersNoCluster.getBounds().isValid()) {
        bounds.extend(gatewayMarkersNoCluster.getBounds());
      }

      map.fitBounds(bounds);
    }
}