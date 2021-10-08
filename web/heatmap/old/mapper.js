//import * as he from "he" ;
//import * as L from "leaflet";


function getGatewayFeature(gateway) {

  let networkType = gateway.network_id.split(":")[0];
  if(networkType === "thethingsnetwork.org") {
    networkType = "NS_TTN_V2";
  }

  return {
    type: "Feature",
    properties: {
      id: gateway.id,
      gateway_id: gateway.gateway_id,
      eui: gateway.gateway_eui || "",
      description: gateway.description,
      network_type: networkType,
      network_id: gateway.network_id,
      channels: gateway.channels || 'UNKNOWN',
      last_heard: gateway.last_heard
    },
    geometry: {
      type: "Point",
      coordinates: [gateway.longitude, gateway.latitude],
    },
  };
}

async function GetGateways(endpoint) {
  const res = await fetch(endpoint);
  const data = await res.json();
  const { gateways } = data
  //   console.log(this.ttnGw);

  // let ttnGwGeo = [];
  // for (let i = 0; i < gateways.length; i++) {
  //   const gw = gateways[i];

  //   var geojsonFeature = getGatewayFeature(gw)

  //   ttnGwGeo.push(geojsonFeature);
  //   // const markerPopup = new maplibre.Popup().setText(gw.gateway_eui);
  //   // const el = document.createElement("div");
  //   // el.className = "marker_ttnv3";
  //   // el.style.width = "20px";
  //   // el.style.height = "20px";
  //   // el.style.backgroundImage = "url(/TTNMarker.png)";
  //   // // el.style.width = marker.properties.iconSize[0] + "px";
  //   // // el.style.height = marker.properties.iconSize[1] + "px";
  //   // el.style.;
  //   // // el.style.background = "blue";
  //   // // make a marker for each feature and add to the map
  //   // new maplibre.Marker(el)
  //   //   .setLngLat([gw.longitude, gw.latitude])
  //   //   .setPopup(markerPopup)
  //   //   .addTo(map);
  // }


  return gateways.map(gateway => getGatewayFeature(gateway))
}

function iconImageByNetworkId(networkId) {
  var icon = iconByNetworkId(networkId);

  return '<img src="'+icon.options.iconUrl+'" style="height:'+icon.options.iconSize[0]+'px;width:'+icon.options.iconSize[1]+'px"></img>';
}

function iconByNetworkId(networkId) {
  if(networkId === "thethingsnetwork.org") {
    return gatewayMarkerOnline;
  }
  if(networkId.startsWith("NS_TTS_V3://")) {
    return gatewayMarkerV3;
  }
  if(networkId.startsWith("NS_CHIRP://")) {
    return gatewayMarkerChirpV3;
  }
  if(networkId.startsWith("NS_HELIUM://")) {
    return gatewayMarkerHelium;
  }
  return gatewayMarkerOnlineNotMapped;

}

function NewFeatureToMarker(L, param) {
  return function featureToMarker(feature, latlng) {
    const props = {
      icon: L.divIcon({
        className: 'marker-' + feature.properties.network_type,
        html: iconByName(feature.properties.network_type, param),
      })
    }
    let marker = L.marker(latlng, props);
    const gwdescriptionHead = popUpHeader(feature);
    const gwdescription = popUpDescription(feature);
    // console.log(`${gwdescriptionHead}<br>${gwdescription}`);
    marker.bindPopup(`${gwdescriptionHead}<br>${gwdescription}`)
    return marker;
  }
}

function ClusteredFeatures(L, features) {
  var markers = L.markerClusterGroup({
    spiderfyOnMaxZoom: true,
    // showCoverageOnHover: false,
    // zoomToBoundsOnClick: false,
    maxClusterRadius: 50,
  });

  // for (feature of features) {
  features.forEach(function (feature) {
    let marker = L.marker(
      [ feature.geometry.coordinates[1], feature.geometry.coordinates[0] ], 
      {
        icon: iconByNetworkId(feature.properties.network_id)
      });
    const gwDescriptionHead = popUpHeader(feature);
    const gwDescription = popUpDescription(feature);
    marker.bindPopup(`${gwDescriptionHead}<br>${gwDescription}`);
    markers.addLayer(marker);
  });

  return markers;
}

async function GetTtnV2Overlay() {
  const ttnv2Gateways = await GetGateways(
      "https://ttnmapper.org/webapi/gwall_ttnv2.php"
  );

  const ttnv2Layer = {
    name: "The Things Network V2",
    icon: iconByName("NS_TTN_V2", {height: 20, width: 20}),
    layer: ClusteredFeatures(L, ttnv2Gateways, {
      height: 20,
      width: 20,
    }),
  };

  return ttnv2Layer
}

async function GetTtnV3Overlay() {
  const ttnv3Gateways = await GetGateways(
      "https://ttnmapper.org/webapi/gwall_ttnv3.php"
  );

  const ttnv3Layer = {
    name: "The Things Network V3",
    icon: iconByName("NS_TTS_V3", {height: 20, width: 20}),
    layer: ClusteredFeatures(L, ttnv3Gateways, {
      height: 20,
      width: 20,
    }),
  };

  return ttnv3Layer
}

async function GetGatewaysOverlay(networkId) {
  const gateways = await GetGateways(
      "https://ttnmapper.org/webapi/gwall_network.php?network_id="+encodeURIComponent(networkId)
  );

  return gateways;
}

function popUpHeader(feature) {
  // First line always the ID
  let header = `<b>${he.encode(feature.properties.gateway_id)}</b>`

  // Add the EUI if it is set
  if (feature.properties.eui !== "") {
    header = `${header}<br>${feature.properties.eui}`
  }

  // Add the network ID if it is set
  if (feature.properties.network_id != null) {
    header = `${header}<br>${feature.properties.network_id}`
  }

  // TODO: If a gateway has a description, add it. V3 and Chirp does not have descriptions yet.

  return header
}

function popUpDescription(feature) {
  const { last_heard, channels, eui } = feature.properties
  const { coordinates } = feature.geometry

  const liH = `<li><a target="_blank" href="/heatmap/gateway/?gateway=${he.encode(feature.properties.gateway_id)}&network=${he.encode(feature.properties.network_id)}">heatmap</a>`
  const liR = `<li><a target="_blank" href="/colour-radar/?gateway[]=${he.encode(eui)}">radar</a>`
  const liA = `<li><a target="_blank" href="/alpha-shapes/?gateway[]=${he.encode(eui)}">alpha shape</a>`
  const lh = `<br>Last heard at ${last_heard}`
  const chn = `<br>Channels heard on: ${channels}`
  const pos = `<br>Lat, Lon: ${coordinates[1]}, ${coordinates[0]}`
  const show = `<br>Show only this gateway's coverage as: <ul>${liH}<br>${liR}<br>${liA}<br></ul>`

  // console.log(show)
  return `${lh}${chn}${pos}${show}`
}

// function formatTime(timestamp) {
//   if (timestamp === undefined) {
//     return "unknown";
//   }
//   var date = new Date(timestamp * 1000);
//   return date.toISOString();
// }

function NetworkIdToName(networkId) {
  if (networkId === "NS_TTS_V3://ttn@000013") {
    return "The Things Stack Community Edition (V3)";
  }

  if (networkId === "thethingsnetwork.org") {
    return "The Things Network (V2)";
  }

  if (networkId === "NS_HELIUM://000024") {
    return "Helium";
  }

  return networkId.replace("NS_TTS_V3://", "").replace("NS_CHIRP://", "");
} 