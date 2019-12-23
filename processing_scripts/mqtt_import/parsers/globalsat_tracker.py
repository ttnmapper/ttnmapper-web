#!/usr/bin/python
import binascii
import struct
import sys

"""
For example, our received payload is 00825b017d6b19073dc188.
GlobalSat Device Type: 0x00 Reserved
GPS-fix Status: 0x82 130 / 64 = 2 3D Fixed
Report Type: 0x82 130 % 64 = 2 Periodic mode report
Battery Capacity: 0x5b 91%
Latitude: 0x017d6b19 24996633*0.000001 = 24.996633
Longitude: 0x073dc188 121487752*0.000001 = 121.487752
"""

def format():
  return "globalsat_tracker"

def parse(payload):
  data = binascii.a2b_base64(payload["payload_raw"])
  parsed = struct.unpack(">BBBii", data)
  print(parsed)

  if(parsed[1]/64 < 2):
    print("Not a 3D fix: "+str(parsed[0]))
    return False

  values = {}
  values['lat'] = parsed[3] * 0.000001
  values['lon'] = parsed[4] * 0.000001

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")
