#!/usr/bin/env python

from pypsrp.client import Client

import sys
import subprocess
import os

ip = sys.argv[1]
certPath = sys.argv[2]
keyPath = sys.argv[3]
certPassword = sys.argv[4]
source = sys.argv[5]
destination = sys.argv[6]

os.system("openssl rsa -in %s -out %s -passin pass:%s -passout pass:\"\"" % (keyPath, "/tmp/temp.pem",certPassword))

client = Client(ip, auth="certificate", certificate_key_pem="/tmp/temp.pem", certificate_pem=certPath,
            cert_validation=False)

try:
    client.fetch(source, destination)
    if os.path.exists(destination) == False:
        raise Exception
except:
    process = subprocess.Popen(("rm /tmp/temp.pem").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    output, error = process.communicate()
    print("File could not be get")