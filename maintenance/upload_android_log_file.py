#!/usr/bin/python
from __future__ import print_function
import paho.mqtt.client as mqtt
import json
import sys
import urllib2
import base64
import os
from pprint import pprint

filename = ""
line_count = 0

if len(sys.argv) > 1:
    filename = sys.argv[1]
    
print ("Reading file: "+filename)

with open(filename, 'r') as content_file:
    content = content_file.read()
    
for line in content.split("\n"):
  line_count+=1
  data = json.loads(line)

  data_dict = {}
  data_dict["time"]=data["metadata"]["time"]
  data_dict["nodeaddr"]=data["dev_id"]
  data_dict["appeui"]=data["app_id"]
  data_dict["datarate"]=data["metadata"]["data_rate"]
  data_dict["freq"]=data["metadata"]["frequency"]
  data_dict["lat"]=data["phone_lat"]
  data_dict["lon"]=data["phone_lon"]
  data_dict["alt"]=data["phone_alt"]
  data_dict["accuracy"]=data["phone_loc_acc"]
  data_dict["provider"]=data["phone_loc_provider"]
  data_dict["mqtt_topic"]=data["mqtt_topic"]
  data_dict["user_agent"]=data["user_agent"]
  data_dict["iid"]="android-log-file"

  for gateway in data["metadata"]["gateways"]:
    data_dict["gwaddr"]=gateway["gtw_id"]
    if "snr" in gateway:
      data_dict["snr"]=gateway["snr"]
    else:
      data_dict["snr"]=None
    data_dict["rssi"]=gateway["rssi"]

    req = urllib2.Request('https://ttnmapper.org/appapi/upload.php')
    req.add_header('Content-Type', 'application/json')

    #print (line_count, req)
    response = urllib2.urlopen(req, json.dumps(data_dict))
    print (data["metadata"]["time"]+": "+response.read())

