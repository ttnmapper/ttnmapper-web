<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Redirect to TTN Mapper mobile app</title>
</head>

<body>
<?php
$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
If you see this page, it means that either you do not have the TTN Mapper app installed, or you have opted out for universal linking.
<ul>
<li>Make sure you have TTN Mapper installed.<br >
  <a href="https://itunes.apple.com/us/app/ttn-mapper/id1128809850">Click here for iOS</a><br />
  <a href="https://play.google.com/store/apps/details?id=com.jpmeijers.ttnmapper&hl=en">Click here for Android.</a>
<li>If you already have the app installed, try long-pressing <a href="<?php echo $actual_link; ?>">HERE</a> to try again.
</ul>
</body>

</html>