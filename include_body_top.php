<div id="map">
  </div>
  <div id="leftcontainer">
    <div id="menu" class="dropSheet"><?php include("menu.php");?></div>
    <div id="leftpadding" style="height: 30px"></div>
    <div id="legend" class="dropSheet"><?php include("legend.html");?></div>
  </div>
  <div id="rightcontainer">
    <div id="stats" class="dropSheet"><?php /*include("stats.php");*/?></div>
    <div id="rightpadding" style="height: 30px"></div>
    <div id="shuttleworth" = class="dropSheet"><a href="http://jpmeijers.com/blog/node/22"><img src="/resources/Shuttleworth Funded.jpg" width="200px"/></a></div>
  </div>

  <!-- Google analytics-->
  <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  var GA_LOCAL_STORAGE_KEY = 'ga:clientId';

  if (window.localStorage) {
    ga('create', 'UA-75921430-1', {
      'storage': 'none',
      'clientId': localStorage.getItem(GA_LOCAL_STORAGE_KEY)
    });
    ga(function(tracker) {
      localStorage.setItem(GA_LOCAL_STORAGE_KEY, tracker.get('clientId'));
    });
  }
  else {
    ga('create', 'UA-75921430-1', 'auto');
  }

  ga('send', 'pageview');

  </script>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
  <script>
  $.ajaxSetup({ dataType: 'json' });
  </script>

  <script>L_PREFER_CANVAS = true;</script>
  <script src="/libs/leaflet/v1.0.3/leaflet.js"></script>
  <!--<script src='//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js'></script>-->
  <script src="/libs/leaflet.geojsoncss.min.js"></script>
  <script src="/libs/oms.min.js"></script>
  <script src="/libs/Leaflet.draw/dist/leaflet.draw.js"></script>
  <script src="/libs/Leaflet.MeasureControl/leaflet.measurecontrol.js"></script>
  <script src="/libs/Leaflet.awesome-markers-2.0-develop/dist/leaflet.awesome-markers.js"></script>
  <script src="/libs/leaflet-grayscale-master/TileLayer.Grayscale.js"></script>
  <script src="/libs/Leaflet.label-master/dist/leaflet.label.js"></script>
  <script src='/libs/turf.min.js'></script>