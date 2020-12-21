<?php

function parse_payload_fields($data) {
  $values = array();

  // Cayenne LPP formats
  for($i = 0; $i < 10; ++$i) {
    $key = "gps_".$i;
    if( array_key_exists($key, $data) ) {
      if( array_key_exists('altitude', $data[$key]) ) {
        $values["alt"] = $data[$key]["altitude"];
      }
      if( array_key_exists('latitude', $data[$key]) ) {
        $values["lat"] = $data[$key]["latitude"];
      }
      if( array_key_exists('longitude', $data[$key]) ) {
        $values['lon'] = $data[$key]["longitude"];
      }
      $values["provider"] = "Cayenne LPP";
    }
  }


  // location object
  if ( array_key_exists('location', $data) ) {
    $values = array_merge($values, extract_from_root($data['location']) );
  }

  // and try root
  $values = array_merge($values, extract_from_root($data) );

  return $values;
}

function extract_from_root($data) {
  $values = array();

  // objects in root
  if( array_key_exists('lat', $data) ) {
    $values["lat"] = $data["lat"];
  }
  elseif( array_key_exists('latitude', $data) ) {
    $values["lat"] = $data["latitude"];
  }
  elseif( array_key_exists('Latitude', $data) ) {
    $values["lat"] = $data["Latitude"];
  }
  elseif( array_key_exists('latitudeDeg', $data) ) {
    $values["lat"] = $data["latitudeDeg"];
  }
  elseif( array_key_exists('gps_lat', $data) ) {
    $values["lat"] = $data["gps_lat"];
  }

  if( array_key_exists('lon', $data) ) {
    $values["lon"] = $data["lon"];
  }
  elseif( array_key_exists('lng', $data) ) {
    $values["lon"] = $data["lng"];
  }
  elseif( array_key_exists('long', $data) ) {
    $values["lon"] = $data["long"];
  }
  elseif( array_key_exists('longitude', $data) ) {
    $values["lon"] = $data["longitude"];
  }
  elseif( array_key_exists('Longitude', $data) ) {
    $values["lon"] = $data["Longitude"];
  }
  elseif( array_key_exists('longitudeDeg', $data) ) {
    $values["lon"] = $data["longitudeDeg"];
  }
  elseif( array_key_exists('gps_lng', $data) ) {
    $values["lon"] = $data["gps_lng"];
  }

  if( array_key_exists('alt', $data) ) {
    $values['alt'] = $data['alt'];
  }
  elseif( array_key_exists('altitude', $data) ) {
    $values['alt'] = $data['altitude'];
  }
  elseif( array_key_exists('Altitude', $data) ) {
    $values['alt'] = $data['Altitude'];
  }
  elseif( array_key_exists('height', $data) ) {
    $values['alt'] = $data['height'];
  }
  elseif( array_key_exists('gpsalt', $data) ) {
    $values['alt'] = $data['gpsalt'];
  }
  elseif( array_key_exists('gps_alt', $data) ) {
    $values['alt'] = $data['gps_alt'];
  }

  if( array_key_exists('hdop', $data) ) {
    $values['hdop'] = $data['hdop'];
    $values["provider"] = "HDOP";
  }
  elseif( array_key_exists('gps_hdop', $data) ) {
    $values['hdop'] = $data['gps_hdop'];
    $values["provider"] = "HDOP";
  }
  elseif( array_key_exists('acc', $data) ) {
    $values['acc'] = $data['acc'];
    $values["provider"] = "acc";
  }
  elseif( array_key_exists('accuracy', $data) ) {
    $values['acc'] = $data['accuracy'];
    $values["provider"] = "accuracy";
  }
  elseif( array_key_exists('hacc', $data) ) {
    $values['acc'] = $data['hacc'];
    $values["provider"] = "hacc";
  }

  if( array_key_exists('sats', $data) ) {
    $values["sats"] = $data["sats"];
    $values["provider"] = "sats";
  }
  elseif( array_key_exists('satellites', $data) ) {
    $values["sats"] = $data["satellites"];
    $values["provider"] = "sats";
  }
  elseif( array_key_exists('numsat', $data) ) {
    $values["sats"] = $data["numsat"];
    $values["provider"] = "sats";
  }

  if( array_key_exists('provider', $data) ) {
    $values["provider"] = $data["provider"];
  }

  return $values;
}
?>
