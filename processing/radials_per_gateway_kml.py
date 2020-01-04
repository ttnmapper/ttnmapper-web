#!/usr/bin/python
import MySQLdb
import numpy as np
from pprint import pprint
import sys, getopt, os
import configparser

def main(argv):
  block_size = 0.0005
  output_folder = os.environ['TTNMAPPER_HOME']+"/web/kml"
  outputfile = "radials_rssi_"+str(block_size)+"sqdeg"

  lat_max = 0
  lat_min = 0
  lon_max = 0
  lon_min = 0

  config = configparser.ConfigParser()
  config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

  db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                       user=  config['database_mysql']['username'],  # your username
                       passwd=config['database_mysql']['password'],  # your password
                       db=    config['database_mysql']['database'],  # name of the data base
                      )

  # you must create a Cursor object. It will let
  #  you execute all the queries you need
  cur_gateways = db.cursor()
  cur_location = db.cursor()
  cur_blocks = db.cursor()

  cur_gateways.execute("SELECT DISTINCT(gwaddr) FROM 500udeg")
  for gwrow in cur_gateways.fetchall():
    binned_data = []
    gwid = str(gwrow[0])

    if(len(argv)):
      if(argv[0]=="--force"):
        if(argv[1]==gwid or argv[1]=="all"):
          pass
        else:
          continue

    print("Processing gateway "+gwid)
    
    cur_location.execute("SELECT lat,lon FROM gateway_updates WHERE gwaddr=\""+gwid+"\" ORDER BY datetime DESC LIMIT 1")
    result = cur_location.fetchone()
    gwlat = float(result[0])
    gwlon = float(result[1])
    
    cur_blocks.execute("SELECT lat, lon, rssiavg FROM 500udeg WHERE gwaddr=\""+gwid+"\"")
    for blkrow in cur_blocks.fetchall():
      if(blkrow[2]==None):
        continue
      binned_data.append([float(blkrow[0]), float(blkrow[1]), float(blkrow[2])])

    # pprint (binned_data)
    binned_data = sorted(binned_data, key=lambda item: item[2])
    binned_data = reversed(binned_data)

    output_text = """<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2">
      <Document>

        <Style id="black">
          <LineStyle>
            <color>7f000000</color>
            <width>2</width>
          </LineStyle>
        </Style>
        <Style id="blue">
          <LineStyle>
            <color>7fff0000</color>
            <width>2</width>
          </LineStyle>
        </Style>
        <Style id="cyan">
          <LineStyle>
            <color>7fffff00</color>
            <width>2</width>
          </LineStyle>
        </Style>
        <Style id="green">
          <LineStyle>
            <color>7f00ff00</color>
            <width>2</width>
          </LineStyle>
        </Style>
        <Style id="yellow">
          <LineStyle>
            <color>7f00ffff</color>
            <width>2</width>
          </LineStyle>
        </Style>
        <Style id="orange">
          <LineStyle>
            <color>7f007fff</color>
            <width>2</width>
          </LineStyle>
        </Style>
        <Style id="red">
          <LineStyle>
            <color>7f0000ff</color>
            <width>2</width>
          </LineStyle>
        </Style>

   <Folder>
   
    
        """

    for i in binned_data:
      #output_text += "<GroundOverlay>"
      output_text += """<Placemark>
      <LineString id="ID">
    <extrude>0</extrude>
    <tessellate>0</tessellate>
    <altitudeMode>clampToGround</altitudeMode>"""

      rssi = i[2]
      level = (rssi +140)/100

      if rssi==0:
        output_text += "<gx:drawOrder>0</gx:drawOrder>"
      elif rssi<-120:
        output_text += "<gx:drawOrder>2</gx:drawOrder>"
      elif rssi<-115:
        output_text += "<gx:drawOrder>4</gx:drawOrder>"
      elif rssi<-110:
        output_text += "<gx:drawOrder>6</gx:drawOrder>"
      elif rssi<-105:
        output_text += "<gx:drawOrder>8</gx:drawOrder>"
      elif rssi<-100:
        output_text += "<gx:drawOrder>10</gx:drawOrder>"
      else:
        output_text += "<gx:drawOrder>12</gx:drawOrder>"

      output_text += """<coordinates>"""+str(gwlon)+","+str(gwlat)+","+str(level)+" "+str(i[1]+block_size/2)+","+str(i[0]+block_size/2)+","+str(level)+"""</coordinates></LineString>"""

      if rssi==0:
        output_text += "<styleUrl>#black</styleUrl>" # black
      elif rssi<-120:
        output_text += "<styleUrl>#blue</styleUrl>" # blue
      elif rssi<-115:
        output_text += "<styleUrl>#cyan</styleUrl>" # cyan
      elif rssi<-110:
        output_text += "<styleUrl>#green</styleUrl>" # green
      elif rssi<-105:
        output_text += "<styleUrl>#yellow</styleUrl>" # yellow
      elif rssi<-100:
        output_text += "<styleUrl>#orange</styleUrl>" # orange
      else:
        output_text += "<styleUrl>#red</styleUrl>" # red
      
      output_text += "</Placemark>"

    output_text += """
    
        </Folder>
      </Document>
    </kml>"""
    
    filename = output_folder+"/"+gwid+"/"+gwid+"_"+outputfile+".kml"
    if not os.path.exists(os.path.dirname(filename)):
      try:
          os.makedirs(os.path.dirname(filename))
      except OSError as exc: # Guard against race condition
          if exc.errno != errno.EEXIST:
              raise

    with open(filename, "w") as text_file:
      text_file.write(output_text)

  cur_gateways.close()
  cur_location.close()
  cur_blocks.close()
  db.close()

if __name__ == "__main__":
   main(sys.argv[1:])
