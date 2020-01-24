<head>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

if(isset($settings['theming']['favicon_dir'])) {
  $favicons = $settings['theming']['favicon_dir'];
} else {
  $favicons = "favicons";
}

if(isset($settings['theming']['brand_icon'])) {
  $brandIcon = $settings['theming']['brand_icon'];
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
  <meta name="description" content="<?php echo $siteDescription; ?>" />
  <meta name="keywords" content="ttn coverage, the things network coverage, gateway reception area, contribute to ttn" />
  <meta name="robots" content="index,follow" />
  <meta name="author" content="JP Meijers">

  <title><?php echo $siteName; ?></title>

  <!--favicons-->
  <link rel="apple-touch-icon" sizes="57x57" href="/<?php echo $favicons; ?>/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/<?php echo $favicons; ?>/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/<?php echo $favicons; ?>/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/<?php echo $favicons; ?>/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/<?php echo $favicons; ?>/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/<?php echo $favicons; ?>/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/<?php echo $favicons; ?>/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/<?php echo $favicons; ?>/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/<?php echo $favicons; ?>/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/<?php echo $favicons; ?>/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/<?php echo $favicons; ?>/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/<?php echo $favicons; ?>/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/<?php echo $favicons; ?>/favicon-16x16.png">
  <link rel="manifest" href="/<?php echo $favicons; ?>/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/<?php echo $favicons; ?>/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">


  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.css" integrity="sha256-AghQEDQh6JXTN1iI/BatwbIHpJRKQcg2lay7DE5U/RQ=" crossorigin="anonymous" />
  <link rel="stylesheet" href="/libs/open-iconic/font/css/open-iconic-bootstrap.min.css" />

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
