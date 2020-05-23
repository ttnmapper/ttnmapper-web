#!/usr/bin/python
import MySQLdb
import MySQLdb.cursors
import urllib, json
import string
import sys, os
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
                     cursorclass=MySQLdb.cursors.DictCursor,
                     charset='utf8',
                     autocommit=True)

def main(argv):
  cursor = db.cursor()

  cursor.execute('SET NAMES utf8mb4;')
  cursor.execute('SET CHARACTER SET utf8mb4;')
  cursor.execute('SET character_set_connection=utf8mb4;')

  cursor.execute("SELECT DISTINCT(gwaddr) FROM  `gateways_aggregated`")
  gateway_list = cursor.fetchall()

  for gateway in gateway_list:

    gwaddr = gateway["gwaddr"]
    
    if(len(argv)>0):
      if not gwaddr in argv:
        continue

    if(len(gwaddr)==16 and all(c in string.hexdigits for c in gwaddr)):
      gwaddr = "eui-"+gwaddr.lower()
    url = config['network']['account_server_gateways']+gwaddr
    response = urllib.urlopen(url)
    data = json.loads(response.read())
    if("attributes" in data):
      if("description" in data["attributes"]):
        description = data["attributes"]["description"]

        values = (description, gateway["gwaddr"])
        print values

        cursor.execute("UPDATE `gateways_aggregated` SET `description` = %s WHERE `gwaddr` = %s", values)

  # db.commit()
  cursor.close()
  db.close()



if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-descriptions-lock"
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
