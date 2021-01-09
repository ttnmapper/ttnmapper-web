#!/usr/bin/python
import MySQLdb
import numpy as np
from pprint import pprint
import sys, getopt, os, json
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
                    )

def main(argv):
  output_folder = os.environ['TTNMAPPER_HOME']+"/web/geojson"
  outputfile = "circles"

  features = []

  # you must create a Cursor object. It will let
  #  you execute all the queries you need
  cur_gateways = db.cursor()
  cur_location = db.cursor()
  cur_blocks = db.cursor()

  cur_gateways.execute("SELECT DISTINCT(gwaddr) AS gwaddr FROM gateways_aggregated WHERE `last_heard` > (NOW() - INTERVAL 5 DAY) ORDER BY gwaddr ASC")
  total = cur_gateways.rowcount
  count = 0
  for gwrow in cur_gateways.fetchall():
    count += 1
    features = []

    gwaddr = str(gwrow[0])
    gwlat = 0
    gwlon = 0

    if(len(argv)>0):
      if(argv[0]!=gwaddr):
        continue
    
    print("Processing gateway "+gwaddr+" "+str(count)+" of "+str(total))

    cur_location.execute('SELECT lat,lon FROM gateways_aggregated WHERE gwaddr=%s', (gwaddr,))
    for sample in cur_location.fetchall():
      gwlat = float(sample[0])
      gwlon = float(sample[1])

    levels = [-100, -105, -110, -115, -120, -200]

    for level in levels:
      cur_location.execute('SELECT max(distance) FROM radar WHERE `gwaddr`=%s AND level=%s', (gwaddr, level))
      
      for sample in cur_location.fetchall():
        distance = sample[0]
      try:
        distance = float(distance)
      except:
        continue # can be NULL
    
      if level == -200:
        colour = "blue"
      elif level == -120:
        colour = "cyan"
      elif level == -115:
        colour = "green"
      elif level == -110:
        colour = "yellow"
      elif level == -105:
        colour = "orange"
      elif level == -100:
        colour = "red"
      else:
        colour = "black"
        
      feature = {}
      feature["type"] = "Feature"
      feature["geometry"] = {}
      feature["geometry"]["type"] = "Point"
      feature["geometry"]["coordinates"] = [gwlon, gwlat]
      feature["style"] = {}
      feature["style"]["color"] = colour
      feature["style"]["stroke-width"] = "2"
      feature["style"]["fill-opacity"] = 0.4
      feature["style"]["opacity"] = 0.4
      feature["properties"] = {}
      feature["properties"]["point_type"] = "circle"
      feature["properties"]["radius"] = distance
      
      if(colour=="red"):
        feature["properties"]["level"] = 10
      elif(colour=="orange"):
        feature["properties"]["level"] = 8
      elif(colour=="yellow"):
        feature["properties"]["level"] = 6
      elif(colour=="green"):
        feature["properties"]["level"] = 4
      elif(colour=="cyan"):
        feature["properties"]["level"] = 2
      elif(colour=="blue"):
        feature["properties"]["level"] = 0

      features.append(feature)

    features = sorted(features, key=lambda k: k['properties']['level'])

    geojson = {}
    geojson["type"] = "FeatureCollection"
    geojson["features"] = features
    
    filename = output_folder+"/"+gwaddr+"/"+outputfile+".geojson"
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
  db.close()

if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/circles-all-lock"
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
