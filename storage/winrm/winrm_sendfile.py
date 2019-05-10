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

process = subprocess.Popen(("openssl rsa -in %s -out %s -passin pass:%s -passout pass:\"\"" % (keyPath, "temp.pem",certPassword)).split(),stdout=subprocess.PIPE,stderr=subprocess.PIPE)
poutput, perror = process.communicate()

try:
    client = Client(ip, auth="certificate", certificate_key_pem="./temp.pem", certificate_pem=certPath,
                    cert_validation=False)

    client.copy(source, destination)

    if os.path.exists(source) == False:
        raise Exception

    output2,streams2,had_errors2 = client.execute_ps("Get-Item " + destination)
    if not source in output2:
        raise Exception
except:
    process = subprocess.Popen(("rm temp.pem").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    poutput, perror = process.communicate()
    print("File could not be send")

process = subprocess.Popen(("rm temp.pem").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
poutput, perror = process.communicate()