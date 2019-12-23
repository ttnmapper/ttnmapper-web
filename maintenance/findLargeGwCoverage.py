#!/usr/bin/python
import MySQLdb
import MySQLdb.cursors
import sys
import configparser

def main(argv):
    
  config = configparser.ConfigParser()
  config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

  db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                       user=  config['database_mysql']['username'],  # your username
                       passwd=config['database_mysql']['password'],  # your password
                       db=    config['database_mysql']['database'],  # name of the data base
                       cursorclass=MySQLdb.cursors.DictCursor)

  cur_select = db.cursor()

  gwAreaDict = {}
  
  # cur_select.execute("SELECT `gwaddr` FROM  `packets` WHERE  `gwaddr` REGEXP  '^eui-.*$' GROUP BY `gwaddr`")
  cur_select.execute("SELECT * FROM `gateway_bbox` WHERE 1")
  for row in cur_select.fetchall():
    # Use all the SQL you like
    latDiff = abs(row['lat_min']-row['lat_max'])
    lonDiff = abs(row['lon_min']-row['lon_max'])
    area = latDiff*lonDiff
    gwAreaDict[row['gweui']] = area

  print(sorted(gwAreaDict.items(), key=lambda kv: kv[1]))

  # db.commit()
  cur_select.close()
  db.close()

if __name__ == "__main__":
   main(sys.argv[1:])
