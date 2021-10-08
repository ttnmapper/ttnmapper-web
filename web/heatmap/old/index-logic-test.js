setUp();

function setUp() {
  initMap();

  // let measureControl = L.control.measure({ position: "topleft" });
  // measureControl.addTo(map);

  map.options.inertia = false;

  // const featureToMarker = api.NewFeatureToMarker(L, {
  //   height: 15,
  //   width: 15,
  // });

  let osmLayer = {
    name: "OSM Mapnik",
    layer: L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
      fadeAnimation: false,
    }),
  };

  let osmLayerGrey = {
    name: "OSM Mapnik Grayscale",
    layer: L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 20,
      attribution:
        '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false,
      className: "toGrayscale",
    }),
  };

  let Esri_WorldShadedRelief = {
    name: "Terrain",
    layer: L.tileLayer(
      "https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}",
      {
        attribution: "Tiles &copy; Esri &mdash; Source: Esri",
        maxZoom: 13,
        fadeAnimation: false,
      }
    ),
  };

  // https: also suppported.
  let Esri_WorldImagery = {
    name: "Satellite",
    layer: L.tileLayer(
      "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
      {
        attribution:
          "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community",
        fadeAnimation: false,
      }
    ),
  };

  // https: also supported.
  let Stamen_TonerLite = {
    name: "Stamen TonerLite",
    layer: L.tileLayer(
      "https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}",
      {
        attribution:
          'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> | Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        subdomains: "abcd",
        minZoom: 0,
        maxZoom: 20,
        ext: "png",
        fadeAnimation: false,
      }
    ),
  };

  let baseLayers = [
    Stamen_TonerLite,
    osmLayerGrey,
    osmLayer,
    Esri_WorldShadedRelief,
    Esri_WorldImagery,
  ];

  // Async fetch and add gateway markers
  // api.GetTtnV2Overlay().then(r => {
  //   panelLayers.addOverlay(r)
  // });
  // api.GetTtnV3Overlay().then(r => {
  //   panelLayers.addOverlay(r)
  // });

  let panelOverlays = [];

  let networkIds = ['thethingsnetwork.org', 'NS_TTS_V3://ttn@000013', 'NS_HELIUM://000024'];
  for (const networkId of networkIds) {
    let group = {
      group: NetworkIdToName(networkId),
      collapsed: true,
      layers: [
        {
          name: "Heatmap",
          // active: true,
          layer: L.tileLayer('https://tms.ttnmapper.org/circles/network/{networkid}/{z}/{x}/{y}.png', 
            {
              networkid: encodeURIComponent(networkId),
              maxNativeZoom: 19,
              maxZoom: 20,
              maxNativeZoom: 20,
              zIndex: 10,
              opacity: 0.5
            }
          )
        }
      ]
    };
    panelOverlays.push(group);

    GetGatewaysOverlay(networkId).then(gateways => {

      panelLayers.addOverlay(
          {
            active: true,
            name: "Gateway markers inside",
            icon: iconImageByNetworkId(networkId),
            layer: ClusteredFeatures(L, gateways),
          },
          'Gateway Markers',
          NetworkIdToName(networkId)
      );
    });
  }

  let panelLayers = new L.Control.PanelLayers(
      baseLayers, panelOverlays,
      {
        collapsibleGroups: false,
      }
  );

  map.addControl(panelLayers);
  map.addLayer(Stamen_TonerLite.layer);

  map.on('click', function(e) {
    if(e.originalEvent.shiftKey) {
        var popLocation= e.latlng;
        // console.log(popLocation.lat, popLocation.lng);
        fetch("get-devices-at.php?lat="+popLocation.lat+"&lon="+popLocation.lng)
        .then(response => response.json())
        .then(data => {
          var dev_ids = [];
            for (const x of data) {
              dev_ids.push(x.id);
              console.log("https://ttnmapper.org/devices/?application="+x.app_id+"&device="+x.dev_id+"&startdate=&enddate=&gateways=on&lines=on&points=on")
            }
            console.log("DevIDs:", dev_ids.join(" "));
          }
        );

        fetch("get-gateways-at.php?lat="+popLocation.lat+"&lon="+popLocation.lng)
        .then(response => response.json())
        .then(data => {
          var gw_ids = [];
            for (const x of data) {
              console.log(x.network_id, "-", x.gateway_id);
              gw_ids.push(x.gateway_id);
            }
            console.log("Gateway IDs:", gw_ids.join(" "));
          }
        );
      }
    });
}


// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  
}