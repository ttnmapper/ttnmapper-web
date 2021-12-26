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
      maxNativeZoom: 20,
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

function AddGateways(network) {

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

  gatewaysFetchPage(markers, network, 0);
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
  const res = fetch("http://localhost:8080/network/"+encodeURIComponent(network.network_id)+"/gateways/"+offset)
  // const res = fetch("https://api.ttnmapper.org/network/gateways/"+encodeURIComponent(network.network_id))
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


function iconByNetworkId(networkId, lastHeardDate) {
  if(networkId === "thethingsnetwork.org") {
    if(lastHeardDate < (Date.now() - (1*60*60*1000)) ) {
      return gatewayMarkerOffline;
    }
    return gatewayMarkerOnline;
  }
  if(networkId.startsWith("NS_TTS_V3://")) {
    if(lastHeardDate < (Date.now() - (1*60*60*1000)) ) {
      return gatewayMarkerV3Offline;
    }
    return gatewayMarkerV3Online;
  }
  if(networkId.startsWith("NS_CHIRP://")) {
    return gatewayMarkerChirpV3;
  }
  if(networkId.startsWith("NS_HELIUM://")) {
    if(lastHeardDate < (Date.now() - (1*24*60*60*1000)) ) {
      return gatewayMarkerHeliumOffline;
    }
    return gatewayMarkerHeliumOnline;
  }
  return gatewayMarkerOnlineNotMapped;
}

function popUpHeader(gateway) {
  let header = `<b>${he.encode(gateway.gateway_id)}</b>`

  if(gateway.description !== "") {
    header = `<b>${he.encode(gateway.description)}</b>`
    header = `${header}<br>${gateway.gateway_id}`
  }

  // Add the EUI if it is set
  if (gateway.gateway_eui !== "") {
    header = `${header}<br>EUI: ${gateway.gateway_eui}`
  }

  // Add the network ID if it is set
  if (gateway.network_id !== "") {
    header = `${header}<br>Network: ${gateway.network_id}`
  }

  return header
}

function popUpDescription(gateway) {
  var description = `
<br>Last heard at ${gateway.last_heard}
<br>Lat, Lon: ${gateway.latitude}, ${gateway.longitude}
<br>Show only this gateway's coverage as: 
<ul>
  <li>
    <a target="_blank" href="/heatmap/private/?gateway=${he.encode(gateway.gateway_id)}&network=${he.encode(gateway.network_id)}">heatmap</a>
  </li>
</ul>
`

  return description;
}