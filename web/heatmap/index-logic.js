setUp();

function setUp() {
  initMap();

  addBackgroundLayers();
  addForegroundLayers();
  getGatewaysInView();

  gatewayMarkers.addTo(map);
}

function addForegroundLayers() {
  var coveragetiles = L.tileLayer('/tms/index.php?tile={z}/{x}/{y}', {
    maxNativeZoom: 18,
    maxZoom: 20,
    zIndex: 10,
    opacity: 0.5
  });
  coveragetiles.addTo(map);
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the neccesary layers, hide them, or display them.
}