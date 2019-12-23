<!DOCTYPE html>
<html>
<head>
  <?php
  include("include_head.php");
  ?>
</head>
<body>
  <?php
  include("include_body_top.php");
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

    var previousZoom = 0;
    var layerSwapZoomLevel = 10;
    
    function swap_layers()
    {
      if(map)
      {
        if(showGlobalRadar && map.getZoom() <= layerSwapZoomLevel) //7
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

    var map;
    var default_zoom = true;
    var zoom_override = false;

<?php
//give priority to url parameters for initial location
//and then fail over to cookie
//or else use defautl amsterdam zoom
    if (isset($_REQUEST['lat']) and isset($_REQUEST['lon']) and isset($_REQUEST['zoom']))
    {
      echo "
    map = L.map('map').setView([".$_REQUEST['lat'].",".$_REQUEST['lon']."],".$_REQUEST['zoom'].");
    default_zoom = false;
    zoom_override = true;
    ";
    }
    else {
      echo "
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
      ";
    }
?>


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
      iconUrl: "/resources/gateway_dot_yellow.png",

      iconSize:     [20, 20], // size of the icon
      iconAnchor:   [10, 10], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [10, 10] // point from which the popup should open relative to the iconAnchor
    });
    

<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

$username = $settings['database_mysql']['username'];
$password = $settings['database_mysql']['password'];
$dbname = $settings['database_mysql']['database'];
$servername = $settings['database_mysql']['host'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if(!isset($_REQUEST['gwall']) or (isset($_REQUEST['gwall']) and $_REQUEST['gwall']!="none"))
    {
      $stmt;
      if(isset($_REQUEST['gwall']) and ($_REQUEST['gwall']==1 or $_REQUEST['gwall']=="on") )
      {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateway_updates"); 
      }
      else
      {
        $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) FROM gateways_aggregated WHERE `last_heard` > (NOW() - INTERVAL 5 DAY)"); 
      }
      $stmt->execute();

      // set the resulting array to associative
      $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
      foreach($stmt->fetchAll() as $k=>$v) {
        $sqlloc = $conn->prepare("SELECT * FROM gateways_aggregated WHERE gwaddr=:gwaddr");
        $sqlloc->bindParam(':gwaddr', $v['gwaddr']);
        $sqlloc->execute();
        $sqlloc->setFetchMode(PDO::FETCH_ASSOC);
        $locresult = $sqlloc->fetch();
        
        $channel_count = $locresult["channels"];

        $gwdescriptionHead = "";

        if ($locresult['description'] != null) {
          $gwdescriptionHead = sprintf("<b>%s</b><br />%s",
            htmlentities($locresult['description']),
            htmlentities($v['gwaddr']));
        } else {
          $gwdescriptionHead = sprintf("<b>%s</b>",
            htmlentities($v['gwaddr']));
        }

        $gwdescription = 
        '<br />Last heard at '.$locresult['last_heard'].
        '<br />Channels heard on: '.$channel_count.
        '<br />Show only this gateway\'s coverage as: '.
        '<ul>'.
          '<li><a href=\"//ttnmapper.org/colour-radar/?gateway[]='.urlencode($gwaddr).
              '\">radar</a><br>'.
          '<li><a href=\"//ttnmapper.org/alpha-shapes/?gateway[]='.urlencode($gwaddr).
              '\">alpha shape</a><br>'.
        '</ul>';
        
        if(strtotime($locresult['last_heard']) < time()-(60*60*1)) //1 hour
        {
          echo '//'.strtotime($locresult['last_heard']).' < '.time().'-'.(60*24*1).'\n;';
          echo '
          marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconRoundRed});
          marker.desc = "'.$gwdescriptionHead.'<br /><br /><font color=\"red\">Offline.</font> Will be removed from the map in 5 days.<br />'.$gwdescription.'";
          ';
        }
        else if($channel_count<3)
        {
          //Single channel gateway
          echo '
          marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconRoundYellow});
          marker.desc = "'.$gwdescriptionHead.'<br /><br />Likely a <font color=\"orange\">Single Channel Gateway.</font><br />'.$gwdescription.'";
        ';
        }
        else
        {
          //LoRaWAN gateway
          echo '
          marker = L.marker(['.$locresult['lat'].', '.$locresult['lon'].'], {icon: gwMarkerIconRoundBlue});
          marker.desc = "'.$gwdescriptionHead.'<br />'.$gwdescription.'";
          ';
        }

        echo '
          marker.addTo(map);
          oms.addMarker(marker);
        ';
        
      }
    }

}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>

  showGlobalRadar = true;
  swap_layers();

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
        url: 'gwbbox.php',
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
                //geojsonLayerCircles.addTo(map);
                map.addLayer(geojsonLayerCircles);
                loadedCircleLayers[gwid] = geojsonLayerCircles;
                visibleCircleLayers[gwid] = geojsonLayerCircles;
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
        url: 'gwbbox.php',
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
              $.getJSON("/geojson/"+gwid+"/radar.geojson", function(data){
                //console.log("Need to show layer for "+gwids[i]);
                let geojsonBlue = L.geoJson(data, 
                  {
                    stroke: false, 
                    fillOpacity: 0.25,
                    fillColor: "#0000FF",
                    zIndex: 25
                    // filter: function (feature) {
                    //   if(feature.style.color=="blue") return true;
                    //   else return false;

                    // }
                  }
                );
                console.log(gwid+" added");
                visibleRadarLayers[gwid] = geojsonBlue; // should add the layer to the map here and store the pointer to the layer
                loadedRadarLayers[gwid] = geojsonBlue; // should add the geojson data to the dictionary here
                geojsonBlue.addTo(map);
              });
            }


          }
        }
    })
}

  </script>
</body>
</html>
