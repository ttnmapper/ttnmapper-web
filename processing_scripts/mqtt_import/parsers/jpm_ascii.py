#!/usr/bin/python
import base64
import re
import sys

def format():
  return "jpm_ascii"

def parse(payload):
  payload = base64.b64decode(payload["payload_raw"])
  payload = re.split(" +",payload)
  if(len(payload)!=4 or payload[2]=="0" or payload[3]=="0"): #if [3]=0, the fix is not good
    print ("Packet format unknown")
    return False

  #sometimes the coordinates jumps to close to the equator - incorrectly
  if(abs(float(payload[0])/1000000.0)<1):
    print ("Coordinates too close to 0,0 point")
    return False

  values = {}
  values["lat"] = float(payload[0])/1000000.0
  values["lon"] = float(payload[1])/1000000.0
  values["alt"] = float(payload[2])
  values["acc"] = float(payload[3])/100

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")    
