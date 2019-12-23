#!/usr/bin/python
import MySQLdb
import MySQLdb.cursors
import sys, os
from datetime import datetime, timedelta
import configparser

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.DictCursor)

cur_select = db.cursor()
cur_update = db.cursor()


def main(argv):
  cur_select.execute("SELECT DISTINCT(gwaddr) FROM  `gateways_aggregated`")
  gateway_list = cur_select.fetchall()
  for gateway in gateway_list:
    gwaddr = str(gateway["gwaddr"])
    print (gwaddr)

    cur_select.execute('SELECT last_update FROM gateway_updates WHERE gwaddr="'+gwaddr+'" LIMIT 1')
    row = cur_select.fetchone()
    last_heard = row["last_update"]
    if(last_heard==None):
      continue
    print ("Last heard="+str(last_heard))

    cur_update.execute('UPDATE `gateways_aggregated` SET last_heard="'+str(last_heard)+'" WHERE gwaddr="'+gwaddr+'"')

  db.commit()
  cur_update.close()
  cur_select.close()
  db.close()

if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateways-aggregate-lastheard-lock"
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
