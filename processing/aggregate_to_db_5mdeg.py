#!/usr/bin/python
from __future__ import print_function
import MySQLdb
import numpy as np
from pprint import pprint
import sys, getopt, os, json
from operator import itemgetter
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
                     db=    config['database_mysql']['database'],  # name of the data base,
                     autocommit=True
                    )

block_size = 0.005

def clear_gateway(db, gweui):
  cursor = db.cursor()
  cursor.execute('DELETE FROM 5mdeg WHERE gwaddr=%s', [gweui])
  db.commit()

def main(argv):
  updates = 0
  inserts = 0

  lat_max = 0
  lat_min = 0
  lon_max = 0
  lon_min = 0

  features = []

  # you must create a Cursor object. It will let
  #  you execute all the queries you need
  cur_gateways = db.cursor()
  cur_gatewayloc = db.cursor()
  cur_lastagg = db.cursor()
  cur_limits = db.cursor()
  cur_blocks = db.cursor()
  cur_insert = db.cursor()

  print ("Startup")

  #Remove non existent gateways from aggregate table
  cur_gateways.execute("SELECT DISTINCT(gwaddr) FROM 5mdeg")
  for gwrow in cur_gateways.fetchall():
    gwid=str(gwrow[0])

    cur_gatewayloc.execute('SELECT * FROM gateways_aggregated WHERE gwaddr=%s AND last_heard > (NOW() - INTERVAL 5 DAY)', [gwid])
    if(cur_gatewayloc.rowcount<1):
      cur_gatewayloc.execute('DELETE FROM 5mdeg WHERE gwaddr=%s', [gwid])

  #Iterate over all known gateways
  cur_gateways.execute("SELECT DISTINCT(gwaddr) FROM gateways_aggregated WHERE last_heard > (NOW() - INTERVAL 5 DAY)")
  
  gateway_count = 0
  for gwrow in cur_gateways.fetchall():
    gateway_count+=1
    gwid=str(gwrow[0])

    print(str(gateway_count)+"/"+str(cur_gateways.rowcount)+"")

    if(len(argv)):
      if(argv[0]=="--force"):
        if(argv[1]==gwid or argv[1]=="all"):
          clear_gateway(db, gwid)
        else:
          continue

    cur_gatewayloc.execute('SELECT lat,lon,datetime FROM gateway_updates WHERE gwaddr=%s ORDER BY datetime DESC LIMIT 1', [gwid])
    location_row = cur_gatewayloc.fetchone()

    gwlat = location_row[0]
    gwlon = location_row[1]
    moved = location_row[2]
    print ("Processing gateway "+gwid)
    startTime = time.time()
    
    try:
      sql = '''DROP TABLE temp_table_5mdeg'''
      cur_limits.execute(sql)
    except:
      pass
    
    sql = '''CREATE TEMPORARY TABLE
    temp_table_5mdeg 
    ( INDEX lat_lon_idx (lat, lon), INDEX lat_idx (lat), INDEX lon_idx (lon) )
    AS (
      SELECT * FROM packets
      WHERE gwaddr=%s 
      )'''
    values = [gwid]

    cur_limits.execute(sql, values)

    print("Temp table created")
    endTime = time.time()
    print(endTime - startTime)

    startTime = time.time()

    sql = "DELETE FROM temp_table_5mdeg WHERE time<%s"
    values = [moved]
    cur_limits.execute(sql, values)

    print("Old data filtered")
    endTime = time.time()
    print(endTime - startTime)
    
    # sql = "SELECT max(lat),min(lat),max(lon),min(lon),max(time),count(*) FROM `packets` WHERE gwaddr=\""+gwid+"\" AND time>\""+moved.strftime('%Y-%m-%d %H:%M:%S')+"\""
    sql = "SELECT max(lat),min(lat),max(lon),min(lon),max(time),count(*) FROM temp_table_5mdeg"
    cur_limits.execute(sql)

    if cur_limits.rowcount==0:
      clear_gateway(db, gwid)
      continue

    last_sample_time=0
    total_samples=0
    for row in cur_limits.fetchall():
      if(row[0]==None or row[1]==None or row[2]==None or row[3]==None):
        print ("No data")
        clear_gateway(db, gwid)
        continue
      lat_max = float(row[0])
      lat_min = float(row[1])
      lon_max = float(row[2])
      lon_min = float(row[3])
      last_sample_time = row[4]
      total_samples = row[5]
    if(lat_max==None or lat_min==None or lon_max==None or lon_min==None or total_samples==0):
      clear_gateway(db, gwid)
      continue

    #if the last update in packets is newer than the last update in 5mdeg,
    #do the aggregation, otherwise skip this gateway
    #also handle when zero entries for gateway in 5mdeg
    startTime = time.time()

    sql_last = "SELECT max(last_update),sum(samples) FROM 5mdeg WHERE gwaddr=%s"
    cur_lastagg.execute(sql_last, [gwid])

    print("Last update read from aggregates")
    endTime = time.time()
    print(endTime - startTime)
    
    if(cur_lastagg.rowcount>0):
      last_agg_time = 0
      last_nr_samples = 0
      for row in cur_lastagg.fetchall():
        last_agg_time=row[0]
        last_nr_samples=row[1]
      if(last_agg_time!=None):
        print ("packets="+str(total_samples)+" aggsamples="+str(last_nr_samples))
        
        if(total_samples==last_nr_samples):
          sys.stderr.write(gwid+": already latest aggregation\n")
          continue
        
        #we should check if last_nr_samples>total_samples, 
        #because then it means outlier samples have been deleted
        if(last_nr_samples>total_samples):
          clear_gateway(db, gwid)


    lat_max = lat_max + (block_size - lat_max%block_size)
    lat_min = lat_min - lat_min%block_size
    lon_max = lon_max + (block_size - lon_max%block_size)
    lon_min = lon_min - lon_min%block_size
    sys.stderr.write(gwid+": "+str(lat_min)+" - "+str(lat_max)+", "+str(lon_min)+" - "+str(lon_max)+'\n')

    if(lat_max-1>lat_min or lon_max-2.5>lon_min):
      print("TOO LARGE AREA!! - exiting")
      with open( os.environ['TTNMAPPER_HOME']+"/logfiles/aggregate_to_db_skipped_5mdeg.log", "a") as myfile:
        myfile.write(gwid+": "+str(lat_min)+" - "+str(lat_max)+", "+str(lon_min)+" - "+str(lon_max)+'\n')
      continue

    lat_range = np.arange(lat_min,lat_max+block_size,block_size) #arange exclude the stop value
    lon_range = np.arange(lon_min,lon_max+block_size,block_size)

    current_cell = 0
    total_cells = lat_range.size * lon_range.size

    for i in np.nditer(lat_range):
      for j in np.nditer(lon_range):
        block_start_lat = i.item(0)
        block_start_lon = j.item(0)
        block_end_lat = i.item(0)+block_size
        block_end_lon = j.item(0)+block_size
        block_mid_lat = i.item(0)+(block_size/2)
        block_mid_lon = j.item(0)+(block_size/2)

        #new point, increment counter and print stats
        current_cell += 1
        print("  "+str(current_cell)+" of "+str(int(total_cells))+" - "+str(round(float(current_cell)/float(total_cells)*100.0))+"%                  ", end='\r')
        
        query = """
        SELECT count(*) FROM temp_table_5mdeg
          WHERE lat>=%s
          AND lat<%s
          AND lon>=%s
          AND lon<%s
        """
        values = [block_start_lat, block_end_lat, block_start_lon, block_end_lon]
        cur_limits.execute(query, values)
        row = cur_limits.fetchone()
        samples = row[0]

        
        if(samples!=0):

          query = """
          SELECT
            AVG(plr.rssi),
            MIN(plr.rssi),
            MAX(plr.rssi),
            AVG(plr.snr),
            MIN(plr.snr),
            MAX(plr.snr),
            MAX(plr.time)
            FROM (
              SELECT * FROM temp_table_5mdeg
              WHERE lat>=%s
               AND lat<%s
               AND lon>=%s
               AND lon<%s
               ORDER BY time DESC
               LIMIT 10
            ) AS plr
            """

          values = [block_start_lat, block_end_lat, block_start_lon, block_end_lon]
          
          cur_limits.execute(query, values)
          row = cur_limits.fetchone()
          rssiavg = row[0]
          rssimin = row[1]
          rssimax = row[2]
          snravg = row[3]
          snrmin = row[4]
          snrmax = row[5]
          timemax = row[6]

          sql_update = """SELECT * FROM 5mdeg
          WHERE gwaddr=%s
          AND lat = %s
          AND lon = %s"""
          
          values = [gwid, i.item(0), j.item(0)]
          cur_insert.execute(sql_update, values)
          
          if(cur_insert.rowcount>0):
            updates+=1

            sql_update = """
            UPDATE 5mdeg
            SET last_update=%s,
            samples=%s,
            rssimin=%s, rssimax=%s,rssiavg=%s,
            snrmin=%s, snrmax=%s, snravg=%s
            WHERE gwaddr=%s
            AND lat = %s
            AND lon = %s
            """

            values = (
              timemax.strftime('%Y-%m-%d %H:%M:%S'),
              samples, rssimin, rssimax, rssiavg,
              snrmin, snrmax, snravg,
              gwid, block_mid_lat, block_mid_lon
            )
            
            cur_insert.execute(sql_update, values)
          
          else:
            inserts+=1
            sql_insert = ('INSERT INTO 5mdeg '+
            '(gwaddr, last_update, lat, lon, samples, '+
            'rssimin, rssimax, rssiavg,'+
            'snrmin, snrmax, snravg) '+
            'VALUES (%s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s)')

            values = (
              gwid, block_mid_lat, block_mid_lon,
              samples, rssimin, rssimax, rssiavg,
              snrmin, snrmax, snravg
            )
            
            cur_insert.execute(sql_insert, values)
          
        else:
          #delete aggregate from db in case gateway moved
          sql_delete = """
          DELETE FROM 5mdeg
          WHERE gwaddr=%s
          AND lat = %s
          AND lon = %s
          """
          values = (gwid, block_mid_lat, block_mid_lon)
          cur_insert.execute(sql_delete, values)
            
        db.commit()

  cur_gateways.close()
  cur_lastagg.close()
  cur_limits.close()
  cur_blocks.close()
  cur_insert.close()
  db.close()
  
  print ("Inserted "+str(inserts))
  print ("Updated: "+str(updates))

if __name__ == "__main__":

  lockfile =  os.environ['TTNMAPPER_HOME']+"/lockfiles/aggregate-lock"
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

  #call main function
  main(sys.argv[1:])
