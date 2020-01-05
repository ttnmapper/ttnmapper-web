<!DOCTYPE html>
<html lang="en">
<head>
<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

if(isset($settings['theming']['site_name'])) {
  $siteName = $settings['theming']['site_name'];
} else {
  $siteName = "TTN Mapper";
}

if(isset($settings['theming']['site_description'])) {
  $siteDescription = $settings['theming']['site_description'];
} else {
  $siteDescription = "Map the coverage for gateways of The Things Network.";
}

if(isset($settings['theming']['brand_icon'])) {
  $brandIcon = "/resources/".$settings['theming']['brand_icon'];
} else {
  $brandIcon = "/favicons/favicon-96x96.png";
}

if(isset($settings['theming']['brand_name'])) {
  $brandName = $settings['theming']['brand_name'];
} else {
  $brandName = "TTN Mapper";
}

if(isset($settings['analytics']['site_id'])) {
  $googleAnalyticsSiteId = $settings['analytics']['site_id'];
} else {
  $googleAnalyticsSiteId = "UA-75921430-1";
}


?>

  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Map the coverage for gateways of The Things Network." />
  <meta name="keywords" content="ttn coverage, the things network coverage, gateway reception area, contribute to ttn" />
  <meta name="robots" content="index,follow" />
  <meta name="author" content="JP Meijers">

  <title><?php echo $siteName; ?></title>

  <!--favicons-->
  <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/favicons/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/favicons/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/favicons/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/favicons/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/favicons/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/favicons/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/favicons/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/favicons/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/favicons/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
  <link rel="manifest" href="/favicons/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/favicons/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">


  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous" />

  <!-- Page theme -->
  <link rel="stylesheet" href="/theme.css" />

  <!-- Leaflet -->
  <link rel="stylesheet" href="/libs/leaflet/leaflet.css" />
  <link rel="stylesheet" href="/libs/Leaflet.markercluster/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="/libs/Leaflet.markercluster/dist/MarkerCluster.Default.css" />
  <link rel="stylesheet" href="/libs/leaflet.measure/leaflet.measure.css" />

  <style>
    html {
      height: 100%;
    }
    body {
      height: 100%;
    }
    #map {
      flex-grow : 1;
    }
  </style>

</head>
<body>



<!-- Image and text -->
<nav class="navbar navbar-fixed-top navbar-expand-lg navbar-light bg-light">
  
  <a class="navbar-brand" href="/">
    <img src="<?php echo $brandIcon; ?>" width="auto" height="32" class="d-inline-block align-top" alt="">
    <?php echo $brandName; ?>
  </a>
  
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
    <ul class="navbar-nav mr-auto">
      <?php
      if(!isset($settings['menu']['menu_advanced']) or $settings['menu']['menu_advanced'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/advanced-maps/">Advanced maps</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_heatmap']) or $settings['menu']['menu_heatmap'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/heatmap/">Heatmap (beta)</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_colour_radar']) or $settings['menu']['menu_colour_radar'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/colour-radar/">Colour Radar</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_area_plot']) or $settings['menu']['menu_area_plot'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/alpha-shapes/">Area Plot</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_leaderboard']) or $settings['menu']['menu_leaderboard'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/leaderboard/">Leader board</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_acknowledgements']) or $settings['menu']['menu_acknowledgements'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/acknowledgements/">Acknowledgements</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_faq']) or $settings['menu']['menu_faq'] == true) {
      ?>
      <li class="nav-item active">
        <a class="nav-link" href="/faq/">FAQ</a>
      </li>
      <?php
      }
      ?>
    </ul>
  </div>

  <div class="navbar-collapse collapse w-100 order-3 dual-collapse2">
    <ul class="navbar-nav ml-auto">
      <?php
      if(!isset($settings['menu']['teespring']) or $settings['menu']['teespring'] == true) {
      ?>
      <li class="nav-item mr-2">
        <a class="nav-link" href="https://teespring.com/ttnmapper">
          <img src="/resources/teespring.svg" height="25" class="d-inline-block align-middle" alt="" title="Teespring">
          Get the T-Shirt
        </a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['patreon']) or $settings['menu']['patreon'] == true) {
      ?>
      <li class="nav-item">
        <a href="https://www.patreon.com/ttnmapper" data-patreon-widget-type="become-patron-button"><img src="/resources/become_a_patron_button@2x.png" class="d-inline-block align-middle" alt="" height="36" title="Patreon"></a>
      </li>
      <?php
      }
      ?>
    </ul>
  </div>

</nav>



<div class="container">
  <h1 class="mt-4">FAQ</h1>

<div class="card mt-4">
  <h5 class="card-header">License</h5>
  <div class="card-body">
    <p>All data from TTN Mapper (ttnmapper.org) should be considered under the PDDL v 1.0 license (<a href="http://opendatacommons.org/licenses/pddl/1.0/">http://opendatacommons.org/licenses/pddl/1.0/</a>).</p>

    <p>The source code for the TTN Mapper system is open source and <a href="https://github.com/ttnmapper">available on Github</a>.</p>
  </div>
</div>


<div class="card mt-4">
  <h5 class="card-header">How can I contribute data?</h5>
  <div class="card-body">
    <h4>Using any node and a smartphone</h4>
    <ol>
      <li>A node transmitting to The Things Network. An Arduino with a RN2483 radio is ideal. Have a look at <a href="https://github.com/jpmeijers/RN2483-Arduino-Library">this library</a> for example code and wiring instructions for the RN2483 and Arduino. Your node can however be any hardware and run any code, as long as you know the address the device use to transmit data to The Things Network.</li>

      <li>An Android phone running the TTN Mapper App available <a href="https://play.google.com/store/apps/details?id=org.ttnmapper.phonesurveyor">on the playstore</a>. -OR- An iPhone running the TTN Mapper app available from the iTunes store.</li>
    </ol>

    <p>Also see <a href="https://www.thethingsnetwork.org/labs/story/using-ttnmapper-on-android">this lab</a> for more details.</p>

    <h4>Using a node with a GPS, transmitting GPS coordinates</h4>
    <p><a href="https://www.thethingsnetwork.org/docs/applications/ttnmapper/">See the documentation here.</a></p>

    <p>If you have a GPS tracker that sends its own location in the payload of the LoRa packet, it is as easy as enabling the TTN Mapper integration to contribute data to the coverage map.</p>

    <p>The assumption is that your end device with a GPS on it sends at least its latitude and longitude, but preferably also its altidude and HDOP values. If HDOP is not available, the accuracy of the GPS fix in metres will be accepted. As a last resort the satellite count can be used.</p>

    <p>Make sure you have a Payload decoder function enabled on the TTN Console that decodes the raw payload into a json object. The JSON object should contain the keys "<b>latitude</b>", "<b>longitude</b>" and "<b>altitude</b>" and one of "<b>hdop</b>", "accuracy" or "sats". When using the Cayenne LPP data format, the GPS coordinates will be decoded into a different JSON format, but this format is also supported. Cayenne LPP does not contain a GPS accuracy, and therefore this data will be considered as inferior and will carry less weight in calculation of coverage, and will be deleted first during data cleanup.</p>

    <p>On the TTN Console, open your application and then click on Integrations. Search for the TTN Mapper integration and click on it. 
      <ul>
        <li>Process ID: fill in a unique string describing this integration. The value does not have an influence on the correct functionality of the integration.</li>
        <li>E-mail address: fill in a valid email address. This email address will be used in the future to associate all data to you, and guarantee the quality of the data.</li>
        <li>Port filter: This text field can be left <b>empty</b> in the most cases. If you are using your application for multiple purposes, and only send GPS coordinates on one specific port, you can use this field to specify the port on which the GPS coordinates are sent.</li>
        <li>Experiment name: This text field can be left <b>empty</b> in the most cases. If you are measuring coverage that is out of the ordinary, like taking a GPS tracker on an <b>aeroplane</b>, strapping it to a <b>balloon</b> or <b>drone</b>, or climbing a <b>high mast</b> or <b>tower</b> that is not publicly accessible, your data should be logged to an experiment to keep it separated from the main TTN Mapper global coverage map.</li>
      </ul>
    </p>

    <p>For more info see the following articles:
      <ul>
        <li><a href="https://www.hackster.io/Amedee/the-things-network-node-for-ttnmapper-org-a8bcd4">Hackster.io - The Things Network Node for TTNmapper.org</a></li>
        <li><a href="https://github.com/ricaun/esp32-ttnmapper-gps">ESP32 TTN Mapper GPS</a></li>
        <li><a href="https://www.thethingsnetwork.org/labs/story/payload-decoder-for-adeunis-field-test-device-ttn-mapper-integration">Adeunis Field Test device</a></li>
        <li><a href="https://github.com/ttnmapper/gps-node-examples">Other examples</a></li>
      </ul>
    </p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">When should I use an "experiment" to map my coverage</h5>
  <div class="card-body">

    <p>An experiment is a way to keep unrealistic coverage measurements away from the main map. Experiments should be used when coverage is mapped from aeroplanes, balloons or any similar unrealistic altitudes.</p>

    <p>In other words logging to the main map should only be done from roughly 0.5m-2m above ground level. "Ground level" should be interpreted as any place easily accessible by a human - or any place where an IoT device would commonly be installed. The top of a skyscraper is only acceptable if the skyscraper has a viewing deck that is publicly accessible. Man made hills and natural mountains are acceptable. The roof of a car or small delivery truck is fine. The roof of a bus or 14 wheeler truck is not as that is not a average acceptable height at which a sensor will be installed. The dashboard of a truck or bus is however roughly 2m above ground and therefore acceptable.</p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">Which spreading factor should I transmit on?</h5>
  <div class="card-body">

    <p>We recommend using SF7. This provides us with a map of "worst case" coverage. Due to the 1% duty cycle limit on transmissions, using the fastest spreading factor will maximize the number of measurements taken.</p>

    <p>From experimentation it has been seen that slower spreading factors (like SF12) does not perform well when the device is mobile. This is likely due to path fading from obstacles in the radio path, causing bursts of interference (erasures) which the forward error correction can not deal with. When you are doing measurements while standing still higher spreading factors (SF9-SF12) will work, but when mobile it's better to use SF7.</p>
  </div>
</div>




<div class="card mt-4">
  <h5 class="card-header">My results are not showing up on the map</h5>
  <div class="card-body">

    <p>New measurements take up to 24 hours to be processed and displayed on the main map. If you want to see if your data is being uploaded, use the advanced map options and filter by your device ID.</p>

    <p>It is possible that the gateway that received your packets does not have its location configured. If you know the gateway owner, please ask them to configure it. See "My gateway is not showing on TTN Mapper". If this is the reason for your measurements not showing, you can check under Advanced Maps, enter your node address, and view the map. If you now see circles without lines connecting them to a gateway, this issue is confirmed.</p>

    <p>Did you mark your data as experimental? If so, the data will not show on the main map, but on a separate map containing only your experiment. Please disable experimental mode in the app so that you can contribute to the global map of TTN coverage.</p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">While I was measuring I saw a lot of points on the app for locations I measured, but only a few points are shown on your map. Did some of the data points go missing, or not upload?</h5>
  <div class="card-body">

    <p>The most likely reason is that the GPS accuracy of your phone or device was not good enough. Make sure you have a location accuracy of less than 10 meters, or an HDOP of less than 2.</p>
    <p>Data logged to an experiment will not be displayed on the main map. Use the experiments section to display your experiment's data. Experiments have less strict location accuracy filters.</p>
    <p>It is possible that some datapoints did not upload correctly to our server because your internet connection was intermittent. This is however unlikely as a working internet connection is needed to receive data packets from TTN.</p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">My results were showing on the map before, but is gone now.</h5>
  <div class="card-body">

    <p>When a gateway moves more than 100 meters it is seen as a new installation with different coverage. All old coverage data is hidden because the old coverage is now invalid.</p>

    <p>In a very few circumstances obvious incorrect measurements might be deleted from the map.</p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">My gateway is not showing on TTN Mapper</h5>
  <div class="card-body">

    <p>For a gateway to appear on TTN Mapper its location needs to be known, and it needs to have at least one coverage mapping point uploaded to TTN Mapper. In other words if a gateway has not been measured yet, the gateway will not appear on TTN Mapper.</p>

    <p>Make sure your gateway's GPS coordinates are configured correctly on the TTN Console. Also make sure fake_gps is disabled in the gateway's local_conf.json file. </p>

    <p>TTN Mapper will use the location configured on the TTN Console first. If the location is not configured there it will use the location which the gateway reports. That means that a gateway with a built in GPS will be located at the location set on the Console, not the location the gateway reports. Only if the location is not set on the Console the fallback location - the location reported by the gateway - will be used.</p>

  <!--   <p>Debugging:
      <ol>
        <li>Have a read through <a href="https://www.thethingsnetwork.org/forum/t/gateway-gps-configuration-in-local-conf-json/6176">this forum topic</a> to understand where a gateway's location comes from.</li>
        <li>Check on the TTN NOC to see what location is being reported for your gateway. TTN Mapper uses this NOC API to obtain gateway locations, so if the location is correct here it will propagate down and be updated on TTN Mapper in a matter of hours. Example: http://noc.thethingsnetwork.org:8085/api/v2/gateways/eui-b827ebfffe608736 for the gateway with EUI B827EBFFFE608736, and http://noc.thethingsnetwork.org:8085/api/v2/gateways/stellenbosch-technopark for the gateway with ID stellenbosch-technopark. Replace the EUI or ID with your gateway's identifier.</li>
        <li>Make sure TTN Mapper has received any data for your gateway. Example: http://ttnmapper.org/gateways/packets.php?gwaddr=001122334455667788</li>
      </ol>
    </p> -->
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">Can I integrate your data into my map? <br /> Can I download my data which I uploaded to you?<br /> Public API access. </h5>
  <div class="card-body">

    <p>The GEOJSON files used to draw the TTN Mapper maps are publicly available at <a href="http://ttnmapper.org/geojson">http://ttnmapper.org/geojson</a>.</p>

    <p>Data is sometimes dumped into CSV files. The dumps are available at <a href="http://ttnmapper.org/dumps">http://ttnmapper.org/dumps</a>. If you want newer data than what there are dumps of, please contact me.</p>

    <p>No public API access is given to the raw data. This is due to the amount of data and number of possible queries that won't be possible to handle on the server. Please use the dumps and your own database. </p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">I want you to remove my gateway or measurement points from your map (privacy, etc)</h5>
  <div class="card-body">

    <p>If you change your gateway's location to another one more than 100m away, any old measurements for your gateway will be hidden automatically.</p>

    <p>In any other circumstance, contact us.</p>
  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">How can I support the project</h5>
  <div class="card-body">

    <p>One way of supporting the project is by contributing to the open source code on <a href="https://github.com/ttnmapper">Github</a>.</p>

    <p>The project however has a monthly running cost which was covered by The Shuttleworth Foundation up to 2018. At this point there is no financial support from any official sources. If you want to financially support the project there are a couple of methods:</p>

    <ul>
      <li><a href="https://www.patreon.com/ttnmapper">Patreon</a></li>
      <li>Any Patreon tier as a support contract with commercial invoice</li>
      <li>Once off donation via PayPal</li>
      <li>Once off donation into a European bank account (IBAN)</li>
    </ul>

    <p>For details contact us.</p>
  </div>
</div>


<div class="card mt-4">
  <h5 class="card-header">My question is not answered here</h5>
  <div class="card-body">

    <ul>
      <li>Ask your question in the #ttnmapper channel on the TTN Slack.</li>
      <li>Send me a message to
        <script type="text/javascript">
        document.write(atob("PGEgaHJlZj0ibWFpbHRvOmluZm8tZnJvbS13ZWJzaXRlLWZhcUB0dG5tYXBwZXIub3JnIj5pbmZvQHR0bm1hcHBlci5vcmc8L2E+IDxhIGhyZWY9Imh0dHA6Ly93d3cud2ViZXN0b29scy5jb20vYW50aXNwYW0tZW1haWwtcHJvdGVjdGlvbi1vYmZ1c2NhdGUtZW1haWwtYWRkcmVzcy13ZWJzaXRlLWNvZGUtc3BhbS1qYXZhc2NyaXB0LWJhc2U2NC1lbWFpbC1lbmNvZGluZy1jcnlwdC5odG1sIiB0aXRsZT0iQW50aS1TUEFNIFByb3RlY3Rpb24iPjxpbWcgc3JjPSIvL3VwbG9hZC53aWtpbWVkaWEub3JnL3dpa2lwZWRpYS9jb21tb25zLzIvMjgvU2VtaV9wcm90ZWN0LnN2ZyIgd2lkdGg9MTQgaGVpZ2h0PTE0IC8+PC9hPg=="));
        </script>
      </li>
    </ul>

  </div>
</div>


    <p><a href="http://jpmeijers.com/blog/node/3">TTN Mapper is a JPMeijers.com creation.</a></p>

  </div>
</div>


  <!-- Google analytics-->
  <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  var GA_LOCAL_STORAGE_KEY = 'ga:clientId';

  if (window.localStorage) {
    ga('create', '<?php echo $googleAnalyticsSiteId; ?>', {
      'storage': 'none',
      'clientId': localStorage.getItem(GA_LOCAL_STORAGE_KEY)
    });
    ga(function(tracker) {
      localStorage.setItem(GA_LOCAL_STORAGE_KEY, tracker.get('clientId'));
    });
  }
  else {
    ga('create', '<?php echo $googleAnalyticsSiteId; ?>', 'auto');
  }

  ga('send', 'pageview');

  </script>

  <!-- Bootstrap -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

  <!-- Leaflet -->
  <script src="/libs/leaflet/leaflet.js"></script>
  <script src="/libs/leaflet.measure/leaflet.measure.js"></script>
  <script src="/libs/Leaflet.markercluster/dist/leaflet.markercluster.js"></script>

  <!-- HTML entity escaping -->
  <script src="/libs/he/he.js"></script>

  <!-- The map style -->
  <script type="text/javascript" src="/theme.php"></script>
  <script type="text/javascript" src="/common.js"></script>
  <!-- The actual main logic for this page -->
  <!-- <script src="index-logic.js"></script> -->

</body>
</html>
