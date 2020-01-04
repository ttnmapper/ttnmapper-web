#!/usr/bin/python
import MySQLdb
import numpy as np
from pprint import pprint
import sys, getopt, os, json
from operator import itemgetter
import configparser

def main(argv):
  block_size = 0.005
  output_folder = os.environ['TTNMAPPER_HOME']+"/web/geojson"
  outputfile = "radials_rssi_"+str(block_size)+"sqdeg"

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
  cur_location = db.cursor()
  cur_blocks = db.cursor()

  cur_gateways.execute("SELECT DISTINCT(gwaddr) FROM 5mdeg")
  for gwrow in cur_gateways.fetchall():
    # Use all the SQL you like
    gwid=str(gwrow[0])
    
    cur_location.execute("SELECT lat,lon FROM gateway_updates WHERE gwaddr=\""+gwid+"\" ORDER BY datetime DESC LIMIT 1")
    result = cur_location.fetchone()
    gwlat = float(result[0])
    gwlon = float(result[1])
    
    cur_blocks.execute("SELECT lat, lon, rssiavg, rssimin, rssimax, snravg, snrmin, snrmax FROM 5mdeg WHERE gwaddr=\""+gwid+"\"")
    
    for row in cur_blocks.fetchall():
      lat = float(row[0])
      lon = float(row[1])
      rssi = float(row[2])
      rssimin = float(row[3])
      rssimax = float(row[4])
      snravg = float(row[5])
      snrmin = float(row[6])
      snrmax = float(row[7])
      
      if rssi==0:
        color = "black"
      elif rssi<-120:
        color = "blue"
      elif rssi<-115:
        color = "cyan"
      elif rssi<-110:
        color = "green"
      elif rssi<-105:
        color = "yellow"
      elif rssi<-100:
        color = "orange"
      else:
        color = "red"
      
      feature = {}
      feature["type"] = "Feature"
      feature["geometry"] = {}
      feature["geometry"]["type"] = "LineString"
      feature["geometry"]["coordinates"] = [[gwlon, gwlat], [lon+block_size/2, lat+block_size/2]]
      feature["style"] = {}
      feature["style"]["color"] = color
      feature["style"]["stroke-width"] = "1"
      feature["style"]["fill-opacity"] = 0.4
      feature["style"]["opacity"] = 0.4
      feature["properties"] = {}
      feature["properties"]["rssi_avg"] = rssi
      feature["properties"]["rssi_min"] = rssimin
      feature["properties"]["rssi_max"] = rssimax
      feature["properties"]["snr_avg"] = snravg
      feature["properties"]["snr_min"] = snrmin
      feature["properties"]["snr_max"] = snrmax
      features.append(feature)

  features = sorted(features, key=lambda k: k['properties']['rssi_avg'])

  geojson = {}
  geojson["type"] = "FeatureCollection"
  geojson["features"] = features
  
  filename = output_folder+"/"+outputfile+".geojson"
  if not os.path.exists(os.path.dirname(filename)):
    try:
        os.makedirs(os.path.dirname(filename))
    except OSError as exc: # Guard against race condition
        if exc.errno != errno.EEXIST:
            raise
  
  with open(filename, "w") as text_file:
    text_file.write(json.dumps(geojson))

  cur_gateways.close()
  cur_location.close()
  cur_blocks.close()
  db.close()

if __name__ == "__main__":
   main(sys.argv[1:])
