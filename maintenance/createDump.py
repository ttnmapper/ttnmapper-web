#!/usr/bin/python
import pymysql
import csv
import sys, os
import time
import configparser

filename = os.environ['TTNMAPPER_HOME']+'/dumps/packets-'+time.strftime('%Y%m%d')+'.csv'

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

#dbQuery='SELECT '+','.join(dbFields)+' FROM packets WHERE `lat`<52.137516 AND `lon`>4.967000 AND `lat`>52.045832 AND `lon`<5.194977'
dbQuery='SELECT '+','.join(dbFields)+' FROM packets'

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
