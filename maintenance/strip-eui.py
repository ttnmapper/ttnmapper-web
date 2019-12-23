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
  cur_update = db.cursor()
  
  # cur_select.execute("SELECT `gwaddr` FROM  `packets` WHERE  `gwaddr` REGEXP  '^eui-.*$' GROUP BY `gwaddr`")
  cur_select.execute("SELECT distinct(`gwaddr`) as 'gwaddr' FROM  `packets`")
  for row in cur_select.fetchall():
    # Use all the SQL you like
    old_eui = row["gwaddr"]
    new_eui = row["gwaddr"]
    if(new_eui.startswith("eui-")):
      new_eui = new_eui[4:]
      new_eui = new_eui.upper()

      print ("Old gwaddr="+row["gwaddr"])
      print ("New gwaddr="+new_eui)
      # updatesql = 'UPDATE `packets` SET `gwaddr`="'+new_eui+'" WHERE `id`='+str(row['id'])
      updatesql = 'UPDATE `packets` SET `gwaddr`="'+new_eui+'" WHERE `gwaddr`="'+str(old_eui)+'"'
      cur_update.execute(updatesql)

  db.commit()
  cur_update.close()
  cur_select.close()
  db.close()

if __name__ == "__main__":
   main(sys.argv[1:])
