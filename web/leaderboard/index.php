<!DOCTYPE html>
<html lang="en">
<?php require getenv("TTNMAPPER_HOME").'/web/head.php'; ?>
<body>



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
      if(!isset($settings['menu']['menu_advanced']) or $settings['menu']['menu_advanced'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/advanced-maps/">Advanced maps</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_heatmap']) or $settings['menu']['menu_heatmap'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/heatmap/">Heatmap (beta)</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_colour_radar']) or $settings['menu']['menu_colour_radar'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/colour-radar/">Colour Radar</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_area_plot']) or $settings['menu']['menu_area_plot'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/alpha-shapes/">Area Plot</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_leaderboard']) or $settings['menu']['menu_leaderboard'] == true) {
      ?>
      <li class="nav-item active">
        <a class="nav-link" href="/leaderboard/">Leader board</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_acknowledgements']) or $settings['menu']['menu_acknowledgements'] == true) {
      ?>
      <li class="nav-item">
        <a class="nav-link" href="/acknowledgements/">Acknowledgements</a>
      </li>
      <?php
      }

      if(!isset($settings['menu']['menu_faq']) or $settings['menu']['menu_faq'] == true) {
      ?>
      <li class="nav-item">
          <a class="nav-link" href="https://docs.ttnmapper.org/FAQ.html">FAQ</a>
      </li>
      <?php
      }
      ?>
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


  <div class="container ">
    <h1 class="mt-4">Leader board</h1>

    <div class="card mt-4">
      <h5 class="card-header">Top contributing applications by packets</h5>
      <div class="card-body">

        <table>
          <tr class="btmborder">
            <th>#</th>
            <th>Name</th>
            <th>App ID</th>
            <th>Packets</th>
            <th>Gateways</th>
            <th>Channels</th>
            <th>Devices</th>
          </tr>

<?php
try {
  
    $username = $settings['database_mysql']['username'];
    $password = $settings['database_mysql']['password'];
    $dbname = $settings['database_mysql']['database'];
    $servername = $settings['database_mysql']['host'];

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM stats_per_application ORDER BY packets DESC LIMIT 10"); 
    $stmt->execute();

    // set the resulting array to associative
    $place = 0;
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      $place++;

      $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui");
      $stmt_user->bindParam(':appeui', $v['app_id']);
      $stmt_user->execute();
      $result = $stmt_user->fetch();

      echo '<tr>';

      echo '<td class="rightborder">';
      echo $place;
      echo '</td>';

      echo '<td class="rightborder">';
      echo $result['name'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['app_id'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['packets'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['gateways'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['channels'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['devices'];
      echo '</td>';

      echo '</tr>';
    }
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

        </table>
      </div>
    </div>

    <div class="card mt-4">
      <h5 class="card-header">Top contributing applications by gateways</h5>
      <div class="card-body">
        <table>
          <tr class="btmborder">
            <th>#</th>
            <th>Name</th>
            <th>App ID</th>
            <th>Gateways</th>
            <th>Packets</th>
            <th>Channels</th>
            <th>Devices</th>
          </tr>

<?php
try {
    $stmt = $conn->prepare("SELECT * FROM stats_per_application ORDER BY gateways DESC LIMIT 10"); 
    $stmt->execute();

    // set the resulting array to associative
    $place = 0;
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      $place++;

      $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui");
      $stmt_user->bindParam(':appeui', $v['app_id']);
      $stmt_user->execute();
      $result = $stmt_user->fetch();

      echo '<tr>';

      echo '<td class="rightborder">';
      echo $place;
      echo '</td>';

      echo '<td class="rightborder">';
      echo $result['name'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['app_id'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['gateways'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['packets'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['channels'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['devices'];
      echo '</td>';

      echo '</tr>';
    }
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

        </table>
      </div>
    </div>



    <div class="card mt-4">
      <h5 class="card-header">Top contributing nodes by packets</h5>
      <div class="card-body">
        <table>
          <tr class="btmborder">
            <th>#</th>
            <th>Name</th>
            <th>App ID</th>
            <th>Dev ID</th>
            <th>Packets</th>
            <th>Gateways</th>
            <th>Channels</th>
          </tr>

<?php
try {
    $stmt = $conn->prepare("SELECT * FROM stats_per_device ORDER BY packets DESC LIMIT 10"); 
    $stmt->execute();

    // set the resulting array to associative
    $place = 0;
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      $place++;

      if($v['app_id']==null)
      {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE nodeaddr=:nodeaddr");
        $stmt_user->bindParam(':nodeaddr', $v['dev_id']);
      }
      else
      {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui AND nodeaddr=:nodeaddr");
        // $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui");
        $stmt_user->bindParam(':nodeaddr', $v['dev_id']);
        $stmt_user->bindParam(':appeui', $v['app_id']);
      }
      $stmt_user->execute();
      $result = $stmt_user->fetch();

      if(!isset($result['name']))
      {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui");
        $stmt_user->bindParam(':appeui', $v['app_id']);
        $stmt_user->execute();
        $result = $stmt_user->fetch();
      }

      echo '<tr>';

      echo '<td class="rightborder">';
      echo $place;
      echo '</td>';

      echo '<td class="rightborder">';
      echo $result['name'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['app_id'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['dev_id'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['packets'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['gateways'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['channels'];
      echo '</td>';

      echo '</tr>';
    }
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
        </table>
      </div>
    </div>



    <div class="card mt-4">
      <h5 class="card-header">Top contributing nodes by gateways</h5>
      <div class="card-body">
        <table>
          <tr class="btmborder">
            <th>#</th>
            <th>Name</th>
            <th>App ID</th>
            <th>Dev ID</th>
            <th>Gateways</th>
            <th>Packets</th>
            <th>Channels</th>
          </tr>

<?php
try {
    $stmt = $conn->prepare("SELECT * FROM stats_per_device ORDER BY gateways DESC LIMIT 10"); 
    $stmt->execute();

    // set the resulting array to associative
    $place = 0;
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach($stmt->fetchAll() as $k=>$v) { 
      $place++;

      if($v['app_id']==null)
      {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE nodeaddr=:nodeaddr");
        $stmt_user->bindParam(':nodeaddr', $v['dev_id']);
      }
      else
      {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui AND nodeaddr=:nodeaddr");
        // $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui");
        $stmt_user->bindParam(':nodeaddr', $v['dev_id']);
        $stmt_user->bindParam(':appeui', $v['app_id']);
      }
      $stmt_user->execute();
      $result = $stmt_user->fetch();

      if(!isset($result['name']))
      {
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE appeui=:appeui");
        $stmt_user->bindParam(':appeui', $v['app_id']);
        $stmt_user->execute();
        $result = $stmt_user->fetch();
      }

      echo '<tr>';

      echo '<td class="rightborder">';
      echo $place;
      echo '</td>';

      echo '<td class="rightborder">';
      echo $result['name'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['app_id'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['dev_id'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['gateways'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['packets'];
      echo '</td>';

      echo '<td class="rightborder">';
      echo $v['channels'];
      echo '</td>';

      echo '</tr>';
    }
}
catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
        </table>
      </div>
    </div>

    <p>&nbsp;</p>
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
  <script type="text/javascript" src="/common.js"></script>
  <!-- The actual main logic for this page -->
  <!-- <script src="index-logic.js"></script> -->
</body>
</html>
