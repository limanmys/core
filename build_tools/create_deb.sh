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
Maintainer: Mert ÇELEN <mcelen@havelsan.com.tr>
Section: admin
Architecture: amd64
Priority: important
Description: Liman
 Liman System Manager""" > DEBIAN/control

cd ../

dpkg-deb --build build_tools