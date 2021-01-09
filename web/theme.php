<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);


if(isset($settings['theming']['ttnmapperorg'])) {
  $isTtnMapperOrg = $settings['theming']['ttnmapperorg'];
} else {
  $isTtnMapperOrg = false;
}

if(isset($settings['theming']['gateway_online'])) {
  $gatewayMarkerOnline = $settings['theming']['gateway_online'];
} else {
  $gatewayMarkerOnline = "/resources/gateway_dot.png";
}

if(isset($settings['theming']['gateway_online_not_mapped'])) {
  $gatewayMarkerOnlineNotMapped = $settings['theming']['gateway_online_not_mapped'];
} else {
  $gatewayMarkerOnlineNotMapped = "/resources/gateway_dot_green.png";
}

if(isset($settings['theming']['gateway_offline'])) {
  $gatewayMarkerOffline = $settings['theming']['gateway_offline'];
} else {
  $gatewayMarkerOffline = "/resources/gateway_dot_red.png";
}

if(isset($settings['theming']['gateway_single_channel'])) {
  $gatewayMarkerSingleChannel = $settings['theming']['gateway_single_channel'];
} else {
  $gatewayMarkerSingleChannel = "/resources/gateway_dot_yellow.png";
}


if(isset($settings['theming']['gateway_icon_size_x'])) {
  $gatewayIconSizeX = $settings['theming']['gateway_icon_size_x'];
} else {
  $gatewayIconSizeX = "20";
}

if(isset($settings['theming']['gateway_icon_size_y'])) {
  $gatewayIconSizeY = $settings['theming']['gateway_icon_size_y'];
} else {
  $gatewayIconSizeY = "20";
}

if(isset($settings['theming']['gateway_icon_anchor_x'])) {
  $gatewayIconAnchorX = $settings['theming']['gateway_icon_anchor_x'];
} else {
  $gatewayIconAnchorX = "10";
}

if(isset($settings['theming']['gateway_icon_anchor_y'])) {
  $gatewayIconAnchorY = $settings['theming']['gateway_icon_anchor_y'];
} else {
  $gatewayIconAnchorY = "10";
}

if(isset($settings['theming']['gateway_popup_anchor_x'])) {
  $gatewayPopupAnchorX = $settings['theming']['gateway_popup_anchor_x'];
} else {
  $gatewayPopupAnchorX = "0";
}

if(isset($settings['theming']['gateway_popup_anchor_y'])) {
  $gatewayPopupAnchorY = $settings['theming']['gateway_popup_anchor_y'];
} else {
  $gatewayPopupAnchorY = "0";
}


if(isset($settings['theming']['map_start_lat'])) {
  $mapStartLat = $settings['theming']['map_start_lat'];
} else {
  $mapStartLat = "48.209661";
}

if(isset($settings['theming']['map_start_lon'])) {
  $mapStartLon = $settings['theming']['map_start_lon'];
} else {
  $mapStartLon = "10.251494";
}

if(isset($settings['theming']['map_start_zoom'])) {
  $mapStartZoom = $settings['theming']['map_start_zoom'];
} else {
  $mapStartZoom = "6";
}


if(isset($settings['theming']['cluster_gateways'])) {
  $clusterGateways = $settings['theming']['cluster_gateways'];
} else {
  $clusterGateways = true;
}

if(isset($settings['theming']['marker_cluster_radius'])) {
  $markerClusterRadius = $settings['theming']['marker_cluster_radius'];
} else {
  $markerClusterRadius = "40";
}

if(isset($settings['theming']['show_unmapped_gateways'])) {
  $showUnmappedGateways = $settings['theming']['show_unmapped_gateways'];
} else {
  $showUnmappedGateways = false;
}

?>

var isTtnMapperOrg = "<?php echo $isTtnMapperOrg; ?>";

var gatewayMarkerOnline = L.icon({
  iconUrl: "<?php echo $gatewayMarkerOnline; ?>",
  iconSize:     [<?php echo $gatewayIconSizeX; ?>, <?php echo $gatewayIconSizeY; ?>], // size of the icon
  iconAnchor:   [<?php echo $gatewayIconAnchorX; ?>, <?php echo $gatewayIconAnchorY; ?>], // point of the icon which will correspond to marker\'s location
  popupAnchor:  [<?php echo $gatewayPopupAnchorX; ?>, <?php echo $gatewayPopupAnchorY; ?>] // point from which the popup should open relative to the iconAnchor
});

var gatewayMarkerOnlineNotMapped = L.icon({
  iconUrl: "<?php echo $gatewayMarkerOnlineNotMapped; ?>",
  iconSize:     [<?php echo $gatewayIconSizeX; ?>, <?php echo $gatewayIconSizeY; ?>],
  iconAnchor:   [<?php echo $gatewayIconAnchorX; ?>, <?php echo $gatewayIconAnchorY; ?>],
  popupAnchor:  [<?php echo $gatewayPopupAnchorX; ?>, <?php echo $gatewayPopupAnchorY; ?>]
});

var gatewayMarkerOffline = L.icon({
  iconUrl: "<?php echo $gatewayMarkerOffline; ?>",
  iconSize:     [<?php echo $gatewayIconSizeX; ?>, <?php echo $gatewayIconSizeY; ?>],
  iconAnchor:   [<?php echo $gatewayIconAnchorX; ?>, <?php echo $gatewayIconAnchorY; ?>],
  popupAnchor:  [<?php echo $gatewayPopupAnchorX; ?>, <?php echo $gatewayPopupAnchorY; ?>]
});

var gatewayMarkerSingleChannel = L.icon({
  iconUrl: "<?php echo $gatewayMarkerSingleChannel; ?>",
  iconSize:     [<?php echo $gatewayIconSizeX; ?>, <?php echo $gatewayIconSizeY; ?>],
  iconAnchor:   [<?php echo $gatewayIconAnchorX; ?>, <?php echo $gatewayIconAnchorY; ?>],
  popupAnchor:  [<?php echo $gatewayPopupAnchorX; ?>, <?php echo $gatewayPopupAnchorY; ?>]
});

// Gateway markers are clustered together
var clusterGateways = "<?php echo $clusterGateways; ?>";
var gatewayMarkers = L.markerClusterGroup({
  maxClusterRadius: <?php echo $markerClusterRadius; ?>
});

var showUnmappedGateways = "<?php echo $showUnmappedGateways; ?>";

// When less than this number of gateways are in view we display the full resolution coverage
var layerSwapGwCount = 600;
// If les than this number is shown we display a lower resolution coverage, ie. circles
var layerHideGwCount = 4000;
// Above this number we only display the gateway markers.

// The location to which the map will zoom to for a new user.
var initialCoords = [<?php echo $mapStartLat; ?>, <?php echo $mapStartLon; ?>];
var initialZoom = <?php echo $mapStartZoom; ?>;




