#!/usr/bin/python
from __future__ import print_function
import MySQLdb
import MySQLdb.cursors
import sys, os
from datetime import datetime, timedelta
import configparser
import time

try:  
   os.environ["TTNMAPPER_HOME"]
except KeyError: 
   print ("Please set the environment variable TTNMAPPER_HOME")
   sys.exit(1)

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.DictCursor,
                     autocommit=True)

cur_select = db.cursor()
cur_update = db.cursor()

# def clear_bbox(gwaddr):
#   query = 'DELETE FROM `gateway_bbox` WHERE gweui=%s'
#   cur_update.execute(query, [gwaddr])

def update_bbox(gwaddr):
  query = "SELECT lat, lon FROM gateways_aggregated WHERE gwaddr=%s"
  cur_select.execute(query, [gwaddr])
  if(cur_select.rowcount==0):
    print("No entry for gateway", end="\t")
    return
  row = cur_select.fetchone()
  gwlat = float(row['lat'])
  gwlon = float(row['lon'])

  # If gateway location is on null island, remove from bbox table
  if(abs(gwlat) < 1 and abs(gwlon) < 1):
    print("Null island", end="\t")
    query = 'DELETE FROM gateway_bbox WHERE gweui=%s'
    cur_update.execute(query, [gwaddr])
    return


  query = 'SELECT datetime FROM gateway_updates WHERE gwaddr=%s ORDER BY datetime DESC LIMIT 1'
  cur_select.execute(query, [gwaddr])
  if(cur_select.rowcount==0):
    print("No location history", end="\t")
    return

  location_row = cur_select.fetchone()
  moved = location_row["datetime"]

  # Include gateway itself in bbox calculation
  lat_max = gwlat
  lat_min = gwlat
  lon_max = gwlon
  lon_min = gwlon
  

  sql = "SELECT max(lat) as maxlat, min(lat) as minlat FROM `packets` WHERE gwaddr=%s AND time>%s"
  cur_select.execute(sql, [gwaddr, moved])

  row = cur_select.fetchone()
  if(row['maxlat']!=None and row['minlat']!=None):
    lat_max = max( lat_max, float(row['maxlat']) )
    lat_min = min( lat_min, float(row['minlat']) )

  sql = "SELECT max(lon) as maxlon, min(lon) as minlon FROM `packets` WHERE gwaddr=%s AND time>%s"
  cur_select.execute(sql, [gwaddr, moved])

  row = cur_select.fetchone()
  if(row['maxlon']!=None and row['minlon']!=None):
    lon_max = max( lon_max, float(row['maxlon']) )
    lon_min = min( lon_min, float(row['minlon']) )

  #save bounding box
  # clear_bbox(gwaddr)
  sql = """INSERT INTO gateway_bbox
    (gweui, lon_min, lat_min, lon_max, lat_max)
  VALUES
    (%s, %s, %s, %s, %s)
  ON DUPLICATE KEY UPDATE
    lon_min = VALUES(lon_min),
    lat_min = VALUES(lat_min),
    lon_max = VALUES(lon_max),
    lat_max = VALUES(lat_max)"""

  print("bbox=",lon_min, ",", lat_min, ",", lon_max, ",", lat_max, end="\t")
  cur_update.execute(sql, [gwaddr, lon_min, lat_min, lon_max, lat_max])


def main(argv):

  # if(len(argv)>0):
  cur_select.execute("SELECT gwaddr, lat, lon FROM gateways_aggregated")
  # else:
  #   cur_select.execute("SELECT gwaddr, lat, lon FROM gateways_aggregated ORDER BY last_heard DESC LIMIT 10000")
  gateways_updates = cur_select.fetchall()

  i = 0
  for gateway in gateways_updates:
    gwaddr = gateway['gwaddr']

    if(len(argv)>0):
      if not gwaddr in argv:
        continue

    i+=1
    print(str(i)+"/"+str(len(gateways_updates)), end="\t")
    print (gwaddr, end="\t")

    
    start = time.time()
    update_bbox(gwaddr)
    end = time.time()
    print(str(end - start)+"s", end="\t")

    print()

  cur_update.close()
  cur_select.close()
  db.close()


if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateways-aggregate-details-lock"
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
  
  main(sys.argv[1:])
