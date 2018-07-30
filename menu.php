<?php
$root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
?>

<div style="font-size:120%; padding: 0px; font-weight: bold;">Menu</div>
<div style="margin-bottom:0; margin-top:0;">
<a href="<?php echo $root;?>">Home</a><br>
<a href="<?php echo $root;?>special_maps.php">Advanced map options</a><br />
<a href="<?php echo $root;?>heatmap/">Heatmap (beta)</a><br />
<a href="<?php echo $root;?>colour-radar/">Colour radar</a><br />
<a href="<?php echo $root;?>leaderboard.php">Leader board</a><br />
<!--<a href="http://www.jpmeijers.com/ttnmapper/kml">Download KML files</a><br>
<a href="http://www.jpmeijers.com/ttnmapper/geojson">Download GEOJSON files</a><br>-->
<!--<a href="http://forum.thethingsnetwork.org/t/measuring-coverage-gateway/1051/50?u=jpmeijers">
How to contribute<br />How this works</a><br />-->
<!--<a href="https://play.google.com/store/apps/details?id=com.jpmeijers.ttnmapper">Download app</a><br />-->
<a href="<?php echo $root;?>faq.php">Help / FAQ / Contribute</a><br />
<a href="<?php echo $root;?>acknowledgements.php">Acknowledgements</a><br />
<a href="http://jpmeijers.com/blog/node/3">Contact</a><br>
</div>
