<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>TTN Mapper</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.css" integrity="sha256-AghQEDQh6JXTN1iI/BatwbIHpJRKQcg2lay7DE5U/RQ=" crossorigin="anonymous" />
  <link rel="stylesheet" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" crossorigin="anonymous">

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
    body {
      padding-top: 58px;
    }
  </style>
  
</head>


<body>


<!-- Image and text -->
<nav class="navbar fixed-top navbar-expand-lg navbar-light bg-light">
  
  <a class="navbar-brand" href="/">
    <img src="/favicons/favicon-96x96.png" width="32" height="32" class="d-inline-block align-top" alt="">
    TTN Mapper
  </a>
  
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="/advanced-maps/">Advanced maps</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/heatmap/">Heatmap (beta)</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/colour-radar/">Colour Radar</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/alpha-shapes/">Area Plot</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/leaderboard/">Leader board</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/acknowledgements/">Acknowledgements</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/faq/">FAQ</a>
      </li>
    </ul>
  </div>

  <div class="navbar-collapse collapse w-100 order-3 dual-collapse2">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item mr-2">
        <a class="nav-link" href="https://teespring.com/ttnmapper">
          <img src="/resources/teespring.svg" height="25" class="d-inline-block align-middle" alt="" title="Teespring">
          Get the T-Shirt
        </a>
      </li>
      <li class="nav-item">
        <a href="https://www.patreon.com/bePatron?u=24672712" data-patreon-widget-type="become-patron-button"><img src="/resources/become_a_patron_button@2x.png" class="d-inline-block align-middle" alt="" height="36" title="Patreon"></a>
      </li>
    </ul>
  </div>

</nav>


<div class="container ">
  <h1 class="mt-4">Experiments</h1>

  <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
    <thead>
      <tr>
        <th>Name</th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody>

<?php

try {
  $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

  $username = $settings['database_mysql']['username'];
  $password = $settings['database_mysql']['password'];
  $dbname = $settings['database_mysql']['database'];
  $servername = $settings['database_mysql']['host'];

  $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $stmt = $conn->prepare("SELECT DISTINCT(name) AS name FROM experiments ORDER BY name");
  $stmt->execute();
  
  foreach($stmt->fetchAll() as $k=>$v) 
  {
    $exp_name = htmlentities($v[0]);

    echo '
            <tr>
                <td>'.$exp_name.'</td>
                <td>
                  <form>
                    <input type="hidden" name="experiment" value="'.$exp_name.'">
                    <a href="/experiments/csv.php?experiment='.$exp_name.'">
                      <button type="submit" class="btn btn-secondary" formaction="/experiments/csv.php">CSV data</button>
                    </a>
                  </form>
                </td>
                <td>
                  <form>
                    <input type="hidden" name="experiment" value="'.$exp_name.'">
                    <a href="/experiments/map.php?experiment='.$exp_name.'">
                      <button type="submit" class="btn btn-primary" formaction="/experiments/map.php">View Map</button>
                    </a>
                  </form>
                </td>
            </tr>
    ';
  }
}
catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}
?>

    </tbody>
  </table>
  <p>&nbsp;</p>
</div>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>

  <script type="text/javascript">
    $(document).ready( function () {
        $('#dataTable').DataTable();
    } );
  </script>
</body>
</html>
