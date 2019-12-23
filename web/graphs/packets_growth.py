#!/usr/bin/python
from __future__ import print_function
import os,sys
import MySQLdb, MySQLdb.cursors
import datetime
import configparser

import matplotlib as mpl
mpl.use('Agg')
import matplotlib.pyplot as plt

import matplotlib.dates as mdates
import matplotlib.ticker as mtick

output_folder = os.environ['TTNMAPPER_HOME']+"/graphs"
outputfile = "packet_growth"

dates = []
values = []

years = mdates.YearLocator()   # every year
months = mdates.MonthLocator()  # every month
yearsFmt = mdates.DateFormatter('%Y')

def daterange(start_date, end_date):
    for n in range(int ((end_date - start_date).days)):
        yield start_date + datetime.timedelta(n)

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
  for i in range(1,13):
      single_date = datetime.datetime(2018, i, 1)
      print (single_date.strftime("%Y-%m-%d"))
      dates.append(single_date)

      cursor_count.execute("SELECT COUNT(*) AS 'count' FROM packets WHERE `time`<\""+single_date.strftime('%Y-%m-%d')+" 00:00:00\"")
      row = cursor_count.fetchone()
      values.append(row['count'])

  for i in range(1,3):
      single_date = datetime.datetime(2019, i, 1)
      print (single_date.strftime("%Y-%m-%d"))
      dates.append(single_date)

      cursor_count.execute("SELECT COUNT(*) AS 'count' FROM packets WHERE `time`<\""+single_date.strftime('%Y-%m-%d')+" 00:00:00\"")
      row = cursor_count.fetchone()
      values.append(row['count'])

  fig = plt.figure()
  ax = fig.add_subplot(111)
  # ax.plot(dates, values)
  ax.fill_between(dates, 0, values, facecolor='blue', alpha=0.5)
  ax.grid(True)
  # ax.set_xlim([0, 10])
  # ax.set_ylim([-120, -60])
  ax.set_title('Measurements over time')
  ax.set_xlabel('Date')
  ax.set_ylabel('Number of data points')
  ax.format_xdata = mdates.DateFormatter('%Y-%m-%d')

  # format the ticks
  ax.yaxis.set_major_formatter(mtick.FormatStrFormatter('%d'))
  ax.xaxis.set_major_locator(years)
  ax.xaxis.set_major_formatter(yearsFmt)
  ax.xaxis.set_minor_locator(months)

  fig.savefig(output_folder+"/"+outputfile+".png")


if __name__ == "__main__":

  lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/process_packet_growt_graph.lock"
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