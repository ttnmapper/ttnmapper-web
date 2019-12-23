<?php
$root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
?>

<a href="<?php echo $root;?>">Home</a> | 
<a href="<?php echo $root;?>special_maps.php">Advanced maps</a> | 
<a href="<?php echo $root;?>leaderboard.php">Leader board</a> | 
<a href="<?php echo $root;?>faq.php">FAQ</a> | 
<a href="<?php echo $root;?>acknowledgements.php">Acknowledgements</a>