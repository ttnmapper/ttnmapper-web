#!/usr/bin/python
import sys
import base64
import re

def format():
  return "ursm"

def parse(payload):
  values = {}
  payload = base64.b64decode(payload["payload_raw"])
  payload = re.split(" +",payload)
  if(len(payload)!=5):
    print "Invalid length packet"
    return False
      
  lat = int(float(payload[0])/100000.0)
  lat = round((float(payload[0])/100000.0 - lat)/60*100 + lat,5)
  lon = int(float(payload[1])/100000.0)
  lon = round((float(payload[1])/100000.0 - lon)/60*100 + lon,5)
  alt = float(payload[2])
  acc = float(payload[3])

  values['lat'] = lat
  values['lon'] = lon
  values['acc'] = acc
  values['alt'] = alt

  if(values['lat'] == 0 or values['lon'] == 0
    or values['lat']>90 or values['lat']<-90
    or values['lon']>180 or values['lon']<-180):
    print "Coordinates out of range"
    print values
    return False

  if(values['acc']>3 or values['acc']<0.3):
    print ("Either really bad hdop, or alt and acc wrong way around")
    return False

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")