#!/usr/bin/python
from __future__ import print_function
import MySQLdb
import paho.mqtt.client as mqtt
import json
import time
import datetime
import dateutil.parser
import os, sys
from geopy.distance import great_circle
import urllib, urllib2
import configparser
import subprocess

ignored_euis = ["0102030405060708", "0000000000001DEE", "000000000000000E", "000000000000FFFE"]

retry_using_noc = [] # gateways that does not have a lastheard set on this api

lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-updates-web-lock"
#This is to check if there is already a lock file existing#
if os.access(lockfile, os.F_OK):
  #if the lockfile is already there then check the PID number 
  #in the lock file
  pidfile = open(lockfile, "r")
  pidfile.seek(0)
  oldpid = pidfile.readline()
  # Now we check the PID from lock file matches to the current
  # process PID
  if oldpid.strip() != "" and os.path.exists("/proc/%s" % oldpid):
    print ("You already have an instance of the program running")
    print ("It is running as process %s," % oldpid)
    sys.exit(1)
  else:
    print ("File is there but the program is not running")
    print ("Removing lock file for the: %s as it can be there because of the last time it was run" % oldpid)
    os.remove(lockfile)

#This is part of code where we put a PID file in the lock file
pidfile = open(lockfile, "w")
newpid = str(os.getpid())
print ("PID="+newpid)
pidfile.write(newpid)
pidfile.close()


config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                    )

cur = db.cursor()

import twitter
api = twitter.Api(consumer_key=config['twitter']['consumer_key'],
                  consumer_secret=config['twitter']['consumer_secret'],
                  access_token_key=config['twitter']['access_token_key'],
                  access_token_secret=config['twitter']['access_token_secret'])

def postToTwitter(message):
  try:
    status = api.PostUpdate(message)
  except UnicodeDecodeError:
    print("Your message could not be encoded.  Perhaps it contains non-ASCII characters? ")
    print("Try explicitly specifying the encoding with the --encoding flag")
    return

  print("{0} just posted: {1}".format(status.user.name, status.text))

def postToSlack(message):
  urlSlack = config['slack']['new_gateway_hook']
  payload = {"text": message}

  req = urllib2.Request(urlSlack)
  req.add_header('Content-Type', 'application/json')
  response = urllib2.urlopen(req, json.dumps(payload))

def doReverseGeocoding(gwaddr, datetime, lat, lon):
  url = "https://maps.googleapis.com/maps/api/geocode/json?language=en&latlng="+str(lat)+","+str(lon)+"&key="+config['google']['api_key']+"&result_type=locality|political|country"
  response = urllib.urlopen(url)
  location = json.loads(response.read())

  if("status" in location and location["status"] == "ZERO_RESULTS"):
    # use lat and lon
    pretty = str(lat)+","+str(lon)
  else:
    pretty = location["results"][0]["formatted_address"]

  postToTwitter("New gateway: "+gwaddr+"\nLocation: "+pretty+"\nhttps://www.google.com/maps/search/?api=1&query="+str(lat)+","+str(lon)+"")
  postToSlack("A new gateway, *"+gwaddr+"*, was installed at <https://www.google.com/maps/search/?api=1&query="+str(lat)+","+str(lon)+"|"+pretty+">")

def on_message(gwid, gwdata):
  """
  {
    "id": "gateway-id",
    "description": "Description of the gateway",  // This field is omitted if unkown, empty or not public
    "owner": "username",                          // This field is omitted if unkown, empty or not public
    "owners": ["username"],                       // This field is omitted if unkown, empty or not public
    "location": {                                 // This field is omitted if unkown, empty or not public
      "latitude": 00.0000000,
      "longitude": 00.0000000,
      "altitude": 00
    },
    "country_code": "nl",
    "attributes": { // Treat this as a generic key-value object. Underscores can be replaced with spaces.
      "antenna_model": "...",
      "brand": "...",
      "placement": "..."
    },
    "last_seen": "2018-01-12T10:34:20Z" // This field is omitted if unkown, empty or not public
  }
  """
  gwaddr = gwid
  if(gwaddr.startswith("eui-")):
    gwaddr = str(gwaddr[4:]).upper()

  print (gwaddr+"\t", end=' ')

  update = False
  exists = False
  gwlatdb=0
  gwlondb=0

  if not "last_seen" in gwdata:
    print("Last seen not set. Trying NOC")
    retry_using_noc.append(gwid)
    return
  
  # 2019-09-09T14:38:03Z
  lastSeen = dateutil.parser.parse(gwdata["last_seen"])

  # lastSeen = lastSeen.split(".")[0]
  # lastSeen = lastSeen.rstrip("Z")
  
  try:
    gwlatjs=gwdata["location"]["latitude"]
    gwlonjs=gwdata["location"]["longitude"]
  except:
    gwlatjs=0
    gwlonjs=0
  
  try:
    gwaltjs=gwdata["location"]["altitude"]
  except:
    gwaltjs=0

  cur.execute("SELECT lat,lon FROM gateway_updates WHERE gwaddr=%s ORDER BY datetime DESC LIMIT 1", (gwaddr,))
  
  if(cur.rowcount <1):
    update = True
    print ("No entry yet.", end=' ')
  for row in cur.fetchall():
    exists = True
    gwlatdb = float(row[0])
    gwlondb = float(row[1])

  # in case a gateway reports its location wrong and we know it and we have an entry for it in our forcing table, force the correct coordinates
  cur.execute("SELECT lat_force,lon_force FROM gateway_force WHERE `gwaddr`=%s AND (`lat_orig`=%s OR `lat_orig` IS NULL) AND (`lon_orig`=%s OR `lon_orig` IS NULL)", (gwaddr, gwlatjs, gwlonjs) )
  for row in cur.fetchall():
    print ("!!Exists in force table - FORCING COORDINATES!!", end=' ')
    gwlatjs = float(row[0])
    gwlonjs = float(row[1])
    
  if(abs(gwlatjs)>90 or abs(gwlonjs)>180):
    print ("Invalid location: "+str(gwlatjs)+","+str(gwlonjs), end=' ')
    update = False
    #return

  distance = great_circle((gwlatjs, gwlonjs),(gwlatdb, gwlondb)).meters
  if(distance>100):
    print ("Distance is: "+str(round(distance))+"m.", end=' ')
    update = True
  else:
    #print ("Location did not change: "+str(round(distance)), end=' ')
    pass

  if(gwaddr in ignored_euis):
    print ("Ignored EUI.", end=' ')
    update = False
    #return
    
  if(gwlatjs==52.0 and gwlonjs==6.0):
    print ("Default SCG location, ignoring.", end=' ')
    update = False

  if(round(gwlatjs,4)==10.0 and round(gwlonjs,4)==20.0):
    print ("Default Lorrier LR2 location, ignoring.", end=' ')
    update = False

  if(gwlatjs==50.008724 and gwlonjs==36.215805):
    print ("Ukrainian hack.", end=' ')
    update = False

  if(gwlatjs==0 and gwlonjs==0):
    print ("Zero location, ignoring.", end=' ')
    update = False
  if(abs(gwlatjs)<1 and abs(gwlonjs)<1):
    print ("Filtering NULL island.", end=' ')
    update = False
    #return
  #also check if the position was already updated in the past ~24h

  if(gwlatjs>90 or gwlatjs<-90 or gwlonjs>180 or gwlonjs<-180):
    print ("Invalid location, ignoring.", end=' ')
    update = False
      

  # sanitise altitude
  if(gwaltjs>99999.9 or gwaltjs<-99999.9):
    print("Altitude out of range, clamping to 0.", end=' ')
    gwaltjs = 0

  if update == True:
    print ("Adding new entry", end=' ')
    cur.execute(
      "INSERT INTO gateway_updates (gwaddr, datetime, lat, lon, alt, last_update) "+
      "VALUES (%s,%s,%s,%s,%s, %s)",
      (gwaddr, lastSeen, gwlatjs, gwlonjs, gwaltjs, lastSeen)
    )
    db.commit()

  elif exists == True:
    print ("Updating last seen.", end=' ')

    # To speed things up do not update last seen column in raw data table
    # cur.execute(
    #   'UPDATE `gateway_updates` SET `last_update`=%s '+
    #   'WHERE gwaddr=%s AND (last_update<%s OR last_update IS NULL)',
    #   (lastSeen, gwaddr, lastSeen)
    # )

    # If it exist it is likely also in the aggregate table, so try and update the last heard
    try:
      cur.execute(
        'UPDATE `gateways_aggregated` SET last_heard=%s WHERE gwaddr=%s', 
        (lastSeen, gwaddr)
      )
    except:
      print("Doesn't exist in aggregate table.", end=' ')
      pass

    db.commit()
    print ("Done.", end=' ')
  else:
    print("Not adding or updating.", end=' ')

  if update == True and exists == False:
    print("New gateway "+gwaddr)
    try:
      doReverseGeocoding(gwaddr, datetime, gwlatjs, gwlonjs)
    except:
      print("Error while posting to slack or twitter")
      pass

  print()


def main(argv):
  # let's start
  start_time = time.time()

  #do a get from the rest api

  current_offset = 0;
  more_lines = True;

  url = "https://www.thethingsnetwork.org/gateway-data/"

  response = urllib.urlopen(url)
  jsonobject = json.loads(response.read())

  gateway_count = len(jsonobject)
  i = 0

  slow_gateways = []

  for gwid in sorted(jsonobject):
    i+=1

    gwaddr = gwid
    if(gwaddr.startswith("eui-")):
      gwaddr = str(gwaddr[4:]).upper()

    if(len(argv)>0):
      if not gwid in argv and not gwaddr in argv:
        continue

    print(str(i)+"/"+str(gateway_count)+"\t", end=" ")

    gateway_start = time.time()
    on_message(gwid, jsonobject[gwid])
    gateway_end = time.time()

    if(gateway_end - gateway_start > 0.5):
      slow_gateways.append(gwid)
    #except:
    #  print ("Gateway error: "+str(gateway))
    # print gateway

    # Give the database some time to rest and handle other calls
    time.sleep(0.01) # 400s for 18452 gateways

  end_time = time.time()

  print("Script took "+str(end_time - start_time)+" seconds")
  print("Slow gateways:")
  print(slow_gateways)

  for gateway in sorted(slow_gateways, key=lambda x: x[1], reverse=True):
    gwaddr = gateway
    if(gwaddr.startswith("eui-")):
      gwaddr = str(gwaddr[4:]).upper()

    cur.execute("SELECT count(*) FROM gateway_updates WHERE `gwaddr`='"+str(gwaddr)+"'" )
    for row in cur.fetchall():
      count = float(row[0])
      print(str(gwaddr) + "\t" + str(count))

  # Retry some using NOC
  print("Retrying using NOC")
  print(retry_using_noc)
  noc_process = subprocess.Popen(["./rest_gateway_updates_noc.py"] + retry_using_noc)
  noc_process.wait()


if __name__ == "__main__":
    # execute only if run as a script
    main(sys.argv[1:])