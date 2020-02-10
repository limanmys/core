#!/bin/bash
curl -X POST -F "file=@/builds/liman/liman/liman-$1.deb" http://$2:8080/api/files/$3
curl -X POST http://$2:8080/api/repos/$3/file/liman
curl -X POST -H "Content-Type: application/json" -d '{"Name":"'$1'"}' http://$2:8080/api/repos/$3/snapshots
curl -X PUT -H 'Content-Type: application/json' --data '{"Snapshots": [{"Component": "main", "Name": "'$1'"}],"GpgKey":"B29FB116187E58FC1FD88AB45B88DFDF4EC23DB3"}' http://$2:8080/api/publish/:./ondokuz