#!/usr/bin/python
import MySQLdb
import sys
import matplotlib
matplotlib.use('Agg')
import numpy as np
import math
from mpl_toolkits.basemap import Basemap
import matplotlib.pyplot as ppl
from pylab import rcParams
import simplekml
import scipy.interpolate
from mpl_toolkits.axes_grid1 import make_axes_locatable
import matplotlib.ticker as ticker
import configparser


def generate_gradient(name, lat, lon, signal):
  print("generating plot")

  lat = np.array(lat, dtype=np.float32)
  lon = np.array(lon, dtype=np.float32)
  signal = np.array(signal, dtype=np.float32)

  # Interpolate data on the grid
  X, Y = np.linspace(lon.min(), lon.max(), 1000), np.linspace(lat.min(), lat.max(), 500)
  X, Y = np.meshgrid(X, Y)
  Z = scipy.interpolate.griddata((lon, lat), signal, (X, Y), method='cubic')

  # Color plot
  rcParams['figure.figsize'] = (8,8)
  fig1 = ppl.figure(1)
  ax1 = fig1.add_axes([0,0,1,1])
  ax1.axis('off')
  surf = ppl.pcolor(X, Y, Z, cmap='jet', vmin=-120, vmax=-110)
  ppl.axis('off')
  # Get borders
  border1 = ppl.axis()
  if False:
      ppl.show()
  else:
      pngName1 = name + '-Overlay.png'
      fig1.savefig(pngName1, transparent=True, dpi=900)

  bottomleft1  = (border1[0],border1[2])
  bottomright1 = (border1[1],border1[2])
  topright1    = (border1[1],border1[3])
  topleft1     = (border1[0],border1[3])

  # # Scatter plot
  # fig2 = ppl.figure(2)
  # ax2 = fig2.add_axes([0,0,1,1])
  # ax2.axis('off')
  # ax2.scatter(lon, lat, s=50, c=signal, cmap='jet', vmin=-120, vmax=-110)
  # ppl.axis('off')
  # # Get borders
  # border2 = ppl.axis()
  # if False:
  #     ppl.show()
  # else:
  #     pngName2 = name + '-OverlayScatter.png'
  #     fig2.savefig(pngName2, transparent=True, dpi=900)

  # bottomleft2  = (border2[0],border2[2])
  # bottomright2 = (border2[1],border2[2])
  # topright2    = (border2[1],border2[3])
  # topleft2     = (border2[0],border2[3])

  # Create kml file with two layers
  kml = simplekml.Kml()
  ground = kml.newgroundoverlay(name='Map')
  ground.icon.href = pngName1
  ground.gxlatlonquad.coords =[bottomleft1, bottomright1, topright1, topleft1]
  # scat = kml.newgroundoverlay(name='Scatter')
  # scat.icon.href = pngName2
  # scat.gxlatlonquad.coords =[bottomleft2, bottomright2, topright2, topleft2]
  kml.save(name + ".kml")

  # # Color plot with colorbar
  # fig3 = ppl.figure(3)
  # ax3 = fig3.add_subplot(111)
  # surf = ppl.pcolor(X, Y, Z, cmap='jet', vmin=-120, vmax=-110)
  # divider = make_axes_locatable(ax3)
  # cbar = fig3.colorbar(surf, orientation="vertical")
  # x_labels = ax3.get_xticks()
  # ax3.xaxis.set_major_formatter(ticker.FormatStrFormatter('%.4f'))
  # y_labels = ax3.get_yticks()
  # ax3.yaxis.set_major_formatter(ticker.FormatStrFormatter('%.4f'))
  # ax3.set_xlabel('Longitude')
  # ax3.set_ylabel('Latitude')

  # fig3.savefig(name+'.png', transparent=False, dpi=900)

def main(argv):
  config = configparser.ConfigParser()
  config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

  db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                       user=  config['database_mysql']['username'],  # your username
                       passwd=config['database_mysql']['password'],  # your password
                       db=    config['database_mysql']['database'],  # name of the data base
                       cursorclass=MySQLdb.cursors.DictCursor)

  # you must create a Cursor object. It will let
  #  you execute all the queries you need
  cur_gateways = db.cursor()
  cur_moved = db.cursor()
  cur_location = db.cursor()
  
  exceptions = []
  features = []
  
  cur_gateways.execute("SELECT DISTINCT(`gwaddr`) FROM gateways_aggregated")
  for gwrow in cur_gateways.fetchall():
    gwaddr = str(gwrow[0])
    lat = []
    lon = []
    rssi = []

    if(len(argv)>0):
      if(gwaddr != argv[0]):
        continue

    moved = None
    cur_moved.execute('SELECT datetime,lat,lon FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
    for t in cur_moved.fetchall():
      moved = t[0]

    #if the gateway does not exist in our gateway_update table, the coordinates of it's location is unknown. This is ok, just plot all it's data.
    if(moved==None):
      #continue #don't plot gateway
      moved = datetime.datetime.fromtimestamp(0) #plot everything

    print gwaddr,
  
    # cur_location.execute('SELECT lat,lon,rssi FROM packets WHERE `gwaddr`="'+str(gwaddr)+'" AND time>"'+moved.strftime('%Y-%m-%d %H:%M:%S')+'"')
    cur_location.execute('SELECT lat,lon,rssimax FROM 500udeg WHERE `gwaddr`="'+str(gwaddr)+'"')
    for sample in cur_location.fetchall():
      lat.append(sample[0])
      lon.append(sample[1])
      rssi.append(sample[2])

    generate_gradient(gwaddr, lat, lon, rssi)
    break


if __name__ == "__main__":
    main(sys.argv[1:])