import os
import subprocess
import sys
from subprocess import check_output
from datetime import datetime, timedelta


def getGatewayLastSeenDateTimeFromEui(gatewayId):
    """
    Returns the last seen datetime (UTC Time) of a gateway acquired by ttnctl as datetime object, or 'None' if gateway not available.

    ttnctl must be callable from the shell. User must be logged in.
    Expects one status output line (if gateway available) in the format: Last seen: 2020-05-24 16:54:00.04129753 +0200 CEST

    Parameters:
    argument1 (string): Takes Gateway ID as string

    Returns:
    datetime: Last seen datetime in UTC time or None

    """
    statusoutput = ""
    datetimeObj = None
    try:
        statusoutput = check_output('ttnctl gateways status ' + gatewayId, shell=True)
    except Exception:
        pass
    #print(statusoutput) # for debugging or curiosity
    indexLastSeen = statusoutput.find("Last seen: ")
    if (-1 != indexLastSeen):
        dateStr = statusoutput[indexLastSeen+11:statusoutput.find("\n",indexLastSeen)]
        datetimeObj = datetime(int(dateStr[0:4]), int(dateStr[5:7]), int(dateStr[8:10]), int(dateStr[11:13]), int(dateStr[14:16]), int(dateStr[17:19]))
        indexOfTimeZoneDiffMinus = dateStr.find("-", 20)
        indexOfTimeZoneDiffPlus = dateStr.find("+", 20)
        if (-1 != indexOfTimeZoneDiffMinus) or (-1 != indexOfTimeZoneDiffPlus):
            indexDiff = indexOfTimeZoneDiffMinus + indexOfTimeZoneDiffPlus + 2
            offsetToZuluTime = timedelta(hours=int(dateStr[indexDiff:indexDiff+2]), minutes=int(dateStr[indexDiff+2:indexDiff+4]))
            if (-1 != indexOfTimeZoneDiffMinus):
                datetimeObj = datetimeObj + offsetToZuluTime
            else:
                datetimeObj = datetimeObj - offsetToZuluTime
    return datetimeObj

def get_info(gwid):
    script_path = os.path.dirname(sys.argv[0])

    subprocess_result = ""
    try:
        subprocess_result = subprocess.check_output([script_path + "/ttnctl", "gateway", "info", gwid])
        # print(subprocess_result.stdout.split(b'\n'))
    except KeyboardInterrupt:
        sys.exit()
    except:
        return None, None, None, None

    description = None

    location = None
    latitude = None
    longitude = None
    altitude = None

    for line in subprocess_result.split(b'\n'):
      line = line.decode().strip()
      if line.startswith("Description: "):
        description = line[line.index(": ") + 2:]
      if line.startswith("Location: "):
        location = line[line.index(": ") + 2:]

    try:
        location = location.split(";")[0]
        location = location.strip("(")
        location = location.strip(")")
        location = location.split(",")

        latitude = float(location[0])
        longitude = float(location[1])
        altitude = float(location[2])
    except:
        pass

    return latitude, longitude, altitude, description

def get_status(gwid):
    script_path = os.path.dirname(sys.argv[0])
    
    subprocess_result = ""
    try:
        subprocess_result = subprocess.check_output([script_path + "/ttnctl", "gateway", "status", gwid])
        # print(subprocess_result.stdout.split(b'\n'))
    except KeyboardInterrupt:
        sys.exit()
    except:
        return None, None, None

    last_seen = None

    location = None
    latitude = None
    longitude = None
    altitude = None

    for line in subprocess_result.split(b'\n'):
        line = line.decode().strip()
        if line.startswith("Last seen"):
          last_seen = line[line.index(": ") + 2:]
        if line.startswith("Location: "):
          location = line[line.index(": ") + 2:]

    # print(last_seen)
    last_seen_time = datetime.strptime(last_seen.split(".")[0], "%Y-%m-%d %H:%M:%S")
    # print(last_seen_time)

    try:
        location = location.split(";")[0]
        location = location.strip("(")
        location = location.strip(")")
        location = location.split(",")

        latitude = float(location[0])
        longitude = float(location[1])
        altitude = float(location[2])
    except:
        pass

    return last_seen_time, latitude, longitude