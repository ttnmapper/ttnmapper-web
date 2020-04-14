setUp();

function setUp() {
  initMap();

  addBackgroundLayers();
  addForegroundLayers();
  getGatewaysInView();

  gatewayMarkers.addTo(map);
  gatewayMarkersNoCluster.addTo(map);
}

// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  // Refresh the visible gateways, which will also trigger a layer refresh
  getGatewaysInView();
}

function addForegroundLayers() {
  var coverageBlocks = L.tileLayer('/tms/index.php?tile={z}/{x}/{y}', {
    maxZoom: 10,
    maxNativeZoom: 18,
    zIndex: 10,
    opacity: 0.5
  });
  coverageBlocks.addTo(map);
  var coverageCircles = L.tileLayer('http://private.ttnmapper.org:8000/{z}/{x}/{y}', {
    minZoom: 11,
    maxZoom: 20,
    maxNativeZoom: 20,
    zIndex: 10,
    opacity: 0.5
  });
  coverageCircles.addTo(map);

  fetch('Cycle Tour 20 Route.kml')
    .then(res => res.text())
    .then(kmltext => {
        // Create new kml overlay
        const parser = new DOMParser();
        const kml = parser.parseFromString(kmltext, 'text/xml');
        const track = new L.KML(kml);
        map.addLayer(track);

        // Adjust map to show the kml
        // const bounds = track.getBounds();
        // map.fitBounds(bounds);
    });
}

function showOrHideLayers() {
  // This function is called after we know which gateways are in view: gatewaysInView
  // Download the neccesary layers, hide them, or display them.
}