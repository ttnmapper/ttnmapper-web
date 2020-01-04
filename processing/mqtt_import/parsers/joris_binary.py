#!/usr/bin/python
import binascii
import struct
import sys

def format():
  return "joris_binary"

def parse(payload):
  data = binascii.a2b_base64(payload["payload_raw"])
  if(len(data)<9):
    return False

  if(len(data)>9):
    print ("Payload too long. Truncating.")
    data = data[:9]

  parsed = struct.unpack("<BBBBBBBBB", data)

  values = {}
  values['lat'] = (parsed[0]*(2**16) + parsed[1]*(2**8) + parsed[2])/16777215.0*180.0-90
  values['lon'] = (parsed[3]*(2**16) + parsed[4]*(2**8) + parsed[5])/16777215.0*360.0-180
  values['alt'] = parsed[6]*(2**8) + parsed[7]
  values['acc'] = parsed[8]/10.0

  if(values['acc']>5.0):
    print (values)
    print ("HDOP above 5")
    return False

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")