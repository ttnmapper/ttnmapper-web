#!/usr/bin/python

import MySQLdb
import MySQLdb.cursors
import sys
import configparser
import os
import json
import time
import pika
# import urllib.request

connection = pika.BlockingConnection(pika.ConnectionParameters('localhost'))
channel = connection.channel()

channel.exchange_declare(exchange='new_packets',
                         exchange_type='fanout',
                         durable=True)

#config = configparser.ConfigParser()
#config.read(os.environ.get('TTNMAPPER_HOME')+"/settings.conf")

db = MySQLdb.connect(host= "10.6.27.53", # config['database_mysql']['host'],      # your host, usually localhost
                     user= "", # config['database_mysql']['username'],  # your username
                     passwd= "", #config['database_mysql']['password'],  # your password
                     db= "", #   config['database_mysql']['database'],  # name of the data base
                     cursorclass=MySQLdb.cursors.SSDictCursor)

cursor = db.cursor()
cursor.execute("SELECT * FROM packets")

count = 0
while True:
    row = cursor.fetchone()
    if not row:
        break
    count+=1
    print("== "+str(row['id'])+" ==")
    # print(row)

    """
    Example packet:
    {
       "app_id":"rak5205node",
       "dev_id":"node1rak5205solar",
       "hardware_serial":"353435317D376A0C",
       "port":2,
       "counter":370,
       "payload_raw":"AYj8fuT5Z7gAAfQ=",
       "payload_fields":{
          "gps_1":{
             "altitude":5,
             "latitude":-22.966,
             "longitude":-43.22
          }
       },
       "metadata":{
          "time":"2019-06-30T23:59:59.573657264Z",
          "frequency":917.4,
          "modulation":"LORA",
          "data_rate":"SF7BW125",
          "coding_rate":"4/5",
          "gateways":[
             {
                "gtw_id":"eui-60c5a8fffe74d3a2",
                "timestamp":4177941027,
                "time":"",
                "channel":3,
                "rssi":-51,
                "snr":9,
                "rf_chain":0,
                "latitude":-22.92926,
                "longitude":-43.25813,
                "altitude":47
             }
          ]
       },
       "experiment":"rock_in_rio-BSUL"
    }

    Test output:
    {
       "dev_id":"tbar-1",
       "counter":0,
       "app_id":"new-mapper",
       "payload_fields":{
          "sats":0.0,
          "hdop":1.4,
          "altitude":45.0,
          "longitude":-0.450922,
          "provider":"hdop",
          "latitude":53.727702,
          "accuracy":0.0
       },
       "hardware_serial":"unknown",
       "port":0,
       "metadata":{
          "data_rate":"SF7BW125",
          "modulation":"LORA",
          "coding_rate":0,
          "bit_rate":0,
          "frequency":868.3,
          "gateways":[
             {
                "rssi":-119.0,
                "gtw_id":"hcc-lora-001",
                "snr":-10.25
             }
          ],
          "time":"2019-12-29T14:28:42Z"
       }
    }
    """

    try:
      packet = {}
      packet['app_id'] = row['appeui']
      packet['dev_id'] = row['nodeaddr']
      packet['hardware_serial'] = "unknown"
      packet['port'] = 0
      packet['counter'] = 0

      metadata = {}
      metadata['time'] = row['time'].strftime("%Y-%m-%dT%H:%M:%SZ")
      if(row['freq'] != None):
        metadata['frequency'] = float(row['freq'])
      metadata['modulation'] = row['modulation']
      metadata['data_rate'] = row['datarate']
      metadata['bit_rate'] = 0
      metadata['coding_rate'] = ""

      gateway = {}
      gateway['gtw_id'] = row['gwaddr']
      if(row['rssi'] != None):
        gateway['rssi'] = float(row['rssi'])
      if(row['snr'] != None):
        gateway['snr'] = float(row['snr'])

      metadata['gateways'] = []
      metadata['gateways'].append(gateway)

      packet['metadata'] = metadata

      payload_fields = {}
      # payload_fields['latitude'] = float(row['lat'])
      # payload_fields['longitude'] = float(row['lon'])
      # payload_fields['altitude'] = float(row['alt'])
      # payload_fields['accuracy'] = float(row['accuracy'])
      # payload_fields['hdop'] = float(row['hdop'])
      # payload_fields['sats'] = float(row['sats'])
      # payload_fields['provider'] = row['provider']

      packet['payload_fields'] = payload_fields

      if(row['lat'] == None): continue
      if(row['lon'] == None): continue
      packet['ttnmapper_latitude'] = float(row['lat'])
      packet['ttnmapper_longitude'] = float(row['lon'])

      if(row['alt'] != None):
        packet['ttnmapper_altitude'] = float(row['alt'])

      if(row['accuracy'] != None):
        packet['ttnmapper_accuracy'] = float(row['accuracy'])
      if(row['sats'] != None):
        packet['ttnmapper_satellites'] = int(row['sats'])
      if(row['hdop'] != None):
        packet['ttnmapper_hdop'] = float(row['hdop'])

      if(row['provider'] != None):
        packet['ttnmapper_provider'] = row['provider']
      packet['ttnmapper_experiment'] = ""
      if(row['user_id'] != None):
        packet['ttnmapper_userid'] = row['user_id']
      if(row['user_agent'] != None):
        packet['ttnmapper_useragent'] = row['user_agent']

      # print(json.dumps(packet))


      channel.basic_publish(exchange='new_packets',
                            routing_key='',
                            body=json.dumps(packet))

    except Exception as ex:
      print(ex)
      print(row)

    time.sleep(0.002)