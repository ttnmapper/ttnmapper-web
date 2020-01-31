#!/usr/bin/python
import pymysql
import csv
import sys, os
import time
import configparser
import MySQLdb, MySQLdb.cursors

filename = os.environ['TTNMAPPER_HOME']+'/web/dumps/packets-'+time.strftime('%Y%m%d')+'.csv'

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.DictCursor)

#cursor = db.cursor(pymysql.cursors.SSCursor)
cursor = db.cursor()

dbFields = ['id', 'time', 'nodeaddr', 'appeui', 'gwaddr', 'modulation', 'datarate', 'snr', 'rssi', 'freq', 'lat', 'lon', 'alt', 'accuracy', 'hdop', 'sats', 'provider', 'user_agent']

dbQuery='SELECT '+','.join(dbFields)+' FROM packets WHERE lat>54.161681 AND lat<54.473828 AND lon>9.807358 AND lon<10.427243'
#dbQuery='SELECT '+','.join(dbFields)+' FROM packets'

cursor.execute(dbQuery)

ofile = open(filename,'wb')
csv_writer = csv.writer(ofile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)

csv_writer.writerow(dbFields)

while True:
  line = cursor.fetchone()
  if line:
    csv_writer.writerow(line)
  else:
    break

cursor.close()
db.close()
