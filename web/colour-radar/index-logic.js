var loadedRadarLayers = {};
var visibleRadarLayers = {};

var loadedCircleLayers = {};
var visibleCircleLayers = {};

var previousZoom = 0;

var canvasRenderer;
var canvasRendererBlue;
var canvasRendererCyan;
var canvasRendererGreen;
var canvasRendererYellow;
var canvasRendererOrange;
var canvasRendererRed;

setUp();

function setUp() {
  $("#legend").load("/legend.html");
  $("#legend").css({ visibility: "visible"});
  
  initMap();

  // Canvas for circle layers
  map.createPane('semitransparent');
  map.getPane('semitransparent').style.opacity = '0.5';
  canvasRenderer = L.canvas({pane: 'semitransparent'});

  // Canvases for radar views
  map.createPane('semitransparentBlue');
  map.getPane('semitransparentBlue').style.opacity = '0.2';
  canvasRendererBlue = L.canvas({pane: 'semitransparentBlue'});
  map.createPane('semitransparentCyan');
  map.getPane('semitransparentCyan').style.opacity = '0.2';
  canvasRendererCyan = L.canvas({pane: 'semitransparentCyan'});
  map.createPane('semitransparentGreen');
  map.getPane('semitransparentGreen').style.opacity = '0.25';
  canvasRendererGreen = L.canvas({pane: 'semitransparentGreen'});
  map.createPane('semitransparentYellow');
  map.getPane('semitransparentYellow').style.opacity = '0.3';
  canvasRendererYellow = L.canvas({pane: 'semitransparentYellow'});
  map.createPane('semitransparentOrange');
  map.getPane('semitransparentOrange').style.opacity = '0.35';
  canvasRendererOrange = L.canvas({pane: 'semitransparentOrange'});
  map.createPane('semitransparentRed');
  map.getPane('semitransparentRed').style.opacity = '0.4';
  canvasRendererRed = L.canvas({pane: 'semitransparentRed'});

  addBackgroundLayers();

  getGatewaysInView();

  gatewayMarkers.addTo(map);
  gatewayMarkersNoCluster.addTo(map);
}

// Callback to refresh layers when the maps was panned or zoomed
function boundsChangedCallback() {
  // Refresh the visible gateways, which will also trigger a layer refresh
  getGatewaysInView();
}

function hideAllLayers() {
  console.log("Hiding all layers");
  hideAllCircleViews();
  hideAllRadarViews();
}

var prevState = "none";
function showOrHideLayers() {
  if(map)
  {
    if(gatewaysInView.length<layerSwapGwCount){
      //View radars
      // console.log("Should show radars");
      if(prevState !== "radar") {
        hideAllLayers();
      }
      previousZoom = map.getZoom();
      prevState = "radar";
      loadRadarsInView();
      hideAllCircleViews();
    }
    else if(gatewaysInView.length<layerHideGwCount){
      //View circles
      // console.log("Should show circles");
      if (prevState!=="circle") {
        hideAllLayers();
      }
      previousZoom = map.getZoom();
      prevState = "circle";
      loadCircleViews();
      hideAllRadarViews();
    }
    else {
      //Hide all and only show markers
      hideAllLayers();
      hideAllCircleViews();
      hideAllRadarViews();
      previousZoom = map.getZoom();
      prevState = "none";
    }
  }
}

function hideAllRadarViews()
{
  // console.log("Hiding radar views: "+Object.keys(visibleRadarLayers));
  Object.keys(visibleRadarLayers).forEach(function(key) {
    map.removeLayer(visibleRadarLayers[key]);
    delete visibleRadarLayers[key];
    // console.log("Removing radar "+key);
  });

  // Object.keys(loadedRadarLayers).forEach(function(key) {
  //   map.removeLayer(loadedRadarLayers[key]);
  // });
}

function hideAllCircleViews()
{
  // console.log("Hiding circle views: "+Object.keys(visibleCircleLayers));
  Object.keys(visibleCircleLayers).forEach(function(key) {
    map.removeLayer(visibleCircleLayers[key]);
    delete visibleCircleLayers[key];
    // console.log("Removing circle "+key);
  });

  // Object.keys(loadedCircleLayers).forEach(function(key) {
  //   map.removeLayer(loadedCircleLayers[key]);
  // });
}

function loadCircleViews()
{
  gwids = gatewaysInView;
  // console.log(gwids);

  // First hide layers that are not visible anymore
  Object.keys(visibleCircleLayers).forEach(function(key) {
    if($.inArray(key, gwids)!=-1) {
      // Keep showing the layer, or download a new one
    }
    else {
      map.removeLayer(visibleCircleLayers[key]);
      delete visibleCircleLayers[key];
    }
  });

  var newRadarsToDownload = [];

  for(var i=0; i<gwids.length; i++) {
    let gwid = gwids[i];

    // Add a marker on the map for the gateway
    // addGateway(gwid);
    
    // Layer download
    if(gwid in loadedCircleLayers) {
      //already downloaded this layer and drew it
      // Layer show/hide
      if(gwid in visibleCircleLayers) {
        // Layer already shown
      }
      else {
        loadedCircleLayers[gwid].addTo(map);
        visibleCircleLayers[gwid] = loadedCircleLayers[gwid];
      }
    }
    else {
      newRadarsToDownload.push(gwid);
    }
  }

  $.ajax({
    type: "POST",
    url: "/webapi/gwcirclelist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": newRadarsToDownload }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){
      for(gwid in data) {
        if(prevState == "radar")
        {
          continue;
        }
        let geojsonLayerCircles = L.geoJSON(data[gwid], {
          pointToLayer: function (feature, latlng) {
            return L.circle(latlng, feature.properties.radius, {
              stroke: false,
              fillOpacity: 0.8,
              fillColor: "#0000FF",
              renderer: canvasRenderer,
            });
          }
        });
        if(gwid in visibleCircleLayers) {
        } else {
          geojsonLayerCircles.addTo(map);
          visibleCircleLayers[gwid] = geojsonLayerCircles;
        }
        if(gwid in loadedCircleLayers) {
        } else {
          loadedCircleLayers[gwid] = geojsonLayerCircles;
        }
      }
    },
    failure: function(errMsg) {
        console.log(errMsg);
    }
  });

}

function loadRadarsInView()
{
  console.log("Loading radars in view");
  gwids = gatewaysInView;

  // First hide layers that are not visible anymore
  Object.keys(visibleRadarLayers).forEach(function(key) {
    if($.inArray(key, gwids)!=-1) {
      // Keep showing the layer, or download a new one
      // console.log("Keeping or showing layer for "+key);
    }
    else {
      // console.log("Removing layer for "+key);
      map.removeLayer(visibleRadarLayers[key]);
      delete visibleRadarLayers[key];
    }
  });

  var newRadarsToDownload = [];

  for(var i=0; i<gwids.length; i++) {
    let gwid = gwids[i];

    // Add a marker on the map for the gateway
    // addGateway(gwid);
    
    // Layer download
    if(gwid in loadedRadarLayers) {
      //already downloaded this layer and drew it
      // Layer show/hide
      if(gwid in visibleRadarLayers) {
        // Layer already shown
        // console.log("Radar already shown for "+gwid);
      }
      else {
        // console.log("Showing previously downloaded radar for "+gwid);
        loadedRadarLayers[gwid].addTo(map);
        visibleRadarLayers[gwid] = loadedRadarLayers[gwid];
        // console.log("ReShowing "+gwid);
      }
    }
    else {
      // console.log("Need to download radar for "+gwid);
      newRadarsToDownload.push(gwid);
    }
  }

  if(newRadarsToDownload.length > 0) {
    $.ajax({
      type: "POST",
      url: "/webapi/gwradarcolourlist.php",
      // The key needs to match your method's input parameter (case-sensitive).
      data: JSON.stringify({ "gateways": newRadarsToDownload }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      success: function(data){
        for(gwid in data) {
          if(prevState == "circle")
          {
            continue;
          }

          let geojsonBlue = L.geoJson(data[gwid], 
            {
              stroke: false, 
              fillOpacity: 0.9,
              fillColor: "#0000FF",
              zIndex: 25,
              renderer: canvasRendererBlue,
              filter: function (feature) {
                if(feature.style.color=="blue") return true;
                else return false;

              }
            }
          );

          let geojsonCyan = L.geoJson(data[gwid], 
            {
              stroke: false, 
              fillOpacity: 0.9,
              fillColor: "#00FFFF",
              zIndex: 30,
              renderer: canvasRendererCyan,
              filter: function (feature) {
                if(feature.style.color=="cyan") return true;
                else return false;

              }
            }
          );

          let geojsonGreen = L.geoJson(data[gwid], 
            {
              stroke: false, 
              fillOpacity: 0.9,
              fillColor: "#00FF00",
              zIndex: 35,
              renderer: canvasRendererGreen,
              filter: function (feature) {
                if(feature.style.color=="green") return true;
                else return false;

              }
            }
          );

          let geojsonYellow = L.geoJson(data[gwid], 
            {
              stroke: false, 
              fillOpacity: 0.9,
              fillColor: "#FFFF00",
              zIndex: 40,
              renderer: canvasRendererYellow,
              filter: function (feature) {
                if(feature.style.color=="yellow") return true;
                else return false;

              }
            }
          );

          let geojsonOrange = L.geoJson(data[gwid], 
            {
              stroke: false, 
              fillOpacity: 0.9,
              fillColor: "#FF7F00",
              zIndex: 45,
              renderer: canvasRendererOrange,
              filter: function (feature) {
                if(feature.style.color=="orange") return true;
                else return false;

              }
            }
          );

          let geojsonRed = L.geoJson(data[gwid], 
            {
              stroke: false, 
              fillOpacity: 0.9,
              fillColor: "#FF0000",
              zIndex: 50,
              renderer: canvasRendererRed,
              filter: function (feature) {
                if(feature.style.color=="red") return true;
                else return false;

              }
            }
          );

          var polygon = L.featureGroup([
            geojsonBlue, 
            geojsonCyan, 
            geojsonGreen,
            geojsonYellow,
            geojsonOrange,
            geojsonRed
          ]);

          if(gwid in visibleRadarLayers) {
          } else {
            visibleRadarLayers[gwid] = polygon; // should add the layer to the map here and store the pointer to the layer
            polygon.addTo(map);
            //radarLayersGroup.addLayer(polygon).addTo(map);
          }
          if(gwid in loadedRadarLayers) {
          } else {
            loadedRadarLayers[gwid] = polygon; // should add the geojson data to the dictionary here

            // When only a subset of gateways are displayed, zoom to fit them into view.
            // And only do this when there is a layer displayed and if the start location is not set by url params
            if("gateway" in getParameters && Object.keys(loadedRadarLayers).length > 0
              && findGetParameter("lat")==null && findGetParameter("lon")==null 
                && findGetParameter("zoom")==null) {
              var bounds = L.featureGroup(Object.values(loadedRadarLayers)).getBounds();

              var gatewayGroupBounds = L.featureGroup(Object.values(loadedGateways)).getBounds();
              if(gatewayGroupBounds.isValid()) {
                bounds.extend(gatewayGroupBounds);
              }

              if(bounds.isValid()) {
                map.fitBounds(bounds);
              }
            }
          }
        }
      },
      failure: function(errMsg) {
          console.log(errMsg);
      }
    });
  }
  
}