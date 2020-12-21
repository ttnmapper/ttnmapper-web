#!/usr/bin/python
from __future__ import print_function
import string

import MySQLdb
import os, sys
from geopy.distance import great_circle
import configparser

from ttnctlFunctions import get_info, get_status


lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-updates-ttnctl-lock"
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
                     db=    config['database_mysql']['database'],  # name of the data base,
                     charset='utf8',
                     autocommit=True
                    )

cur = db.cursor()
cur.execute('SET NAMES utf8mb4;')
cur.execute('SET CHARACTER SET utf8mb4;')
cur.execute('SET character_set_connection=utf8mb4;')

script_path = os.path.dirname(sys.argv[0])

def on_message(gwid):
  latitude, longitude, altitude, description = get_info(gwid)
  last_seen, latitude_status, longitude_status = get_status(gwid)

  if latitude == None:
    if latitude_status != None:
      latitude = latitude_status
    else:
      latitude = 0.0
  if longitude == None:
    if longitude_status != None:
      longitude = longitude_status
    else:
      longitude = 0.0
  if altitude == None:
    altitude = 0

  if last_seen == None:
    print("Last seen not set.")
    return

  gwaddr = gwid
  if(gwaddr.startswith("eui-")):
    gwaddr = str(gwaddr[4:]).upper()

  if(abs(latitude)>=90 or abs(longitude)>=180):
    print ("Invalid ttnctl location: "+str(latitude)+","+str(longitude), end=' ')
    latitude = 0
    longitude = 0


  update = False
  exists = False
  gwlatdb=0
  gwlondb=0


  gwlatjs=latitude
  gwlonjs=longitude

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
  cur.execute("SELECT lat_force,lon_force FROM gateway_force WHERE `gwaddr`=%s AND (`lat_orig`=%s OR `lat_orig` IS NULL) AND (`lon_orig`=%s OR `lon_orig` IS NULL)", (gwaddr, gwlatjs, gwlonjs) )
  for row in cur.fetchall():
    print ("!!Exists in force table - FORCING COORDINATES!!", end=' ')
    gwlatjs = float(row[0])
    gwlonjs = float(row[1])

  if(abs(gwlatjs)>=90 or abs(gwlonjs)>=180):
    print ("Invalid old db location: "+str(gwlatjs)+","+str(gwlonjs), end=' ')
    gwlatjs = 0
    gwlonjs = 0
    update = False
    #return

  # Handle out of range database coordinates
  if(abs(gwlatdb)>=90 or abs(gwlondb)>=180):
    update = True
  else:
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
      "INSERT INTO gateway_updates (gwaddr, datetime, lat, lon, alt) "+
      "VALUES (%s,%s,%s,%s,%s)",
      (gwaddr, last_seen, gwlatjs, gwlonjs, altitude)
    )
    db.commit()

  elif exists == True:
    print ("Updating last seen.", end=' ')
    update_gateway(gwaddr, latitude, longitude, last_seen, description)
    print ("Done.", end=' ')
  else:
    print("Not adding or updating.", end=' ')

  if update == True and exists == False:
    print("New gateway "+gwaddr)

  print()


def update_gateway(gwaddr, latitude, longitude, last_seen, description):

  cur.execute("SELECT last_heard FROM gateways_aggregated WHERE gwaddr=%s", (gwaddr,))
  last_heard_db = None
  for row in cur.fetchall():
    last_heard_db = row[0]

  # Doesn't exist, so add a new one
  if(cur.rowcount <1):
    print ("No entry yet.", end=' ')
    cur.execute(
      'INSERT INTO gateways_aggregated (gwaddr, lat, lon, last_heard, description) VALUES (%s, %s, %s, %s, %s)',
      (gwaddr, latitude, longitude, last_seen, description)
    )

  else:

    # Update the last_seen time in the gateways_aggregated table only if our last_seen is newer than the existing last_seen
    if last_heard_db < last_seen:
      cur.execute(
        'UPDATE gateways_aggregated SET lat=%s, lon=%s, last_heard=%s, description=%s WHERE gwaddr = %s',
        (latitude, longitude, last_seen, description, gwaddr)
      )
    else:
      print("Last heard stale")

  db.commit()





def main(argv):
  cur.execute("SELECT gwaddr, last_heard FROM gateways_aggregated ORDER BY last_heard DESC")
  gateway_list = cur.fetchall()

  i = 0
  for gwaddr in gateway_list:
    i += 1

    # EUI to ID
    gwaddr = gwaddr[0]
    gwid = gwaddr
    if(len(gwid) == 16 and all(c in string.hexdigits for c in gwid)):
      gwid = "eui-"+gwid.lower()

    # Argument to process specific gateway?
    if(len(argv)>0):
      if not gwid in argv and not gwaddr in argv:
        continue

    print(str(i)+"/"+str(len(gateway_list)), end="\t")
    print(gwid, end="\t")

#    if(i<45000):
#      print()
#      continue

    on_message(gwid)



if __name__ == "__main__":
    # execute only if run as a script
    main(sys.argv[1:])
