#!/usr/bin/python
import json
import sys

def format():
  return "preparsed_json"

def parse(payload):
  data = payload["payload_fields"]
  values = {}
  if("location" in data):
    if("lat" in data["location"]):
      values["lat"] = data["location"]["lat"]

    if("lon" in data["location"]):
      values["lon"] = data["location"]["lon"]
    elif("lng" in data["location"]):
      values["lon"] = data["location"]["lng"]

    if("alt" in data["location"]):
      values["alt"] = data["location"]["alt"]
    if("acc" in data["location"]):
      values["acc"] = data["location"]["acc"]
    if("hdop" in data["location"]):
      values["acc"] = data["location"]["hdop"]

  if("gps_1" in data):
    if("altitude" in data["gps_1"]):
      values["alt"] = data["gps_1"]["altitude"]
    if("latitude" in data["gps_1"]):
      values["lat"] = data["gps_1"]["latitude"]
    if("longitude" in data["gps_1"]):
      values['lon'] = data["gps_1"]["longitude"]

  if("gps_8" in data):
    if("altitude" in data["gps_8"]):
      values["alt"] = data["gps_8"]["altitude"]
    if("latitude" in data["gps_8"]):
      values["lat"] = data["gps_8"]["latitude"]
    if("longitude" in data["gps_8"]):
      values['lon'] = data["gps_8"]["longitude"]

  if("lat" in data):
    values["lat"] = data["lat"]
  elif("latitude" in data):
    values["lat"] = data["latitude"]
  elif("Latitude" in data):
    values["lat"] = data["Latitude"]

  if("lon" in data):
    values["lon"] = data["lon"]
  elif("lng" in data):
    values["lon"] = data["lng"]
  elif("longitude" in data):
    values["lon"] = data["longitude"]
  elif("Longitude" in data):
    values["lon"] = data["Longitude"]

  if("alt" in data):
    values['alt'] = data['alt']
  elif("altitude" in data):
    values['alt'] = data['altitude']
  elif("Altitude" in data):
    values['alt'] = data['Altitude']
  elif("height" in data):
    values['alt'] = data['height']
  elif("gpsalt" in data):
    values['alt'] = data['gpsalt']

  if("hdop" in data):
    values['acc'] = data['hdop']
  elif("acc" in data):
    values['acc'] = data['acc']
  elif("accuracy" in data):
    values['acc'] = data['accuracy']
  elif("hacc" in data):
    values['acc'] = data['hacc']

  if("sats" in data):
    values["sats"] = data["sats"]
  elif("satellites" in data):
    values["sats"] = data["satellites"]

  # if(not "lon" in values):
  #   values["lon"] = 50.754607
  # if(not "lat" in values):
  #   values["lat"] = 6.020931

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  print arv
  if(len(arv)>0):
    print parse(json.loads(arv[0]))
  else:
    print (format()+" parser")