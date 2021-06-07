#!/usr/bin/python
import MySQLdb
import MySQLdb.cursors
import csv
import sys, os
import time
import configparser

filename = os.environ['TTNMAPPER_HOME']+'/web/dumps/packets-'+time.strftime('%Y%m%d')+'-Lindlar.csv'

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.SSCursor)

cursor = db.cursor()

dbFields = ['id', 'time', 'nodeaddr', 'appeui', 'gwaddr', 'modulation', 'datarate', 'snr', 'rssi', 'freq', 'fcount', 'lat', 'lon', 'alt', 'accuracy', 'hdop', 'sats', 'provider', 'user_agent']

latMin = 50.945835 #50.873508
latMax = 51.073839 #51.001711
lonMin = 7.290469 #6.842058
lonMax = 7.524741 #7.07633

query = 'SELECT '+','.join(dbFields)+' FROM packets WHERE lat>%s AND lat<%s AND lon>%s AND lon<%s'
values = (latMin, latMax, lonMin, lonMax)
print(query)
cursor.execute(query, values)

ofile = open(filename,'w')
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
