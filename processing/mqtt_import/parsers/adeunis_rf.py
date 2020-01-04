#!/usr/bin/python
import binascii
import struct
import datetime
import sys

"""
Incomplete due to unknown data format
"""

def format():
  return "adeunis_rf"

def parse(payload):
    
    data = binascii.a2b_base64(payload["payload_raw"])
    hexline = binascii.hexlify(data)

    if(len(data)>17):
     print ("Too long payload")
     return False

    latdeg = int(hexline[4])*10.0 + int(hexline[5])
    latmin = int(hexline[6]) * 10.0 + int(hexline[7]) + int(hexline[8]) / 10.0 + int(hexline[9]) / 100.0 + int(hexline[10]) / 1000.0
    lat = latdeg+(latmin/60.0)
    if(int(hexline[11]) & 1):
      lat = lat*-1.0

    londeg = int(hexline[12]) * 100.0 + int(hexline[13]) * 10.0 + int(hexline[14])
    lonmin = (int(hexline[15]) * 10.0 + int(hexline[16]) + int(hexline[17]) / 10.0 + int(hexline[18]) / 100.0)
    lon = londeg + (lonmin/60.0)
    if(int(hexline[19]) & 1):
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
    values["hdop"] = int(hexline[20])
    values["sats"] = int(hexline[21])

    return values
  # except:
  #   print ("Exception")
  #   return False

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")
