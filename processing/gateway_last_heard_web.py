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

ignored_euis = ["0102030405060708", "0000000000001DEE"]


lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateways-lastheard-lock"
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



# If either the web or noc importers are running, do not start, as that will cause too much load on the same db table
lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-updates-web-lock"
if os.access(lockfile, os.F_OK):
  pidfile = open(lockfile, "r")
  pidfile.seek(0)
  oldpid = pidfile.readline()
  if oldpid.strip() != "" and os.path.exists("/proc/%s" % oldpid):
    print ("Gateway status web importer running")
    print ("It is running as process %s," % oldpid)
    sys.exit(1)


lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-updates-noc-lock"
if os.access(lockfile, os.F_OK):
  pidfile = open(lockfile, "r")
  pidfile.seek(0)
  oldpid = pidfile.readline()
  if oldpid.strip() != "" and os.path.exists("/proc/%s" % oldpid):
    print ("Gateway status noc importer running")
    print ("It is running as process %s," % oldpid)
    sys.exit(1)




config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                    )

cur = db.cursor()


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

  if not "last_seen" in gwdata:
    print("Last seen not set")
    return
  
  # 2019-09-09T14:38:03Z
  lastSeen = dateutil.parser.parse(gwdata["last_seen"])

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

  print()


def main(argv):
  # let's start
  start_time = time.time()

  url = "https://www.thethingsnetwork.org/gateway-data/"

  response = urllib.urlopen(url)
  jsonobject = json.loads(response.read())

  gateway_count = len(jsonobject)
  i = 0

  slow_gateways = []

  for gwid in sorted(jsonobject):
    i+=1

    if(len(argv)>0):
      if not gwid in argv:
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


if __name__ == "__main__":
    # execute only if run as a script
    main(sys.argv[1:])