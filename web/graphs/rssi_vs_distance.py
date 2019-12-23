#!/usr/bin/python
import sys, os
import MySQLdb
import datetime
import configparser

import matplotlib as mpl
mpl.use('Agg')
import matplotlib.pyplot as plt

from geopy.distance import great_circle


output_folder = os.environ['TTNMAPPER_HOME']+"/geojson"
outputfile = "rssi_vs_distance"

allrssi = []
alldistance = []


def main(argv):
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
    
    cur_gateways.execute("SELECT DISTINCT(`gwaddr`) FROM packets")
    for gwrow in cur_gateways.fetchall():
      gwaddr = str(gwrow[0])
      points = []

      moved = None
      cur_moved.execute('SELECT datetime,lat,lon FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
      for t in cur_moved.fetchall():
        moved = t[0]
        gwlat = t[1]
        gwlon = t[2]
        if(t[1]!=None and t[2]!=None):
          points.append([float(t[1])*1000, float(t[2])*1000])

      #if the gateway does not exist in our gateway_update table, the coordinates of it's location is unknown. This is ok, just plot all it's data.
      if(moved==None):
        #continue #don't plot gateway
        moved = datetime.datetime.fromtimestamp(0) #plot everything

      print gwaddr
      rssis = []
      distances = []
    
      cur_location.execute('SELECT rssi,lat,lon FROM packets WHERE `gwaddr`="'+str(gwaddr)+'" AND time>"'+moved.strftime('%Y-%m-%d %H:%M:%S')+'"')
      for sample in cur_location.fetchall():
        distance = great_circle((sample[1], sample[2]),(gwlat, gwlon)).meters / 1000
        rssi = sample[0]
        distances.append(distance)
        alldistance.append(distance)
        rssis.append(rssi)
        allrssi.append(rssi)

      fig = plt.figure()
      ax = fig.add_subplot(111)
      ax.scatter(distances, rssis, s=15, marker='o', lw=0)
      ax.set_xlim([0, 10])
      ax.set_ylim([-120, -60])
      ax.set_title('Signal vs distance '+gwaddr)
      ax.set_xlabel('Distance (km)')
      ax.set_ylabel('RSSI (dBm)')
      if not os.path.exists(output_folder+"/"+gwaddr):
        os.makedirs(output_folder+"/"+gwaddr)
      fig.savefig(output_folder+"/"+gwaddr+"/"+outputfile+".png")
      plt.close()

    cur_gateways.close()
    cur_location.close()

    fig = plt.figure()
    ax = fig.add_subplot(111)
    ax.scatter(alldistance, allrssi)
    ax.set_xlim([0, 10])
    ax.set_ylim([-120, -60])
    ax.set_title('Signal vs distance all')
    ax.set_xlabel('Distance (km)')
    ax.set_ylabel('RSSI (dBm)')
    fig.savefig(output_folder+"/"+outputfile+".png")
    plt.close()

if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/rssi_distance.lock"
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
