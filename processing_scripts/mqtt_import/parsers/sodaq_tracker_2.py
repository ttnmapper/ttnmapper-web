#!/usr/bin/python
import binascii
import struct
import datetime
import sys

def format():
  return "sodaq_tracker_2"

def parse(payload):
  # try:
    data = binascii.a2b_base64(payload["payload_raw"])

    if(len(data)<21):
      print "Payload too short"
      return False

    data = data[-21:]
    # hex_chars = map(hex,map(ord,data))
    # print hex_chars
    # print len(data)
    # Description Length
    # Epoch Timestamp long (4)
    # Battery voltage (between 3 and 4.5 V) uint8 (1)
    # Board Temperature (degrees celcius) int8 (1)
    # Lat long (4)
    # Long  long (4)
    # Altitude (MSL in meters below sea level is set to FFFF) uint16 (2)
    # Speed (SOG * 100 km/h)  uint16 (2)
    # Course (COG)  uint8 (1)
    # Number of satellites  uint8 (1)
    # Time to fix (seconds, FF = no fix, in that case the above position is the last known) uint8 (1)
    # Plus 0 - 3 of the following 10 bytes: 
    # Previous fix (seconds ago, FFFF means longer than)  uint16 (2)
    # Lat long (4)
    # Long  long(4)
    parsed = struct.unpack("<IBbiiHHBBB", data)
    # if(values[9]==0xFF):
    #   print "No Fix"
    values = {}
    values["time"] = datetime.datetime.fromtimestamp(parsed[0])
    values["volt"] = parsed[1]
    values["temp"] = parsed[2]
    values["lat"] = parsed[3] / 10000000.0
    values["lon"] = parsed[4] / 10000000.0
    if parsed[5]==0xFFFF:
      values["alt"] = 0
    else:
      values["alt"] = parsed[5]
    values["speed"] = parsed[6]
    values["course"] = parsed[7]
    values["sats"] = parsed[8]
    values["ttf"] = parsed[9]

    return values
  # except:
  #   return False

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")
