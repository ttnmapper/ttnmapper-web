<!DOCTYPE html>
<html lang="en">
<?php require getenv("TTNMAPPER_HOME").'/web/head.php'; ?>
<body>


<div class="container-fullwidth" style="display: flex; flex-flow: column; height: 100%;">

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
            <li class="nav-item">
                <a class="nav-link" href="/heatmap/">Heatmap</a>
            </li>
            <li class="nav-item active">
              <a class="nav-link" href="/advanced-maps/">Advanced maps</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="/acknowledgements/">Acknowledgements</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="https://coveragemap.net">Helium</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="https://docs.ttnmapper.org">Docs</a>
            </li>
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

  <div id="map"></div>
  <div id="rightcontainer">
    <div id="legend" class="dropSheet"></div>
  </div>

  <div class="overlay"></div>
  <div class="spanner">
    <div class="loader"></div>
    <p>Fetching data, please be patient.</p>
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

<!-- Moment for datetime manipulation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

  <!-- The map style -->
  <script type="text/javascript" src="/theme.php"></script>
  <script type="text/javascript" src="/common.js"></script>
  <!-- The actual main logic for this page -->
  <script src="index-logic.js"></script>

</body>
</html>
