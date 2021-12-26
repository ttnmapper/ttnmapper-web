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
  var tms_url = '/tms/index.php?tile={z}/{x}/{y}';
  var network = findGetParameter("network");
  var gateway = findGetParameter("gateway");

  if(network === "thethingsnetwork.org" || network === "NS_TTS_V3://ttn@000013" || network === "NS_HELIUM://000024") {
    $("#private-network-warning").remove();
  }

  var tms_url = 'https://tms.ttnmapper.org/circles/network/{networkid}/{z}/{x}/{y}.png';
  if(gateway !== null) {
    tms_url = 'https://tms.ttnmapper.org/circles/gateway/{networkid}/{gatewayid}/{z}/{x}/{y}.png';
  }

  
  
  var coveragetiles = L.tileLayer(tms_url, {
    networkid: encodeURIComponent(network),
    gatewayid: encodeURIComponent(gateway),
    maxNativeZoom: 19,
    maxZoom: 20,
    zIndex: 10,
    opacity: 0.5
  });
  coveragetiles.addTo(map);

  AddGateways(network, gateway);
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the neccesary layers, hide them, or display them.
}

function AddGateways(network, gatewayFilter) {
  const res = fetch("https://api.ttnmapper.org/network/"+encodeURIComponent(network)+"/gateways")
  .then(response => response.json())
  .then(data => {
    // console.log(data);
    var markers = L.markerClusterGroup({
      spiderfyOnMaxZoom: true,
      // showCoverageOnHover: false,
      // zoomToBoundsOnClick: false,
      maxClusterRadius: 50,
    });

    for(gateway of data) {
      if(gatewayFilter != null) {
        if(gatewayFilter !== gateway.gateway_id) {
          continue;
        } else {
          // single gateway: enter map at gateway
          map.panTo(new L.LatLng(gateway.latitude, gateway.longitude));
        }
      }

      let lastHeardDate = Date.parse(gateway.last_heard);

      // Only add gateways last heard in past 5 days
      if(lastHeardDate > (Date.now() - (5*24*60*60*1000)) || gatewayFilter != null) {
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

    markers.addTo(map);

  });
}



function iconByNetworkId(networkId, lastHeardDate) {
  if(networkId === "thethingsnetwork.org") {
    if(lastHeardDate < (Date.now() - (1*60*60*1000)) ) {
      return gatewayMarkerOffline;
    }
    return gatewayMarkerOnline;
  }
  if(networkId.startsWith("NS_TTS_V3://")) {
    console.log("Last heard", lastHeardDate);
    console.log("Before", (Date.now() - (1*60*60*1000)));
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
  // First line always the ID
  let header = `<b>${he.encode(gateway.gateway_id)}</b>`

  // Add the EUI if it is set
  if (gateway.gateway_eui != null) {
    header = `${header}<br>${gateway.gateway_eui}`
  }

  // Add the network ID if it is set
  if (gateway.network_id != null) {
    header = `${header}<br>${gateway.network_id}`
  }

  // TODO: If a gateway has a description, add it. V3 and Chirp does not have descriptions yet.

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