#!/usr/bin/python
import sys
import base64

def format():
  return "vannut"

def parse(payload):
  values = {}
  payload = base64.b64decode(payload["payload_raw"])
  payload = payload.split(" ")

  if(len(payload)!=4):
    print("Wrong packet format")
    return False

  values['lat'] = float(payload[0])/1000000.0
  values['lon'] = float(payload[1])/1000000.0
  values['acc'] = float(payload[2])/100.0
  values['alt'] = float(payload[3])

  if(values['lat'] == 0 or values['lon'] == 0
    or values['lat']>90 or values['lat']<-90
    or values['lon']>180 or values['lon']<-180):
    return False

  if(values['acc']>3 or values['acc']<0.3):
    print ("Either really bad hdop, or alt and acc wrong way around")
    return False

  return values

if __name__ == "__main__":
  arv = sys.argv[1:]
  if(len(arv)>0):
    print parse(arv[0])
  else:
    print (format()+" parser")