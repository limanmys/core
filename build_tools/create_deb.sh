# Liman System Manager .deb Package Creation Script
mkdir DEBIAN

touch DEBIAN/postinst
touch DEBIAN/md5sums
touch DEBIAN/control

find . -type f ! -regex '.*.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' -printf '%P ' | xargs md5sum > DEBIAN/md5sums

chmod 775 DEBIAN/preinst
chmod 775 DEBIAN/postinst

echo """Package: Liman
Version: $1
Installed-Size: 29892
Maintainer: Mert CELEN <mcelen@havelsan.com.tr>
Section: admin
Date : $1
Architecture: amd64
Priority: important
Description: Liman System Manager
Depends: zip, unzip, dnsutils, nginx, php7.3-fpm, php7.3-curl, php7.3, php7.3-sqlite3, php7.3-ldap, php7.3-mbstring, php7.3-xml, php7.3-zip, php7.3-ssh2, php7.3-posix, libnginx-mod-http-headers-more-filter, php7.3-smbclient, krb5-user, smbclient, libssl1.1, acl, novnc, supervisor, expect, php-mongodb, php7.3-gd, rsyslog
""" > DEBIAN/control

cd ../

dpkg-deb -Zgzip --build build_tools