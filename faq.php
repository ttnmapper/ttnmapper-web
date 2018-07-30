<!DOCTYPE html>
<html>
<head>
  <title>TTN Mapper</title>
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
<body>
<?php include "menu_horizontal.php"; ?>

<h2>FAQ</h2>

<h3>License</h3>
<p>All data from TTNmapper.org should be considered under the PDDL v 1.0 license (<a href="http://opendatacommons.org/licenses/pddl/1.0/">http://opendatacommons.org/licenses/pddl/1.0/</a>).

<h3>How can I contribute?</h3>
<h4>Using any node and a smartphone (prefered method)</h4>
<ol>
<li>A node transmitting to The Things Network. An Arduino with a RN2483 radio is ideal. Have a look at <a href="https://github.com/jpmeijers/RN2483-Arduino-Library">my github</a> for example code and wiring instructions for the RN2483 and Arduino. Your node can however be any hardware and run any code, as long as you know the address the device use to transmit data to The Things Network.</li>

<li>An Android phone running the TTN Mapper App available <a href="https://play.google.com/store/apps/details?id=com.jpmeijers.ttnmapper">on the playstore</a>. -OR- An iPhone running the TTN Mapper app available from the iTunes store. -OR- By using the online javascript client available at <a href="https://www.lora-mapper.org/client">https://www.lora-mapper.org/client</a> (still in BETA testing)
</ol>

<h4>Using a node with a GPS, transmitting GPS coordinates (like a Sodaq One)</h4>
<p>If you have a GPS tracker that sends its own location in the payload of the LoRa packet, it is as easy as enabling the TTN Mapper integration to contribute data to the coverage map.

<p>The assumption is that your end device with a GPS on it sends at least its latitude and longitude, but preferably also its altidude and HDOP values. If HDOP is not available, the accuracy of the GPS fix in metres will be accepted. As a last resort the satellite count can be used.

<p>Make sure you have a Payload decoder function enabled on the TTN Console that decodes the raw payload into a json object. The JSON object should contain the keys "<b>latitude</b>", "<b>longitude</b>" and "<b>altitude</b>" and one of "<b>hdop</b>", "accuracy" or "sats". When using the Cayenne LPP data format, the GPS coordinates will be decoded into a different JSON format, but this format is also supported. Cayenne LPP does not contain a GPS accuracy, and therefore this data will be considered as inferior and will carry less weight in calculation of coverage, and will be deleted first during data cleanup.

<p>On the TTN Console, open your application and then click on Integrations. Search for the TTN Mapper integration and click on it. 
<ul>
<li>Process ID: fill in a unique string describing this integration. The value does not have an influence on the correct functionality of the integration.</li>
<li>E-mail address: fill in a valid email address. This email address will be used in the future to associate all data to you, and guarantee the quality of the data.</li>
<li>Port filter: This text field can be left <b>empty</b> in the most cases. If you are using your application for multiple purposes, and only send GPS coordinates on one specific port, you can use this field to specify the port on which the GPS coordinates are sent.</li>
<li>Experiment name: This text field can be left <b>empty</b> in the most cases. If you are measuring coverage that is out of the ordinary, like taking a GPS tracker on an <b>aeroplane</b>, strapping it to a <b>balloon</b> or <b>drone</b>, or climbing a <b>high mast</b> or <b>tower</b> that is not pubically accessible, your data should be logged to an experiment to keep it seperated from the main TTN Mapper global coverage map.</li>
</ul>
<!--
<h5>MQTT import</h5>
I will listen for your node on MQTT and import measurement as I receive packets from your device. Your node needs to at least transmit latitude, longitude, altitude and hdop. Then send me your:
<ul>
<li>Application ID (MQTT username)
<li>Access Key (MQTT password)
<li>Device ID (or tell me if I should listen to all devices in your application)
<li>Region (e.g. eu). Region is the last part of the handler you registered your application to.
<li>A description of your payload format. You can follow the same payload format as the Sodaq One universal tracker, or use my even more compressed format which is used <a href="https://github.com/jpmeijers/RN2483-Arduino-Library/blob/master/examples/SodaqOne-TTN-Mapper-binary/SodaqOne-TTN-Mapper-binary.ino">in this example</a>.
</ul>

<h5>Using the TTN HTTP integration V2 (beta)</h5>
If you have a location set for your device on the TTN Console, that location will take priority and all other coordinates in your payload will be ignored.

You will need to have a payload decoder function enabled for your application on the TTN Console. The output of the decoder should contain "latitude", "longitude", "altitude" and "hdop", "accuracy" or "satellites". The abbreviations of these keys are also accepted. Cayenne LPP format is also accepted.

Enable the TTN HTTP integration for your application from the TTN Console. 
<ul>
<li>Access Key: default key (or create a dedicated one)
<li>URL: https://integrations.ttnmapper.org/ttn/v2/
<li>Method: POST
<li>Authorization: A valid email address. Your data will be associated with this email address. When incorrect data is seen on the map, data will be deleted from the map. In some cases where doubt exist about the quality of the data, this email address will be used to query it.
</ul>

Setting the Custom Header Name is optional and can be used for advanced features.
<ul>
<li>Custom Header Name: ttnmapper-extra</li>
<li>Custom Header Value: a <a href="https://en.wikipedia.org/wiki/Query_string">query string</a> containing any of the following:
  <ul>
    <li>port - only import packets that are sent on this port</li>
    <li>experiment - If you want to log your data to an experiment, rather than to the main map, set the experiment name here.</li>
    <li>provider - A string description of the device that you are using. This must reflect the source of the location. If you are using WiFi localisation, call it something that will reflect that the location was calculated using WiFi.</li>
  </ul>
</li>
<li>Example query strings:
  <ul>
    <li>Only log packets on port 1 to an experiment, giving the provider name of the device: port=1&experiment=My experiment Name&provider=My home built gps tracker</li>
  </ul>
</ul>

<img src="http://ttnmapper.org/resources/TTN%20Mapper%20HTTP%20integration.png">

<h4>Upload to my public API</h4>
You can receive and process your packets yourself and upload the results to my public API. For access to this API please contact me.
-->


<h3>Which spreading factor should I transmit on?</h3>
<p>We use SF7. This provides us with a map of "worst case" coverage. It also does not make sense to use a higher SF, as the performance decreases when you are mobile. The time between packets, and therefore distance between samples also increases with higher SF. SF7 is just good enough.

<h3>My results are not showing up on the map</h3>
<p>Possibly the gateway that received your packets does not send its coordinates in its status update packets. If you know the gateway owner, please ask them to enable the GPS (and fake gps) so that the coordinates are sent through. If this is the reason for your measurements not showing, you can check under Advanced Maps, enter your node address, and view the map. If you now see circles without lines connecting them to a gateway, this issue is confirmed.

<p>Did you mark your data as experimental? If so, the data will not show on the main map, but on a separate map containing only your experiment. Please disable experimental mode in the app so that you can contribute to the global map of TTN coverage.

<p>The maps should show new data within a couple of minutes. A scheduled task starts executing every two minutes to aggregate data and create the GEOJSON files. The task may however take a long time to execute.

<p>If this is not the case, likely the Android app is not uploading data to our server. It is also possible that the server is down (but then the website won't display), or that for some other reason stuff are breaking on our side. Best to log your coverage to a file and e-mail it to us if your results are not displayed within a day or two. When we get an email like this, it will also be seen as a bug report and the issue will be investigated.


<h3>My results were showing on the map before, but is gone now.</h3>
<p>As I'm typing this it has not been implemented yet (feature implemented on 2016-03-20), but the idea is to hide all "old" measurements for a gateway if it has moved more than 100m. Therefore, if you mapped the coverage of a gateway, the measurements showed up on the map, but the gateway was then placed at a new location more than 100m from the original location, the old measurements will be hidden.

<p>The major reason for this is that the map should be an indication of the current TTN coverage, not what it was a few weeks ago. Also, if a gateway moves, the origin of the radial lines will move too. It is possible to have multiple markers for a gateway, creating in essence two separate coverage maps, but again, the old coverage does not mean anything to us.

<h3>While I was measuring I saw a lot of points on the app for locations I measured, but only a few points are shown on your map. Did some of the data points go missing, or not upload?</h3>
<p>To be safe, e-mail me your log file (if you also logged to a file).
<p>It is possible that some datapoints did not upload correctly to our server because your internet connection did not work correctly, however this is unlikely.
<p>The most likely reason is that the gateway that received your packets does not report its location. We therefore are not able to plot the datapoints as we also do not know the path the packet was received along.
<p>We aggregate datapoints accross a region of 0.005 degrees to make displaying it on a map faster. Click on a gateway and choose to view only that one's coverage, and you will see data aggregated over a 0.0005 degrees area, thus more data points.


<h3>Can I integrate your data into my map? <br /> Can I download my data which I uploaded to you?<br /> Public API access. </h3>

<p>All data is dumped into tab-delimited files at an irregular interval. The dumps are available at <a href="http://ttnmapper.org/dumps">http://ttnmapper.org/dumps</a>. If you want newer data than what there are dumps of, please contact me. 

<p>No public api access is given to the database. This is due to the amount of data and number of possible queries that won't be possible to handle on the server. Please use the dumps and your own database. 

<p>The GEOJSON files used to draw the TTNmapper maps are also publicly available at <a href="http://ttnmapper.org/geojson">http://ttnmapper.org/geojson</a>.


<h3>I want you to remove my gateway or measurement points from your map (privacy, etc)</h3>
<p>If you change your gateway's location to another one more than 100m away, any old measurements for your gateway will be hidden automatically.

<p>In any other circumstance, contact me.

<h3>Is TTN Mapper opensource?</h3>
The plan is to open source some parts of the code base. We are working hard to get the code for these parts in a state that is possible to opensource. The source code for the parts that are already open source can be found at: https://github.com/ttnmapper

<h3>My question is not answered here</h3>
<ul>
<li>Send me an email. Address is available <a href="http://jpmeijers.com/blog/node/3">here</a>.

<li>Ping me on the TTN Slack: @jpmeijers.
</ul>
</body>
</html>
