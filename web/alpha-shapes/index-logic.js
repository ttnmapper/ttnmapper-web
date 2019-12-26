var loadedRadarLayers = {};
var visibleRadarLayers = {};

var loadedCircleLayers = {};
var visibleCircleLayers = {};

var loadedGateways = {};
var gatewaysInView = [];
var gatewayMarkers = L.markerClusterGroup({
  maxClusterRadius: 40
});

var previousZoom = 0;
var layerSwapGwCount = 300;
var layerHideGwCount = 2000;
// var layerSwapZoomLevel = 10;
// var layerHideZoomLevel = 7;

var map;
var default_zoom = true;
var zoom_override = false;

var gwMarkerIconRoundBlue = L.icon({
  iconUrl: "/resources/gateway_dot.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});

var gwMarkerIconRoundGreen = L.icon({
  iconUrl: "/resources/gateway_dot_green.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});
var gwMarkerIconRoundRed = L.icon({
  iconUrl: "/resources/gateway_dot_red.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});
var gwMarkerIconRoundYellow = L.icon({
  iconUrl: "/resources/gateway_dot_yellow.png",

  iconSize:     [20, 20], // size of the icon
  iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
});

var canvasRenderer;

var getParameters = getJsonFromUrl();

setUp();

function setUp() {

  map = L.map('map');

  map.createPane('semitransparent');
  map.getPane('semitransparent').style.opacity = '0.5';
  canvasRenderer = L.canvas({pane: 'semitransparent'});

  if(findGetParameter("lat")!=null && findGetParameter("lon")!=null && findGetParameter("zoom")!=null) {
    map.setView([ findGetParameter("lat"), findGetParameter("lon") ], findGetParameter("zoom"));
    default_zoom = false;
    zoom_override = true;
  }
  else {
    b = JSON.parse(localStorage.getItem('bounds'));
    if (b == null)
    {
      map.setView([48.209661, 10.251494], 6);
    }
    else {
      try {
        map.fitBounds([[b._southWest.lat%90,b._southWest.lng%180],[b._northEast.lat%90,b._northEast.lng%180]]);
        default_zoom = false;
      } catch (err) {
        map.setView([48.209661, 10.251494], 6);
      }
    }
  }

  map.on('dragend', boundsChanged);
  map.on('zoomend', boundsChanged);

  //disable inertia because it is irritating and slow
  map.options.inertia=false;

  L.control.measure({
    position: 'topleft'
  }).addTo(map)

  map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");

  // Listen for orientation changes
  window.addEventListener("orientationchange", showHideMenu(), false);
  window.onresize = showHideMenu;

  addBackgroundLayers();

  getGatewaysInView();

  //Clusterfier for gateway markers
  gatewayMarkers.addTo(map);

  // swap_layers();
}

function addBackgroundLayers() {
  // https: also suppported.
  var Esri_WorldImagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
    fadeAnimation: false
  });

  // https: also suppported.
  var Stamen_TonerLite = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}', {
    attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    subdomains: 'abcd',
    minZoom: 0,
    maxZoom: 20,
    ext: 'png',
    fadeAnimation: false
  });


  var OpenStreetMap_Mapnik_Grayscale = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    fadeAnimation: false,
    className: 'toGrayscale'
  });

  // https: also suppported.
  var Esri_WorldShadedRelief = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri',
    maxZoom: 13,
    fadeAnimation: false
  });

  // https: also suppported.
  var OpenStreetMap_Mapnik = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    fadeAnimation: false
  });

  switch (findGetParameter("layer")) {
    case "mapnik":
      OpenStreetMap_Mapnik.addTo(map);
      break;
    case "mapnik_grayscale":
      OpenStreetMap_Mapnik_Grayscale.addTo(map);
      break;
    case "terrain":
      Esri_WorldShadedRelief.addTo(map);
      break;
    case "satellite":
      Esri_WorldImagery.addTo(map);
      break;
    default:
      // use default layer
      Stamen_TonerLite.addTo(map);
  }


  L.control.layers({
    "Stamen TonerLite": Stamen_TonerLite,
    "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
    "Terrain": Esri_WorldShadedRelief, 
    "OSM Mapnik": OpenStreetMap_Mapnik,
    "Satellite": Esri_WorldImagery
  })
  .addTo(map);
}


// function swap_layers()
// {
//   if(map)
//   {
//     if(map.getZoom()<layerHideZoomLevel)
//     {
//       map.eachLayer(function (layer) {
//         if ("feature" in layer) {
//           if ("geometry" in layer.feature) {
//             if ("type" in layer.feature.geometry) {
//               if (layer.feature.geometry.type == "Point"
//                 || layer.feature.geometry.type == "Polygon") {
//                   map.removeLayer(layer);
//               }
//             }
//           }
//         }
//       });
//       previousZoom = map.getZoom();
//       getGatewaysInView();
//     }
//     else if(showGlobalRadar && map.getZoom() <= layerSwapZoomLevel) //7
//     {
//       if (previousZoom>layerSwapZoomLevel) {
//         map.eachLayer(function (layer) {
//           if ("feature" in layer) {
//             if ("geometry" in layer.feature) {
//               if ("type" in layer.feature.geometry) {
//                 if (layer.feature.geometry.type == "Point"
//                   || layer.feature.geometry.type == "Polygon") {
//                     map.removeLayer(layer);
//                 }
//               }
//             }
//           }
//         });
//       }
//       previousZoom = map.getZoom();
//       loadCircleViews();
//       hideAllRadarViews();
//     }
//     else
//     {
//       if(previousZoom<=layerSwapZoomLevel) {
//         map.eachLayer(function (layer) {
//           if ("feature" in layer) {
//             if ("geometry" in layer.feature) {
//               if ("type" in layer.feature.geometry) {
//                 if (layer.feature.geometry.type == "Point"
//                   || layer.feature.geometry.type == "Polygon") {
//                     map.removeLayer(layer);
//                 }
//               }
//             }
//           }
//         });
//       }
//       previousZoom = map.getZoom();
//       loadRadarsInView();
//       hideAllCircleViews();
//     }
//   }
// }

function showHideMenu()
{

}

//Create a map that remembers where it was zoomed to
function boundsChanged () {
  // swap_layers();
  getGatewaysInView();
  localStorage.setItem('bounds', JSON.stringify(map.getBounds()));
  default_zoom = false;
}

function findGetParameter(parameterName) {
  var result = null;
  var tmp = [];
  var items = location.search.substr(1).split("&");
  for (var index = 0; index < items.length; index++) {
    tmp = items[index].split("=");
    if (tmp[0] === parameterName) {
      result = decodeURIComponent(tmp[1]);
    }
  }
  return result;
}

function getJsonFromUrl(url) {
  if(!url) url = location.href;
  url = url.replace("%5B", "[");
  url = url.replace("%5D", "]");
  console.log(url);
  var question = url.indexOf("?");
  var hash = url.indexOf("#");
  if(hash==-1 && question==-1) return {};
  if(hash==-1) hash = url.length;
  var query = question==-1 || hash==question+1 ? url.substring(hash) : 
  url.substring(question+1,hash);
  var result = {};
  query.split("&").forEach(function(part) {
    if(!part) return;
    part = part.split("+").join(" "); // replace every + with space, regexp-free version
    var eq = part.indexOf("=");
    var key = eq>-1 ? part.substr(0,eq) : part;
    var val = eq>-1 ? decodeURIComponent(part.substr(eq+1)) : "";
    var from = key.indexOf("[");
    if(from==-1) result[decodeURIComponent(key)] = val;
    else {
      var to = key.indexOf("]",from);
      var index = decodeURIComponent(key.substring(from+1,to));
      key = decodeURIComponent(key.substring(0,from));
      if(!result[key]) result[key] = [];
      if(!index) result[key].push(val);
      else result[key][index] = val;
    }
  });
  return result;
}


function getGatewaysInView()
{
  if("gateway" in getParameters) {
    
    if(Array.isArray(getParameters['gateway'])) {
      gatewaysInView = getParameters['gateway'];
    } else {
      gatewaysInView = [getParameters['gateway']];
    }

    console.log("Is array of gateways");
    addGateways(gatewaysInView);
    // showOrHideLayers();

  } else {
    var bounds = map.getBounds();

    $.ajax
      ({
        type: "POST",
        url: '/webapi/gwbbox.php',
        dataType: 'json',
        data: JSON.stringify(bounds),
        success: function (data) {
          gatewaysInView = data["gateways"];
          addGateways(gatewaysInView);
          console.log(gatewaysInView.length + " gateways in view");
          // showOrHideLayers();
        }
      });
  }
}

function addGateways(gateways)
{
  //gwdetailslist.php
  var gatewaysToAdd = [];
  for (i in gateways) {
    if(!(gateways[i] in loadedGateways)) {
      // console.log(gateways[i]+" not loaded yet");
      gatewaysToAdd.push(gateways[i]);
    }
  }

  $.ajax({
    type: "POST",
    url: "/webapi/gwdetailslist.php",
    // The key needs to match your method's input parameter (case-sensitive).
    data: JSON.stringify({ "gateways": gatewaysToAdd }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function(data){
      for(gateway in data) {
        addGatewayMarker(gateway, data[gateway]);
      }
    },
    failure: function(errMsg) {
        console.log(errMsg);
    }
  });

  showOrHideLayers(); // First add the gateway markers, then do the layers.
}

function addGatewayMarker(gateway, data)
{
  if(gateway in loadedGateways) {
    return;
  } else {
    // console.log("Adding "+gateway);
    var gwdescriptionHead = "";
    if (data['description'] != null) {
      gwdescriptionHead = "<b>"+he.encode(data['description'])+"</b><br />"+he.encode(gateway);
    } else {
      gwdescriptionHead = "<b>"+he.encode(gateway)+"</b>";
    }

    gwdescription = 
      '<br />Last heard at '+formatTime(data['last_heard'])+
      '<br />Channels heard on: '+data['channels']+
      '<br />Lat, Lon: '+data['lat'] +','+ data['lon']+
      '<br />Show only this gateway\'s coverage as: '+
      '<ul>'+
        '<li><a href=\"//ttnmapper.org/colour-radar/?gateway[]='+he.encode(gateway)+
            '\">radar</a><br>'+
        '<li><a href=\"//ttnmapper.org/alpha-shapes/?gateway[]='+he.encode(gateway)+
            '\">alpha shape</a><br>'+
      '</ul>';

    if(data['last_heard'] < (Date.now()/1000)-(60*60*1)) //1 hour
    {
      marker = L.marker([data['lat'], data['lon']], {icon: gwMarkerIconRoundRed});
      marker.bindPopup(gwdescriptionHead+'<br /><br /><font color="red">Offline.</font> Will be removed from the map in 5 days.<br />'+gwdescription);
    }
    else if(data['channels']<3)
    {
      //Single channel gateway
      marker = L.marker([data['lat'], data['lon']], {icon: gwMarkerIconRoundYellow});
      marker.bindPopup(gwdescriptionHead+'<br /><br />Likely a <font color="orange">Single Channel Gateway.</font><br />'+gwdescription);
    }
    else
    {
      //LoRaWAN gateway
      marker = L.marker([data['lat'], data['lon']], {icon: gwMarkerIconRoundBlue});
      marker.bindPopup(gwdescriptionHead+'<br />'+gwdescription);
    }

    gatewayMarkers.addLayer(marker);
    loadedGateways[gateway] = marker;
  }
}


function formatTime(timestamp)
{
  var date = new Date(timestamp*1000);
  return date.toISOString();
}



function hideAllLayers() {
  console.log("Hiding all layers");
      hideAllCircleViews();
      hideAllRadarViews();
    // map.eachLayer(function (layer) {
    //   if ("feature" in layer) {
    //     if ("geometry" in layer.feature) {
    //       if ("type" in layer.feature.geometry) {
    //         if (layer.feature.geometry.type == "Point"
    //           || layer.feature.geometry.type == "Polygon") {
    //             map.removeLayer(layer);
    //             visibleCircleLayers = {};
    //             visibleRadarLayers = {};
    //         }
    //       }
    //     }
    //   }
    // });
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
      url: "/webapi/gwalphalist.php",
      // The key needs to match your method's input parameter (case-sensitive).
      data: JSON.stringify({ "gateways": newRadarsToDownload }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      success: function(data){
        for(gwid in data) {
          // let polygon = L.polygon(latlngs,
          let polygon = L.geoJSON(data[gwid], 
            {
              stroke: false,
              // weight: 2,
              // color: "#000000",
              fillOpacity: 0.8,
              fillColor: "#0000FF",
              // zIndex: 25,
              renderer: canvasRenderer
            }
          );
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
              var group = L.featureGroup(Object.values(loadedRadarLayers));
              map.fitBounds(group.getBounds());
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