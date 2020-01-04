#!/usr/bin/python
import binascii
import struct
import sys

"""
function Decoder(bytes, port) {
  var decoded = {};
  if (port === 1) {
   var temp = (bytes[0]<<8) + bytes[1];
   var hpa = (bytes[2]<<8) + bytes[3];
   var vcc = (bytes[4]<<8) + bytes[5];
   var baralt = (bytes[6]<<8) + bytes[7];
   var mode = bytes[8];
   var rssi = bytes[9];
   var snr = bytes[10];
   var seq = (bytes[11]<<8) + bytes[12];
   var lat = (bytes[13]<<24) + (bytes[14]<<16) + (bytes[15]<<8);
   var lon = (bytes[16]<<24) + (bytes[17]<<16) + (bytes[18]<<8);
   var sats = bytes[19];
   var gpsalt = (bytes[20]<<8) + bytes[21];
   
   if (temp > 32767) { temp = temp - 65536; }
   if (baralt > 32767) { baralt = baralt - 65536; }
   if (snr > 128) { snr = snr - 256; }
   
   decoded.vcc = vcc/1000;
   decoded.temp = temp/10;
   decoded.hpa = hpa/10;
   decoded.baralt = baralt;
   decoded.rssi = -rssi;
   decoded.snr = snr;
   decoded.seq = seq-1;
   decoded.lat = lat/10000000;
   decoded.lon = lon/10000000;
   decoded.sats = sats;
   decoded.gpsalt = gpsalt;
   decoded.type = "stat";
   decoded.trigger = true;
   decoded.mode = mode;
     return decoded;
  }
  if (port === 2) {
     
     var gpsalt = (bytes[1]<<8) + bytes[2];
     var freqindex = bytes[0];
     decoded.freqindex = freqindex;
     decoded.type = "scan";
     decoded.gpsalt = gpsalt;
     decoded.trigger = false;
     return decoded;
  }
  // if (port === 1) decoded.led = bytes[0];
  
}
"""

def format():
  return "telkamp_balloon"

def parse(payload):
  data = binascii.a2b_base64(payload["payload_raw"])
  if(len(data)<27):
    return False

  if(len(data)>27):
    print ("Payload too long.")
    return False

  parsed = struct.unpack("<BBBBBBBBBBBBBBBBBBBBBBBBBBB", data)

  values = {}
  #var lat = (bytes[13]<<24) + (bytes[14]<<16) + (bytes[15]<<8);
  #var lon = (bytes[16]<<24) + (bytes[17]<<16) + (bytes[18]<<8);
  values['lat'] = (parsed[13]*(2**24) + parsed[14]*(2**16) + parsed[15]*(2**8))/10000000.0
  values['lon'] = (parsed[16]*(2**24) + parsed[17]*(2**16) + parsed[18]*(2**8))/10000000.0
  values['alt'] = parsed[20]*(2**8) + parsed[21]
  values['sats'] = parsed[19]
  values['acc'] = (parsed[23]*(2**8) + parsed[24])
  values['vacc'] = (parsed[25]*(2**8) + parsed[26])

  # if(values['alt']<50):
  #   print ("Altitude too low, ignoring.")
  #   print (values)
  #   return False

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")
