# USAGE:
# bash create_liman_deb.sh current/liman/path/ packagename version
# örn1: bash create_liman_deb.sh 0.40a190515
cd build_tools

mkdir DEBIAN

touch DEBIAN/postinst
touch DEBIAN/md5sums
touch DEBIAN/control

find . -type f ! -regex '.*.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' -printf '%P ' | xargs md5sum > DEBIAN/md5sums
echo """
sudo chown -R liman:liman /liman/

sudo chmod o+xr /liman/
sudo chmod -R o+xr /liman/sandbox
sudo chmod o+x /liman/extensions

cd /liman/server
sudo composer install
sudo php /liman/server/artisan key:generate
sudo php /liman/server/artisan administrator | sudo tee --append /liman/admin.info

sudo mv /liman/server/nginx.conf /etc/nginx/sites-available/liman.conf

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