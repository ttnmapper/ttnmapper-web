<!DOCTYPE html>
<html>
<head>
  <title>TTN Mapper</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  <script>
  $(function() {
    //$( "#datepicker" ).datepicker();
    $('#datepicker').datepicker({ dateFormat: 'yy-mm-dd'});
    $('#datepicker').datepicker('setDate', 'today');
  });
  </script>
  
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
  
</head>
<body style="padding-bottom: 50px;">
  <?php include "menu_horizontal.php"; ?>
  
  <h2>Special Maps</h2>

  <h3>Specific node</h3>
  
  Draw circles or radials for every measurement made by a specific device on a specific day.<br /><br />

  <form action="special.php" method="get">
  Device ID:<br />
  <input type="text" name="node" placeholder="my-device-id">
  <input type="checkbox" name="allnodes" value="on"> show data for all devices
  <br />
  Date:<br />
  <input type="text" name="date" id="datepicker">
  <input type="checkbox" name="alldates" value="on"> show data since the beginning of time (a lot of data - <b>this will crash your browser</b>)
  <br />
  <!--
  <br />
  <input type="radio" name="type" value="placemarks" checked="checked"> Circles<br>
  <input type="radio" name="type" value="radials"> Radials<br>
  <br />-->
  <input type="checkbox" name="gateways" value="on" checked="checked"> Show markers for gateways<br />
  <button type="submit" name="csv" formaction="csv_node_date.php">CSV</button>
  <input type="submit" value="View map">
  </form>

  <h3>Experiment</h3>
  <form action="experiments/list_all.php" method="get">
  <input type="submit" value="List all experiments">
  </form>

  <h3>Radar</h3>
  This is the old TTN Mapper style. Use with caution as this will slow down your browser.
  <form action="/colour-radar/" method="get">
  <input type="text" hidden="true" name="radials" value="on">
  <input type="submit" value="View radials">
  </form>

  <h3>Areas - alpha shapes</h3>
  Plot areas of measured coverage. This is done using alpha shapes.
  <form action="/alphashapes/" method="get">
  <input type="text" hidden="true" name="areas" value="on">
  <input type="submit" value="View only areas">
  </form>
<!--   <form action="/colour-radar/" method="get">
  <input type="text" hidden="true" name="radials" value="on">
  <input type="text" hidden="true" name="areastoo" value="on">
  <input type="submit" value="View areas and radials">
  </form> -->

<!--
  <h3>Areas - radar plot</h3>
  Plot areas of measured coverage. This is done using alpha shapes.
  <form action="/" method="get">
  <input type="text" hidden="true" name="radar" value="on">
  <input type="submit" value="View only areas">
  </form>
  <form action="/" method="get">
  <input type="text" hidden="true" name="radials" value="on">
  <input type="text" hidden="true" name="radartoo" value="on">
  <input type="submit" value="View areas and radials">
  </form>
  -->

  <h3>Circles</h3>
  Draw circles to indicate the radius a gateway can reach.
  <form action="/circles.php" method="get">
  <input type="submit" value="View circles">
  </form>

  <h3>All gateways</h3>
  View the coverage map with placemarks for all TTN gateways.
  <form action="/colour-radar/" method="get">
  <input type="text" hidden="true" name="gwall" value="1">
  <input type="submit" value="View map">
  </form>

  <h3>No gateways</h3>
  View the coverage map without any gateway markers.
  <form action="/colour-radar/" method="get">
  <input type="text" hidden="true" name="gwall" value="none">
  <input type="submit" value="View map">
  </form>

  <h3>Pade.nl</h3>
  Overlay the coverage map with the data from http://pade.nl/lora/. Most of this data is already merged into the TTNmapper dataset. This function overlays a copy of Pade's raw data onto the processed TTNmapper data. <b>This will slow down your browser!</b>
  <form action="/colour-radar/" method="get">
  <input type="text" hidden="true" name="pade" value="1">
  <input type="submit" value="View map">
  </form>
  <br />
  View TTNmapper's data in the same format as Pade.nl. In other words, draw circles of which the colour is a representation of the signal-to-nise ratio.
  <form action="pade_format.php" method="get">
  <input type="checkbox" name="gateways" value="on"> Show markers for gateways<br />
  <input type="submit" value="View map">
  </form>
  
  <h3>Moved gateways</h3>
  View a list of gateways that have moved.
  <form action="/gateway_moves.php" method="get">
  <input type="submit" value="View list">
  </form>
  
  
</body>
</html>
