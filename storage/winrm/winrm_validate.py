#!/usr/bin/env python

from pypsrp.client import Client
from multiprocessing import Process, Pipe

import sys
import subprocess

ip = sys.argv[1]
certPath = sys.argv[2]
keyPath = sys.argv[3]
certPassword = sys.argv[4]

process = subprocess.Popen(("openssl rsa -in %s -out %s -passin pass:%s -passout pass:\"\"" % (keyPath, certPath+".tmp",certPassword)).split(),stdout=subprocess.PIPE,stderr=subprocess.PIPE)
output, error = process.communicate()

try:
    client = Client(ip, auth="certificate", certificate_key_pem=certPath+".tmp", certificate_pem=certPath,
                    cert_validation=False)
    output, streams, had_errors = client.execute_cmd('ipconfig')
    print("ok")

#    process = subprocess.Popen(("rm "+certPath+".tmp").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
#    output, error = process.communicate()

except:
    print("Connection error")
#    process = subprocess.Popen(("rm "+certPath+".tmp").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
#    output, error = process.communicate()
    exit()
