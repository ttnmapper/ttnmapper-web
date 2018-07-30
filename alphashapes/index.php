<!DOCTYPE html>
<html>
<head>
  <?php
  include("../include_head.php");
  ?>
</head>
<body>
  <?php
  include("../include_body_top.php");
  ?>
  <script>
    var geojsonLayer500udeg;
    var geojsonLayer5mdeg;
    var geojsonLayerCircles;

    var showGlobalRadar = false;
    var loadedRadarLayers = {};
    var visibleRadarLayers = {};
    var loadedCircleLayers = {};
    var visibleCircleLayers = {};
    var loadedGateways = {};

    var previousZoom = 0;
    var layerSwapZoomLevel = 10;
    var layerHideZoomLevel = 7;
    
    function swap_layers()
    {
      if(map)
      {
        if(map.getZoom()<layerHideZoomLevel)
        {
          map.eachLayer(function (layer) {
            if ("feature" in layer) {
              if ("geometry" in layer.feature) {
                if ("type" in layer.feature.geometry) {
                  if (layer.feature.geometry.type == "Point"
                    || layer.feature.geometry.type == "Polygon") {
                      map.removeLayer(layer);
                  }
                }
              }
            }
          });
          previousZoom = map.getZoom();
          getGatewaysInView();
        }
        else if(showGlobalRadar && map.getZoom() <= layerSwapZoomLevel) //7
        {
          if (previousZoom>layerSwapZoomLevel) {
            map.eachLayer(function (layer) {
              if ("feature" in layer) {
                if ("geometry" in layer.feature) {
                  if ("type" in layer.feature.geometry) {
                    if (layer.feature.geometry.type == "Point"
                      || layer.feature.geometry.type == "Polygon") {
                        map.removeLayer(layer);
                    }
                  }
                }
              }
            });
          }
          previousZoom = map.getZoom();
          loadCircleViews();
          hideAllRadarViews();
        }
        else
        {
          if(previousZoom<=layerSwapZoomLevel) {
            map.eachLayer(function (layer) {
              if ("feature" in layer) {
                if ("geometry" in layer.feature) {
                  if ("type" in layer.feature.geometry) {
                    if (layer.feature.geometry.type == "Point"
                      || layer.feature.geometry.type == "Polygon") {
                        map.removeLayer(layer);
                    }
                  }
                }
              }
            });
          }
          previousZoom = map.getZoom();
          loadRadarsInView();
          hideAllCircleViews();
        }
      }
    }

    function showHideMenu()
    {
      document.getElementById('leftpadding').style.display = 'none';
      document.getElementById('legend').style.display = 'none';
      document.getElementById('stats').style.display = 'none';
      document.getElementById('rightpadding').style.display = 'none';

        
      if(window.innerWidth >= 800 && window.innerHeight >= 600){
        document.getElementById('leftcontainer').style.visibility = 'visible';
        document.getElementById('menu').style.visibility = 'visible';
        // document.getElementById('leftpadding').style.display = 'none';
        // document.getElementById('legend').style.display = 'none';


        document.getElementById('rightcontainer').style.visibility = 'visible';
        // document.getElementById('stats').style.display = 'none';
        // document.getElementById('rightpadding').style.display = 'none';
        document.getElementById('shuttleworth').style.visibility = 'visible';
      }
      else
      {
        document.getElementById('leftcontainer').style.visibility = 'hidden';
        document.getElementById('menu').style.visibility = 'hidden';
        // document.getElementById('leftpadding').style.display = 'none';
        // document.getElementById('legend').style.display = 'none';

        document.getElementById('rightcontainer').style.visibility = 'hidden';
        // document.getElementById('stats').style.display = 'none';
        // document.getElementById('rightpadding').style.display = 'none';
        document.getElementById('shuttleworth').style.visibility = 'hidden';
      }
    }

    //Create a map that remembers where it was zoomed to
    function boundsChanged () {
      swap_layers();
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

    var map;
    var default_zoom = true;
    var zoom_override = false;

    if(findGetParameter("lat")!=null && findGetParameter("lon")!=null && findGetParameter("zoom")!=null) {
      map = L.map('map').setView([ findGetParameter("lat"), findGetParameter("lon") ], findGetParameter("zoom"));
      default_zoom = false;
      zoom_override = true;
    }
    else {
      b = JSON.parse(localStorage.getItem('bounds'));
      if (b == null)
      {
        map = L.map('map').setView([48.209661, 10.251494], 6);
      }
      else {
        map = L.map('map');
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

    //var map = L.map('map').setView([0, 0], 6);
    L.Control.measureControl().addTo(map);

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
    }).addTo(map);

    
    var OpenStreetMap_Mapnik_Grayscale = L.tileLayer.grayscale('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false
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


    L.control.layers({
      "Stamen TonerLite": Stamen_TonerLite,
      "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
      "Terrain": Esri_WorldShadedRelief, 
      "OSM Mapnik": OpenStreetMap_Mapnik,
      "Satellite": Esri_WorldImagery
    })
    .addTo(map);

    map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 1});

    //add popups to marker click action
    var popup = new L.Popup({"offset": [0, 0]});
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });

    // Listen for orientation changes
    window.addEventListener("orientationchange", showHideMenu(), false);
    window.onresize = showHideMenu;

    var gwMarkerIconRoundBlue = L.icon({
      iconUrl: "/resources/gateway_dot.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconRoundGreen = L.icon({
      iconUrl: "/resources/gateway_dot_green.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });
    var gwMarkerIconRoundRed = L.icon({
      iconUrl: "/resources/gateway_dot_red.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });
    var gwMarkerIconRoundYellow = L.icon({
      iconUrl: "../resources/gateway_dot_yellow.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });

    showGlobalRadar = true;
    swap_layers();

function getGateways()
{
  $.get( "/gwall.php", function( data ) {
      Object.keys(data['gateways']).forEach(function(key) {
        addGateway(data['gateways'][key]);
      });
  });
}

function getGatewaysInView()
{
  var bounds = map.getBounds();

  $.ajax
    ({
      type: "POST",
      url: '/gwbbox.php',
      dataType: 'json',
      data: JSON.stringify(bounds),
      success: function (data) {
        gwids = data["gateways"];

        for(var i=0; i<gwids.length; i++) {
          let gwid = gwids[i];

          // Add a marker on the map for the gateway
          addGateway(gwid);
        }
      }
    });
}

function addGateway(gwaddr)
{
  if(gwaddr in loadedGateways) {
    return;
  }
  else {
    $.get( "/gwdetails.php?gwaddr="+gwaddr, function( data ) {
          var gwdescriptionHead = "";
          if (data['details']['description'] != null) {
            gwdescriptionHead = "<b>"+data['details']['description']+"</b><br />"+gwaddr;
          } else {
            gwdescriptionHead = "<b>"+gwaddr+"</b>";
          }

          gwdescription = 
            '<br />Last heard at '+formatTime(data['details']['last_heard'])+
            '<br />Channels heard on: '+data['details']['channels']+
            '<br />Show only this gateway\'s coverage as: '+
            '<ul>'+
              '<li><a href=\"//ttnmapper.org/colour-radar/?gateway='+gwaddr+
                  '&type=radar&hideothers=on'+
                  '\">radar</a><br>'+
              '<li><a href=\"//ttnmapper.org/colour-radar/?gateway='+gwaddr+
                  '&type=alpha&hideothers=on'+
                  '\">alpha shape</a><br>'+
            '</ul>';

          if(data['details']['last_heard'] < (Date.now()/1000)-(60*60*1)) //1 hour
          {
            marker = L.marker([data['details']['lat'], data['details']['lon']], {icon: gwMarkerIconRoundRed});
            marker.desc = gwdescriptionHead+'<br /><br /><font color="red">Offline.</font> Will be removed from the map in 5 days.<br />'+gwdescription;
          }
          else if(data['details']['channels']<3)
          {
            //Single channel gateway
            marker = L.marker([data['details']['lat'], data['details']['lon']], {icon: gwMarkerIconRoundYellow});
            marker.desc = gwdescriptionHead+'<br /><br />Likely a <font color="orange">Single Channel Gateway.</font><br />'+gwdescription;
          }
          else
          {
            //LoRaWAN gateway
            marker = L.marker([data['details']['lat'], data['details']['lon']], {icon: gwMarkerIconRoundBlue});
            marker.desc = gwdescriptionHead+'<br />'+gwdescription;
          }

          if(gwaddr in loadedGateways) {
            return;
          } else {
            marker.addTo(map);
            oms.addMarker(marker);
            loadedGateways[gwaddr] = marker;
          }
    });
  }
}

function formatTime(timestamp)
{
  // Create a new JavaScript Date object based on the timestamp
  // multiplied by 1000 so that the argument is in milliseconds, not seconds.
  var date = new Date(timestamp*1000);
  // Hours part from the timestamp
  // var hours = "0" + date.getHours();
  // // Minutes part from the timestamp
  // var minutes = "0" + date.getMinutes();
  // // Seconds part from the timestamp
  // var seconds = "0" + date.getSeconds();

  // // Will display time in 10:30:23 format
  // var formattedTime = hours.substr(-2) + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);

  // return formattedTime;
  return date.toISOString();
}

function hideAllRadarViews()
{
  Object.keys(visibleRadarLayers).forEach(function(key) {
    map.removeLayer(visibleRadarLayers[key]);
    delete visibleRadarLayers[key];
    console.log("Removing radar "+key);
  });
}

function hideAllCircleViews()
{
  console.log("Hiding circle views: "+visibleCircleLayers);
  Object.keys(visibleCircleLayers).forEach(function(key) {
    map.removeLayer(visibleCircleLayers[key]);
    delete visibleCircleLayers[key];
    console.log("Removing circle "+key);
  });
}

function loadCircleViews()
{
  var bounds = map.getBounds();

  $.ajax
    ({
        type: "POST",
        url: '/gwbbox.php',
        dataType: 'json',
        data: JSON.stringify(bounds),
        success: function (data) {
          gwids = data["gateways"];
          console.log(gwids);

          // First hide layers that are not visible anymore
          Object.keys(visibleCircleLayers).forEach(function(key) {
            console.log("Checking "+key+" "+$.inArray(key, gwids));
            if($.inArray(key, gwids)!=-1) {
              // Keep showing the layer, or download a new one
            }
            else {
              map.removeLayer(visibleCircleLayers[key]);
              delete visibleCircleLayers[key];
              console.log("Removing "+key);
            }
          });

          for(var i=0; i<gwids.length; i++) {
            let gwid = gwids[i];

            // Add a marker on the map for the gateway
            addGateway(gwid);
            
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
                console.log("ReShowing "+gwid);
              }
            }
            else {
              $.getJSON("/geojson/"+gwid+"/circle-single.geojson", function(data){
                console.log("Loading circle layer");
                let geojsonLayerCircles = L.geoJson(data, {
                  pointToLayer: function (feature, latlng) {
                    return L.circle(latlng, feature.properties.radius, {
                      stroke: false,
                      color: feature.style.color,
                      fillColor: feature.style.color,
                      fillOpacity: 0.25
                    });
                  }
                });
                if(gwid in visibleCircleLayers) {
                } else {
                  map.addLayer(geojsonLayerCircles);
                  visibleCircleLayers[gwid] = geojsonLayerCircles;
                }
                if(gwid in loadedCircleLayers) {
                } else {
                  loadedCircleLayers[gwid] = geojsonLayerCircles;
                }
              });
            }

          }
        }
    })
}

function loadRadarsInView()
{
  var bounds = map.getBounds();

  $.ajax
    ({
        type: "POST",
        url: '/gwbbox.php',
        dataType: 'json',
        data: JSON.stringify(bounds),
        success: function (data) {
          gwids = data["gateways"];
          console.log(gwids);

          // First hide layers that are not visible anymore
          Object.keys(visibleRadarLayers).forEach(function(key) {
            console.log("Checking "+key+" "+$.inArray(key, gwids));
            if($.inArray(key, gwids)!=-1) {
              // Keep showing the layer, or download a new one
            }
            else {
              map.removeLayer(visibleRadarLayers[key]);
              delete visibleRadarLayers[key];
              console.log("Removing "+key);
            }
          });

          for(var i=0; i<gwids.length; i++) {
            let gwid = gwids[i];

            // Add a marker on the map for the gateway
            addGateway(gwid);
            
            // Layer download
            if(gwid in loadedRadarLayers) {
              //already downloaded this layer and drew it
              // Layer show/hide
              if(gwid in visibleRadarLayers) {
                // Layer already shown
              }
              else {
                loadedRadarLayers[gwid].addTo(map);
                visibleRadarLayers[gwid] = loadedRadarLayers[gwid];
                console.log("ReShowing "+gwid);
              }
            }
            else {
              // Should download layer
              $.getJSON("/geojson/"+gwid+"/alphashape.geojson", function(data){
                //console.log("Need to show layer for "+gwids[i]);
                let geojsonBlue = L.geoJson(data, 
                  {
                    stroke: false, 
                    fillOpacity: 0.25,
                    fillColor: "#0000FF",
                    zIndex: 25,
                    // filter: function (feature) {
                    //   if(feature.style.color=="blue") return true;
                    //   else return false;

                    // }
                  }
                );
                console.log(gwid+" added");
                if(gwid in visibleRadarLayers) {
                } else {
                  visibleRadarLayers[gwid] = geojsonBlue; // should add the layer to the map here and store the pointer to the layer
                  geojsonBlue.addTo(map);
                }
                if(gwid in loadedRadarLayers) {
                } else {
                  loadedRadarLayers[gwid] = geojsonBlue; // should add the geojson data to the dictionary here
                }
              });
            }


          }
        }
    })
}

  </script>
</body>
</html>
