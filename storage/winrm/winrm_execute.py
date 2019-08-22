#!/usr/bin/env python

from pypsrp.client import Client

import sys
import os

ip = sys.argv[1]
certPath = sys.argv[2]
keyPath = sys.argv[3]
certPassword = sys.argv[4]
command = sys.argv[5]

os.system("openssl rsa -in %s -out %s -passin pass:%s -passout pass:\"\"" % (keyPath, "/tmp/temp.pem",certPassword))

try:
    client = Client(ip, auth="certificate", certificate_key_pem="/tmp/temp.pem", certificate_pem=certPath,
                cert_validation=False)
    client.execute_ps("$PSDefaultParameterValues['*:Encoding'] = 'utf8'")
    output, streams, had_errors = client.execute_ps(command)
    if had_errors:
        raise Exception
    print(output)

except:
    print("Invalid command")
    exit()