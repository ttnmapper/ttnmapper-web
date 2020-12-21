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
import requests
from requests.auth import HTTPBasicAuth
import configparser
import base64
import pytz

try:  
   os.environ["TTNMAPPER_HOME"]
except KeyError: 
   print ("Please set the environment variable TTNMAPPER_HOME")
   sys.exit(1)



lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-updates-noc-lock"
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


def on_message(gwid, gwdata):
  gwaddr = gwid
  if(gwaddr.startswith("eui-")):
    gwaddr = str(gwaddr[4:]).upper()

  print (gwaddr+"\t", end=' ')

  update = False
  exists = False
  gwlatdb=0
  gwlondb=0

  lastSeen = dateutil.parser.parse(gwdata["timestamp"])
  #lastSeen = jdata["status"]["lastSeen"][:-9]
  # lastSeen = gwdata["time"][:-9]
  
  try:
    gwlatjs=gwdata["gps"]["latitude"]
    gwlonjs=gwdata["gps"]["longitude"]
  except:
    gwlatjs=0
    gwlonjs=0
  
  try:
    gwaltjs=gwdata["gps"]["altitude"]
  except:
    gwaltjs=0

  cur.execute("SELECT lat,lon FROM gateways_aggregated WHERE gwaddr=%s", (gwaddr,))
  
  if(cur.rowcount <1):
    update = True
    print ("No entry yet.", end=' ')
  for row in cur.fetchall():
    exists = True
    gwlatdb = float(row[0])
    gwlondb = float(row[1])

  # in case a gateway reports its location wrong and we know it and we have an entry for it in our forcing table, force the correct coordinates
  cur.execute("SELECT lat_force,lon_force FROM gateway_force WHERE `gwaddr`='"+str(gwaddr)+"' AND (`lat_orig`="+str(gwlatjs)+" OR `lat_orig` IS NULL) AND (`lon_orig`="+str(gwlonjs)+" OR `lon_orig` IS NULL)" )
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
      "VALUES (%s,%s,%s,%s,%s,%s)",
      (gwaddr, lastSeen, gwlatjs, gwlonjs, gwaltjs, lastSeen)
    )
    db.commit()

  elif exists == True:
    print ("Updating last seen.", end=' ')
    update_gateway(gwaddr, gwlatjs, gwlonjs, lastSeen)
    print ("Done.", end=' ')
  else:
    print("Not adding or updating.", end=' ')

  if update == True and exists == False:
    print("New gateway "+gwaddr)

  print()


def update_gateway(gwaddr, latitude, longitude, last_seen):

  cur.execute("SELECT lat,lon,last_heard FROM gateways_aggregated WHERE gwaddr=%s", (gwaddr,))
  last_heard_db = None
  for row in cur.fetchall():
    lat_db = row[0]
    lon_db = row[1]
    last_heard_db = row[2]

  last_heard_db = last_heard_db.replace(tzinfo=pytz.UTC)

  # If new location is 0,0 but old location is valid, do not change location
  if latitude == 0 or longitude == 0:
    latitude = lat_db
    longitude = lon_db

  # Doesn't exist, so add a new one
  if(cur.rowcount <1):
    print ("No entry yet.", end=' ')
    cur.execute(
      'INSERT INTO gateways_aggregated (gwaddr, lat, lon, last_heard) VALUES (%s, %s, %s, %s)',
      (gwaddr, latitude, longitude, last_seen)
    )

  else:

    # Update the last_seen time in the gateways_aggregated table only if our last_seen is newer than the existing last_seen
    if last_heard_db < last_seen:
      cur.execute(
        'UPDATE gateways_aggregated SET lat=%s, lon=%s, last_heard=%s WHERE gwaddr = %s',
        (latitude, longitude, last_seen, gwaddr)
      )
    else:
      print("Last heard stale")

  db.commit()


def main(argv):
  # let's start
  start_time = time.time()

  #do a get from the rest api

  current_offset = 0;
  more_lines = True;

  url = config['network']['noc_gateway_url']
  if(url == None):
    #url = "http://noc.thethingsnetwork.org:2020/api/v1/gateways"
    url = "http://noc.thethingsnetwork.org:8085/api/v2/gateways"

  # If the NOC needs Basic Auth, install an opener
  if(config['network'].getboolean('noc_use_basic_auth')):
    username = config['network']['noc_username']
    password = config['network']['noc_password']

    r = requests.get(url=url, auth=HTTPBasicAuth(username, password))
  else:
    r = requests.get(url=url)

  jsonobject = r.json()

  gateway_count = len(jsonobject["statuses"])
  i = 0

  slow_gateways = []
  argv = sys.argv[1:]

  for gwid in sorted(jsonobject["statuses"]):
    i+=1

    gwaddr = gwid
    if(gwaddr.startswith("eui-")):
      gwaddr = str(gwaddr[4:]).upper()

    # Force process specific gateways
    if(len(argv)>0):
      if not gwid in argv and not gwaddr in argv:
        continue

    print(str(i)+"/"+str(gateway_count)+"\t", end=" ")

    gateway_start = time.time()
    on_message(gwid, jsonobject["statuses"][gwid])
    gateway_end = time.time()

    if(gateway_end - gateway_start > 0.5):
      slow_gateways.append(gwid)


  end_time = time.time()

  print("Script took "+str(end_time - start_time)+" seconds")
  print("Slow gateways:")
  print(slow_gateways)

  for gateway in slow_gateways:
    gwaddr = gateway
    if(gwaddr.startswith("eui-")):
      gwaddr = str(gwaddr[4:]).upper()

    cur.execute("SELECT count(*) FROM gateway_updates WHERE `gwaddr`='"+str(gwaddr)+"'" )
    for row in cur.fetchall():
      count = float(row[0])
      print(str(gwaddr) + "\t" + str(count))


if __name__ == "__main__":
    # execute only if run as a script
    main(sys.argv[1:])
