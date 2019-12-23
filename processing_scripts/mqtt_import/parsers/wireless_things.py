#!/usr/bin/python
import binascii
import struct
import datetime
import sys

def format():
  return "wireless_things"

def parse(payload):
  """
  0 = unsinged byte LED
  1, 2 = unsigned short pressure / 10
  3, 4 = signed short temperature / 100
  5, 6 = signed short altitude form baro / 10
  7 = unsigned byte battery status
  8, 9, 10 = signed latitude
  11, 12, 13 = signed longitude
  14, 15 = ?? short altitude

  """
  data = binascii.a2b_base64(payload["payload_raw"])
  # print(binascii.hexlify(data))

  # if(len(data)<15):
  #   return False

  # data = data[-15:]

  # parsed = struct.unpack("<BHhhBbbbbbbh", data)
  parsed = struct.unpack("<BBBBBBB",data)

  values = {}
  # values["led"] = parsed[0]
  # values["pressure"] = parsed[1]/10.0
  # values["temperature"] = parsed[2]/100.0
  # values["baroalt"] = parsed[3]/10.0
  # values["battery"] = parsed[4]
  # values["lat"] = (parsed[5]<<16 + parsed[6]<<8 + parsed[7]) / (2**24) * 180 - 90
  # values["lon"] = parsed[8]<<16 + parsed[9]<<8 + parsed[10] / (2**24) * 360 - 180
  # values["alt"] = parsed[11]<<8 + parsed[12]
  values["lat"] = ((parsed[1]<<16) + (parsed[2]<<8) + parsed[3]) / (2.0**24) * 180.0
  values["lon"] = ((parsed[4]<<16) + (parsed[5]<<8) + parsed[6]) / (2.0**24) * 360.0
  values["unk"] = parsed[0]>>0

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")