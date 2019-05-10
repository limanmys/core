#!/usr/bin/env python

# Modules
from re import split
from pypsrp.client import Client
import sys
import os

# Variables
ip = sys.argv[2]
userName = sys.argv[3]
userPassword = sys.argv[4]
certPath = sys.argv[5]
keyPath = sys.argv[6]
certPassword = sys.argv[7]
subject = split("\.",os.path.split(certPath)[-1])[0]

def checkWritePermission(dir):
    if not os.access(dir, os.W_OK):
        sys.exit('Unable to write to file: ' + dir)

def checkCerts(file):
    if os.path.exists(file):
        sys.exit('File is already exist: ' + file)

def before():
    ### Certificate password can not be empty
    # if certPassword == "":
    #     sys.exit('Please enter a certificate password.')

    ### Check Directories
    # checkCerts(keyPath)
    # checkCerts(certPath)

    # ### Check Write Permissions of File
    # checkWritePermission(keyPath)
    # checkWritePermission(certPath)

    ### Check Connection
    client = Client(ip, username=userName, password=userPassword, cert_validation=False)
    try:
        output,streams,had_errors = client.execute_ps('ipconfig')
    except:
        print("Connection error")
        exit()

    ### Check is certificate exist
    # output, streams, had_errors = client.execute_ps("Get-ChildItem -Recurse -Path WSMan:\localhost\ClientCertificate | Where-Object { $_.Value -eq \"administrator\"} ")
    output, streams, had_errors = client.execute_ps("foreach ($file in Get-Item -Path WSMan:\\localhost\\ClientCertificate\\*) { Where-Object { $file.Keys | Select-Item \"%s\"} " % userName)
    if (output != ""):
        print("User already exist in Client Certificates.")
        exit()

    ### Check is user in trusted root cert authority
    output1, streams, had_errors = client.execute_ps(
       "Get-ChildItem -Path cert:\LocalMachine\\root | Where-Object { $_.Subject -eq \"CN=%s\"}" % subject)
    if (output1 != ""):
       print("User already exist in Trusted Root Certification Authorities.")
       exit()

    ### Check is user in trusted people
    output2, streams, had_errors = client.execute_ps(
       "Get-ChildItem -Path cert:\LocalMachine\\trustedPeople | Where-Object { $_.Subject -eq \"CN=%s\"}" % subject)
    if (output2 != ""):
       print("User already exist in Trusted People.")
       exit()

    print("ok")

def run():
    before()
    ### Connection
    client = Client(ip, username=userName, password=userPassword, cert_validation=False)

    ### Create Cert

    output, streams, had_errors = client.execute_ps("New-Item -Path C:\\temp -ItemType Directory")
    try:
        output, streams, had_errors = client.execute_ps("""
            $username = \"%s\"
            $password = \"%s\"
            $output_path = \"C:\\temp"
    
            $cert = New-SelfSignedCertificate -Type Custom `
                -Subject "CN=%s" `
                -TextExtension @("2.5.29.37={text}1.3.6.1.5.5.7.3.2","2.5.29.17={text}upn=%s@localhost") `
                -KeyUsage DigitalSignature,KeyEncipherment `
                -KeyAlgorithm RSA `
                -KeyLength 2048 
    
            $pem_output = @()
            $pem_output += "-----BEGIN CERTIFICATE-----"
            $pem_output += [System.Convert]::ToBase64String($cert.RawData) -replace \".{64}\", \"$&`n\"
            $pem_output += "-----END CERTIFICATE-----"
    
            [System.IO.File]::WriteAllLines(\"$output_path\\cert.pem\", $pem_output)
            [System.IO.File]::WriteAllBytes(\"$output_path\\cert.pfx\", $cert.Export('Pfx'))
    
        """ % (userName, userPassword, subject, userName))
        if had_errors:
            raise Exception()
    except:
        print("Pfx file could not be created")
        exit()

    ### Get pfx files
    client.fetch("C:\\temp\\cert.pfx", "./cert.pfx")
    if os.path.exists("./cert.pfx") == False:
        print("Pfx file could not be get")
        exit()

    ### Add user to trusted root and people
    try:
        output, streams, had_errors = client.execute_ps(
            "Import-PfxCertificate -FilePath C:\\temp\\cert.pfx -CertStoreLocation Cert:\LocalMachine\Root")
        if had_errors:
            raise Exception()
    except:
        print("User couldn't be added to Trusted Root Certification Authorities. ")
        exit()
    try:
        output, streams, had_errors = client.execute_ps(
            "Import-PfxCertificate -FilePath C:\\temp\\cert.pfx -CertStoreLocation Cert:\LocalMachine\TrustedPeople")
        if had_errors:
            raise Exception()
    except:
        print("User couldn't be added to Trusted People.")
        exit()

    ### Add Certificate
    try:
        output, streams, had_errors = client.execute_ps("""
            $username = \"%s\"
            $subject = \"%s\"
            $password = ConvertTo-SecureString -String \"%s\" -AsPlainText -Force
            $credential = New-Object -TypeName System.Management.Automation.PSCredential -ArgumentList $username, $password
            $thumbprint = (Get-ChildItem -Path cert:\LocalMachine\\root | Where-Object { $_.Subject -eq "CN=$subject" }).Thumbprint
            
            New-Item -Path WSMan:\localhost\ClientCertificate  `
             -Subject "$username@localhost" `
             -URI * `
             -Issuer $thumbprint `
             -Credential $credential `
             -Force
             
             Set-Item -Path WSMan:\localhost\Service\Auth\Certificate -Value $true
        """ % (userName, subject,userPassword))
        if had_errors:
            raise Exception()
    except:
        # print(output, streams,had_errors)
        print("Certificate can not be added.")
        exit()

    ### Create ssl certs
    os.popen("openssl pkcs12 -in cert.pfx -clcerts -nokeys -out %s -passin pass:\"\"" % certPath)
    os.popen("openssl pkcs12 -in cert.pfx -clcerts -out %s -passin pass:\"\" -passout pass:%s" % (keyPath,certPassword))

    ### Clean unnecessary files
    try:
        output, streams, had_errors = client.execute_ps("Remove-Item -Path C:\\temp -Recurse")
        if had_errors:
            raise Exception()
        os.popen("rm cert.pfx")
    except:
        print("C:\\temp file could not be removed")
        exit()

if __name__ == "__main__":
    globals()[sys.argv[1]]()