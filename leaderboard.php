<!doctype html>

<html>
<head>
  <meta charset="utf-8">
  <title>TTN Mapper</title>
  <meta name="description" content="TTN Mapper">

  <!-- Google analytics-->
  <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  var GA_LOCAL_STORAGE_KEY = 'ga:clientId';

  if (window.localStorage) {
    ga('create', 'UA-75921430-1', {
      'storage': 'none',
      'clientId': localStorage.getItem(GA_LOCAL_STORAGE_KEY)
    });
    ga(function(tracker) {
      localStorage.setItem(GA_LOCAL_STORAGE_KEY, tracker.get('clientId'));
    });
  }
  else {
    ga('create', 'UA-75921430-1', 'auto');
  }

  ga('send', 'pageview');

  </script>

<style>
tr.separated td {
    /* set border style for separated rows */
    border-bottom: 1px solid black;
} 

table {
    /* make the border continuous (without gaps between columns) */
    border-collapse: collapse;
}

tr.btmborder {
    border-bottom: 1px solid #000;
}

td.leftborder {
  border-left: 1px solid #000;
}

td.rightborder {
  border-right: 1px solid #BBB;
}
</style>

</head>

<body>
<?php include "menu_horizontal.php"; ?>
<?php
  $settings = parse_ini_file($_SERVER['DOCUMENT_ROOT']."/settings.conf",true);

  $username = $settings['database_mysql']['username'];
  $password = $settings['database_mysql']['password'];
  $dbname = $settings['database_mysql']['database'];
  $servername = $settings['database_mysql']['host'];

  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>


<h3>Top contributing applications by packets</h3>
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

<br />


<h3>Top contributing applications by gateways</h3>
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

<br />


<h3>Top contributing nodes by packets</h3>
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


<h3>Top contributing nodes by gateways</h3>
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

</body>
</html>