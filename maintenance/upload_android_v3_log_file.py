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

  req = urllib2.Request('http://mapper.packetworx.net/integrations/android/v3/')
  req.add_header('Content-Type', 'application/json')

  #print (line_count, req)
  response = urllib2.urlopen(req, json.dumps(data))
  print (data["metadata"]["time"]+": "+response.read())

