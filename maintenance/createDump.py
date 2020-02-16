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
                     cursorclass=MySQLdb.cursors.SSCursor)

cursor = db.cursor()

dbFields = ['id', 'time', 'nodeaddr', 'appeui', 'gwaddr', 'modulation', 'datarate', 'snr', 'rssi', 'freq', 'lat', 'lon', 'alt', 'accuracy', 'hdop', 'sats', 'provider', 'user_agent']

dbQuery='SELECT '+','.join(dbFields)+' FROM packets WHERE lat>53.437823 AND lat<55.121245 AND lon>7.484045 AND lon<11.516027'
#dbQuery='SELECT '+','.join(dbFields)+' FROM packets'

cursor.execute(dbQuery)

ofile = open(filename,'wb')
csv_writer = csv.writer(ofile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)

csv_writer.writerow(dbFields)

counter = 0
while True:
  counter += 1
  print(counter)
  line = cursor.fetchone()
  # print(line)
  if line:
    csv_writer.writerow(list(line))
  else:
    break

cursor.close()
db.close()
