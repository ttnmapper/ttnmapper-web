var pointMarkers = L.featureGroup();
var lineMarkers = L.featureGroup();

var gatewayData = {};
var pointData = {};

var showOfflineGateways = "1";

setUp();

function setUp() {
    $("#legend").load("/legend.html");
    $("#legend").css({visibility: "visible"});

    initMap();

    addBackgroundLayers();
    if (findGetParameter("gateways") != "off") {
        gatewayMarkers.addTo(map);
        gatewayMarkersNoCluster.addTo(map);
    }
    if (findGetParameter("points") != "off") {
        pointMarkers.addTo(map);
    }
    if (findGetParameter("lines") != "off") {
        lineMarkers.addTo(map);
    }
    getData();
}

// Callback to refresh layers when the map was panned or zoomed
function boundsChangedCallback() {
    //nothing to do
}

function showOrHideLayers() {

}

function getData() {
    $("div.spanner").addClass("show");
    $("div.overlay").addClass("show");

    var startTime = moment(findGetParameter("startdate"));
    var endTime = moment(findGetParameter("enddate"));

    if(!startTime.isValid()) {
        startTime = moment.unix(0);
    }
    if(!endTime.isValid()) {
        endTime = moment().startOf('day');
    }

    // If end time is only a date, add time
    if (endTime.hour() === 0 && endTime.minute() === 0 && endTime.second() === 0) {
        endTime.add(1, 'days');
    }

    // var url = new URL('http://localhost:8080/experiment/data')
    var url = new URL('https://api.ttnmapper.org/experiment/data')
    var params = {
        experiment: findGetParameter("experiment"),
        start_time: startTime.toISOString(),
        end_time: endTime.toISOString()
    };

    url.search = new URLSearchParams(params).toString();
    fetch(url)
        .then(response => response.json())
        .then(data => {
            pointData = data;
            for (point of data) {
                getGatewayLocation(point['gateway_network_id'], point['gateway_id']);
            }
        })
        .finally(() => {
            $("div.spanner").addClass("hide");
            $("div.overlay").addClass("hide");
        });
}

function getGatewayLocation(network_id, gateway_id) {
    if (gatewayData[network_id] === undefined) {
        gatewayData[network_id] = {};
    }
    if (gatewayData[network_id][gateway_id] === undefined) {
        gatewayData[network_id][gateway_id] = "busy";
        // const res = fetch("http://localhost:8080/gateway/" + encodeURIComponent(network_id) + "/" + encodeURIComponent(gateway_id) + "/details")
        const res = fetch("https://api.ttnmapper.org/gateway/" + encodeURIComponent(network_id) + "/" + encodeURIComponent(gateway_id) + "/details")
            .then(response => response.json())
            .then(gateway => {
                gatewayData[network_id][gateway_id] = gateway;
                addLines(network_id, gateway_id);
            });
    }
}

function addLines(network_id, gateway_id) {

    const gateway = gatewayData[network_id][gateway_id];
    if (!(gateway['latitude'] === 0 && gateway['longitude'] === 0)) {
        const lastHeardDate = Date.parse(gateway.last_heard);
        const gatewayMarker = L.marker(
            [gateway.latitude, gateway.longitude],
            {
                icon: iconByNetworkId(gateway.network_id, lastHeardDate)
            });
        const gwDescriptionHead = popUpHeader(gateway);
        const gwDescription = popUpDescription(gateway);
        gatewayMarker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
        gatewayMarkers.addLayer(gatewayMarker);
    } else {
        console.log("Gateway " + gateway_id + " on NULL island");
    }

    for (data of pointData) {
        if (data['gateway_id'] !== gateway_id || data['gateway_network_id'] !== network_id) {
            continue;
        }

        var colour = getColour(data);

        var signal = Number(data['rssi']);
        if (Number(data['snr']) < 0) {
            signal = signal + Number(data['snr']);
        }

        let distance = 0;
        const lat = data['latitude'];
        const lon = data['longitude'];
        const gwLat = Number(gateway['latitude']);
        const gwLon = Number(gateway['longitude']);

        if (gwLat === 0 && gwLon === 0) {
            // console.log("Gateway " + gateway_id + " on NULL island");
        } else {
            distance = Math.round(getDistance(lat, lon, gwLat, gwLon));

            // Line
            const lineOptions = {
                radius: 10,
                color: colour,
                fillColor: colour,
                opacity: 0.3,
                weight: 2
            };
            const lineMarker = L.polyline([[data['latitude'], data['longitude']], [gwLat, gwLon]], lineOptions);
            lineMarker.bindPopup(
                data['time'] +
                '<br /><b>AppID:</b> ' + data['app_id'] +
                '<br /><b>DevID:</b> ' + data['dev_id'] +
                '<br /><b>Device Network:</b> ' + data['device_network_id'] +
                '<br /><b>Gateway ID:</b> <br />' + data['gateway_id'] +
                '<br /><b>Gateway Network:</b> <br />' + data['gateway_network_id'] +
                '<br /><b>Location accuracy:</b> ' + data['accuracy_meters'] +
                '<br /><b>Satellite count:</b> ' + data['satellites'] +
                '<br /><b>HDOP:</b> ' + data['hdop'] +
                '<br /><b>Packet id:</b> ' + data['database_id'] +
                '<br /><b>RSSI:</b> ' + data['rssi'] + 'dBm' +
                '<br /><b>SNR:</b> ' + data['snr'] + 'dB' +
                '<br /><b>Signal:</b> ' + signal + 'dBm' +
                '<br /><b>SF:</b> ' + data['spreading_factor'] +
                '<br /><b>BW:</b> ' + data['bandwidth'] + ' Hz' +
                '<br /><b>Distance:</b> ' + distance + 'm' +
                '<br /><b>Altitude: </b>' + data['altitude'] + 'm');
            lineMarkers.addLayer(lineMarker);
        }

        // Point
        const markerOptions = {
            stroke: false,
            radius: 5,
            color: colour,
            fillColor: colour,
            fillOpacity: 0.8
        };
        const pointMarker = L.circleMarker([data['latitude'], data['longitude']], markerOptions);
        pointMarker.bindPopup(
            data['time'] +
            '<br /><b>AppID:</b> ' + data['app_id'] +
            '<br /><b>DevID:</b> ' + data['dev_id'] +
            '<br /><b>Device Network:</b> ' + data['device_network_id'] +
            '<br /><b>Gateway ID:</b> <br />' + data['gateway_id'] +
            '<br /><b>Gateway Network:</b> <br />' + data['gateway_network_id'] +
            '<br /><b>Location accuracy:</b> ' + data['accuracy_meters'] +
            '<br /><b>Satellite count:</b> ' + data['satellites'] +
            '<br /><b>HDOP:</b> ' + data['hdop'] +
            '<br /><b>Packet id:</b> ' + data['database_id'] +
            '<br /><b>RSSI:</b> ' + data['rssi'] + 'dBm' +
            '<br /><b>SNR:</b> ' + data['snr'] + 'dB' +
            '<br /><b>Signal:</b> ' + signal + 'dBm' +
            '<br /><b>SF:</b> ' + data['spreading_factor'] +
            '<br /><b>BW:</b> ' + data['bandwidth'] + ' Hz' +
            '<br /><b>Distance:</b> ' + distance + 'm' +
            '<br /><b>Altitude: </b>' + data['altitude'] + 'm');
        pointMarkers.addLayer(pointMarker);
    }

    setTimeout(fitBounds, 1000, null);
}

function fitBounds() {
    // Zoom map to fit points and gateways
    if (pointMarkers.getBounds().isValid()) {
        const bounds = pointMarkers.getBounds();

        if (lineMarkers.getBounds().isValid()) {
            bounds.extend(lineMarkers.getBounds());
        }
        if (gatewayMarkers.getBounds().isValid()) {
            bounds.extend(gatewayMarkers.getBounds());
        }
        if (gatewayMarkersNoCluster.getBounds().isValid()) {
            bounds.extend(gatewayMarkersNoCluster.getBounds());
        }
        map.fitBounds(bounds);
    }
}