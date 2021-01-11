<?php

function add_to_db($values) {

  # Trim the time so that mysql is happy
  if( strpos($values["time"], '.') !== false)
  {
    $values["time"] = explode(".", $values["time"])[0];
  }

  $settings = parse_ini_file(getenv("TTNMAPPER_HOME")."/settings.conf",true);

  $username = $settings['database_mysql']['username'];
  $password = $settings['database_mysql']['password'];
  $dbname = $settings['database_mysql']['database'];
  $servername = $settings['database_mysql']['host'];

  $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ( isset($values["experiment"]) )
  {
    // prepare sql and bind parameters
    $stmt = $conn->prepare("INSERT INTO experiments 
        (time, nodeaddr, appeui, gwaddr,
        modulation, datarate, snr, rssi, freq, fcount,
        lat, lon, alt, accuracy, hdop, sats, provider,
        user_agent, user_id, name) 
        VALUES 
        (:time, :nodeaddr, :appeui, :gwaddr,
        :modulation, :datarate, :snr, :rssi, :freq, :fcount,
        :lat, :lon, :alt, :accuracy, :hdop, :sats, :provider,
        :user_agent, :user_id, :name)");
    $stmt->bindParam(':name', $values['experiment']);
  }
  else
  {
    // // Prevent duplicates
    // $stmt = $conn->prepare("SELECT * FROM packets WHERE abs(lat-:lat)<0.0001 AND abs(lon-:lon)<0.0001 AND gwaddr=:gwaddr AND nodeaddr=:nodeaddr AND time>DATE_SUB(:servertime, INTERVAL 1 HOUR)");
    // $stmt->bindParam(':lat', $values['lat']);
    // $stmt->bindParam(':lon', $values['lon']);
    // $stmt->bindParam(':gwaddr', $values['gtw_id']);
    // $stmt->bindParam(':nodeaddr', $values['dev_id']);
    // $stmt->bindParam(':servertime', $values['time']);
    // $stmt->execute();

    // /* Check the number of rows that match the SELECT statement */
    // if ($stmt->fetchColumn() > 0) {
    //   //print "already exists";
    //   return false;
    // }

    // prepare sql and bind parameters
    $stmt = $conn->prepare("INSERT INTO packets 
        (time, nodeaddr, appeui, gwaddr,
        modulation, datarate, snr, rssi, freq, fcount,
        lat, lon, alt, accuracy, hdop, sats, provider,
        user_agent, user_id) 
        VALUES 
        (FROM_UNIXTIME(:time), :nodeaddr, :appeui, :gwaddr,
        :modulation, :datarate, :snr, :rssi, :freq, :fcount,
        :lat, :lon, :alt, :accuracy, :hdop, :sats, :provider,
        :user_agent, :user_id)");
  }
  $stmt->bindParam(':time', $values['time']);
  $stmt->bindParam(':nodeaddr', $values['dev_id']);
  $stmt->bindParam(':appeui', $values['app_id']);
  $stmt->bindParam(':gwaddr', $values['gtw_id']);
  $stmt->bindParam(':modulation', $values['modulation']);
  $stmt->bindParam(':datarate', $values['data_rate']);
//TODO: coding rate
  $stmt->bindParam(':snr', $values['snr']);
  $stmt->bindParam(':rssi', $values['rssi']);
  $stmt->bindParam(':freq', $values['frequency']);
  $stmt->bindParam(':fcount', $values['counter']);
  $stmt->bindParam(':lat', $values['lat']);
  $stmt->bindParam(':lon', $values['lon']);
  $stmt->bindParam(':alt', $values['alt']);
  $stmt->bindParam(':accuracy', $values['acc']);
  $stmt->bindParam(':hdop', $values['hdop']);
  $stmt->bindParam(':sats', $values['sats']);
  $stmt->bindParam(':provider', $values['provider']);
  $stmt->bindParam(':user_agent', $values['user_agent']);
  $stmt->bindParam(':user_id', $values['user_id']);

  $stmt->execute();

  $conn = null;
  return true;
}
?>
