#!/usr/bin/python

import MySQLdb
import MySQLdb.cursors
import sys
import configparser

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.DictCursor)

cursor = db.cursor()
cursor.execute("SELECT * FROM packets")

count = 0
for row in cursor:
    count+=1
    print(row)
    exit()