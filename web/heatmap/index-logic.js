setUp();

function setUp() {
  initMap();

  addBackgroundLayers();
  addForegroundLayers();
  getGatewaysInView();

  gatewayMarkers.addTo(map);
  //gatewayMarkersNoCluster.addTo(map);
}

// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  // Refresh the visible gateways, which will also trigger a layer refresh
  getGatewaysInView();
}

function addForegroundLayers() {
  var tms_url = 'https://ttnmapperfsa4if0y-tilemapserver.functions.fnc.fr-par.scw.cloud/circles/{z}/{x}/{y}.png';
  if(findGetParameter("type")!=null) {
    var type = findGetParameter("type");

    if(type==="blocks") {
      tms_url = 'https://ttnmapperfsa4if0y-tilemapserver.functions.fnc.fr-par.scw.cloud/blocks/{z}/{x}/{y}.png';
    }
    if(type==="circles") {
      tms_url = 'https://ttnmapperfsa4if0y-tilemapserver.functions.fnc.fr-par.scw.cloud/blocks/{z}/{x}/{y}.png';
    }
    if(type==="legacy") {
      tms_url = '/tms/index.php?tile={z}/{x}/{y}';
    }
  }
  //var coveragetiles = L.tileLayer('/tms/index.php?tile={z}/{x}/{y}', {
  //var coveragetiles = L.tileLayer('https://tms.ttnmapper.org/circles/{z}/{x}/{y}.png', {
  var coveragetiles = L.tileLayer(tms_url, {
    maxNativeZoom: 19,
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