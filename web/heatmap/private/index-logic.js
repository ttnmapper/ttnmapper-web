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

  if(gateway !== null) {
    AddGateway(network, gateway);
  } else {
    AddGateways(network);
  }
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the necessary layers, hide them, or display them.
}

function AddGateways(network) {
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

function AddGateway(network, gateway) {
  // /{network_id}/{gateway_id}/details
  const res = fetch("https://api.ttnmapper.org/gateway/"+encodeURIComponent(network)+"/"+encodeURIComponent(gateway)+"/details")
      .then(response => response.json())
      .then(gateway => {
        console.log(gateway);

        // single gateway: center map at gateway
        map.panTo(new L.LatLng(gateway.latitude, gateway.longitude));

        let lastHeardDate = Date.parse(gateway.last_heard);

        // Only add gateways last heard in past 5 days
        if(lastHeardDate > (Date.now() - (5*24*60*60*1000))) {
          let marker = L.marker(
              [ gateway.latitude, gateway.longitude ],
              {
                icon: iconByNetworkId(gateway.network_id, lastHeardDate)
              });
          const gwDescriptionHead = popUpHeader(gateway);
          const gwDescription = popUpDescription(gateway);
          marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
          marker.addTo(map);
        }
      });
}
