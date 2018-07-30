<title>TTN Mapper</title>
  <meta charset="utf-8" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <meta name="description" content="Map the coverage for gateways of The Things Network." />
  <meta name="keywords" content="ttn coverage, the things network coverage, gateway reception area, contribute to ttn" />
  <meta name="robots" content="index,follow" />
  <meta name="author" content="JP Meijers">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

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


  <link rel="stylesheet" href="/libs/leaflet/v1.0.3/leaflet.css" />
  <link rel="stylesheet" href="/libs/Leaflet.draw/dist/leaflet.draw.css" />
  <link rel="stylesheet" href="/libs/Leaflet.MeasureControl/leaflet.measurecontrol.css" />
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
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
  body,td,th {
  //  font-family: Arial, Helvetica, sans-serif;
  //  color: #000000;
  //  font-weight: normal;
  }
  a:link {
          text-decoration: none;
          color: #000010;
  }
  a:visited {
          text-decoration: none;
          color: #000020;
  }
  a:hover {
          text-decoration: underline;
          color: #000070;
  }
  a:active {
          text-decoration: none;
          color: #000070;
  }
  #leftcontainer
  {
    position: absolute;
    left: 20px;
    bottom: 30px;
    //max-width: 190px;
    z-index: 500;
  }
  #rightcontainer
  {
    position: absolute;
    right: 20px;
    bottom: 30px;
    //max-width: 190px;
    z-index: 500;
  }
/*  #map {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  }*/
  #menu
  {
    //position: absolute;
    //bottom: 230px;  /* adjust value accordingly */
    //left: 20px;  /* adjust value accordingly */
    //width: 140px;
    visibility: visible;
  }
  #legend
  {
    //position:absolute;
    //bottom: 30px;  /* adjust value accordingly */
    //left: 20px;  /* adjust value accordingly */
    //width: 140px;
    visibility: visible;
  }
  #stats
  {
    //position:absolute;
    //bottom: 30px;  /* adjust value accordingly */
    //right: 20px;  /* adjust value accordingly */
    visibility: visible;
    //z-index: 500;
  }
  #shuttleworth
  {
    visibility: visible;
  }
  
  .leaflet-control.enabled a {
      background-color: yellow;
  }
  .dropSheet
  {
      background-color/**/: #FFFFFF;
//        background-image/**/: none;
//        opacity: 0.5;
//        filter: alpha(opacity=50);
      border-width: 2px; 
      border-style: solid; 
//      border-color: #AAAAAA; 
      border-color: #555555;
      padding: 5px;
  }
  </style>