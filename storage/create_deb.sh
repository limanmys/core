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
#curl -s https://api.github.com/repos/limanmys/next/releases/latest \
#| grep "browser_download_url.*zip" \
#| cut -d : -f 2,3 \
#| tr -d \" \
#| wget -qi -
wget https://github.com/limanmys/next/releases/download/release.master.150/ui-master-150.zip
unzip ui-master-150.zip -d package/liman/ui
rm ui-master-150.zip

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
rm -rf package/liman/server/docs
mv package/liman/server/storage/build_tools/DEBIAN package/
mv package/liman/server/storage/build_tools/rhel/liman.spec liman.spec
mv package/liman/server/storage/build_tools/rhel/liman-system-worker.ini liman-system-worker.ini
mv package/liman/server/storage/build_tools/rhel/liman-high-availability-syncer.ini liman-high-availability-syncer.ini
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
Depends: curl, gpg, zip, unzip, nginx, redis, php8.4-redis, php8.4-fpm, php8.4-gd, php8.4-curl, php8.4, php8.4-snmp, php8.4-mbstring, php8.4-xml, php8.4-zip, php8.4-posix, libnginx-mod-http-headers-more-filter, libssl1.1 | libssl3, supervisor, postgresql-15, php8.4-pgsql, php8.4-bcmath, dnsutils, php8.4-ldap, krb5-user, php8.4-ssh2, nodejs""" > DEBIAN/control
cat DEBIAN/control
cd ../
dpkg-deb -Zgzip --build package

rm -rf DEBIAN
VERSION=$(cat package/liman/server/storage/VERSION | tr - .)
sed -i s/%VERSION%/$VERSION.$5/g liman.spec
mkdir -p ./package/etc/supervisord.d
cp liman-system-worker.ini ./package/etc/supervisord.d/liman-system-worker.ini
cp liman-high-availability-syncer.ini ./package/etc/supervisord.d/liman-high-availability-syncer.ini
rpmbuild -ba liman.spec --define "_app_dir $PWD/package" --define "_rpmdir /tmp" --define "_rpmfilename package.rpm"
rm -rf package
