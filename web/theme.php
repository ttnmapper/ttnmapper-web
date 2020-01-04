<?php
$settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);


if($settings['theming']['gateway_online'] === NULL) {
  $gatewayMarkerOnline = "/resources/gateway_dot.png";
} else {
  $gatewayMarkerOnline = "/resources/".$settings['theming']['gateway_online'];
}

if($settings['theming']['gateway_online_not_mapped'] === NULL) {
  $gatewayMarkerOnlineNotMapped = "/resources/gateway_dot_green.png";
} else {
  $gatewayMarkerOnlineNotMapped = "/resources/".$settings['theming']['gateway_online_not_mapped'];
}

if($settings['theming']['gateway_offline'] === NULL) {
  $gatewayMarkerOffline = "/resources/gateway_dot_red.png";
} else {
  $gatewayMarkerOffline = "/resources/".$settings['theming']['gateway_offline'];
}

if($settings['theming']['gateway_single_channel'] === NULL) {
  $gatewayMarkerSingleChannel = "/resources/gateway_dot_yellow.png";
} else {
  $gatewayMarkerSingleChannel = "/resources/".$settings['theming']['gateway_single_channel'];
}


if($settings['theming']['gateway_icon_size_x'] === NULL) {
  $gatewayIconSizeX = "20";
} else {
  $gatewayIconSizeX = $settings['theming']['gateway_icon_size_x'];
}

if($settings['theming']['gateway_icon_size_y'] === NULL) {
  $gatewayIconSizeY = "20";
} else {
  $gatewayIconSizeY = $settings['theming']['gateway_icon_size_y'];
}

if($settings['theming']['gateway_icon_anchor_x'] === NULL) {
  $gatewayIconAnchorX = "10";
} else {
  $gatewayIconAnchorX = $settings['theming']['gateway_icon_anchor_x'];
}

if($settings['theming']['gateway_icon_anchor_y'] === NULL) {
  $gatewayIconAnchorY = "10";
} else {
  $gatewayIconAnchorY = $settings['theming']['gateway_icon_anchor_y'];
}

if($settings['theming']['gateway_popup_anchor_x'] === NULL) {
  $gatewayPopupAnchorX = "0";
} else {
  $gatewayPopupAnchorX = $settings['theming']['gateway_popup_anchor_x'];
}

if($settings['theming']['gateway_popup_anchor_y'] === NULL) {
  $gatewayPopupAnchorY = "0";
} else {
  $gatewayPopupAnchorY = $settings['theming']['gateway_popup_anchor_y'];
}


if($settings['theming']['map_start_lat'] === NULL) {
  $mapStartLat = "48.209661";
} else {
  $mapStartLat = $settings['theming']['map_start_lat'];
}

if($settings['theming']['map_start_lon'] === NULL) {
  $mapStartLon = "10.251494";
} else {
  $mapStartLon = $settings['theming']['map_start_lon'];
}

if($settings['theming']['map_start_zoom'] === NULL) {
  $mapStartZoom = "6";
} else {
  $mapStartZoom = $settings['theming']['map_start_zoom'];
}


if($settings['theming']['cluster_gateways'] === NULL) {
  $clusterGateways = true;
} else {
  $clusterGateways = $settings['theming']['cluster_gateways'];
}

if($settings['theming']['marker_cluster_radius'] === NULL) {
  $markerClusterRadius = "40";
} else {
  $markerClusterRadius = $settings['theming']['marker_cluster_radius'];
}

if($settings['theming']['show_unmapped_gateways'] === NULL) {
  $showUnmappedGateways = false;
} else {
  $showUnmappedGateways = $settings['theming']['show_unmapped_gateways'];
}

?>


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
var layerSwapGwCount = 300;
// If les than this number is shown we display a lower resolution coverage, ie. circles
var layerHideGwCount = 2000;
// Above this number we only display the gateway markers.

// The location to which the map will zoom to for a new user.
var initialCoords = [<?php echo $mapStartLat; ?>, <?php echo $mapStartLon; ?>];
var initialZoom = <?php echo $mapStartZoom; ?>;




