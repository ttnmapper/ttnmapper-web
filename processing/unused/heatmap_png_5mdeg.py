#!/usr/bin/python
import MySQLdb
import numpy as np
from pprint import pprint
import sys, getopt, os, json
from operator import itemgetter
import png
import configparser

def main(argv):
  block_size = 0.005
  
  updates = 0
  inserts = 0

  lat_max = 0
  lat_min = 0
  lon_max = 0
  lon_min = 0

  features = []
    
  config = configparser.ConfigParser()
  config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

  db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                       user=  config['database_mysql']['username'],  # your username
                       passwd=config['database_mysql']['password'],  # your password
                       db=    config['database_mysql']['database'],  # name of the data base
                      )

  # you must create a Cursor object. It will let
  #  you execute all the queries you need
  cur_gateways = db.cursor()
  cur_lastagg = db.cursor()
  cur_limits = db.cursor()
  cur_blocks = db.cursor()
  cur_insert = db.cursor()

  #cur_gateways.execute("SELECT gwaddr,lat,lon,max(`datetime`) AS moved FROM gateway_updates GROUP BY gwaddr")
  cur_gateways.execute("""
  SELECT gwaddr,lat,lon,datetime AS moved
  FROM gateway_updates
  WHERE (gwaddr, datetime) IN
  (
    SELECT gwaddr, MAX(datetime)
     FROM gateway_updates
    GROUP BY gwaddr
  )
  ORDER BY gwaddr ASC""")
  #Iterate over all known gateways
  for gwrow in cur_gateways.fetchall():
    # Use all the SQL you like
    gwid=str(gwrow[0])
    gwlat = gwrow[1]
    gwlon = gwrow[2]
    moved = gwrow[3]
    
    #handle moved gateways->zero sample blocks are deleted
    
    #print ("SELECT max(lat),min(lat),max(lon),min(lon) FROM `packets` WHERE gwaddr=\""+gwid+"\"")
    cur_limits.execute("SELECT max(lat),min(lat),max(lon),min(lon),max(time),count(*) FROM `packets` WHERE gwaddr=\""+gwid+"\" AND time>\""+moved.strftime('%Y-%m-%d %H:%M:%S')+"\"")

    if cur_limits.rowcount==0:
      continue

    last_sample_time=0
    total_samples=0
    for row in cur_limits.fetchall():
      if(row[0]==None or row[1]==None or row[2]==None or row[3]==None):
        continue
      lat_max = float(row[0])
      lat_min = float(row[1])
      lon_max = float(row[2])
      lon_min = float(row[3])
      last_sample_time = row[4]
      total_samples = row[5]
    if(lat_max==None or lat_min==None or lon_max==None or lon_min==None or total_samples==0):
      continue
      

    lat_max = lat_max+lat_max%block_size
    lat_min = lat_min-lat_min%block_size
    lon_max = lon_max+lon_max%block_size
    lon_min = lon_min-lon_min%block_size
    sys.stderr.write(gwid+": "+str(lat_min)+" - "+str(lat_max)+", "+str(lon_min)+" - "+str(lon_max)+'\n')




    lat_range = np.arange(lat_min,lat_max,block_size)
    lon_range = np.arange(lon_min,lon_max,block_size)

    # print np.shape(lon_range)

    image_data = list()

    for i in np.nditer(lat_range):
      image_row = []
      for j in np.nditer(lon_range):
        query = ("SELECT COUNT(*),"+
          "AVG(rssi),MIN(rssi),MAX(rssi),AVG(snr),MIN(snr),MAX(snr),MAX(time) "+
          "FROM `packets` "+
          "WHERE lat>="+str(i.item(0))+
          " AND lat<"+str(i.item(0)+block_size)+
          " AND lon>="+str(j.item(0))+
          " AND lon<"+str(j.item(0)+block_size)+
          " AND gwaddr=\""+gwid+"\""+
          " AND time>\""+moved.strftime('%Y-%m-%d %H:%M:%S')+"\"")
        
        
        cur_blocks.execute(query)
        for row in cur_blocks.fetchall():
          samples = row[0]
          rssi = row[1]
        
        if(samples!=0):
          if rssi==0:
            image_row+=[0,0,0] # black
          elif rssi<-120:
            image_row+=[0,0,0xFF] # blue
          elif rssi<-115:
            image_row+=[0,0xFF,0xFF] # cyan
          elif rssi<-110:
            image_row+=[0,0xFF,0] # green
          elif rssi<-105:
            image_row+=[0xFF,0xFF,0] # yellow
          elif rssi<-100:
            image_row+=[0xFF,0x7F,0] # orange
          # elif rssi<-30:
          else:
            image_row+=[0,0,0xFF] # red
        else:
            image_row+=[0xFF,0xFF,0xFF]
            
      image_data.append(image_row)
      image_row=[]
      sys.stderr.write('|\n')
    
    image_png = png.from_array(image_data, 'RGB')
    image_png.save(gwid+"test.png")
    image_data=[]

if __name__ == "__main__":
  #call main function
  main(sys.argv[1:])
