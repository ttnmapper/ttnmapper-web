#!/usr/bin/python
from __future__ import print_function
import datetime
import os
import subprocess
import sys
from ttnctlFunctions import get_info
from ttnctlFunctions import get_status

gwid = "eui-3135323515003100"

latitude, longitude, altitude, description = get_info(gwid)
last_seen, latitude_status, longitude_status = get_status(gwid)

if latitude == None and latitude_status != None:
    latitude = latitude_status
if longitude == None and longitude_status != None:
    longitude = longitude_status

print(gwid)
print(description)
print(last_seen)
print(latitude, longitude, altitude)
