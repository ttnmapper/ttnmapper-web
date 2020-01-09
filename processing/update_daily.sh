#!/bin/bash -l

$TTNMAPPER_HOME/processing/stats_per_device.py
$TTNMAPPER_HOME/processing/stats_per_application.py
#$TTNMAPPER_HOME/processing/aggregate_gateways_lastheard.py
$TTNMAPPER_HOME/processing/aggregate_gateways_details.py
$TTNMAPPER_HOME/processing/gateway_descriptions.py
#$TTNMAPPER_HOME/processing/points_to_radar_db.py
$TTNMAPPER_HOME/processing/circles_all_geojson.py
#$TTNMAPPER_HOME/processing/circles_single.py
#$TTNMAPPER_HOME/processing/radials_all_geojson.py
#$TTNMAPPER_HOME/processing/radials_all_geojson_5mdeg.py
#$TTNMAPPER_HOME/processing/radials_per_gateway_geojson.py
#$TTNMAPPER_HOME/processing/radials_per_gateway_kml.py
#$TTNMAPPER_HOME/processing/heatmap_per_gateway_kml.py
#$TTNMAPPER_HOME/processing/radar_db_to_geojson_colour.py
#$TTNMAPPER_HOME/processing/radar_db_to_geojson_single.py
$TTNMAPPER_HOME/processing/concave_huls_multi.py
#$TTNMAPPER_HOME/processing/points_to_radar_db.py --force all
#$TTNMAPPER_HOME/processing/createDump.py

$TTNMAPPER_HOME/processing/aggregate_to_db_5mdeg.py
$TTNMAPPER_HOME/processing/aggregate_to_db.py
