<!DOCTYPE html>
<html>
<head>
<?php
  include("../include_head.php");
?>
</head>
<body>
  <?php
    include("../include_body_top.php");
  ?>
  <script>
  <?php
    include("../include_base_map.php");
  ?>

  var maximum_distance = 0;
  var maximum_altitude = 0;

  function swap_layers()
  {

  }
  
  function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
  }

  function comparePacketsByTime(a,b) {
    if (a.unixtime < b.unixtime)
      return -1;
    if (a.unixtime > b.unixtime)
      return 1;
    return 0;
  }

  function createDescription(point)
  {
    var distance = "-";
    if(point.gateway != false)
    {
      distance = getDistanceFromLatLon(point.lat, point.lon, point.gateway.lat, point.gateway.lon).toFixed(0)+"m";
    }
    return point.time+'<br /><b>Node:</b> '+point.nodeaddr+'<br /><b>Received by gateway:</b> <br />'+point.gwaddr+'<br /><b>Location accuracy:</b> '+point.accuracy+'<br /><b>Packet id:</b> '+point.id+'<br /><b>RSSI:</b> '+point.rssi+'<br /><b>SNR:</b> '+point.snr+'<br /><b>DR:</b> '+point.datarate+'<br /><b>Distance:</b> '+distance+'<br /><b>Altitude: </b>'+point.alt+'m';
  }
  
  var loaded_packets = [];
  var loaded_gateways = [];
  var busy_fetching_packets = false;
  var last_node_locations = [];
  var last_time = 0;
  var number_of_points = 0;

  var markers_on_map = [];

  var latestMarkerIcon = L.AwesomeMarkers.icon({
      icon: "ion-android-bicycle",
      prefix: "ion",
      markerColor: "purple"
    });


  function fetch_data()
  {
    if(busy_fetching_packets)
    {
      return;
    }
    busy_fetching_packets=true;
    if(last_time == 0)
    {
      last_time = getParameterByName('start_time');
      if(last_time == null)
      {
        last_time = 0;
      }
    }

    if(getParameterByName('timelapse') == null)
    {
      var api_url = 'track_api.php?name='+getParameterByName('name')+'&last';
    }
    else
    {
      var api_url = 'track_api.php?name='+getParameterByName('name')+'&last&time='+last_time;
    }
    console.log(api_url);

    $.get(api_url, function(data) {
      
      if(data.points.length!=0)
      {
        for(i=0;i<markers_on_map.length;i++) {
          map.removeLayer(markers_on_map[i]);
        }
      }
      
      var i;
      console.log("Fetched "+data.points.length+" new packets");
      number_of_points=0;
      for(i=0; i<data.points.length; i++)
      {
        number_of_points++;
        var point = data.points[i];
        
        if(point.unixtime>last_time)
        {
          last_time = point.unixtime;
        }

        var rssi = point.rssi;
        var color = "#0000ff";
        if (rssi==0)
        {
          color = "black";
        }
        else if (rssi<-120)
        {
          color = "blue";
        }
        else if (rssi<-115)
        {
          color = "cyan";
        }
        else if (rssi<-110)
        {
          color = "green";
        }
        else if (rssi<-105)
        {
          color = "yellow";
        }
        else if (rssi<-100)
        {
          color = "orange";
        }
        else
        {
          color = "red";
        }

        // marker = L.circleMarker([point.lat, point.lon], {
        //   stroke: false,
        //   radius: 5,
        //   color: color,
        //   fillColor: color,
        //   fillOpacity: 0.8
        // });
        // marker.bindPopup(createDescription(point));
        // marker.addTo(map);
        // markers_on_map.push(marker);

        if(point.gateway != false)
        {

          marker = L.circleMarker([point.gateway.lat, point.gateway.lon], {
            stroke: false,
            radius: 10,
            color: color,
            fillColor: heatMapColorforValue((200-rssi)/30.0),
            fillOpacity: 0.8
          });
          marker.bindPopup(createDescription(point));
          marker.addTo(map);
          markers_on_map.push(marker);
          // marker = L.marker([point.gateway.lat, point.gateway.lon], 
          //   {icon: gwMarkerIcon});
          // marker.desc = point.gwaddr;
          // marker.addTo(map);
          // oms.addMarker(marker);
          // markers_on_map.push(marker);

          // if(getParameterByName('nolines')==null)
          // {
          //   markerOptions = {
          //     radius: 10,
          //     color: color,
          //     fillColor: color,
          //     opacity: 0.1,
          //     weight: 2
          //   };
          //   marker = L.polyline([[point.lat, point.lon],[point.gateway.lat, point.gateway.lon]], markerOptions);
          //   marker.bindPopup(createDescription(point));
          //   marker.addTo(map);
          //   markers_on_map.push(marker);
          // }

          distance = getDistanceFromLatLon(point.lat, point.lon, point.gateway.lat, point.gateway.lon);
          if(maximum_distance<distance)
          {
            maximum_distance = distance;
          }
        }

        if( Number(point.alt) >= maximum_altitude )
        {
          maximum_altitude = Number(point.alt);
        }
      }

      if(data.points.length>0)
      {
        document.getElementById("stats").innerHTML = 
        "Last packet received: "+point.time+"<br />"+
        "Last altitude: "+point.alt+"m<br />"+
        "<br />"+
        "Number of points: "+number_of_points+"<br />"+
        "Number of gateways: "+data.points.length+"<br />" +
        "<br />"+
        "Maximum altitude: "+maximum_altitude+"m<br />" +
        "Maximum distance: "+(maximum_distance/1000).toFixed(3)+"km<br />";

        busy_fetching_packets=false;
        setTimeout(fetch_data, 1000);
      }
      else
      {
        busy_fetching_packets=false;
        setTimeout(fetch_data, 1000);
      }
    });
  }

  function heatMapColorforValue(value){
    var h = (1.0 - value) * 240
    return "hsl(" + h + ", 100%, 50%)";
  }

  //http://stackoverflow.com/questions/18883601/function-to-calculate-distance-between-two-coordinates-shows-wrong
  function getDistanceFromLatLon(lat1,lon1,lat2,lon2) {
    var R = 6371000; // Radius of the earth in km
    var dLat = deg2rad(lat2-lat1);  // deg2rad below
    var dLon = deg2rad(lon2-lon1); 
    var a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
      Math.sin(dLon/2) * Math.sin(dLon/2)
      ; 
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
    var d = R * c; // Distance in km
    return d;
  }

  function deg2rad(deg) {
    return deg * (Math.PI/180)
  }

  function startUpdating()
  {
    fetch_data();
    //var intervalID = setInterval(fetch_data, 100);
  }
  window.onload = startUpdating();
  </script>
</body>
</html>