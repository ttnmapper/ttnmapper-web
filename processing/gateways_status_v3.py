#!/usr/bin/python3

"""
v3_gateway_list = http://lns.ttnmapper.org/api/v3/gateways
v3_gateway_status = http://lns.ttnmapper.org/api/v3/gs/gateways/<gateway_id>/connection/stats
v3_api_key = NNSXS.XR2QSAA5BE6KX2JSP23K34N26OGGI65PGDNWEOI.PKCOVCU3MAOYTXRFMVLTDRS5FQRU2IIAWBKSUE4BLDIHTRMC6U2A
"""
import requests
import os
import json
import configparser
import dateutil.parser
import MySQLdb
from geopy.distance import great_circle

ignored_euis = ["0102030405060708", "0000000000001DEE", "000000000000000E", "000000000000FFFE", "3135323512003300"]


config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                    )

cur = db.cursor()



def main():


    headers = {'content-type': 'application/json', 'Authorization': "Bearer "+config['network']['v3_api_key']}

    """
    http://lns.ttnmapper.org/api/v3/gateways
    Authorization: Bearer NNSXS.XR2QSAA5BE6KX2JSP23K34N26OGGI65PGDNWEOI.PKCOVCU3MAOYTXRFMVLTDRS5FQRU2IIAWBKSUE4BLDIHTRMC6U2A

    {
        "gateways": [
            {
                "ids": {
                    "gateway_id": "pisupply-shield",
                    "eui": "B827EBFFFED88375"
                },
                "created_at": "2020-02-15T15:39:04.153Z",
                "updated_at": "2020-02-28T13:38:35.617Z",
                "version_ids": {}
            }
        ]
    }
    """
    url = config['network']['v3_gateway_list']
    r = requests.get(url, headers=headers)
    if r.status_code != 200:
        return

    gateway_ids = r.json()['gateways']


    """
    http://lns.ttnmapper.org/api/v3/gs/gateways/pisupply-shield/connection/stats
    Authorization: Bearer NNSXS.XR2QSAA5BE6KX2JSP23K34N26OGGI65PGDNWEOI.PKCOVCU3MAOYTXRFMVLTDRS5FQRU2IIAWBKSUE4BLDIHTRMC6U2A

    {
        "connected_at": "2020-02-28T06:36:46.347339472Z",
        "protocol": "udp",
        "last_status_received_at": "2020-02-28T13:55:23.695058405Z",
        "last_status": {
            "time": "2020-02-28T13:55:23Z",
            "boot_time": "0001-01-01T00:00:00Z",
            "versions": {
                "ttn-lw-gateway-server": "3.6.0"
            },
            "antenna_locations": [
                {
                    "latitude": -33.93597,
                    "longitude": 18.87081
                }
            ],
            "ip": [
                "169.0.114.36"
            ],
            "metrics": {
                "ackr": 0,
                "rxfw": 0,
                "rxin": 0,
                "rxok": 0,
                "txin": 0,
                "txok": 0
            }
        },
        "last_uplink_received_at": "2020-02-28T13:52:20.420943415Z",
        "uplink_count": "248",
        "last_downlink_received_at": "2020-02-28T07:04:17.475094386Z",
        "downlink_count": "110",
        "round_trip_times": {
            "min": "0.195202702s",
            "max": "0.247702865s",
            "median": "0.197161336s",
            "count": 32
        }
    }
    """
    url = config['network']['v3_gateway_status']
    for gateway in gateway_ids:
        gateway_id = gateway['ids']['gateway_id']
        r = requests.get(url.replace('<gateway_id>', gateway_id), headers=headers)
        status = r.json()
        # print(status)

        gtw_id = ""
        if('eui' in gateway['ids']):
            gtw_id = gateway['ids']['eui']
        else:
            gtw_id = gateway['ids']['gateway_id']


        last_heard = max(status['last_status_received_at'], status['last_uplink_received_at'])

        try:
            latitude = status['last_status']['antenna_locations'][0]['latitude']
            longitude = status['last_status']['antenna_locations'][0]['longitude']
        except:
            latitude = 0
            longitude = 0

        try:
            altitude = status['last_status']['antenna_locations'][0]['altitude']
        except:
            altitude = 0
        
        process_gateway(gtw_id, last_heard, latitude, longitude, altitude)






def process_gateway(gwaddr, last_heard, latitude, longitude, altitude):

  if(gwaddr.startswith("eui-")):
    gwaddr = str(gwaddr[4:]).upper()

  print (gwaddr+"\t", end=' ')

  update = False
  exists = False
  gwlatdb=0
  gwlondb=0
  
  # 2019-09-09T14:38:03Z
  lastSeen = dateutil.parser.parse(last_heard)

  cur.execute("SELECT lat,lon FROM gateway_updates WHERE gwaddr=%s ORDER BY datetime DESC LIMIT 1", (gwaddr,))
  
  if(cur.rowcount <1):
    update = True
    print ("No entry yet.", end=' ')
  for row in cur.fetchall():
    exists = True
    gwlatdb = float(row[0])
    gwlondb = float(row[1])

  # in case a gateway reports its location wrong and we know it and we have an entry for it in our forcing table, force the correct coordinates
  cur.execute("SELECT lat_force,lon_force FROM gateway_force WHERE `gwaddr`=%s AND (`lat_orig`=%s OR `lat_orig` IS NULL) AND (`lon_orig`=%s OR `lon_orig` IS NULL)", 
    (gwaddr, latitude, longitude) )
  for row in cur.fetchall():
    print ("!!Exists in force table - FORCING COORDINATES!!", end=' ')
    latitude = float(row[0])
    longitude = float(row[1])
    
  if(abs(latitude)>90 or abs(longitude)>180):
    print ("Invalid location: "+str(latitude)+","+str(longitude), end=' ')
    update = False
    #return

  distance = great_circle((latitude, longitude),(gwlatdb, gwlondb)).meters
  if(distance>100):
    print ("Distance is: "+str(round(distance))+"m.", end=' ')
    update = True
  else:
    #print ("Location did not change: "+str(round(distance)), end=' ')
    pass

  if(gwaddr in ignored_euis):
    print ("Ignored EUI.", end=' ')
    update = False

    
  if(latitude==52.0 and longitude==6.0):
    print ("Default SCG location, ignoring.", end=' ')
    update = False

  if(round(latitude,4)==10.0 and round(longitude,4)==20.0):
    print ("Default Lorrier LR2 location, ignoring.", end=' ')
    update = False

  if(latitude==50.008724 and longitude==36.215805):
    print ("Ukrainian hack.", end=' ')
    update = False

  if(latitude==0 and longitude==0):
    print ("Zero location, ignoring.", end=' ')
    update = False
  if(abs(latitude)<1 and abs(longitude)<1):
    print ("Filtering NULL island.", end=' ')
    update = False


  # sanitise altitude
  if(altitude>99999.9 or altitude<-99999.9):
    print("Altitude out of range, clamping to 0.", end=' ')
    altitude = 0

  if update == True:
    print ("Adding new entry", end=' ')
    cur.execute(
      "INSERT INTO gateway_updates (gwaddr, datetime, lat, lon, alt, last_update) "+
      "VALUES (%s,%s,%s,%s,%s, %s)",
      (gwaddr, lastSeen, latitude, longitude, altitude, lastSeen)
    )
    db.commit()

  elif exists == True:
    print ("Updating last seen.", end=' ')

    # If it exist it is likely also in the aggregate table, so try and update the last heard
    try:
      cur.execute(
        'UPDATE `gateways_aggregated` SET last_heard=%s WHERE gwaddr=%s', 
        (lastSeen, gwaddr)
      )
    except:
      print("Doesn't exist in aggregate table.", end=' ')
      pass

    db.commit()
    print ("Done.", end=' ')
  else:
    print("Not adding or updating.", end=' ')

  if update == True and exists == False:
    print("New gateway "+gwaddr)
    try:
      # Announce new gateway to slack and twitter
      # doReverseGeocoding(gwaddr, datetime, gwlatjs, gwlonjs)
      pass
    except:
      print("Error while posting to slack or twitter")
      pass

  print()




try:
   os.environ["TTNMAPPER_HOME"]
except KeyError: 
   print ("Please set the environment variable TTNMAPPER_HOME")
   sys.exit(1)



lockfile = os.environ['TTNMAPPER_HOME']+"/lockfiles/gateway-status-v3.lock"
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


main()
