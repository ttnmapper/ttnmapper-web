#!/usr/bin/python
from __future__ import print_function
import os,sys
import MySQLdb, MySQLdb.cursors
from datetime import datetime
from dateutil import rrule
import configparser

import matplotlib as mpl
mpl.use('Agg')
import matplotlib.pyplot as plt

import matplotlib.dates as mdates

output_folder = os.environ['TTNMAPPER_HOME']+"/graphs"
outputfile = "gateway_growth"

dates = []
mvalues = []
kvalues = []

years = mdates.YearLocator()   # every year
months = mdates.MonthLocator()  # every month
yearsFmt = mdates.DateFormatter('%Y')

def main(arv):
  config = configparser.ConfigParser()
  config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

  db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                       user=  config['database_mysql']['username'],  # your username
                       passwd=config['database_mysql']['password'],  # your password
                       db=    config['database_mysql']['database'],  # name of the data base
                       cursorclass=MySQLdb.cursors.DictCursor)

  # you must create a Cursor object. It will let
  #  you execute all the queries you need
  cursor_dates = db.cursor()
  cursor_count = db.cursor()

  # cursor_dates.execute("SELECT DISTINCT(DATE(`time`)) AS 'day' FROM packets")
  start_datetime = datetime(year = 2015, month = 8, day = 1, hour = 0, minute = 0, second = 0)
  now = datetime.now()

  for dt in rrule.rrule(rrule.MONTHLY, dtstart=start_datetime, until=now):
    print (dt)
  
  # sys.exit()
  # for day in cursor_dates.fetchall():
    # print(day['day'].strftime('%Y-%m-%d'))
    cursor_count.execute("SELECT COUNT(DISTINCT(`gwaddr`)) AS 'count' FROM packets WHERE `time` < \""+dt.strftime('%Y-%m-%d')+"\" ")
    row = cursor_count.fetchone()
    mvalues.append(row['count'])
    dates.append(dt)

    cursor_count.execute("SELECT COUNT(DISTINCT(`gwaddr`)) AS 'count' FROM gateway_updates WHERE `datetime` < \""+dt.strftime('%Y-%m-%d')+"\" ")
    row = cursor_count.fetchone()
    kvalues.append(row['count'])

  fig = plt.figure()
  ax = fig.add_subplot(111)
  # ax.plot(dates, values)
  ax.fill_between(dates, 0, mvalues, facecolor='blue', alpha=0.5)
  ax.fill_between(dates, mvalues, kvalues, facecolor='red', alpha=0.5)
  ax.grid(True)
  # ax.set_xlim([0, 10])
  # ax.set_ylim([-120, -60])
  ax.set_title('Gateways over time (red=known, blue=measured)')
  ax.set_xlabel('Date')
  ax.set_ylabel('Number of gateways')
  ax.format_xdata = mdates.DateFormatter('%Y-%m-%d')

  # format the ticks
  ax.xaxis.set_major_locator(years)
  ax.xaxis.set_major_formatter(yearsFmt)
  ax.xaxis.set_minor_locator(months)

  fig.savefig(output_folder+"/"+outputfile+".png")


if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/process_daily.lock"
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