<!DOCTYPE html>
<html lang="en">
<head>
<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

if($settings['theming']['site_name'] === NULL) {
  $siteName = "TTN Mapper";
} else {
  $siteName = $settings['theming']['site_name'];
}

if($settings['theming']['site_description'] === NULL) {
  $siteDescription = "Map the coverage for gateways of The Things Network.";
} else {
  $siteDescription = $settings['theming']['site_description'];
}

if($settings['theming']['brand_icon'] === NULL) {
  $brandIcon = "/favicons/favicon-96x96.png";
} else {
  $brandIcon = "/resources/".$settings['theming']['brand_icon'];
}

if($settings['theming']['brand_name'] === NULL) {
  $brandName = "TTN Mapper";
} else {
  $brandName = $settings['theming']['brand_name'];
}

if($settings['analytics']['site_id'] === NULL) {
  $googleAnalyticsSiteId = "UA-75921430-1";
} else {
  $googleAnalyticsSiteId = $settings['analytics']['site_id'];
}


?>

  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="<?php echo $siteDescription; ?>" />
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
    .toGrayscale img {
      filter: grayscale(1);
    }
  </style>

</head>
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
        <?php
        if($settings['menu']['menu_advanced'] === NULL or $settings['menu']['menu_advanced'] == true) {
        ?>
        <li class="nav-item">
          <a class="nav-link" href="/advanced-maps/">Advanced maps</a>
        </li>
        <?php
        }

        if($settings['menu']['menu_heatmap'] === NULL or $settings['menu']['menu_heatmap'] == true) {
        ?>
        <li class="nav-item">
          <a class="nav-link" href="/heatmap/">Heatmap (beta)</a>
        </li>
        <?php
        }

        if($settings['menu']['menu_colour_radar'] === NULL or $settings['menu']['menu_colour_radar'] == true) {
        ?>
        <li class="nav-item">
          <a class="nav-link" href="/colour-radar/">Colour Radar</a>
        </li>
        <?php
        }

        if($settings['menu']['menu_area_plot'] === NULL or $settings['menu']['menu_area_plot'] == true) {
        ?>
        <li class="nav-item">
          <a class="nav-link" href="/alpha-shapes/">Area Plot</a>
        </li>
        <?php
        }

        if($settings['menu']['menu_leaderboard'] === NULL or $settings['menu']['menu_leaderboard'] == true) {
        ?>
        <li class="nav-item">
          <a class="nav-link" href="/leaderboard/">Leader board</a>
        </li>
        <?php
        }

        if($settings['menu']['menu_acknowledgements'] === NULL or $settings['menu']['menu_acknowledgements'] == true) {
        ?>
        <li class="nav-item">
          <a class="nav-link" href="/acknowledgements/">Acknowledgements</a>
        </li>
        <?php
        }

        if($settings['menu']['menu_faq'] === NULL or $settings['menu']['menu_faq'] == true) {
        ?>
        <li class="nav-item">
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
        if($settings['menu']['teespring'] === NULL or $settings['menu']['teespring'] == true) {
        ?>
        <li class="nav-item mr-2">
          <a class="nav-link" href="https://teespring.com/ttnmapper">
            <img src="/resources/teespring.svg" height="25" class="d-inline-block align-middle" alt="" title="Teespring">
            Get the T-Shirt
          </a>
        </li>
        <?php
        }

        if($settings['menu']['patreon'] === NULL or $settings['menu']['patreon'] == true) {
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
  <!-- The actual main logic for this page -->
  <script src="index-logic.js"></script>

</body>
</html>
