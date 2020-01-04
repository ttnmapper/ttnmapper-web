#!/usr/bin/python
import MySQLdb
import numpy as np
from pprint import pprint
import sys, getopt, os
import configparser

def main(argv):
  block_size = 0.0005
  output_folder = os.environ['TTNMAPPER_HOME']+"/web/kml"
  outputfile = "heatmap_rssi_"+str(block_size)+"sqdeg"

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
    
    cur_location.execute("SELECT lat,lon FROM gateway_updates WHERE gwaddr=\""+gwid+"\" ORDER BY datetime DESC LIMIT 1")
    result = cur_location.fetchone()
    gwlat = result[0]
    gwlon = result[1]
    
    cur_blocks.execute("SELECT lat, lon, rssiavg FROM 500udeg WHERE gwaddr=\""+gwid+"\"")
    for blkrow in cur_blocks.fetchall():
      if(blkrow[2]==None):
        continue
      binned_data.append([float(blkrow[0]), float(blkrow[1]), float(blkrow[2])])

    # pprint (binned_data)
    binned_data = sorted(binned_data, key=lambda item: item[2])
    binned_data = reversed(binned_data)

    output_text = """<?xml version="1.0" encoding="UTF-8"?>
    <kml xmlns="http://www.opengis.net/kml/2.2">
      <Folder>
        <name>RSSI measurements</name>

    <ScreenOverlay>
    <name>Legend: Signal strength</name>
    <Icon> <href>http://jpmeijers.com/ttnmapper/kml/legend.png</href>
    </Icon>
    <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
    <screenXY x="25" y="95" xunits="pixels" yunits="pixels"/>
    <rotationXY x="0.5" y="0.5" xunits="fraction" yunits="fraction"/>
    <size x="0" y="0" xunits="pixels" yunits="pixels"/>
    </ScreenOverlay>
    
        """

    for i in binned_data:
      output_text += "<GroundOverlay>"

      rssi = i[2]

      if rssi==0:
        output_text += "<color>7f000000</color>" # black
      elif rssi<-120:
        output_text += "<color>7fff0000</color>" # blue
      elif rssi<-115:
        output_text += "<color>7fffff00</color>" # cyan
      elif rssi<-110:
        output_text += "<color>7f00ff00</color>" # green
      elif rssi<-105:
        output_text += "<color>7f00ffff</color>" # yellow
      elif rssi<-100:
        output_text += "<color>7f007fff</color>" # orange
      # elif rssi<-30:
      else:
        output_text += "<color>7f0000ff</color>" # red

      output_text += "<LatLonBox>"
      output_text += "<north>"+str(i[0]+block_size)+"</north>"
      output_text += "<south>"+str(i[0])+"</south>"
      output_text += "<east>"+str(i[1]+block_size)+"</east>"
      output_text += "<west>"+str(i[1])+"</west>"
      output_text += "<rotation>0</rotation>"
      output_text += "</LatLonBox>"

      output_text += "<drawOrder>10</drawOrder>"
      output_text += "<name>"+str(rssi)+"</name>"

      output_text += "</GroundOverlay>"

    output_text += """  </Folder>
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
