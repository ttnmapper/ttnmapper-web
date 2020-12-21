#!/usr/bin/python
from __future__ import print_function
from geopy.distance import great_circle
import os,sys
import MySQLdb, MySQLdb.cursors
import math
import time
import datetime
import pprint
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

#max length of radial in meters
DISTANCE_CAP = 100000
levels = [-100, -105, -110, -115, -120, -200]
#levels = [-80, -82, -84, -86, -88, -90, -92, -94, -96, -98, -100, -102, -104, -106, -108, -110, -112, -114, -116, -118, -120, -200]

# A temporary storage location for data to prevent too many database hits.
memcache = {}

FORCE_UPDATE = []
FORCE_ALL = False


def calculate_initial_compass_bearing(pointA, pointB):
  if (type(pointA) != tuple) or (type(pointB) != tuple):
      raise TypeError("Only tuples are supported as arguments")

  lat1 = math.radians(pointA[0])
  lat2 = math.radians(pointB[0])

  diffLong = math.radians(pointB[1] - pointA[1])

  x = math.sin(diffLong) * math.cos(lat2)
  y = math.cos(lat1) * math.sin(lat2) - (math.sin(lat1)
          * math.cos(lat2) * math.cos(diffLong))

  initial_bearing = math.atan2(x, y)

  initial_bearing = math.degrees(initial_bearing)
  compass_bearing = (initial_bearing + 360) % 360

  return compass_bearing

def main():

  cursor = db.cursor()

  print ("Startup")

  gateways_active = []
  gateways_radar = []

  cursor.execute("SELECT DISTINCT(`gwaddr`) AS gwid FROM radar WHERE 1")
  for gwrow in cursor.fetchall():
    gateways_radar.append(str(gwrow['gwid']))
  
  #cur_gateways.execute("SELECT gwaddr,lat,lon,max(`datetime`) AS moved FROM gateway_updates GROUP BY gwaddr")
  cursor.execute("SELECT `gwaddr` FROM gateways_aggregated WHERE `last_heard` > (NOW() - INTERVAL 5 DAY)")
  #Iterate over all known gateways
  for gwrow in cursor.fetchall():
    gateways_active.append(str(gwrow['gwaddr']))

  print("Iterating gateways "+str(len(gateways_radar)))
  #remove radar for old gateways
  for gwid in gateways_radar:
    if gwid in gateways_active:
      pass
    else:
      print ("Gateway "+gwid+" older than 5 days. Removing radar plot.")
      cursor.execute('DELETE FROM `radar` WHERE gwaddr="'+gwid+'" ')
      db.commit()


  for gwid in gateways_active:
    print("PROCESSING "+str(gwid))
    process_gateway(gwid)
    print("-", end="")
    add_memcache_to_db(gwid)
    print("-", end="")
    db.commit()
    print("-")



def process_gateway(gwid):
    cursor = db.cursor()

    # get the start time from when the gateway was moved/installed
    cursor.execute('SELECT lat,lon,datetime FROM gateway_updates WHERE gwaddr="'+gwid+'" ORDER BY datetime DESC LIMIT 1')

    if cursor.rowcount < 1:
      print("No location history")
      return

    location_row = cursor.fetchone()

    gwlat = location_row['lat']
    gwlon = location_row['lon']
    moved = location_row['datetime']

    select_from_timestamp = moved

    if (gwid in FORCE_UPDATE) or FORCE_ALL:
      print("Force updating", gwid)
      add_zeros_to_memcache(gwid)
      cursor.execute('DELETE FROM `radar` WHERE gwaddr="'+gwid+'" ')
      db.commit()

      select_from_timestamp = moved

    elif len(FORCE_UPDATE) > 0:
      return

    else:
      
      # print("SELECT all packets since move")
      # cursor.execute('SELECT count(*) as samples FROM packets WHERE gwaddr = "'+gwid+'" AND time>"'+moved.strftime('%Y-%m-%d %H:%M:%S')+'"')
      # samples_to_include = cursor.fetchone()['samples']
      
      #check if we need to update or not
      # print("SELECT radar stats")
      cursor.execute('SELECT max(last_update) as last_update, sum(samples) as samples FROM radar WHERE gwaddr = "'+gwid+'"')
      row = cursor.fetchone()
      last_update_radar = row['last_update']
      # samples_radar = row['samples']

      # print("samples_include="+str(samples_to_include)+"\tsamples_radar="+str(samples_radar))

      """
      if(samples_to_include == 0 and (samples_radar == 0 or samples_radar == None)):
        print("Gateway has no measurements")
        return

      elif(samples_to_include == samples_radar):
        print("Gateway already up to date")
        return


      # New gateway
      elif(samples_to_include > 0 and samples_radar == None):
        select_from_timestamp = moved
        print("NEW GATEWAY "+gwid)

        # Prefill memecache with all directions - only do for new gateways, as this makes things really slow
        print("FILLING memcache")
        add_zeros_to_memcache(gwid)


      # If rowncount to include < rowcount included - delete radar and rebuild
      # Either the gateway moved, or we deleted data. Both times reprocess.
      elif(samples_to_include < samples_radar):
        print("Reprosessing gateway")
        select_from_timestamp = moved
        print("DELETING radar")
        cursor.execute('DELETE FROM `radar` WHERE gwaddr="'+gwid+'" ')
        db.commit()

        # Prefill memecache with all directions - only do for new gateways, as this makes things really slow
        print("FILLING memcache")
        add_zeros_to_memcache(gwid)

      # else check if the gateway has been updated yet
      el"""
      if(last_update_radar!=None):
        select_from_timestamp = max(last_update_radar, moved)
        # cursor.execute('SELECT * FROM packets WHERE gwaddr = %s AND time > %s LIMIT 1', (gwid, select_from_timestamp))
        # if(cursor.rowcount==0):
        #   #no new data
        #   print("Gateway "+gwid+" already up to date")
        #   return
        # else:
        #   print ("New data for "+gwid)


    # Select all new points and process
    print("SELECT all new packets")
    cursor.execute('SELECT time, snr, rssi, lat, lon FROM packets WHERE gwaddr = %s AND time > %s', (gwid, select_from_timestamp))
    total = cursor.rowcount
    number = 0
    for point in cursor.fetchall():
      number+=1
      bearing = get_bearing(gwlat, gwlon, point['lat'], point['lon'])
      distance = get_distance(gwlat, gwlon, point['lat'], point['lon'])
      level = get_level(point['rssi'], point['snr'])
      print(number, "/", total, "                  ", end='\r')

      add_point_to_memcache(gwid, bearing, distance, point['time'], level)

    print("")



def get_bearing(gw_lat, gw_lon, point_lat, point_lon):
  return int(round(calculate_initial_compass_bearing((gw_lat, gw_lon),(point_lat, point_lon)))%360)

def get_distance(gw_lat, gw_lon, point_lat, point_lon):
  return int(great_circle((point_lat, point_lon),(gw_lat, gw_lon)).meters)

def get_level(rssi, snr):
  if(snr == None):
    signal = rssi
  elif(snr < 0):
    signal = rssi + snr
  else:
    signal = rssi

  for level in levels:
    # Not > not <. Because -90 > -100.
    if signal > level:
      return level

  # Should only get here if RSSI <= -200
  return -200

def add_zeros_to_memcache(gwid):
  for bearing in range(0,360):
    for level in levels:
      global memcache

      if not gwid in memcache:
        memcache[gwid] = {}

      cache = memcache[gwid]

      cache[bearing] = {}
      cache[bearing][level] = {}
      cache[bearing][level]['distance'] = 0
      cache[bearing][level]['distance_max'] = 0
      cache[bearing][level]['time'] = datetime.datetime.fromtimestamp(0)
      cache[bearing][level]['samples'] = 0

def add_point_to_memcache(gwid, bearing, distance, point_time, level):
  global memcache


  if(bearing>=360):
    with open('points-to-radar-errors.log', 'a') as the_file:
      the_file.write("ERROR: "+str(gwid)+" "+str(bearing)+" "+str(distance)+"\n")
    distance = 10
    bearing = 0

  if(distance>DISTANCE_CAP):
    distance=0
    with open('points-to-radar-errors.log', 'a') as the_file:
      the_file.write("Capping distance "+str(gwid)+" "+str(distance)+"\n")
    # Ignore too long radials
    distance = 10
    bearing = 0


  if not gwid in memcache:
    memcache[gwid] = {}

  cache = memcache[gwid]

  if not bearing in cache:
    cache[bearing] = {}

  if not level in cache[bearing]:
    cache[bearing][level] = {}
    cache[bearing][level]['distance'] = distance
    cache[bearing][level]['distance_max'] = distance
    cache[bearing][level]['time'] = point_time
    cache[bearing][level]['samples'] = 1

  else:
    latest_timestamp = max(cache[bearing][level]['time'], point_time)
    cache[bearing][level]['time'] = latest_timestamp
    cache[bearing][level]['samples'] += 1

    if(distance > cache[bearing][level]['distance_max']):
      cache[bearing][level]['distance'] = cache[bearing][level]['distance_max']
      cache[bearing][level]['distance_max'] = distance

    elif(distance > cache[bearing][level]['distance']):
      cache[bearing][level]['distance'] = distance


def add_memcache_to_db(gwid):
  global memcache
  cursor = db.cursor()
  if gwid in memcache:
    for bearing, levels in memcache[gwid].items():
      # print("bearing", bearing)
      for level, values in levels.items():
        # print("level", level)
        if(level == None):
          # print (level)
          # print (values)
          # print (levels)
          # print (bearing)
          print (memcache[gwid])

        distance = values['distance']
        distance_max = values['distance_max']
        point_time = values['time']
        samples = values['samples']

        cursor.execute("SELECT distance, distance_max, last_update, samples FROM radar WHERE bearing = %s AND gwaddr = %s AND level = %s", (bearing, gwid, level))
        # print("Previous radar select: ", time.time() - start)
        if(cursor.rowcount<1):
          # First packet for this radial
          # print("Adding: ", gwid, bearing, level)
          cursor.execute('INSERT INTO `radar` (`gwaddr`, `bearing`, `distance`, `distance_max`, `level`, `last_update`, `samples`) VALUES (%s, %s, %s, %s, %s, %s, %s)', (gwid, bearing, distance, distance_max, level, point_time, samples))
          # db.commit()

        else:
          row = cursor.fetchone()
          old_distance = float(row['distance'])
          old_distance_max = float(row['distance_max'])
          old_timestamp = row['last_update']
          old_samples = row['samples']

          latest_timestamp = max(old_timestamp, point_time)
          distances = sorted([distance, distance_max, old_distance, old_distance_max])
          samples_new = old_samples + samples

          cursor.execute('UPDATE `radar` SET `distance`=%s, `distance_max`=%s, `last_update`=%s, `samples`=%s WHERE gwaddr=%s AND bearing=%s AND level=%s',
            (distances[2], distances[3], latest_timestamp, samples_new, gwid, bearing, level))
            



if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/radar-per-point-lock"
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



  if(len(sys.argv)>1):
    if sys.argv[1] == "all":
      FORCE_ALL = True
    else:
      FORCE_UPDATE = sys.argv[1:]

  #call main function
  main()
