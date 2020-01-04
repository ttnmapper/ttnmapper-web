#!/usr/bin/python
import MySQLdb
import MySQLdb.cursors
import sys, os
from datetime import datetime, timedelta
import configparser

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
                     cursorclass=MySQLdb.cursors.DictCursor)

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
      # if(row['maxlat']==None or row['minlat']==None or row['maxlon']==None or row['minlon']==None):
      #   print ("No data")
      #   clear_bbox(gwaddr)
      #   return
      if(row['maxlat']!=None and row['minlat']!=None and row['maxlon']!=None and row['minlon']!=None):
        lat_max = float(row['maxlat'])
        lat_min = float(row['minlat'])
        lon_max = float(row['maxlon'])
        lon_min = float(row['minlon'])
    # if(lat_max==None or lat_min==None or lon_max==None or lon_min==None):
    #   clear_bbox(gwaddr)
    #   return

    #save bounding box
    clear_bbox(gwaddr)
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


  for gwaddr in gateways_updates:
    print (gwaddr)

    if(len(argv)>0):
      if not gwaddr in argv:
        continue

    cur_select.execute('SELECT count(distinct(`freq`)) as channel_count, count(*) as packet_count FROM packets WHERE gwaddr="'+gwaddr+'"')
    row = cur_select.fetchone()
    channel_count = row["channel_count"]
    packet_count = row["packet_count"]
    #print (`channel_count` + " " + `packet_count`)

    cur_select.execute('SELECT * FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
    row = cur_select.fetchone()
    lat = row["lat"]
    lon = row["lon"]
    last_heard = row["last_update"]
    if(last_heard==None):
      last_heard = row["datetime"]
    print ("Last heard="+str(last_heard))

    # if(packet_count>0):
    cur_select.execute('SELECT count(*) as count FROM gateways_aggregated WHERE gwaddr="'+gwaddr+'"')
    rowcount = cur_select.fetchone()["count"]
    if(rowcount > 1):
      cur_update.execute('DELETE FROM `gateways_aggregated` WHERE gwaddr="'+gwaddr+'"')
      cur_update.execute('INSERT INTO `gateways_aggregated`(`gwaddr`, `channels`, `lat`, `lon`, `last_heard`) VALUES ("'+gwaddr+'","'+str(channel_count)+'", '+str(lat)+', '+str(lon)+', "'+str(last_heard)+'")')
    elif(rowcount > 0):
      cur_update.execute('UPDATE `gateways_aggregated` SET `channels`='+str(channel_count)+', lat='+str(lat)+', lon='+str(lon)+', last_heard="'+str(last_heard)+'" WHERE gwaddr="'+gwaddr+'"')
    else:
      cur_update.execute('INSERT INTO `gateways_aggregated`(`gwaddr`, `channels`, `lat`, `lon`, `last_heard`) VALUES ("'+gwaddr+'","'+str(channel_count)+'", '+str(lat)+', '+str(lon)+', "'+str(last_heard)+'")')
    
    if(last_heard < (datetime.now() - timedelta(days=5))):
      print ("Offline")
      clear_bbox(gwaddr)
    else:
      update_bbox(gwaddr)

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
