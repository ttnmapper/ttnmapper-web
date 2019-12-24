<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>TTN Mapper</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker3.css" integrity="sha256-AghQEDQh6JXTN1iI/BatwbIHpJRKQcg2lay7DE5U/RQ=" crossorigin="anonymous" />
  <link rel="stylesheet" href="/libs/open-iconic/font/css/open-iconic-bootstrap.min.css" />

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
    .entry:not(:first-of-type)
    {
        margin-top: 10px;
    }
    .oi
    {
        font-size: 12px;
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
          <a class="nav-link active" href="/advanced-maps/">Advanced maps</a>
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
          <a href="https://www.patreon.com/ttnmapper" data-patreon-widget-type="become-patron-button"><img src="/resources/become_a_patron_button@2x.png" class="d-inline-block align-middle" alt="" height="36" title="Patreon"></a>
        </li>
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
          <button type="submit" class="btn btn-secondary" formaction="/devices/csv.php">CSV data</button>
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
            <input type='hidden' value='off' name='lines' id="experimentShowLines">
            <input type="checkbox" 
                    class="form-check-input"
                    onclick="this.checked ? document.getElementById('experimentShowLines').value = 'on' : document.getElementById('experimentShowLines').value = 'off' "
                    checked>
            <label class="form-check-label" for="experimentLines">
              Draw lines between gateway and measurement location
            </label>
          </div>
          <div class="form-check">
            <input type='hidden' value='off' name='points' id="experimentShowPoints">
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
          <button type="submit" class="btn btn-primary" formaction="/experiments/map.php">View Map</button>
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



<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js" integrity="sha256-bqVeqGdJ7h/lYPq6xrPv/YGzMEb6dNxlfiTUHSgRCp8=" crossorigin="anonymous"></script>

<script>
  $('#device-period').datepicker({
    inputs: $('.date-range-device')
  });
  $('#gateway-period').datepicker({
    inputs: $('.date-range-gateway')
  });
  $('#experiment-period').datepicker({
    inputs: $('.date-range-experiment')
  });


  // Dynamically add more gateway id fiels
  $(document).on('click', '.btn-gwid-add', function(e)
  {
      e.preventDefault();

      var controlForm = $("#agg-gateway-list"),
          currentEntry = $(this).parents('.entry:first'),
          newEntry = $(currentEntry.clone()).appendTo(controlForm);

      newEntry.find('input').val('');
      controlForm.find('.entry:not(:last) .btn-gwid-add')
          .removeClass('btn-gwid-add').addClass('btn-gwid-remove')
          .removeClass('btn-success').addClass('btn-danger')
          .html('<span class="oi oi-minus"></span>');
  }).on('click', '.btn-gwid-remove', function(e)
  {
    $(this).parents('.entry:first').remove();

    e.preventDefault();
    return false;
  });


  // $('input[type=radio][name=agg-gateways-radio-type]').change(function() {
  //     if (this.value == 'alpha') {
  //       $('#agg-gateways-form').attr('action', "/alpha-shapes/");
  //     }
  //     else if (this.value == 'radar') {
  //       $('#agg-gateways-form').attr('action', "/colour-radar/");
  //     }
  // });

  $("#agg-gateways-btn-map").click(function(event){
    event.preventDefault();

    var tempForm = $('<form id="tempForm" method="POST" target="_blank"></form>');

    // Depending on radio, choose action
    var radioValue = $("input[type=radio][name=agg-gateways-radio-type]:checked").val();
    if (radioValue == 'alpha') {
      tempForm.attr('action', "/alpha-shapes/");
    }
    else if (radioValue == 'radar') {
      tempForm.attr('action', "/colour-radar/");
    }
    
    //agg-gateways-gateway-id
    tempForm.attr('action', tempForm.attr('action')+"?");
    $('#agg-gateways-form *').filter('#agg-gateways-gateway-id').each(function(){
        //tempForm.append('<input type="hidden" name="gateway[]" value="' + this.value + '" /> ');
        if(this.value != "") {
          tempForm.attr('action', tempForm.attr('action')+"gateway[]="+this.value+"&");
        }

    });
    
    var currentAction = tempForm.attr('action');
    tempForm.attr('action', currentAction.substring(0, currentAction.length - 1));
    console.log(tempForm.attr('action'));
    tempForm.appendTo(document.body).submit();
    $("#tempForm").remove();
  });

</script>

<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>

</body>
</html>