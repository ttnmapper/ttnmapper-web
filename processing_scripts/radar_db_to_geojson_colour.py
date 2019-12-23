#!/usr/bin/python

import sys, os, copy
import MySQLdb
import json
import datetime
import configparser

import geopy
from geopy.distance import VincentyDistance

import shapely.geometry
import shapely.ops
from shapely.geometry.polygon import Polygon

output_folder = os.environ['TTNMAPPER_HOME']+"/web/geojson"
outputfile = "radar"

mergedBluePolygons = []

def addTriangle(gwlat, gwlon, bearing, distance, features):
  if(distance<1):
    return
  origin = geopy.Point(gwlat, gwlon)
  destination = VincentyDistance(kilometers=distance/1000.0).destination(origin, ((360+bearing-(0.5/2.0))%360))
  lat, lon = destination.latitude, destination.longitude
  destination = VincentyDistance(kilometers=distance/1000.0).destination(origin, ((360+bearing+(0.5/2.0))%360))
  lat2, lon2 = destination.latitude, destination.longitude

  feature = {}
  feature["type"] = "Feature"
  feature["geometry"] = {}
  feature["geometry"]["type"] = "Polygon"
  feature["geometry"]["coordinates"] = [[[gwlon, gwlat], [round(lon,6), round(lat,6)], [round(lon2,6), round(lat2,6)], [gwlon, gwlat]]]

  features.append(copy.deepcopy(feature))

def main(argv):
    global mergedBluePolygon
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
    cur_moved = db.cursor()
    cur_location = db.cursor()
    
    exceptions = []
    features = []
    bluefeatures = []
    cyanfeatures = []
    greenfeatures = []
    yellowfeatures = []
    orangefeatures = []
    redfeatures = []
    
    cur_gateways.execute("SELECT DISTINCT(`gwaddr`) FROM radar")
    for gwrow in cur_gateways.fetchall():
      gwfeatures = []

      gwaddr = str(gwrow[0])
      gwlat = 0
      gwlon = 0

      if(len(argv)>0):
        if not gwaddr in argv:
          continue
      
      print("Processing gateway "+gwaddr)

      cur_moved.execute('SELECT lat,lon FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
      for sample in cur_moved.fetchall():
        gwlat = float(sample[0])
        gwlon = float(sample[1])

      levels = [-100, -105, -110, -115, -120, -200]
      # levels = [-80, -82, -84, -86, -88, -90, -92, -94, -96, -98, -100, -102, -104, -106, -108, -110, -112, -114, -116, -118, -120, -200]
      jump_degrees = 1

      previous_points = []

      for level in levels:
        memcache = {}
        points = []
        #add gateway as a point
        #points.append([gwlon,gwlat])
        
        cur_location.execute('SELECT bearing,distance FROM radar WHERE `gwaddr`="'+gwaddr+'" AND level>='+`level`+' ORDER BY bearing ASC')
        previous_distance = 11
        origin = geopy.Point(gwlat, gwlon)
          #add starting point
        destination = VincentyDistance(kilometers=10/1000.0).destination(origin, ((360-(jump_degrees/2.0))%360))
        lat2, lon2 = destination.latitude, destination.longitude
        # points.append([lon2,lat2])

        for sample in cur_location.fetchall():
          bearing = float(sample[0])
          distance = float(sample[1])
          if bearing in memcache:
            if distance > memcache[bearing]:
              memcache[bearing] = distance
            #else previous one is larger
          else:
            memcache[bearing] = distance

        for bearing in range(0, 360):
          if bearing not in memcache:
            memcache[bearing] = 0

        for bearing in sorted(memcache.keys()):
          distance = memcache[bearing]
          if(distance>10):
            if(previous_distance<=10):
              destination = VincentyDistance(kilometers=0).destination(origin, ((360+bearing-(jump_degrees/2.0))%360))
              lat2, lon2 = destination.latitude, destination.longitude
              points.append([round(lon2,6),round(lat2,6)])
            
            # for every direction two points
            destination = VincentyDistance(kilometers=distance/1000.0).destination(origin, ((360+bearing-(jump_degrees/2.0))%360))
            lat2, lon2 = destination.latitude, destination.longitude
            points.append([round(lon2,6),round(lat2,6)])
            destination = VincentyDistance(kilometers=distance/1000.0).destination(origin, ((360+bearing+(jump_degrees/2.0))%360))
            lat2, lon2 = destination.latitude, destination.longitude
            points.append([round(lon2,6),round(lat2,6)])

            # destination = VincentyDistance(kilometers=distance/1000.0).destination(origin, ((360+bearing)%360))
            # lat2, lon2 = destination.latitude, destination.longitude
            # points.append([round(lon2,6),round(lat2,6)])

          if(distance<=10 and previous_distance>10):
            destination = VincentyDistance(kilometers=0).destination(origin, ((360+bearing-1+(jump_degrees/2.0))%360))
            lat2, lon2 = destination.latitude, destination.longitude
            points.append([round(lon2,6),round(lat2,6)])

          previous_distance = distance

        try:

          feature = {}
          feature["type"] = "Feature"
          feature["geometry"] = {}
          feature["geometry"]["type"] = "Polygon"
          if(len(points)>3):
            points.append(points[0])
          else:
            continue

          feature["geometry"]["coordinates"] = [points]
          feature["style"] = {}
          # feature["style"]["stroke-width"] = "0"
          # feature["style"]["fill-opacity"] = 1
          # feature["style"]["opacity"] = 1
          feature["properties"] = {}
          feature["properties"]["rssi_avg"] = level

          if(level==-200):
            feature["style"]["color"] = "blue"
            bluefeatures.append(copy.deepcopy(feature))
          elif (level==-120):
            feature["style"]["color"] = "cyan"
            cyanfeatures.append(copy.deepcopy(feature))
          elif (level==-115):
            feature["style"]["color"] = "green"
            greenfeatures.append(copy.deepcopy(feature))
          elif (level==-110):
            feature["style"]["color"] = "yellow"
            yellowfeatures.append(copy.deepcopy(feature))
          elif (level==-105):
            feature["style"]["color"] = "orange"
            orangefeatures.append(copy.deepcopy(feature))
          elif (level==-100):
            feature["style"]["color"] = "red"
            redfeatures.append(copy.deepcopy(feature))
          else:
            feature["style"]["color"] = "black"


          if(len(previous_points)>0):
            feature["geometry"]["coordinates"] = [points, previous_points]
          previous_points=points


          features.append(feature)
          gwfeatures.append(feature)
        except Exception as e: 
          print str(e)
          # print "Exception"
          # exceptions.append(gwaddr)

      #create geojson file for this gateway only
      gwgeojson = {}
      gwgeojson["type"] = "FeatureCollection"
      gwgeojson["features"] = gwfeatures

      filename = output_folder+"/"+gwaddr+"/"+outputfile+".geojson"
      if not os.path.exists(os.path.dirname(filename)):
        try:
            os.makedirs(os.path.dirname(filename))
        except OSError as exc: # Guard against race condition
            if exc.errno != errno.EEXIST:
                raise

      with open(filename, "w") as text_file:
        text_file.write(json.dumps(gwgeojson))


    #global geojson file
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

    geojson["features"] = bluefeatures
    with open(output_folder+"/"+outputfile+"_blue.geojson", "w") as text_file:
      text_file.write(json.dumps(geojson))

    geojson["features"] = cyanfeatures
    with open(output_folder+"/"+outputfile+"_cyan.geojson", "w") as text_file:
      text_file.write(json.dumps(geojson))

    geojson["features"] = greenfeatures
    with open(output_folder+"/"+outputfile+"_green.geojson", "w") as text_file:
      text_file.write(json.dumps(geojson))

    geojson["features"] = yellowfeatures
    with open(output_folder+"/"+outputfile+"_yellow.geojson", "w") as text_file:
      text_file.write(json.dumps(geojson))

    geojson["features"] = orangefeatures
    with open(output_folder+"/"+outputfile+"_orange.geojson", "w") as text_file:
      text_file.write(json.dumps(geojson))

    geojson["features"] = redfeatures
    with open(output_folder+"/"+outputfile+"_red.geojson", "w") as text_file:
      text_file.write(json.dumps(geojson))

    with open(filename+"-exceptions", "w") as text_file:
      text_file.write(json.dumps(exceptions))

    cur_gateways.close()
    cur_location.close()

if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/radar-geojson-colour-lock"
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
