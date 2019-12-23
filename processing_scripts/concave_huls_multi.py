#!/usr/bin/python

from scipy.spatial import Delaunay
from scipy.spatial import ConvexHull
import networkx as nx

import sys, os
import MySQLdb
import json
import datetime
from pprint import pprint
import configparser

import numpy as np
#import matplotlib.pyplot as plt

import math
import shapely.geometry as geometry
from shapely.ops import cascaded_union, polygonize
from descartes import PolygonPatch


output_folder = os.environ['TTNMAPPER_HOME']+"/web/geojson"
outputfile = "concave_huls"

def alpha_shape(points, alpha):
    """
    Compute the alpha shape (concave hull) of a set
    of points.
    @param points: Iterable container of points.
    @param alpha: alpha value to influence the
        gooeyness of the border. Smaller numbers
        don't fall inward as much as larger numbers.
        Too large, and you lose everything!
    """
    if len(points) < 4:
        # When you have a triangle, there is no sense
        # in computing an alpha shape.
        return geometry.MultiPoint(list(points)).convex_hull
    def add_edge(edges, edge_points, coords, i, j):
        """
        Add a line between the i-th and j-th points,
        if not in the list already
        """
        if (i, j) in edges or (j, i) in edges:
            # already added
            return
        edges.add( (i, j) )
        edge_points.append(coords[ [i, j] ])
    #coords = np.array([point.coords[0]
    #                   for point in points])
    coords = np.array(points)
    tri = Delaunay(coords)
    edges = set()
    edge_points = []
    # loop over triangles:
    # ia, ib, ic = indices of corner points of the
    # triangle
    for ia, ib, ic in tri.vertices:
        pa = coords[ia]
        pb = coords[ib]
        pc = coords[ic]
        # Lengths of sides of triangle
        a = math.sqrt((pa[0]-pb[0])**2 + (pa[1]-pb[1])**2)
        b = math.sqrt((pb[0]-pc[0])**2 + (pb[1]-pc[1])**2)
        c = math.sqrt((pc[0]-pa[0])**2 + (pc[1]-pa[1])**2)
        # Semiperimeter of triangle
        s = (a + b + c)/2.0
        # Area of triangle by Heron's formula
        area = math.sqrt(s*(s-a)*(s-b)*(s-c))
        if(area == 0):
            continue
        circum_r = a*b*c/(4.0*area)
        # Here's the radius filter.
        if circum_r < 1.0/alpha:
            add_edge(edges, edge_points, coords, ia, ib)
            add_edge(edges, edge_points, coords, ib, ic)
            add_edge(edges, edge_points, coords, ic, ia)
    m = geometry.MultiLineString(edge_points)
    triangles = list(polygonize(m))
    return cascaded_union(triangles), edge_points

def plot_polygon(polygon):
    fig = plt.figure(figsize=(10,10))
    ax = fig.add_subplot(111)
    margin = .3
    x_min, y_min, x_max, y_max = polygon.bounds
    ax.set_xlim([x_min-margin, x_max+margin])
    ax.set_ylim([y_min-margin, y_max+margin])
    patch = PolygonPatch(polygon, fc='#999999',
                         ec='#000000', fill=True,
                         zorder=-1)
    ax.add_patch(patch)
    return fig


def main(argv):
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
    cur_moved = db.cursor()
    cur_location = db.cursor()
    
    exceptions = []
    features = []
    
    cur_gateways.execute("SELECT DISTINCT(`gwaddr`) FROM packets")
    for gwrow in cur_gateways.fetchall():
      gwaddr = str(gwrow[0])
      points = []

      if(len(argv)>0):
        if(gwaddr != argv[0]):
          continue

      moved = None
      cur_moved.execute('SELECT datetime,lat,lon FROM gateway_updates WHERE gwaddr="'+gwaddr+'" ORDER BY datetime DESC LIMIT 1')
      for t in cur_moved.fetchall():
        moved = t[0]
        if(t[1]!=None and t[2]!=None):
          points.append([float(t[1])*1000, float(t[2])*1000])

      #if the gateway does not exist in our gateway_update table, the coordinates of it's location is unknown. This is ok, just plot all it's data.
      if(moved==None):
        #continue #don't plot gateway
        moved = datetime.datetime.fromtimestamp(0) #plot everything

      print gwaddr,
    
      cur_location.execute('SELECT lat,lon FROM packets WHERE `gwaddr`="'+str(gwaddr)+'" AND time>"'+moved.strftime('%Y-%m-%d %H:%M:%S')+'"')
      for sample in cur_location.fetchall():
          points.append([float(sample[1])*1000, float(sample[0])*1000])

      print "Points="+str(len(points))+"\t",

      if len(points)<10:
        print "Not enough."
        continue

      i=0.5
      
      x = None
      y = None

      try:
        concave_hull, edge_points = alpha_shape(points,i)
      except:
        print "Error."
        continue

      gwfeatures = []
      for i in (geometry.base.GeometrySequence(concave_hull, geometry.Polygon)):
        #pprint(geometry.base.dump_coords(i))
        x, y = i.exterior.coords.xy

        feature = {}
        try:
          x = [i / 1000 for i in x]
          y = [i / 1000 for i in y]

          feature["type"] = "Feature"
          feature["geometry"] = {}
          feature["geometry"]["type"] = "Polygon"
          feature["geometry"]["coordinates"] = [zip(x,y)]
          feature["style"] = {}
          feature["style"]["color"] = "blue"
          feature["style"]["stroke-width"] = "2"
          feature["style"]["fill-opacity"] = 0.4
          feature["style"]["opacity"] = 0.4
          feature["properties"] = {}
          # features.append(feature)
          gwfeatures.append(feature)
        except Exception as e: 
          print str(e)
          # print "Exception"
          # exceptions.append(gwaddr)

      #create geojson file for this gateway only
      gwgeojson = {}
      gwgeojson["type"] = "FeatureCollection"
      gwgeojson["features"] = gwfeatures

      filename = output_folder+"/"+gwaddr+"/alphashape.geojson"
      if not os.path.exists(os.path.dirname(filename)):
        try:
            os.makedirs(os.path.dirname(filename))
        except OSError as exc: # Guard against race condition
            if exc.errno != errno.EEXIST:
                raise

      print "done"

      with open(filename, "w") as text_file:
        text_file.write(json.dumps(gwgeojson))
      
    # geojson = {}
    # geojson["type"] = "FeatureCollection"
    # geojson["features"] = features

    # pprint(geojson)

    # filename = output_folder+"/"+outputfile+".geojson"
    # if not os.path.exists(os.path.dirname(filename)):
    #   try:
    #       os.makedirs(os.path.dirname(filename))
    #   except OSError as exc: # Guard against race condition
    #       if exc.errno != errno.EEXIST:
    #           raise

    # with open(filename, "w") as text_file:
    #   text_file.write(json.dumps(geojson))

    # with open(filename+"-exceptions", "w") as text_file:
    #   text_file.write(json.dumps(exceptions))

    cur_gateways.close()
    cur_location.close()

if __name__ == "__main__":
    main(sys.argv[1:])
