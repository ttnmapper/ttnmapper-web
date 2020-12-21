#!/usr/bin/python
import json
import geojson
from functools import partial
import shapely.geometry
import shapely.ops
from shapely.geometry.polygon import Polygon
import time

output_folder = os.environ['TTNMAPPER_HOME']+"/web/geojson"
outputfile = "radar_merged_"


def subtract_multipolygons(a, b):
  clipped = Polygon()
  i = 0
  for first in a:
    for second in b:
      print (float(i)/(len(a)*len(b)) * 100)
      i+=1
      first = first.buffer(0)
      second = second.buffer(0)
      if(first.intersects(second)):
        print ("Intersection")
        clipped.union(first.difference(second))
  return clipped



#colors = ["blue", "cyan", "green", "yellow", "orange", "red"]
colors = ["yellow", "red"]

merged = {}

for color in colors:
  # reading into two geojson objects, in a GCS (WGS84)
  with open(output_folder+'/radar_'+color+'.geojson') as geojson1:
      poly1_geojson = json.load(geojson1)

  # mergedPolygon = Polygon()#Polygon([(0, 0), (0, 0), (0, 0)])
  # i = 0
  polygons = []
  for polygon in poly1_geojson['features']:
    # print (float(i)/float(len(poly1_geojson['features']))*100.0)
    # i+=1
    # print (polygon)
    poly1 = shapely.geometry.asShape(polygon['geometry'])
    poly1 = poly1.buffer(0)
    polygons.append(poly1)
  #   # print (poly1)
  #   # try:
  #   mergedPolygon = mergedPolygon.union(poly1)
  #   # except:
  #   #   print ("Error merging polygon:")
  #   #   print (polygon['geometry'])

  print ("Fixed all "+color+" polygons, now merging")
  start = time.time()
  # from shapely.geometry import shape
  # polygons =[shape(polygon['geometry']) for polygon in poly1_geojson['features']]
  from shapely.ops import cascaded_union, unary_union
  mergedPolygon = cascaded_union(polygons) # or unary_union(polygons)

  print ("Done merge of "+color+" in "+str(time.time()-start)+" seconds")

  # using geojson module to convert from WKT back into GeoJSON format
  geojson_out = geojson.Feature(geometry=mergedPolygon, properties={})

  # outputting the updated geojson file - for mapping/storage in its GCS format
  with open(output_folder+'/'+outputfile+color+'.geojson', 'w') as outfile:
      json.dump(geojson_out.geometry, outfile, indent=3, encoding="utf-8")
  outfile.close()

  merged[color] = mergedPolygon



# print ("Calculating differneces")
# clipped = {}
# print ("Blue - Cyan")
# clipped["blue"] = merged["blue"].difference(merged["cyan"])
# print ("Cyan - Green")
# clipped["cyan"] = merged["cyan"].difference(merged["green"])
# print ("Green - Yellow")
# clipped["green"] = merged["green"].difference(merged["yellow"])
# print ("Yellow - Orange")
# clipped["yellow"] = merged["yellow"].difference(merged["orange"])
print ("Orange - Red")
# clipped["orange"] = merged["orange"].difference(merged["red"])
clipped["orange"] = subtract_multipolygons(merged["yellow"], merged["red"])
print ("Red")
clipped["red"] = merged["red"]

for color in colors:
  geojson_out = geojson.Feature(geometry=clipped[color], properties={})
  with open(output_folder+'/'+outputfile+color+'_clipped.geojson', 'w') as outfile:
      json.dump(geojson_out.geometry, outfile, indent=3, encoding="utf-8")
  outfile.close()
