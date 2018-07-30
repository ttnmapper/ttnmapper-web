    function showHideMenu()
    {
      if(window.innerWidth >= 800 && window.innerHeight >= 600){
        document.getElementById('leftcontainer').style.visibility = 'visible';
        document.getElementById('rightcontainer').style.visibility = 'visible';
        document.getElementById('menu').style.visibility = 'visible';
        document.getElementById('legend').style.visibility = 'visible';
        document.getElementById('stats').style.visibility = 'visible';
      }
      else
      {
        document.getElementById('menu').style.visibility = 'hidden';
        document.getElementById('legend').style.visibility = 'hidden';
        document.getElementById('stats').style.visibility = 'hidden';
        document.getElementById('leftcontainer').style.visibility = 'hidden';
        document.getElementById('rightcontainer').style.visibility = 'hidden';
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

    var OpenStreetMap_HOT = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>',
      fadeAnimation: false
      });

    var OpenStreetMap_BlackAndWhite = L.tileLayer('https://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      fadeAnimation: false
    });

    // var OpenMapSurfer_Grayscale = L.tileLayer('http://korona.geog.uni-heidelberg.de/tiles/roadsg/x={x}&y={y}&z={z}', {
    // maxZoom: 19,
    // attribution: 'Imagery from <a href="http://giscience.uni-hd.de/">GIScience Research Group @ University of Heidelberg</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    // });
    
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
      //"OSM B&W": OpenStreetMap_BlackAndWhite, 
      "OSM Mapnik Grayscale": OpenStreetMap_Mapnik_Grayscale,
      "Terrain": Esri_WorldShadedRelief, 
      "OSM Mapnik": OpenStreetMap_Mapnik,
      "Satellite": Esri_WorldImagery
    })
    .addTo(map);

    map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 2});

    //add popups to marker click action
    var popup = new L.Popup({"offset": [0, -25]});
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });

    // Listen for orientation changes
    window.addEventListener("orientationchange", showHideMenu(), false);
    window.onresize = showHideMenu;

    // map.locate({setView: true, maxZoom: 16});
    // function onLocationFound(e) {
    //     var radius = e.accuracy / 2;

    //     L.marker(e.latlng).addTo(map)
    //         .bindPopup("You are within " + radius + " meters from this point").openPopup();

        // L.circle(e.latlng, radius).addTo(map);

    // }

    // map.on('locationfound', onLocationFound);

    // function onLocationError(e) {
    //     alert(e.message);
    // }

    // map.on('locationerror', onLocationError);


    var gwMarkerIcon = L.AwesomeMarkers.icon({
      icon: "ion-load-b",
      prefix: "ion",
      markerColor: "blue"
    });

    var gwMarkerIcon = L.icon({
      iconUrl: "/resources/TTNraindropJPM45px.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconSCG = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxOrange.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconNoData = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxGreen.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconOffline = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxRed.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });

    var gwMarkerIconRound = L.icon({
      iconUrl: "/resources/gateway_dot.png",

      iconSize:     [10, 10], // size of the icon
      iconAnchor:   [5, 5], // point of the icon which will correspond to marker\'s location
      popupAnchor:  [5, 5] // point from which the popup should open relative to the iconAnchor
    });

    map.on('click', function(e) {
      console.log("Clicked: " + e.latlng.lat + ", " + e.latlng.lng);
    });