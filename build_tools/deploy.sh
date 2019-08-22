#!/bin/bash
curl -X POST -F "file=@/builds/liman/liman/liman-$1.deb" http://depo.lab:8080/api/files/liman
curl -X POST -H "Content-Type: application/json" -d '{"Name":"$1"}' http://depo.lab:8080/api/repos/liman/snapshots
curl -X POST -H "Content-Type: application/json" -d '{"SourceKind":"snapshot", "Sources": [{"Name": "$1","Component":"main"}], "Architectures": ["amd64"], "Distribution":"ondokuz", "GpgKey":"B29FB116187E58FC1FD88AB45B88DFDF4EC23DB3"}' http://depo.lab:8080/api/publish/:.