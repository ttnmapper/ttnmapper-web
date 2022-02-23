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
      network_id: 'thethingsnetwork.org',
      network_name: 'The Things Network (v2)',
      default_shown: false

    },
    {
      network_id: 'NS_TTS_V3://ttn@000013',
      network_name: 'The Things Stack CE (v3)',
      default_shown: true

    },
    // {
    //   network_id: 'NS_HELIUM://000024',
    //   network_name: 'Helium - The People\'s Network',
    //   default_shown: false
    //
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
    layerControl.addOverlay(coveragetiles, "Heatmap: "+network.network_name);
    if(network.default_shown) {
      coveragetiles.addTo(map);
    }

    AddGateways(network);
  }
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the neccesary layers, hide them, or display them.
}

function AddGateways(network) {
  const res = fetch("https://api.ttnmapper.org/network/"+encodeURIComponent(network.network_id)+"/gateways")
  .then(response => response.json())
  .then(data => {
    var markers = L.markerClusterGroup({
      spiderfyOnMaxZoom: true,
      // showCoverageOnHover: false,
      // zoomToBoundsOnClick: false,
      maxClusterRadius: 50,
    });

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

    layerControl.addOverlay(markers, "Gateways: "+network.network_name);
    if(network.default_shown) {
      markers.addTo(map);
    }

  });
}