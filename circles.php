<!DOCTYPE html>
<html>
<head>
  <?php
  include("include_head.php");
  ?>
</head>
<body>
  <?php
  include("include_body_top.php");
  ?>
  <script>
  var geojsonLayer500udeg;
  var geojsonLayer5mdeg;
  var geojsonLayerCircles;
  
  function swap_layers()
  {
    
  }

<?php
include("include_base_map.php");
?>
    

<?php
  $settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/settings.conf",true);

  $username = $settings['database_mysql']['username'];
  $password = $settings['database_mysql']['password'];
  $dbname = $settings['database_mysql']['database'];
  $servername = $settings['database_mysql']['host'];

  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
  $stmt = $conn->prepare("SELECT DISTINCT(gwaddr) AS gwaddr FROM gateways_aggregated WHERE `last_heard` > (NOW() - INTERVAL 5 DAY)");
    
  $stmt->execute();
  $stmt->setFetchMode(PDO::FETCH_ASSOC);
  foreach($stmt->fetchAll() as $k=>$v) { 
    echo '
  $.getJSON("geojson/'.$v['gwaddr'].'/circles.geojson", function(data){
      L.geoJson(data, {
      pointToLayer: function (feature, latlng) {
        return L.circle(latlng, feature.properties.radius, {
          stroke: false,
          color: feature.style.color,
          fillColor: feature.style.color,
          fillOpacity: 0.3
        });
      }
    }).addTo(map);
  });
  ';
  }
?>
  </script>
</body>
</html>
