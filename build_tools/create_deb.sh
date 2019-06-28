# Liman System Manager .deb Package Creation Script
mkdir DEBIAN

touch DEBIAN/postinst
touch DEBIAN/md5sums
touch DEBIAN/control

find . -type f ! -regex '.*.hg.*' ! -regex '.*?debian-binary.*' ! -regex '.*?DEBIAN.*' -printf '%P ' | xargs md5sum > DEBIAN/md5sums

echo """
sudo mkdir -p /liman/{server,certs,logs,database,sandbox,keys,scripts,extensions}
sudo mkdir -p /liman/keys/{windows,linux}
if not getent passwd liman > /dev/null 2>&1; then
    echo "Liman Kullanıcısı Ekleniyor..."
    sudo useradd liman -m
    echo "liman     ALL=\(ALL:ALL\) NOPASSWD:ALL" | sudo tee --append /etc/sudoers
    sudo mkdir /home/liman
fi
echo "Depolar Güncelleniyor..."
sudo apt update
echo "Sistemdeki Paketler Güncelleniyor..."
sudo apt upgrade -y
echo "Gerekli Paketler Kuruluyor"
sudo apt -y install apt-transport-https ca-certificates dirmngr python3-pip unzip dnsutils nginx
echo "Sertifikalar Ekleniyor"
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg3
echo "Gerekli PHP Deposu Ekleniyor..."
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
echo "Sistemdeki Paketler Güncelleniyor..."
sudo apt update && sudo apt upgrade -y
echo "PHP Kuruluyor..."
sudo apt install php7.3-fpm php7.3 php7.3-sqlite php7.3-ldap php7.3-mbstring php7.3-xml php7.3-zip php7.3-ssh2 -y
sudo sed -i "s/www-data/liman/g" /etc/php/7.3/fpm/pool.d/www.conf
sudo echo "TLS_REQCERT     never" | sudo tee --append /etc/ldap/ldap.conf
sudo sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf
sudo ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf
sudo touch /liman/database/liman.sqlite
sudo chmod 700 /liman/database/liman.sqlite
sudo openssl req \
   -new \
   -newkey rsa:4096 \
   -days 365 \
   -nodes \
   -x509 \
   -subj "/C=TR/ST=Ankara/L=Merkez/O=Havelsan/CN=liman" \
   -keyout /liman/certs/liman.key \
   -out /liman/certs/liman.crt

sudo systemctl restart nginx
sudo systemctl enable nginx

sudo apt-get install python3-setuptools
sudo runuser liman -c "pip3 install pypsrp paramiko tornado"
""" > DEBIAN/preinst
chmod 775 DEBIAN/preinst


echo """

chmod o+xr /liman/
chmod -R o+xr /liman/sandbox
chmod o+x /liman/extensions

mv /liman/server/nginx.conf /etc/nginx/sites-available/liman.conf
mv /liman/webssh/liman-webssh.service /etc/systemd/system/liman-webssh.service

systemctl enable liman-webssh
systemctl start webssh
systemctl restart nginx
systemctl restart php7.3-fpm
find /liman -type d ! -name extensions -exec chown -R liman:liman {} \;
php /liman/server/artisan migrate
printf "\nKurulum Tamamlandı\n\nYönetici Hesabı oluşturmak yada şifrenizi yenilemek için aşağıdaki komutu çalıştırabilisiniz\n\n\n"
echo "sudo runuser liman -c "php /liman/server/artisan administrator""
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