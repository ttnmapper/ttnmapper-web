#!/usr/bin/python
from __future__ import print_function
import MySQLdb, MySQLdb.cursors
import subprocess
import os
import configparser

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.DictCursor)
               
cur = db.cursor()

sql = "SELECT * FROM mqtt_import_nodes WHERE 1"
cur.execute(sql)

for row in cur.fetchall():

  dir_path = os.path.dirname(os.path.realpath(__file__))
  errlogfile = dir_path+"/logfiles/"+row["appeui"] + "_" + row["devaddr"] + "_" + row["format"]  + "-error.log"
  stdlogfile = dir_path+"/logfiles/"+row["appeui"] + "_" + row["devaddr"] + "_" + row["format"]  + "-status.log"

  command = []
  command.append('nohup')
  command.append(dir_path+"/mqtt_listener_v2.py")
  command.append("--appeui")
  command.append(row["appeui"])
  command.append("--accesskey")
  command.append(row["accesskey"])
  command.append("--devaddr")
  command.append(row["devaddr"])
  command.append("--format")
  command.append(row["format"])
  command.append("--provider")
  command.append(row["provider"])
  command.append("--broker")
  command.append(row["broker"])
  command.append("--version")
  command.append(str(row["version"]))
  if(row["experiment"]!=None):
    command.append("--experiment")
    command.append(row["experiment"])


  subprocess.Popen(command,
               stdout=open(stdlogfile, 'a'),
               stderr=open(errlogfile, 'a'),
               preexec_fn=os.setpgrp
               )