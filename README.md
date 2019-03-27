#### İlk Güncelleme
```bash
sudo apt update
sudo apt upgrade -y
```

#### Gerekli Klasorleri Olusturmak
```bash
sudo mkdir -p /liman/{server,certs,logs,webssh}
sudo mkdir -p /data/db/
```

#### Liman Kullanıcısı Oluşturma
```bash
sudo useradd liman -M
sudo chmod -R o= /liman
sudo chown -R liman:liman /liman
```

#### PHP kütüphaneleri kurulumu
```bash
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt upgrade -y

sudo apt install php7.3-fpm -y
sudo apt install php7.3 php7.3-mongodb php7.3-ldap php7.3-mbstring php7.3-xml php7.3-zip php7.3-simplexml php7.3-ssh2 php7.3-mysqli -y
```

#### Git Kurulumu ve Dosyaları çıkartmak (bunun yerine dışarıdan da çekilebilir)
```bash
sudo apt install git
sudo git clone https://github.com/mertcelen/liman.git /liman/server
```

#### liman izinlerini düzenlemek
```bash
sudo chown -R liman:liman /liman
```

#### Web Sunucusu Kurulumu
```bash
sudo apt install nginx -y
```

#### Self-Signed Sertifika oluşturma
```bash
sudo mkdir -p /liman/certs/
sudo openssl req \
   -new \
   -newkey rsa:4096 \
   -days 365 \
   -nodes \
   -x509 \
   -subj "/C=TR/ST=Ankara/L=Merkez/O=aciklab/CN=liman" \
   -keyout /liman/certs/liman.key \
   -out /liman/certs/liman.crt
```

#### MongoDB kurulumu (ubuntu 18.04 için xenial yerine bionic yazılacak)
```bash 
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 9DA31620334BD75D9DCB49F368818C72E52529D4
echo "deb [ arch=amd64 ] https://repo.mongodb.org/apt/ubuntu xenial/mongodb-org/4.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-4.0.list
sudo apt-get update
sudo apt-get install -y mongodb-org
sudo chown `id -u` /data/db
```

#### Composer Kurulumu ve çalıştırma 
```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo cp composer.phar /usr/bin/composer
sudo chmod +x /usr/bin/composer
cd /liman/server
composer install
```

#### Liman yönetici hesabı açma
```bash
sudo php /liman/server/artisan key:generate
sudo systemctl restart mongod
sudo php /liman/server/artisan administrator
```

#### Yapılandırma Dosyası bağlama
```bash
sudo cp /liman/server/.env.example /liman/server/.env
sudo ln -sf /liman/server/.env /liman/liman.conf
```

#### Nginx Yapılandırma Ayarları
```bash
sudo cp /liman/server/nginx.conf /etc/nginx/sites-available/liman.conf
sudo ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf
sudo sed -i "s/php7.2/php7.3/g" /etc/nginx/sites-enabled/liman.conf
sudo rm /etc/nginx/sites-enabled/default.conf
```

#### Servisleri Aktifleştirmek
```bash
sudo systemctl enable nginx
sudo systemctl enable mongodb
```
#### Nginx ve PHP FPM ayarları
```bash
sudo sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf
sudo sed -i "s/www-data/liman/g" /etc/php/7.3/fpm/pool.d/www.conf
```
#### LDAP bağımlı eklentiler için özel ayar
```bash
echo "TLS_REQCERT     never" | sudo tee --append /etc/ldap/ldap.conf
```

#### Web Sunucusunu Ayağa Kaldırmak
```bash
sudo systemctl restart php7.3-fpm
sudo systemctl restart nginx
```
