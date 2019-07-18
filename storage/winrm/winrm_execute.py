#!/usr/bin/env python

from pypsrp.client import Client

import sys
import subprocess

ip = sys.argv[1]
certPath = sys.argv[2]
keyPath = sys.argv[3]
certPassword = sys.argv[4]
command = sys.argv[5]

process = subprocess.Popen(("openssl rsa -in %s -out %s -passin pass:%s -passout pass:\"\"" % (keyPath, "/tmp/temp.pem",certPassword)).split(),stdout=subprocess.PIPE,stderr=subprocess.PIPE)
output, error = process.communicate()

try:
    client = Client(ip, auth="certificate", certificate_key_pem="/tmp/temp.pem", certificate_pem=certPath,
                cert_validation=False)
    output, streams, had_errors = client.execute_ps(command)
    if had_errors:
        raise Exception
    print(output)
    # process = subprocess.Popen(("rm /tmp/temp.pem").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    # output, error = process.communicate()

except:
    print("Invalid command")
    # process = subprocess.Popen(("rm /tmp/temp.pem").split(), stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    # output, error = process.communicate()
    exit()
