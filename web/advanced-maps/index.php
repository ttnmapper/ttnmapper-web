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
      <li class="nav-item active">
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
      <li class="nav-item">
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
        <a class="nav-link" href="/faq/">FAQ</a>
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
  <h1 class="mt-4">Advanced Maps</h1>

<div class="card mt-4">
  <h5 class="card-header">Device data</h5>
  <div class="card-body">


      <p>Draw circles or radials for every measurement made by a specific device on a specific day or range of days. The result will be limited to the 10000 latest measurements for the selected time range.</p>

      <form method="get" class="needs-validation" novalidate target="_blank">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="device-id">Device ID</label>
            <input class="form-control"
                  type="text"
                  id="device-id"
                  name="device"
                  placeholder="my-device-id"
                  required
                  autocomplete="on"
                  autocorrect="off"
                  autocapitalize="off"
                  spellcheck="false"
                  style="text-transform: lowercase;">
            <div class="invalid-feedback">
              Device ID can't be empty.
            </div>
          </div>
        </div>
        

        <div class="form-row" 
            id="device-period"
            data-date-format="yyyy-mm-dd"
            data-date-end-date="0d"
            data-date-autoclose="true"
            data-date-clear-btn="true"
            data-date-today-btn="linked">

          <div class="form-group col-md-6">
            <label for="device-start-date">Start Date</label>
            <input 
              type="text"
              id="device-start-date"
              name="startdate"
              class="form-control date-range-device"
              autocomplete="off">
            <div class="invalid-feedback">
              A start date needs to be selected.
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="device-end-date">End Date</label>
            <input 
              type="text"
              id="device-end-date"
              name="enddate"
              class="form-control date-range-device"
              autocomplete="off">
            <div class="invalid-feedback">
              An end date needs to be selected.
            </div>
          </div>
        </div>


        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" 
                    class="form-check-input" 
                    id="deviceGateways" 
                    name="gateways"
                    checked>
            <label class="form-check-label" for="deviceGateways">
              Show markers for gateways
            </label>
          </div>
          <div class="form-check">
            <input type="checkbox" 
                    class="form-check-input" 
                    id="deviceLines" 
                    name="lines"
                    checked>
            <label class="form-check-label" for="deviceLines">
              Draw lines between gateway and measurement location
            </label>
          </div>
          <div class="form-check">
            <input type="checkbox" 
                    class="form-check-input" 
                    id="deviceCircles" 
                    name="points"
                    checked>
            <label class="form-check-label" for="deviceCircles">
              Draw a circle at measurement location
            </label>
          </div>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-secondary" formaction="/devices/csv-pg.php">CSV data</button>
          <button type="submit" class="btn btn-primary" formaction="/devices/">View Map</button>
        </div>
      </form>


  </div>
</div>



<div class="card mt-4">
  <h5 class="card-header">Gateway data</h5>
  <div class="card-body">


      <p>Draw circles or radials for every measurement made for a specific gateway on a specific day or range of days. The result will be limited to the 10000 latest measurements for the selected time range.</p>

      <form method="get" class="needs-validation" novalidate target="_blank">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="gateway-id">Gateway ID</label>
            <input class="form-control"
                  type="text"
                  id="gateway-id"
                  name="gateway"
                  placeholder="eui-0123456789abcdef"
                  required
                  autocomplete="on"
                  autocorrect="off"
                  autocapitalize="off"
                  spellcheck="false">
            <div class="invalid-feedback">
              Gateway ID can't be empty.
            </div>
          </div>
        </div>
        

        <div class="form-row" 
            id="gateway-period"
            data-date-format="yyyy-mm-dd"
            data-date-end-date="0d"
            data-date-autoclose="true"
            data-date-clear-btn="true"
            data-date-today-btn="linked">

          <div class="form-group col-md-6">
            <label for="gateway-start-date">Start Date</label>
            <input 
              type="text"
              id="gateway-start-date"
              name="startdate"
              class="form-control date-range-gateway"
              autocomplete="off">
            <div class="invalid-feedback">
              A start date needs to be selected.
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="gateway-end-date">End Date</label>
            <input 
              type="text"
              id="gateway-end-date"
              name="enddate"
              class="form-control date-range-gateway"
              autocomplete="off">
            <div class="invalid-feedback">
              An end date needs to be selected.
            </div>
          </div>
        </div>


        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" 
                    class="form-check-input" 
                    id="gatewayGateways" 
                    name="gateways"
                    checked>
            <label class="form-check-label" for="gatewayGateways">
              Show marker for gateway
            </label>
          </div>
          <div class="form-check">
            <input type="checkbox" 
                    class="form-check-input" 
                    id="gatewayLines" 
                    name="lines"
                    checked>
            <label class="form-check-label" for="gatewayLines">
              Draw lines between gateway and measurement location
            </label>
          </div>
          <div class="form-check">
            <input type="checkbox" 
                    class="form-check-input" 
                    id="gatewayCircles" 
                    name="points"
                    checked>
            <label class="form-check-label" for="gatewayCircles">
              Draw a circle at measurement location
            </label>
          </div>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-secondary" formaction="/gateways/csv.php">CSV data</button>
          <button type="submit" class="btn btn-primary" formaction="/gateways/">View Map</button>
        </div>
      </form>


  </div>
</div>


<div class="card mt-4">
  <h5 class="card-header">Show Experiment Data</h5>
  <div class="card-body">

    <p>Draw circles or radials for every measurement made using a specific experiment on a specific day or range of days. The result will be limited to the 10000 latest measurements for the selected time range.</p>

      <form method="get" class="needs-validation" novalidate target="_blank">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="experiment-name">Experiment Name</label>
            <input class="form-control"
                  type="text"
                  id="experiment-name"
                  name="experiment"
                  placeholder="My Experiment Name 2019-09-09"
                  required
                  autocomplete="on"
                  autocorrect="off"
                  autocapitalize="off"
                  spellcheck="false">
            <div class="invalid-feedback">
              Experiment name can't be empty.
            </div>
          </div>
        </div>
        

        <div class="form-row" 
            id="experiment-period"
            data-date-format="yyyy-mm-dd"
            data-date-end-date="0d"
            data-date-autoclose="true"
            data-date-clear-btn="true"
            data-date-today-btn="linked">

          <div class="form-group col-md-6">
            <label for="experiment-start-date">Start Date</label>
            <input 
              type="text"
              id="experiment-start-date"
              name="startdate"
              class="form-control date-range-experiment"
              autocomplete="off">
            <div class="invalid-feedback">
              A start date needs to be selected.
            </div>
          </div>
          <div class="form-group col-md-6">
            <label for="experiment-end-date">End Date</label>
            <input 
              type="text"
              id="experiment-end-date"
              name="enddate"
              class="form-control date-range-experiment"
              autocomplete="off">
            <div class="invalid-feedback">
              An end date needs to be selected.
            </div>
          </div>
        </div>


        <div class="form-group">
          <div class="form-check">
            <input type='hidden' value='on' name='gateways' id="experimentShowGateways">
            <input type="checkbox" 
                    class="form-check-input"
                    onclick="this.checked ? document.getElementById('experimentShowGateways').value = 'on' : document.getElementById('experimentShowGateways').value = 'off' "
                    checked>
            <label class="form-check-label" for="experimentGateways">
              Show marker for gateway
            </label>
          </div>
          <div class="form-check">
            <input type='hidden' value='on' name='lines' id="experimentShowLines">
            <input type="checkbox" 
                    class="form-check-input"
                    onclick="this.checked ? document.getElementById('experimentShowLines').value = 'on' : document.getElementById('experimentShowLines').value = 'off' "
                    checked>
            <label class="form-check-label" for="experimentLines">
              Draw lines between gateway and measurement location
            </label>
          </div>
          <div class="form-check">
            <input type='hidden' value='on' name='points' id="experimentShowPoints">
            <input type="checkbox" 
                    class="form-check-input"
                    onclick="this.checked ? document.getElementById('experimentShowPoints').value = 'on' : document.getElementById('experimentShowPoints').value = 'off' "
                    checked>
            <label class="form-check-label" for="experimentCircles">
              Draw a circle at measurement location
            </label>
          </div>
        </div>
        
        <div class="form-group">
          <button type="submit" class="btn btn-secondary" formaction="/experiments/csv.php">CSV data</button>
          <button type="submit" class="btn btn-primary" formaction="/experiments/">View Map</button>
        </div>

      </form>
  </div>
</div>


<div class="card mt-4">
  <h5 class="card-header">List All Experiments</h5>
  <div class="card-body">

    <p>List all experiments and search through them.</p>

      <form method="get" class="needs-validation" novalidate target="_blank">
        <div class="form-group">
          <a href="/experiments/list.php">
            <button type="submit" class="btn btn-primary" formaction="/experiments/list.php">List All</button>
          </a>
        </div>

      </form>
  </div>
</div>


<div class="card mt-4">
  <h5 class="card-header">Aggregated data for specific gateways</h5>
  <div class="card-body">

    <form id="agg-gateways-form">
      
      <div  class="form-group">
        <div class="col-md-4 mb-0">
          <label>Gateway IDs</label>
          <small id="emailHelp" class="form-text text-muted">Click + to view multiple.</small>
        </div>

        <div id="agg-gateway-list">
          <div class="entry input-group col-md-4">
            <input class="form-control"
                    type="text"
                    id="agg-gateways-gateway-id"
                    name="gateway[]"
                    placeholder="eui-0123456789abcdef"
                    autocomplete="on"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    style="text-transform: lowercase;">
            <div class="input-group-apend">
              <button class="btn btn-success btn-gwid-add" type="button">
                <span class="oi oi-plus"></span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="agg-gateways-radio-type" id="alpha" value="alpha" checked>
          <label class="form-check-label" for="alpha">
            Area plot (alpha shapes)
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="agg-gateways-radio-type" id="radar" value="radar">
          <label class="form-check-label" for="radar">
            Colour radar plot
          </label>
        </div>
        <!-- <div class="form-check">
          <input class="form-check-input" type="radio" name="agg-gateways-radio-type" id="heatmap" value="heatmap" disabled>
          <label class="form-check-label" for="heatmap">
            Heatmap
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="agg-gateways-radio-type" id="circle" value="circle">
          <label class="form-check-label" for="circle">
            Circles
          </label>
        </div> -->
        <!-- <div class="form-check">
          <input class="form-check-input" type="radio" name="mapType" id="raw" value="raw" disabled>
          <label class="form-check-label" for="raw">
            Raw data points
          </label>
        </div> -->
      </div>

      <div class="form-group">
        <button id="agg-gateways-btn-map" class="btn btn-primary">View Map</button>
      </div>
      
    </form>

  </div>
</div>

<p>&nbsp;</p>

</div>



  <!-- Bootstrap -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha256-bqVeqGdJ7h/lYPq6xrPv/YGzMEb6dNxlfiTUHSgRCp8=" crossorigin="anonymous"></script>

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
  <script src="index-logic.js"></script>

</body>
</html>
