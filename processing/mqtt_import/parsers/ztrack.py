#!/usr/bin/python
import binascii
import struct
import sys

def format():
  return "ztrack"

"""
GPS coordinates message (204 port):
Payload structure: LLLNNNAAHTTB
(12bytes)

port: 204, payload: 43ad230f7343007f07027148

latitude(3 bytes), longitude (3 bytes), altitude (2 bytes), HDOP integer part (1 byte),
temperature (2 bytes), battery (1 byte)
LAT_MSB, LAT_CSB, LAT_LSB, LON_MSB, LON_CSB, LON_MSB, ALT_MSB, ALT_LSB,
HDOP, TEMP_MSB, TEMP_LSB, BAT

Latitude: 24 bit long signed hexadecimal number. Positive number means North,
negative means South.
Formula to get latitude from given payload:
latitude = (lat_value/8388606)*90
In our example 43ad23(hex)= 4435235(dec)
latitude = (4435235/8388606)*90=47.584920

Longitude: 24 bit long signed hexadecimal number. Positive number means East,
negative means West.
The formula to get longitude from given payload:
longitude = (lon_value/8388606) * 180
In our example 0f7343(hex) = 1012547(dec)
longitude = (1012547 / 8388606) * 180 = 21.726906

Altitude: 16 bit unsigned hexadecimal number.
In our example 007f(hex) means 127m altitude as decimal value.

HDOP: 2 digit decimal number. It contains the integer part of HDOP value.
In our example: HDOP value is 7.

0271:
- 1. character indicates negative or positive temperature (0 - positive, 1 - negative)
- 2. and 3. character is integer part of temperature
- 4. character is fractional part of temperature

48:
- battery status in percentage (1%-99%)
Device's near ambient temperature is 27.1C, and battery level is on 48%.
"""

def parse(payload):

  data = binascii.a2b_base64(payload["payload_raw"])

  # Check the correct port number
  if (payload["port"] != 204):
    print("Incorrect port number "+payload["port"])
    return False

  if(len(data)<12):
    print ("Payload too short.")
    return False

  if(len(data)>12):
    print ("Payload too long. Truncating.")
    data = data[:12]

  parsed = struct.unpack("<BBBBBBBBBBBB", data)

  values = {}
  values['lat'] = (parsed[0]*(2**16) + parsed[1]*(2**8) + parsed[2])
  # Handle negative range
  if(values['lat']>=8388606):
    values['lat'] = 16777215 - values['lat']
  values['lat'] = ( values['lat'] / 8388606 ) * 90

  values['lon'] = (parsed[3]*(2**16) + parsed[4]*(2**8) + parsed[5])
  # Handle negative range
  if(values['lon']>=8388606):
    values['lon'] = 16777215 - values['lon']
  values['lon'] = ( values['lon'] / 8388606 ) * 180


  values['alt'] = parsed[6]*(2**8) + parsed[7]
  values['acc'] = parsed[8]

  if(values['acc']>5.0):
    print (values)
    print ("HDOP above 5")
    return False

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    data = {"payload_raw": arv[0], "port": 204}
    print parse(data)
  else:
    print (format()+" parser")
