setUp();

function setUp() {
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

  var tms_url = 'https://tms.ttnmapper.org/circles/gateway/{networkid}/{gatewayid}/{z}/{x}/{y}.png';
  
  var coveragetiles = L.tileLayer(tms_url, {
    networkid: encodeURIComponent(network),
    gatewayid: encodeURIComponent(gateway),
    maxNativeZoom: 19,
    maxZoom: 20,
    maxNativeZoom: 20,
    zIndex: 10,
    opacity: 0.5
  });
  coveragetiles.addTo(map);
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the neccesary layers, hide them, or display them.
}