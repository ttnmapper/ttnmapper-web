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


def update_channel_count(gwaddr):

  cur_select.execute('SELECT count(distinct(freq)) as channel_count FROM packets WHERE gwaddr=%s', [gwaddr])
  row = cur_select.fetchone()
  channel_count = row["channel_count"]

  cur_update.execute('UPDATE gateways_aggregated SET channels=%s WHERE gwaddr=%s', [channel_count, gwaddr])

  print ("channels=", channel_count, end="\t")


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
    update_channel_count(gwaddr)
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
