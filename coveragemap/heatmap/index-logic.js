setUp();

function setUp() {
  $("#legend").load("/legend.html");
  $("#legend").css({ visibility: "visible"});
  
  initMap();

  addBackgroundLayers();
  addForegroundLayers();
  // getGatewaysInView();

  // gatewayMarkers.addTo(map);
  //gatewayMarkersNoCluster.addTo(map);
}

// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  // Refresh the visible gateways, which will also trigger a layer refresh
  // getGatewaysInView();
  if(map.getZoom() >= 7) {
    // loadGatewaysBetweenLongitudes();
    // loadGatewaysInView();
    loadNewZ5Tiles();
  } else {
    // markers.clearLayers();
  }
}

function addForegroundLayers() {
  var tms_url = 'https://tms.ttnmapper.org/circles/network/{networkid}/{z}/{x}/{y}.png';

  let networks = [
    {
      network_id: 'NS_HELIUM://000024',
      network_name: 'Helium - The People\'s Network',
      default_shown: true
    },
    // {
    //   network_id: 'NS_TTS_V3://ttn@000013',
    //   network_name: 'The Things Stack CE (v3)',
    //   default_shown: true
    // },
  ];
  
  for (const network of networks) {
    var coveragetiles = L.tileLayer(tms_url, {
      networkid: encodeURIComponent(network.network_id),
      maxNativeZoom: 19,
      maxZoom: 20,
      zIndex: 10,
      opacity: 0.5
    });
    layerControl.addOverlay(coveragetiles, "Helium Heatmap");
    if(network.default_shown) {
      coveragetiles.addTo(map);
    }

    setTimeout(AddGateways, 1000, network);
    // AddGateways(network);
  }
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the necessary layers, hide them, or display them.
}

// var longitudeColumnWidth = 1;
// var loadedLongitudes = new Array(360/longitudeColumnWidth).fill(false);
//
// function loadGatewaysBetweenLongitudes() {
//   var west = Math.floor(map.getBounds().getWest() / longitudeColumnWidth) + (180/longitudeColumnWidth);
//   var east = Math.ceil(map.getBounds().getEast() / longitudeColumnWidth) + (180/longitudeColumnWidth);
//   console.log("west, east", map.getBounds().getWest(), map.getBounds().getEast(), west, east);
//
//   for(var i = west; i<east; i++) {
//     if(loadedLongitudes[i] === false) {
//       console.log(i, "not loaded");
//       loadedLongitudes[i] = true;
//       getGatewaysBetweenLongitudes((i*longitudeColumnWidth)-180, (i*longitudeColumnWidth)-180+longitudeColumnWidth);
//     }
//   }
// }
//
// function getGatewaysBetweenLongitudes(west, east) {
//   console.log("Loading hotspots between", west, east)
//   var network_id = 'NS_HELIUM://000024';
//   const res = fetch("http://192.168.86.33:8080/network/"+encodeURIComponent(network_id) + "/gateways/longitudes/" + west + "/" + east)
//       .then(response => response.json())
//       .then(data => {
//         for(gateway of data) {
//           let lastHeardDate = Date.parse(gateway.last_heard);
//
//           // Only add gateways last heard in past 5 days
//           if(lastHeardDate > (Date.now() - (5*24*60*60*1000)) ) {
//             let marker = L.marker(
//                 [ gateway.latitude, gateway.longitude ],
//                 {
//                   icon: iconByNetworkId(network_id, lastHeardDate)
//                 });
//             const gwDescriptionHead = popUpHeader(gateway);
//             const gwDescription = popUpDescription(gateway);
//             marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
//             markers.addLayer(marker);
//           }
//         }
//       });
// }

// function loadGatewaysInView() {
//   var network_id = 'NS_HELIUM://000024';
//
//   var west = map.getBounds().getWest();
//   var east = map.getBounds().getEast();
//   var north = map.getBounds().getNorth();
//   var south = map.getBounds().getSouth();
//
//   const res = fetch("http://192.168.86.33:8080/network/"+encodeURIComponent(network_id) + "/gateways/bbox?west=" + west + "&east=" + east + "&north=" + north + "&south=" + south)
//       .then(response => response.json())
//       .then(data => {
//         markers.clearLayers();
//
//         for(gateway of data) {
//           let lastHeardDate = Date.parse(gateway.last_heard);
//
//           // Only add gateways last heard in past 5 days
//           if(lastHeardDate > (Date.now() - (5*24*60*60*1000)) ) {
//             let marker = L.marker(
//                 [ gateway.latitude, gateway.longitude ],
//                 {
//                   icon: iconByNetworkId(network_id, lastHeardDate)
//                 });
//             const gwDescriptionHead = popUpHeader(gateway);
//             const gwDescription = popUpDescription(gateway);
//             marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
//             markers.addLayer(marker);
//           }
//         }
//       });
//
// }

var z5cache = new Array(32);

for (var i = 0; i < z5cache.length; i++) {
  z5cache[i] = new Array(32);
}

function lon2tile(lon,zoom) { return (Math.floor((lon+180)/360*Math.pow(2,zoom))); } // x
function lat2tile(lat,zoom)  { return (Math.floor((1-Math.log(Math.tan(lat*Math.PI/180) + 1/Math.cos(lat*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom))); } // y
function tile2lon(x,z) {
  return (x/Math.pow(2,z)*360-180);
}
function tile2lat(y,z) {
  var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
  return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
}

function loadNewZ5Tiles() {
  var network_id = 'NS_HELIUM://000024';

  var west = map.getBounds().getWest();
  var east = map.getBounds().getEast();
  var north = map.getBounds().getNorth();
  var south = map.getBounds().getSouth();

  var minX = lon2tile(west,5);
  var maxX = lon2tile(east,5);
  var minY = lat2tile(north,5);
  var maxY = lat2tile(south,5);

  for (var x = minX; x <= maxX; x++) {
    for (var y = minY; y <= maxY; y++) {
      if (z5cache[x][y] === undefined) {
        console.log("Getting gateways in", x, y);
        z5cache[x][y] = true;
        const res = fetch("http://192.168.86.33:8080/network/" + encodeURIComponent(network_id) + "/gateways/z5tile/" + x + "/" + y)
        .then(response => response.json())
        .then(data => {

          for(gateway of data) {
            let lastHeardDate = Date.parse(gateway.last_heard);

            // Only add gateways last heard in past 5 days
            if(lastHeardDate > (Date.now() - (5*24*60*60*1000)) ) {
              let marker = L.marker(
                  [ gateway.latitude, gateway.longitude ],
                  {
                    icon: iconByNetworkId(network_id, lastHeardDate)
                  });
              const gwDescriptionHead = popUpHeader(gateway);
              const gwDescription = popUpDescription(gateway);
              marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
              markers.addLayer(marker);
            }
          }
        });
      }
    }
  }
}


var markers = L.markerClusterGroup({
  spiderfyOnMaxZoom: true,
  // showCoverageOnHover: false,
  // zoomToBoundsOnClick: false,
  maxClusterRadius: 50,
  chunkedLoading: true,
  chunkInterval: 100, // default=200
  chunkDelay: 100, //default=50
  chunkProgress: updateProgressBar,
});
function AddGateways(network) {

  // gatewaysFetchPage(markers, network, 0);

  layerControl.addOverlay(markers, "Helium Hotspots");
  markers.addTo(map);
  // loadGatewaysBetweenLongitudes();
  // loadGatewaysInView();
  boundsChangedCallback();
}

var progress = document.getElementById('progress');
var progressBar = document.getElementById('progress-bar');
function updateProgressBar(processed, total, elapsed, layersArray) {
  if (elapsed > 1000) {
    // if it takes more than a second to load, display the progress bar:
    progress.style.display = 'block';
    progressBar.style.width = Math.round(processed/total*100) + '%';
  }

  if (processed === total) {
    // all markers processed - hide the progress bar:
    // progress.style.display = 'none';
    progress.innerText = total+' hotspots';
    progress.title = 'online in the past 5 days'
    progress.style.height = '27px';
    progress.style.width = '150px';
    // progress.style.display = 'inline-block';
  }

  // console.log("Loading markers", processed, total, Math.round(processed/total*100) + '%');
}

function gatewaysFetchPage(markers, network, offset) {
  // const res = fetch("http://localhost:8080/network/"+encodeURIComponent(network.network_id)+"/gateways/"+offset)
  const res = fetch("https://api.ttnmapper.org/network/"+encodeURIComponent(network.network_id) + "/gateways/" + offset)
      .then(response => response.json())
      .then(data => {
        for(gateway of data) {
          let lastHeardDate = Date.parse(gateway.last_heard);

          // Only add gateways last heard in past 5 days
          if(lastHeardDate > (Date.now() - (5*24*60*60*1000)) ) {
            let marker = L.marker(
                [ gateway.latitude, gateway.longitude ],
                {
                  icon: iconByNetworkId(gateway.network_id, lastHeardDate)
                });
            const gwDescriptionHead = popUpHeader(gateway);
            const gwDescription = popUpDescription(gateway);
            marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
            markers.addLayer(marker);
          }
        }

        // No data received? Then we are done downloading, so add to map.
        if(data.length === 0) {
          layerControl.addOverlay(markers, "Helium Hotspots");
          if (network.default_shown) {
            markers.addTo(map);
          }
        } else {
          gatewaysFetchPage(markers, network, offset+1);
        }
      });

  // const res = fetch("http://localhost:8080/network/"+encodeURIComponent(network.network_id)+"/gateways")
  //     .then(response => response.json())
  //     .then(data => {
  //       for(gateway of data) {
  //         let lastHeardDate = Date.parse(gateway.last_heard);
  //
  //         // Only add gateways last heard in past 5 days
  //         if(lastHeardDate > (Date.now() - (5*24*60*60*1000)) ) {
  //           let marker = L.marker(
  //               [ gateway.latitude, gateway.longitude ],
  //               {
  //                 icon: iconByNetworkId(gateway.network_id, lastHeardDate)
  //               });
  //           const gwDescriptionHead = popUpHeader(gateway);
  //           const gwDescription = popUpDescription(gateway);
  //           marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
  //           markers.addLayer(marker);
  //         }
  //       }
  //
  //       layerControl.addOverlay(markers, "Helium Hotspots");
  //       if(network.default_shown) {
  //         markers.addTo(map);
  //       }
  //
  //     });
}