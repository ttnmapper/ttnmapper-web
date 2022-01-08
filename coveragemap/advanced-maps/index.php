<!DOCTYPE html>
<html lang="en">
<?php require getenv("TTNMAPPER_HOME").'/coveragemap/head.php'; ?>
<body>


<!-- Image and text -->
<nav class="navbar navbar-fixed-top navbar-expand-lg navbar-light bg-light">

    <a class="navbar-brand" href="/">
        <img src="<?php echo $brandIcon; ?>" width="auto" height="32" class="d-inline-block align-top" alt="">
        Coverage Map
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="/heatmap/">Helium Heatmap</a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="/advanced-maps/">Advanced maps</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://ttnmapper.org">The Things Network</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://docs.ttnmapper.org">Docs</a>
            </li>
        </ul>
    </div>

    <div class="navbar-collapse collapse w-100 order-3 dual-collapse2">
        <ul class="navbar-nav ml-auto">
<!--            <li class="nav-item">-->
<!--                <a href="https://www.patreon.com/ttnmapper" data-patreon-widget-type="become-patron-button"><img src="/resources/become_a_patron_button@2x.png" class="d-inline-block align-middle" alt="" height="36" title="Patreon"></a>-->
<!--            </li>-->
        </ul>
    </div>

</nav>


<div class="container ">
    <h1 class="mt-4">Advanced Maps</h1>

    <div class="card mt-4">
        <h5 class="card-header">Device data</h5>
        <div class="card-body">


            <p>Draw circles or radials for every measurement made by a specific device on a specific day or range of
                days. The result will be limited to the 10000 latest measurements for the selected time range.</p>

            <form method="get" class="needs-validation" novalidate target="_blank">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="device-id">Device Name</label>
                        <input class="form-control"
                               type="text"
                               id="device-id"
                               name="device"
                               placeholder="my-device-name"
                               required
                               autocomplete="on"
                               autocorrect="off"
                               autocapitalize="off"
                               spellcheck="false">
                        <div class="invalid-feedback">
                            Device Name can't be empty.
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
                            Show markers for hotspots
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input"
                               id="deviceLines"
                               name="lines"
                               checked>
                        <label class="form-check-label" for="deviceLines">
                            Draw lines between hotspot and measurement location
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
                    <button type="submit" class="btn btn-primary" formaction="/devices/">View Map</button>
                    <button type="submit" class="btn btn-secondary" formaction="/devices/csv-pg.php">CSV data</button>
                </div>
            </form>


        </div>
    </div>


    <div class="card mt-4">
        <h5 class="card-header">Hotspot data</h5>
        <div class="card-body">


            <p>Draw circles or radials for every measurement made for a specific hotspot on a specific day or range of
                days. The result will be limited to the 10000 latest measurements for the selected time range.</p>

            <form method="get" class="needs-validation" novalidate target="_blank">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="gateway-id">Hotspot Name (three words) or ID (address)</label>
                        <input class="form-control"
                               type="text"
                               id="gateway-id"
                               name="gateway"
                               placeholder="angry-purple-tiger"
                               required
                               autocomplete="on"
                               autocorrect="off"
                               autocapitalize="off"
                               spellcheck="false">
                        <div class="invalid-feedback">
                            Hotspot ID can't be empty.
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
                            Show marker for hotspot
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input"
                               id="gatewayLines"
                               name="lines"
                               checked>
                        <label class="form-check-label" for="gatewayLines">
                            Draw lines between hotspot and measurement location
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
                    <button type="submit" class="btn btn-primary" formaction="/gateways/">View Map</button>
                    <button type="submit" class="btn btn-secondary" formaction="/gateways/csv-pg.php">CSV data</button>
                </div>
            </form>


        </div>
    </div>


    <div class="card mt-4">
        <h5 class="card-header">Experiment Data</h5>
        <div class="card-body">

            <p>Draw circles or radials for every measurement made using a specific experiment on a specific day or range
                of days. The result will be limited to the 10000 latest measurements for the selected time range.</p>

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
                            Show marker for hotspot
                        </label>
                    </div>
                    <div class="form-check">
                        <input type='hidden' value='on' name='lines' id="experimentShowLines">
                        <input type="checkbox"
                               class="form-check-input"
                               onclick="this.checked ? document.getElementById('experimentShowLines').value = 'on' : document.getElementById('experimentShowLines').value = 'off' "
                               checked>
                        <label class="form-check-label" for="experimentLines">
                            Draw lines between hotspot and measurement location
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
                    <button type="submit" class="btn btn-primary" formaction="/experiments/">View Map</button>
                    <button type="submit" class="btn btn-secondary" formaction="/experiments/csv-pg.php">CSV data
                    </button>
                </div>

            </form>
        </div>
    </div>


    <div class="card mt-4">
        <h5 class="card-header">Find Experiment</h5>
        <div class="card-body">

            <p>Search for an experiment based on a part of its name.</p>

            <form method="get" class="needs-validation form-inline" novalidate target="_blank">
                <div class="form-group">
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
                <button type="submit" class="btn btn-primary" formaction="/experiments/list.php">Search</button>

            </form>
        </div>
    </div>

    <p>&nbsp;</p>

</div>


<!-- Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"
        integrity="sha256-bqVeqGdJ7h/lYPq6xrPv/YGzMEb6dNxlfiTUHSgRCp8=" crossorigin="anonymous"></script>

<!-- Leaflet -->
<script src="/libs/leaflet/leaflet.js"></script>
<script src="/libs/leaflet.measure/leaflet.measure.js"></script>
<script src="/libs/Leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<!-- HTML entity escaping -->
<script src="/libs/he/he.js"></script>

<!-- Moment for datetime manipulation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- The map style -->
<script type="text/javascript" src="/theme.php"></script>
<script type="text/javascript" src="/common.js"></script>
<!-- The actual main logic for this page -->
<script src="index-logic.js"></script>

</body>
</html>
