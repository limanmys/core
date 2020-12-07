#!/bin/bash

DEBIAN_FRONTEND=noninteractive sudo apt install jq -y 1>/dev/null 2>/dev/null
VERSION=$(cat package/liman/server/storage/VERSION)
echo $GITHUB_RUN_NUMBER >package/liman/server/storage/VERSION_CODE
COMMIT="${GITHUB_REF#refs/heads/} : "
COMMIT+=$(git --git-dir=package/liman/server/.git log -1 --pretty=%B)
COMMIT=$(echo $COMMIT | jq -SrR @uri)
DATE=$(date)
composer install --no-dev -d package/liman/server
git --git-dir=package/liman/server/.git log -30 --pretty=format:"%s%x09%ad" >package/liman/server/storage/changelog
rm -rf package/liman/server/.git package/liman/sandbox/php/.git
rm -rf package/liman/server/storage/extension_templates/.git
rm -rf package/liman/webssh/.git
rm -rf package/liman/server/node_modules
mv package/liman/server/storage/build_tools/DEBIAN package/
mv render_engine/liman_render package/liman/server/storage/liman_render
rm -rf package/liman/server/storage/build_tools
cd package
touch DEBIAN/md5sums
touch DEBIAN/md5sums
touch DEBIAN/control

find . -type f ! -regex '.*.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' -printf '%P ' | xargs md5sum 1>/dev/null 2>/dev/null || true
find . \( -name ".git" -o -name ".gitignore" -o -name ".gitmodules" -o -name ".gitattributes" \) -exec rm -rf -- {} + 1>/dev/null 2>/dev/null || true

chmod 775 DEBIAN/preinst
chmod 775 DEBIAN/postinst

echo """Package: liman
    Version: $VERSION-$GITHUB_RUN_NUMBER
    Installed-Size: 77892
    Maintainer: Mert CELEN <mcelen@havelsan.com.tr>
    Section: admin
    Date : $DATE
    Architecture: amd64
    Priority: important
    Description: Liman MYS
    Depends: curl, gpg, zip, unzip, nginx, redis, php-redis, php7.3-fpm, php7.3-curl, php7.3, php7.3-sqlite3, php7.3-snmp, php7.3-mbstring, php7.3-xml, php7.3-zip, php7.3-posix, libnginx-mod-http-headers-more-filter, libssl1.1, supervisor, postgresql-13, php7.3-pgsql, pgloader, php7.3-bcmath, rsync, dnsutils, php7.3-ldap, php-smbclient, krb5-user, php-ssh2, smbclient, novnc, python3.7, python3-jinja2, python3-requests, python3-crypto, python3-paramiko, python3-tornado
""" >DEBIAN/control
cat DEBIAN/control
cd ../
dpkg-deb -Zgzip --build package
