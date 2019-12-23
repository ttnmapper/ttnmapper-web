#!/usr/bin/python
import binascii
import struct
import sys

def format():
  return "mbox_gps"

def parse(payload):
  data = binascii.a2b_base64(payload["payload_raw"])
  if(len(data)<11):
    return False

  if(len(data)>11):
    print ("Payload too long. Truncating.")
    data = data[:11]

  parsed = struct.unpack(">BBBii", data)

  values = {}
  values['lat'] = (parsed[3]/((2**31)-1.0))*90.0
  values['lon'] = (parsed[4]/((2**31)-1.0))*180.0

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")