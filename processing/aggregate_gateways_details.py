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

def clear_bbox(gwaddr):
  cur_update.execute('DELETE FROM `gateway_bbox` WHERE gweui="'+gwaddr+'"')

def update_bbox(gwaddr):
    cur_select.execute('SELECT datetime FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
    location_row = cur_select.fetchone()
    moved = location_row["datetime"]

    # Include gateway itself in bbox calculation
    sql = 'SELECT `lat`, `lon` FROM `gateways_aggregated` WHERE gwaddr="'+gwaddr+'"'
    cur_select.execute(sql)
    for row in cur_select.fetchall():
      lat_max = float(row['lat'])
      lat_min = float(row['lat'])
      lon_max = float(row['lon'])
      lon_min = float(row['lon'])
    

    sql = "SELECT max(lat) as maxlat, min(lat) as minlat, max(lon) as maxlon, min(lon) as minlon, count(*) as count FROM `packets` WHERE gwaddr=\""+gwaddr+"\" AND time>\""+moved.strftime('%Y-%m-%d %H:%M:%S')+"\""
    cur_select.execute(sql)

    for row in cur_select.fetchall():
      if(row['maxlat']!=None and row['minlat']!=None and row['maxlon']!=None and row['minlon']!=None):
        lat_max = max( lat_max, float(row['maxlat']) )
        lat_min = min( lat_min, float(row['minlat']) )
        lon_max = max( lon_max, float(row['maxlon']) )
        lon_min = min( lon_min, float(row['minlon']) )

    #save bounding box
    # clear_bbox(gwaddr)
    cur_update.execute("""INSERT INTO gateway_bbox
      (gweui, lon_min, lat_min, lon_max, lat_max)
    VALUES
      ("""+`gwaddr`+', '+`lon_min`+', '+`lat_min`+', '+`lon_max`+', '+`lat_max`+""")
    ON DUPLICATE KEY UPDATE
      lon_min = """+`lon_min`+""",
      lat_min = """+`lat_min`+""",
      lon_max = """+`lon_max`+""",
      lat_max = """+`lat_max`)


def main(argv):

  gateways_aggregated = []
  gateways_updates = []
  
  cur_select.execute("SELECT DISTINCT(gwaddr) FROM  `gateway_updates`")
  gateway_list = cur_select.fetchall()
  for gateway in gateway_list:
    gwaddr = str(gateway["gwaddr"])
    gateways_updates.append(gwaddr)

  cur_select.execute("SELECT DISTINCT(gwaddr) FROM  `gateways_aggregated`")
  gateway_list = cur_select.fetchall()
  for gateway in gateway_list:
    gwaddr = str(gateway["gwaddr"])
    if(gwaddr not in gateways_updates):
      cur_update.execute('DELETE FROM `gateways_aggregated` WHERE gwaddr="'+gwaddr+'"')
      clear_bbox(gwaddr)
    else:
      gateways_aggregated.append(gwaddr)

  i = 0
  for gwaddr in gateways_updates:

    if(len(argv)>0):
      if not gwaddr in argv:
        continue

    i+=1
    print(str(i)+"/"+str(len(gateways_updates))+"\t", end="\t")
    print (gwaddr, end="\t")

    start_time = time.time()
    cur_select.execute('SELECT count(distinct(`freq`)) as channel_count FROM packets WHERE gwaddr="'+gwaddr+'"')
    row = cur_select.fetchone()
    end_time = time.time()
    print("channel count = "+str(end_time - start_time), end="\t")
    channel_count = row["channel_count"]

    start_time = time.time()
    cur_select.execute('SELECT count(*) as packet_count FROM packets WHERE gwaddr="'+gwaddr+'"')
    row = cur_select.fetchone()
    end_time = time.time()
    print("packet count = "+str(end_time - start_time), end="\t")
    packet_count = row["packet_count"]

    cur_select.execute('SELECT * FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
    row = cur_select.fetchone()
    end_time = time.time()
    print("updates="+str(end_time - start_time), end="\t")

    lat = row["lat"]
    lon = row["lon"]
    first_heard = row["datetime"]


    cur_select.execute('SELECT count(*) as count FROM gateways_aggregated WHERE gwaddr="'+gwaddr+'"')
    rowcount = cur_select.fetchone()["count"]
    end_time = time.time()
    print("bboxcnt="+str(end_time - start_time), end="\t")
    if(rowcount > 1):
      cur_update.execute('DELETE FROM `gateways_aggregated` WHERE gwaddr="'+gwaddr+'"')
      cur_update.execute('INSERT INTO `gateways_aggregated`(`gwaddr`, `channels`, `lat`, `lon`, `last_heard`) VALUES ("'+gwaddr+'","'+str(channel_count)+'", '+str(lat)+', '+str(lon)+', "'+str(first_heard)+'")')
      end_time = time.time()
      print("delinsert="+str(end_time - start_time), end="\t")
    elif(rowcount > 0):
      cur_update.execute('UPDATE `gateways_aggregated` SET `channels`='+str(channel_count)+', lat='+str(lat)+', lon='+str(lon)+' WHERE gwaddr="'+gwaddr+'"')
      end_time = time.time()
      print("update="+str(end_time - start_time), end="\t")
    else:
      cur_update.execute('INSERT INTO `gateways_aggregated`(`gwaddr`, `channels`, `lat`, `lon`, `last_heard`) VALUES ("'+gwaddr+'","'+str(channel_count)+'", '+str(lat)+', '+str(lon)+', "'+str(first_heard)+'")')
      end_time = time.time()
      print("insert="+str(end_time - start_time), end="\t")
    
    update_bbox(gwaddr)

    end_time = time.time()
    print("total="+str(end_time - start_time))

  db.commit()
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
