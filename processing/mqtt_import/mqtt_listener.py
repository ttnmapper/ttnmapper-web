#!/usr/bin/python
from __future__ import print_function
import argparse
import paho.mqtt.client as mqtt
import json
import MySQLdb, MySQLdb.cursors
import sys, os
import configparser

#parsers
from parsers import sodaq_tracker_2
from parsers import jpm_ascii
from parsers import vannut
from parsers import joris_binary
from parsers import ttnmapper_ascii
from parsers import ursm
from parsers import adeunis_demo
from parsers import wireless_things

global mqtt_topic
global payload_format
global args

config = configparser.ConfigParser()
config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host=  config['database_mysql']['host'],      # your host, usually localhost
                     user=  config['database_mysql']['username'],  # your username
                     passwd=config['database_mysql']['password'],  # your password
                     db=    config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.DictCursor)
                     
cur = db.cursor()


# The callback for when the client receives a CONNACK response from the server.
def on_connect(client, userdata, rc):
  global mqtt_topic
  print("Connected with result code "+str(rc))
  # Subscribing in on_connect() means that if we lose the connection and
  # reconnect then subscriptions will be renewed.
  client.subscribe(mqtt_topic)

def on_subscribe(client, userdata, mid, granted_qos):
  print("Subscribed "+str(mid)+" "+str(granted_qos))

# The callback for when a PUBLISH message is received from the server.
def on_message(client, userdata, msg):
  """
  {
  "payload":"h9S4V4L6i/YzHwWd4wIVAAAAxgUD",
  "port":1,
  "counter":429,
  "dev_eui":"00000000B7D205D0",
  "metadata":
    [
      {
        "frequency":867.1,
        "datarate":"SF11BW125",
        "codingrate":"4/5",
        "gateway_timestamp":2591030748,
        "gateway_time":"2016-08-20T22:07:04.288103Z",
        "channel":0,
        "server_time":"2016-08-20T22:07:04.312535034Z",
        "rssi":-120,
        "lsnr":-15.8,
        "rfchain":0,
        "crc":1,
        "modulation":"LORA",
        "gateway_eui":"0000024B08060112",
        "altitude":25,
        "longitude":4.88663,
        "latitude":52.37368
      }
    ]
  }
  """
  node_addr = str(msg.topic.split("/")[2])
  appeui = str(msg.topic.split("/")[0])

  data = json.loads(msg.payload)
  print(data["metadata"][0]["server_time"] + ", " + data["payload"])
  
  if(not "payload" in data):
    print ("No payload in MQTT message")
    return

  values = parse_packet(data["payload"])
  if(not values):
    return

  values = sanitize_data(values)
  if(not values):
    return

  for gateway in data["metadata"]:
    values["rssi"] = gateway["rssi"]
    values["snr"] = gateway["lsnr"]
    values["gwaddr"] = str(gateway["gateway_eui"])
    values["nodeaddr"] = node_addr
    values["appeui"] = appeui
    values["server_time"] = gateway["server_time"]
    values["datarate"] = gateway["datarate"]
    values["freq"] = gateway["frequency"]
    values["topic"] = msg.topic

    add_to_db(values)




#parse the packet according to the format specified in the database
def parse_packet(payload):
  global payload_format

  values = {}

  if payload_format == sodaq_tracker_2.format():
    print ("this is a sodaq tracker 2")
    values = sodaq_tracker_2.parse(payload)
  elif payload_format == jpm_ascii.format():
    print ("this is a jpm_ascii node")
    values = jpm_ascii.parse(payload)
  elif payload_format == vannut.format():
    print ("this is a vannut node")
    values = vannut.parse(payload)
  elif payload_format == joris_binary.format():
    print ("this is a joris binary node")
    values = joris_binary.parse(payload)
  elif payload_format == ttnmapper_ascii.format():
    print ("this is a ttn mapper ascii node")
    values = ttnmapper_ascii.parse(payload)
  elif payload_format == ursm.format():
    print ("this is a ursm node")
    values = ursm.parse(payload)
  elif payload_format == adeunis_demo.format():
    print ("this is a adeunis_demo node")
    values = adeunis_demo.parse(payload)
  elif payload_format == wireless_things.format():
    print ("this is a wireless_things node")
    values = wireless_things.parse(payload)
    
  else:
    print ("unknown format payload: "+payload_format)

  #if the parsing failed, we get a False back, not a tuple
  if(values==False):
    print ("Can not parse this packet")
    return False

  return values

def sanitize_data(values):
  #Less than 4 satellites is not accurate enough
  if("sats" in values):
    if(values["sats"]<4):
      return False

  # altitude clamp to ground if unknown or unknown negative
  if(not "alt" in values):
    values["alt"] = 0

  if(values["alt"] == 0xFFFF):
    values["alt"] = 0

  #lat
  values["lat"] = round(values["lat"], 6)
  if(values["lat"] > 90 or values["lat"] < -90 or values["lat"] == 0):
    return False

  #bounding box around 0,0 point for incorrectly parsed coordinates
  if(values["lat"]<1 and values["lat"]>-1):
    return False

  if(values["lon"]<1 and values["lon"]>-1):
    return False

  #lon
  values["lon"] = round(values["lon"], 6)
  if(values["lon"] > 180 or values["lon"] < -180 or values["lon"] == 0):
    return False

  #accuracy
  if(not "acc" in values):
    values["acc"] = None

  return values


#add the values in the tuple to the DB, after some sanitization
def add_to_db(values):
  global args

  values["provider"] = args.provider

  sql = "SELECT * FROM packets WHERE lat=%(lat)s AND lon=%(lon)s AND rssi=%(rssi)s AND snr=%(snr)s AND gwaddr=%(gwaddr)s AND nodeaddr=%(nodeaddr)s"
  cur.execute(sql, values)
  if(cur.rowcount<1):
    print ("Does not exist in DB")
    sql = 'INSERT INTO packets (time, nodeaddr, appeui, gwaddr, datarate, snr, rssi, freq, lat, lon, alt, accuracy, provider, mqtt_topic, user_agent) '
    sql += 'VALUES (%(server_time)s, %(nodeaddr)s, %(appeui)s, %(gwaddr)s, %(datarate)s, %(snr)s, %(rssi)s, %(freq)s, %(lat)s, %(lon)s, %(alt)s, %(acc)s, %(provider)s, %(topic)s, "mqtt import")'
    cur.execute(sql, values)

    cur.execute('UPDATE `gateway_updates` SET `last_update`=NOW() WHERE gwaddr="'+values["gwaddr"]+'"')

    db.commit()
  else:
    print ("Exists")



if __name__ == "__main__":
  #TODO: add lock to prevent multiple runs

  parser = argparse.ArgumentParser()
  parser.add_argument("--appeui", help="Application EUI")
  parser.add_argument("--accesskey", help="Access Key")
  parser.add_argument("--devaddr", help="Device Address")
  parser.add_argument("--format", help="Device Address")
  parser.add_argument("--provider", help="Provider name")
  args = parser.parse_args()

  if not args.appeui:
      print ("AppEUI not given")
      sys.exit()
  if not args.accesskey:
      print ("Access Key not given")
      sys.exit()
  if not args.devaddr:
      print ("Device address not given")
      sys.exit()
  if not args.format:
      print ("Format not given")
      sys.exit()
  if not args.format:
      print ("Provider not given")
      sys.exit()

  #global vars
  mqtt_topic = args.appeui + "/devices/" + args.devaddr + "/up"
  payload_format = args.format

  #lockfile name
  dir_path = os.path.dirname(os.path.realpath(__file__))
  lockfile = dir_path+"/lockfiles/"+args.appeui + "_" + args.devaddr + "_" + args.format  + ".lock"

  #check if we are already running
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




  client = mqtt.Client()
  client.on_connect = on_connect
  client.on_message = on_message
  client.on_subscribe = on_subscribe
  client.username_pw_set(args.appeui, args.accesskey)
  client.connect("staging.thethingsnetwork.org", 1883, 60)

  # Blocking call that processes network traffic, dispatches callbacks and
  # handles reconnecting.
  # Other loop*() functions are available that give a threaded interface and a
  # manual interface.
  client.loop_forever()