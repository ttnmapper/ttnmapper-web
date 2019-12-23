#!/usr/bin/python
import sys, os, copy
import MySQLdb
import json
import datetime
import configparser


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
  cursor = db.cursor()
  
  print("Running apps query")
  cursor.execute("SELECT DISTINCT(`appeui`) FROM `packets` WHERE 1")
  print("Iterating apps result")
  for appid in cursor.fetchall():
    # try:
    #   appid = appid[0]
    # except:
    #   pass
    cursor.execute("SELECT DISTINCT(`nodeaddr`) FROM `packets` WHERE `appeui` = %s", (appid))
    for devid in cursor.fetchall():
      # try:
      #   devid = devid[0]
      # except:
      #   pass
      print(str(appid) + " - " + str(devid))
      cursor.execute("SELECT COUNT(*), COUNT(DISTINCT(`gwaddr`)), COUNT(DISTINCT(`freq`)) FROM `packets` WHERE `appeui` = %s AND `nodeaddr` = %s", (appid, devid))
      result = cursor.fetchone()
      packets = result[0]
      gateways = result[1]
      channels = result[2]

      cursor.execute("""
        SELECT * FROM `stats_per_device` WHERE app_id=%s AND dev_id=%s
        """,
        (appid[0], devid[0]))

      if(cursor.rowcount == 0):
        cursor.execute("""
          INSERT INTO `stats_per_device`
            (`app_id`,`dev_id`,`packets`, `gateways`, `channels`)
            VALUES (%s,%s,%s,%s,%s)
          """,
          (appid[0], devid[0], packets, gateways, channels)
        )
      else:
        cursor.execute(
          """
          UPDATE `stats_per_device`
          SET packets=%s, gateways=%s, channels=%s WHERE app_id=%s AND dev_id=%s
          """,
          (packets, gateways, channels, appid[0], devid[0])
        )

  db.commit()
  cursor.close()
  db.close()


if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/stats-per-dev-lock"
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
