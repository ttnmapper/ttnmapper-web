<?php
function check_data($values) {
  #Less than 4 satellites is not accurate enough
  if( isset($values["sats"]) ) {
    if($values["sats"]<4) {
      return_error("less than 4 satellites");
      return false;
    }
  }

  #acc must be lower than 10m
  if( isset($values["acc"]) ) {
    if($values["acc"]>10) {
      return_error("acc too high");
      return false;
    }
  }

  #hdop must be lower than 5
  if( isset($values["hdop"]) ) {
    if($values["hdop"]>5) {
      return_error("hdop too high");
      return false;
    }
  }

  #lat
  if( !isset($values["lat"]) ) {
    return_error("lat not set");
    return false;
  }

  if( $values["lat"] >= 90 || $values["lat"] <= -90 || $values["lat"] == 0 ) {
    return_error("lat out of range");
    return false;
  }

  #lon
  if( !isset($values["lon"]) ) {
    return_error("lon not set");
    return false;
  }

  if( $values["lon"] >= 180 || $values["lon"] <= -180 || $values["lon"] == 0 ) {
    return_error("lon out of range");
    return false;
  }

  #bounding box around 0,0 point for incorrectly parsed coordinates
  if( $values["lat"] <1 && $values["lat"] > -1
    && $values["lon"] <1 && $values["lon"] > -1 ) {
    return_error("null island");
    return false;
  }

  return true;

}

function sanitize_data($values) {

  # altitude clamp to ground if unknown or unknown negative
  if( !isset($values["alt"]) ) {
    $values["alt"] = 0;
  }

  if( $values["alt"] == 65535) {
    $values["alt"] = 0;
  }

  if(isset($values["lat"]))
  {
    $values["lat"] = round($values["lat"], 6);
  }

  if(isset($values["lon"]))
  {
    $values["lon"] = round($values["lon"], 6);
  }

  #accuracy
  if( !isset($values["acc"]) ) {
    $values["acc"] = null;
  }
  if( !isset($values["sats"]) ) {
    $values["sats"] = null;
  }
  if( !isset($values["hdop"]) ) {
    $values["hdop"] = null;
  }

  return $values;
}
?>