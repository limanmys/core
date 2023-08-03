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

#UI
wget "https://github.com/limanmys/next/archive/$1.zip" -q
unzip -qq $1.zip 
mkdir -p package/liman/ui
mv next-$1/* package/liman/ui
rm -rf $1.zip next-$1

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
mv package/liman/server/storage/build_tools/rhel/liman-system-worker.ini liman-system-worker.ini
mv package/liman/server/storage/build_tools/rhel/liman-high-availability-syncer.ini liman-high-availability-syncer.ini
rm -rf package/liman/server/storage/build_tools
cd package/liman/ui && npm install && npm run build && cd ../../..

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
Depends: curl, gpg, zip, unzip, nginx, redis, php8.1-redis, php8.1-fpm, php8.1-gd, php8.1-curl, php8.1, php8.1-sqlite3, php8.1-snmp, php8.1-mbstring, php8.1-xml, php8.1-zip, php8.1-posix, libnginx-mod-http-headers-more-filter, libssl1.1 | libssl3, supervisor, postgresql-15, php8.1-pgsql, pgloader, php8.1-bcmath, rsync, dnsutils, php8.1-ldap, php8.1-smbclient, krb5-user, php8.1-ssh2, smbclient, nodejs""" > DEBIAN/control
cat DEBIAN/control
cd ../
dpkg-deb -Zgzip --build package

rm -rf DEBIAN
VERSION=$(cat package/liman/server/storage/VERSION | tr - .)
sed -i s/%VERSION%/$VERSION.$5/g liman.spec
mkdir -p ./package/etc/supervisord.d
cp liman-system-worker.ini ./package/etc/supervisord.d/liman-system-worker.ini
cp liman-high-availability-syncer.ini ./package/etc/supervisord.d/liman-high-availability-syncer.ini
rpmbuild -ba liman.spec --define "_app_dir $(pwd)/package" --define "_rpmdir /tmp" --define "_rpmfilename package.rpm"
rm -rf package
