#!/usr/bin/python
import binascii
import struct
import datetime
import sys

"""
[
  {
    "DL_counter": 1,
    "UL_counter": 0,
    "batt": 3.842,
    "boardtemp": 25,
    "device_id": "adeunis_logger_01",
    "hdop": 1,
    "lat": 52.53641666666667,
    "long": 13.4175,
    "raw": "vxlSMhhQATJQUBcAAQ8Cd/0=",
    "rssi": -119,
    "sats": 7,
    "snr": 125,
    "time": "2017-04-06T21:43:01.063278581Z"
  },
  {
    "DL_counter": 2,
    "UL_counter": 4,
    "batt": 3.842,
    "boardtemp": 25,
    "device_id": "adeunis_logger_01",
    "hdop": 3,
    "lat": 52.53641666666667,
    "long": 13.4175,
    "raw": "vxlSMhhQATJQUDgEAg8Cd/w=",
    "rssi": -119,
    "sats": 8,
    "snr": 124,
    "time": "2017-04-06T21:43:05.403191493Z"
  },
  {
    "DL_counter": 3,
    "UL_counter": 5,
    "batt": 3.841,
    "boardtemp": 24,
    "device_id": "adeunis_logger_01",
    "hdop": 3,
    "lat": 52.53638333333333,
    "long": 13.4175,
    "raw": "vxhSMhgwATJQUDgFAw8Bevo=",
    "rssi": -122,
    "sats": 8,
    "snr": 122,
    "time": "2017-04-06T21:43:37.355034547Z"
  },
  {
    "DL_counter": 4,
    "UL_counter": 8,
    "batt": 3.841,
    "boardtemp": 23,
    "device_id": "adeunis_logger_01",
    "hdop": 3,
    "lat": 52.53638333333333,
    "long": 13.4175,
    "raw": "vxdSMhgwATJQUDgIBA8Bc/4=",
    "rssi": -115,
    "sats": 8,
    "snr": 126,
    "time": "2017-04-06T21:44:05.274816328Z"
  },
  {
    "DL_counter": 4,
    "UL_counter": 8,
    "batt": 3.841,
    "boardtemp": 23,
    "device_id": "adeunis_logger_01",
    "hdop": 3,
    "lat": 52.53638333333333,
    "long": 13.4175,
    "raw": "vxdSMhgwATJQUDgIBA8Bc/4=",
    "rssi": -115,
    "sats": 8,
    "snr": 126,
    "time": "2017-04-06T21:44:18.382410974Z"
  },
  {
    "DL_counter": 4,
    "UL_counter": 8,
    "batt": 3.841,
    "boardtemp": 23,
    "device_id": "adeunis_logger_01",
    "hdop": 3,
    "lat": 52.53638333333333,
    "long": 13.4175,
    "raw": "vxdSMhgwATJQUDgIBA8Bc/4=",
    "rssi": -115,
    "sats": 8,
    "snr": 126,
    "time": "2017-04-06T21:44:24.279640036Z"
  }
]
"""

def format():
  return "adeunis_demo"

def parse(payload):
  # try:
  # 9E13473323200073505006000CF3
  # nhNHMyMgAHNQUAYADPM=
    
    data = binascii.a2b_base64(payload["payload_raw"])
    hexline = binascii.hexlify(data)

    if(len(data)<14):
      print ("Too short payload")
      return False
    #if(len(data)>17):
    #  print ("Too long payload")
    #  return False

    latdeg = int(hexline[4], 16)*10.0 + int(hexline[5], 16)
    latmin = int(hexline[6], 16) * 10.0 + int(hexline[7], 16) + int(hexline[8], 16) / 10.0 + int(hexline[9], 16) / 100.0 + int(hexline[10], 16) / 1000.0
    lat = latdeg+(latmin/60.0)
    if(int(hexline[11], 16) & 1):
      lat = lat*-1.0

    londeg = int(hexline[12], 16) * 100.0 + int(hexline[13], 16) * 10.0 + int(hexline[14], 16)
    lonmin = (int(hexline[15], 16) * 10.0 + int(hexline[16], 16) + int(hexline[17], 16) / 10.0 + int(hexline[18], 16) / 100.0)
    lon = londeg + (lonmin/60.0)
    if(int(hexline[19], 16) & 1):
      lon = lon*-1.0

    if(lon>180 or lon<-180):
      print ("lon="+lon)
      return False
    if(lat>90 or lat<-90):
      print ("lat="+lat)
      return False

    if(lon<1 and lon>-1):
      print ("lon="+lon)
      return False
    if(lat<1 and lat>-1):
      print ("lat="+lat)
      return False

    values = {}
    values["lat"] = lat
    values["lon"] = lon
    values["hdop"] = int(hexline[20], 16)
    values["sats"] = int(hexline[21], 16)

    return values
  # except:
  #   print ("Exception")
  #   return False

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    payload = {}
    payload["payload_raw"] = arv[0]
    print parse(payload)
  else:
    print (format()+" parser")
