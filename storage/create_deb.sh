#!/bin/bash

# Parameters
# 1.Sandbox Branch
# 2.Extension Templates Branch
# 3.Go Engine Branch
# 4.Liman Branch
# 5.Build Number
# 6.Commit Message

#Sandbox
wget "https://github.com/limanmys/php-sandbox/archive/$1.zip" -q
unzip -qq $1.zip 
mkdir -p package/liman/sandbox/php
mv php-sandbox-$1/* package/liman/sandbox/php/
rm -rf $1.zip php-sandbox-$1

#Extension Templates
wget "https://github.com/limanmys/extension_templates/archive/$2.zip" -q
unzip -qq $2.zip
mkdir -p package/liman/server/storage/extension_templates
mv extension_templates-$2/* package/liman/server/storage/extension_templates/
rm -rf $2.zip extension_templates-$2

#Render Engine
curl -s https://api.github.com/repos/limanmys/fiber-render-engine/releases/latest \
| grep "browser_download_url.*zip" \
| cut -d : -f 2,3 \
| tr -d \" \
| wget -qi -
unzip liman_render*.zip
mv liman_render package/liman/server/storage/liman_render

#WebSSH
wget "https://github.com/limanmys/webssh/archive/master.zip" -q
unzip -qq master.zip
mkdir -p package/liman/webssh
mv webssh-master/* package/liman/webssh
rm -rf master.zip webssh-master

#Setup variables and version codes.
VERSION=$(cat package/liman/server/storage/VERSION)
echo $5 >package/liman/server/storage/VERSION_CODE
DATE=$(date)

#Install/Update dependencies
composer install --no-dev -d package/liman/server
composer install --no-dev -d package/liman/sandbox/php
rm -rf package/liman/server/node_modules
mv package/liman/server/storage/build_tools/DEBIAN package/
mv package/liman/server/storage/build_tools/rhel/liman.spec liman.spec
mv package/liman/server/storage/build_tools/rhel/liman-cron-mail.ini liman-cron-mail.ini
mv package/liman/server/storage/build_tools/rhel/liman-system-worker.ini liman-system-worker.ini
rm -rf package/liman/server/storage/build_tools

#Build Package
cd package
touch DEBIAN/md5sums
touch DEBIAN/md5sums
touch DEBIAN/control

find . \( -name ".git" -o -name ".gitignore" -o -name ".gitmodules" -o -name ".gitattributes" \) -exec rm -rf -- {} + 1>/dev/null 2>/dev/null || true

mkdir -p liman/hashes
find . -type f ! -regex '.*.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' -printf '%P ' | xargs md5sum > liman/hashes/core.md5 || true
sed -i '/nginx.conf/d' liman/hashes/core.md5
sed -i '/liman/hashes/d' liman/hashes/core.md5
gpg --batch --yes --passphrase $6 --default-key aciklab@havelsan.com.tr --sign liman/hashes/core.md5
rm liman/hashes/core.md5

chmod 775 DEBIAN/preinst
chmod 775 DEBIAN/postinst

echo """Package: liman
Version: $VERSION-$5
Installed-Size: 77892
Maintainer: Dogukan OKSUZ <dogukan@liman.dev>
Section: admin
Date : $DATE
Architecture: amd64
Priority: important
Description: Liman MYS
Depends: curl, gpg, zip, unzip, nginx, redis, php7.3-redis | php-redis, php7.3-fpm, php7.3-gd, php7.3-curl, php7.3, php7.3-sqlite3, php7.3-snmp, php7.3-mbstring, php7.3-xml, php7.3-zip, php7.3-posix, libnginx-mod-http-headers-more-filter, libssl1.1 | libssl3, supervisor, postgresql-13, php7.3-pgsql, pgloader, php7.3-bcmath, rsync, dnsutils, php7.3-ldap, php7.3-smbclient | php-smbclient, krb5-user, php7.3-ssh2 | php-ssh2, smbclient, novnc, python3.7 | python3.8 | python3.9 | python3.10, python3-paramiko, python3-tornado""" > DEBIAN/control
cat DEBIAN/control
cd ../
dpkg-deb -Zgzip --build package

rm -rf DEBIAN
VERSION=$(cat package/liman/server/storage/VERSION | tr - .)
sed -i s/%VERSION%/$VERSION.$5/g liman.spec
mkdir -p ./package/etc/supervisord.d
cp liman-cron-mail.ini ./package/etc/supervisord.d/liman-cron-mail.ini
cp liman-system-worker.ini ./package/etc/supervisord.d/liman-system-worker.ini
rpmbuild -ba liman.spec --define "_app_dir $(pwd)/package" --define "_rpmdir /tmp" --define "_rpmfilename package.rpm"
rm -rf package