<!DOCTYPE html>
<html lang="en">
<?php require getenv("TTNMAPPER_HOME").'/web/head.php'; ?>
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
      <li class="nav-item active">
        <a class="nav-link" href="/acknowledgements/">Acknowledgements</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_faq']) or $settings['menu']['menu_faq'] == true) {
      ?>
      <li class="nav-item">
          <a class="nav-link" href="https://docs.ttnmapper.org/FAQ.html">FAQ</a>
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


  <div class="container ">
    <h1 class="mt-4">Acknowledgements</h1>

    <div class="card mt-4">
      <h5 class="card-header">Initial data set</h5>
      <div class="card-body">

        <dl>
        <dt><a href="http://www.decentlab.com/news/2016/2/2/the-things-network-zurich-coverage">Decentlab</a> and <a href="https://github.com/ttn-zh">TTN Zurich</a></dt>
        <dd>For coverage data around the area of Zurich</dd>

        <dt><a href="http://pade.nl/lora/">pade.nl</a></dt>
        <dd>For coverage data of the major cities in The Netherlands</dd>
        </dl>

      </div>
    </div>

    <div class="card mt-4">
      <h5 class="card-header">Shuttleworth Foundation</h5>
      <div class="card-body">
        For a <a href="https://www.shuttleworthfoundation.org/fellows/flash-grants/">flash grant during 2018</a> to support with opensourcing TTN Mapper, server hosting costs, and getting me to attend The Things Conference.
      </div>
    </div>

    <div class="card mt-4">
      <h5 class="card-header">Patreon supporters</h5>
      <div class="card-body">
        A number of patrons make a monthly contribution to cover the running cost of TTN Mapper. Some of their names are listed below.
        <ul>
          <li>22 anonymous patrons</li>
          <li>Bruce Fitzsimons</li>
          <li>Giovanni Bertozzi</li>
          <li>@Ryanteck</li>
          <li>Adam Roberts</li>
          <li><a href="https://meshed.com.au/"><img src="Meshed-240x150-2.png" style="height: 40px;"/>&nbsp;Meshed</a></li>
          <li>Keptenkurk</li>
          <li>Howard Phillips</li>
          <li>EmielN Heemskerk</li>
        </ul>
      </div>
    </div>

   <div class="card mt-4">
      <h5 class="card-header">Other donations received from</h5>
      <div class="card-body">
        <ul>
          <li><a href="https://www.boatofficer.com/">BoatOfficer</a></li>
        </ul>
      </div>
    </div>

    <div class="card mt-4">
      <h5 class="card-header">Core Contributors</h5>
      <div class="card-body">
        <dl>
          <dt><a href="https://github.com/jpmeijers">JP Meijers</a></dt>
          <dd>Project initiator, Android app, frontend, backend</dd>

          <dt><a href="https://github.com/TimothySealy">Timothy Sealy</a></dt>
          <dd>iOS mapping app, branding, architecture</dd>

          <dt><a href="https://github.com/KolijnWolfaardt">Kolijn Wolfaardt</a></dt>
          <dd>Backend development, hardware design</dd>
        </dl>

      </div>
    </div>

    <p>&nbsp;</p>
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
