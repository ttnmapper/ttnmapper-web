<!DOCTYPE html>
<html>
<head>
  <title>TTN Mapper</title>
  <meta charset="utf-8" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="description" content="Map the coverage for gateways of The Things Network." />
  <meta name="keywords" content="ttn coverage, the things network coverage, gateway reception area, contribute to ttn" />
  <meta name="robots" content="index,follow" />
  <meta name="author" content="JP Meijers">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css"
   integrity="sha512-M2wvCLH6DSRazYeZRIm1JnYyh22purTM+FDB5CsyxtQJYeKq83arPe5wgbNmcFXGqiSH2XR8dT/fJISVA1r/zQ=="
   crossorigin=""/>
    <link rel="stylesheet" href="/libs/Leaflet.draw/dist/leaflet.draw.css" />
    <link rel="stylesheet" href="/libs/Leaflet.MeasureControl/leaflet.measurecontrol.css" />
    <link rel="stylesheet" href="http://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="/libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.css">
    <link rel="stylesheet" href="/libs/Leaflet.label-master/dist/leaflet.label.css">
    
  <style>
  body {
    padding: 0;
    margin: 0;
  }
  html, body, #map {
    height: 100%;
  }
  .leaflet-control.enabled a {
      background-color: yellow;
  }
  </style>
</head>
<body>
  <div id="map">
  </div>


  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <script>
  $.ajaxSetup({ dataType: 'json' });
  </script>

  <script>L_PREFER_CANVAS = true;</script>
  <script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js"
   integrity="sha512-lInM/apFSqyy1o6s89K4iQUKg6ppXEgsVxT35HbzUupEVRh2Eu9Wdl4tHj7dZO0s1uvplcYGmt3498TtHq+log=="
   crossorigin=""></script>
  <script src="/libs/leaflet.geojsoncss.min.js"></script>
  <script src="/libs/oms.min.js"></script>
  <script src="/libs/Leaflet.draw/dist/leaflet.draw.js"></script>
  <script src="/libs/Leaflet.MeasureControl/leaflet.measurecontrol.js"></script>
  <script src="/libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.js"></script>
  <script src="/libs/leaflet-grayscale-master/TileLayer.Grayscale.js"></script>
  <script src="/libs/Leaflet.label-master/dist/leaflet.label.js"></script>
  <script src='/libs/turf.min.js'></script>
  <script>


    var map;

    // TODO: Set the initial zoom using: 1. get parameters, 2. local storage, 3. default location
    map = L.map('map', { zoomControl:false, attributionControl: false }).setView([48.209661, 10.251494], 6);


    // https: also suppported.
    var Stamen_TonerLite = L.tileLayer('http://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.{ext}', {
      attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      subdomains: 'abcd',
      minZoom: 0,
      maxZoom: 20,
      ext: 'png'
    }).addTo(map);


    //map.attributionControl.setPrefix("Data layers &copy; TTN Mapper");
    
    //spiderfier for markers
    var oms = new OverlappingMarkerSpiderfier(map, {keepSpiderfied: true, legWeight: 2});

    //add popups to marker click action
    var popup = new L.Popup({"offset": [0, -25]});
    oms.addListener('click', function(marker) {
      popup.setContent(marker.desc);
      popup.setLatLng(marker.getLatLng());
      map.openPopup(popup);
    });


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

    var gwMarkerIconOffline = L.icon({
      iconUrl: "/resources/TTNraindropJPM45pxRed.png",
      shadowUrl: "/resources/marker-shadow.png",

      iconSize:     [45, 45], // size of the icon
      shadowSize:   [46, 46], // size of the shadow
      iconAnchor:   [22, 45], // point of the icon which will correspond to marker\'s location
      shadowAnchor: [16, 46],  // the same for the shadow
      popupAnchor:  [23, 25] // point from which the popup should open relative to the iconAnchor
    });
    
    var coveragetiles = L.tileLayer('http://dev.ttnmapper.org/tms/gateway_heatmap/<?php echo $_REQUEST['gwaddr']; ?>/{z}/{x}/{y}.png', {
      maxNativeZoom: 18,
      maxZoom: 20,
      zIndex: 10,
      opacity: 0.5
    });
    coveragetiles.addTo(map);

  </script>
</body>
</html>
