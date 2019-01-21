#### Gerekli Klasorleri Olusturmak
```bash
sudo mkdir -p /liman/{server,certs,logs,webssh}

sudo mkdir -p /data/db/
```

#### Git Kurulumu
```bash
sudo apt install git -y
```
#### Dosyalari Indirmek
```bash
sudo git clone https://github.com/mertcelen/liman.git /liman/server
sudo git clone https://github.com/mertcelen/webssh.git /liman/webssh
```
#### Sistem Servislerinin Kopyalanmasi
```bash
sudo cp /liman/server/liman-queue.service /etc/systemd/system/liman-queue.service
sudo cp /liman/webssh/liman-webssh.service /etc/systemd/system/liman-webssh.service
sudo cp /liman/server/liman.service /etc/systemd/system/liman.service
```
#### Depolari Guncellemek
```bash
sudo apt update
```
#### PHP ve diger kutuphanelerin kurulumu
```bash
sudo apt install php-fpm -y
sudo apt install php php-mongodb php-ldap php-mbstring php-xml php-zip -y
```
#### Ubuntu'daki olmayan guncel surumun kurulumu
```bash
wget http://ppa.launchpad.net/ondrej/php/ubuntu/pool/main/p/php-mongodb/php-mongodb_1.5.3-1+ubuntu18.04.1+deb.sury.org+10_amd64.deb
sudo dpkg -i php-mongodb_1.5.3-1+ubuntu18.04.1+deb.sury.org+10_amd64.deb
sudo rm php-mongodb_1.5.3-1+ubuntu18.04.1+deb.sury.org+10_amd64.deb
```
#### Nginx Kurulumu
```bash
sudo apt install nginx -y
```
#### Self Signed Sertifika
```bash
sudo openssl req \
   -new \
   -newkey rsa:4096 \
   -days 365 \
   -nodes \
   -x509 \
   -subj "/C=TR/ST=Ankara/L=Merkez/O=deneme/CN=liman" \
   -keyout /liman/certs/liman.key \
   -out /liman/certs/liman.crt
```
#### MongoDB Kurulumu
```bash
sudo apt install mongodb -y
sudo chown `id -u` /data/db
```
#### Gerekli Paketler
```bash
sudo apt install sshpass telnet nmap python3 python3-paramiko python3-tornado -y
```
#### Config Dosyasi Linkleme
```bash
sudo cp /liman/server/.env.example /liman/server/.env
sudo ln -sf /liman/server/.env /liman/liman.conf
```
#### Liman Sessionlari icin Key Olusturma ve Administrator Hesabı Ekleme
```bash
sudo php /liman/server/artisan key:generate
sudo php /liman/server/artisan setup
```
#### Liman Kullanicisi Olusturma
```bash
sudo useradd liman -M
sudo chmod -R o= /liman
sudo chown -R liman:liman /liman
```
#### Nginx Ayarlari
```bash
sudo cp /liman/server/nginx.conf /etc/nginx/sites-available/liman.conf
sudo ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf
```
#### Servisleri Aktiflestirmek
```bash
sudo systemctl enable nginx
sudo systemctl enable mongodb
sudo systemctl enable liman
sudo systemctl enable liman-queue
sudo systemctl enable liman-webssh
```
#### Nginx ve PHP FPM ayarlari
```bash
sudo sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf
sudo sed -i "s/www-data/liman/g" /etc/php/7.2/fpm/pool.d/www.conf
```
#### ** Gecici Cozum **
```bash
sudo sed -i "s/if origin is not/if False and origin is not/g" /usr/lib/python3/dist-packages/tornado/websocket.py
```
#### Web Sunucusunu Ayaga Kaldirmak
```bash
sudo systemctl restart php7.2-fpm
sudo systemctl restart nginx
```