# Liman System Manager .deb Package Creation Script
mkdir DEBIAN

touch DEBIAN/postinst
touch DEBIAN/md5sums
touch DEBIAN/control

find . -type f ! -regex '.*.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' -printf '%P ' | xargs md5sum > DEBIAN/md5sums
echo """
# TODO fix line above for updates, it will overwrite existing extension permissions.
# sudo chown -R liman:liman /liman/

chmod o+xr /liman/
chmod -R o+xr /liman/sandbox
chmod o+x /liman/extensions

cd /liman/server
composer install

# TODO Administra
# php /liman/server/artisan administrator

mv /liman/server/nginx.conf /etc/nginx/sites-available/liman.conf
mv /liman/webssh/liman-webssh.service /etc/systemd/system/liman-webssh.service

systemctl enable liman-webssh
systemctl start webssh
systemctl restart nginx
systemctl restart php7.3-fpm
""" > DEBIAN/postinst
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