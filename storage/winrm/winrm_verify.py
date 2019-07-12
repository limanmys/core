#!/usr/bin/env python

from pypsrp.client import Client
import sys


# Variables
ip = sys.argv[1]
userName = sys.argv[2]
userPassword = sys.argv[3]

if __name__ == "__main__":
    client = Client(ip, username=userName, password=userPassword, cert_validation=False)
    output, streams, had_errors = client.execute_ps("hostname")
    if output:
        print("OK")
