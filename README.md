# Liman Installation

## Create Necessary Folders
```bash
sudo mkdir -p /liman/{server,certs,logs,database}
```
## Get Liman Files
```bash
sudo git clone https://github.com/mertcelen/liman.git /liman/server
```
# Liman User Add
```bash
sudo useradd liman
sudo chmod -R o= /liman
sudo chown -R liman:liman /liman
```
# PHP7.3 Installation

## Retrieve Repositories and Update Packages
```bash
sudo apt update && sudo apt upgrade -y
```
## Install HTTPS Transportation Packages
```bash
sudo apt -y install git apt-transport-https ca-certificates dirmngr unzip
```
## Retrieve key and add to trusted list.
```bash
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
```
### Option 1 : Ubuntu Based Distros
```bash
sudo add-apt-repository ppa:ondrej/php
```
### Option 2 : Pardus and Debian Based Distros
```bash
echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list
```

## Retrieve Repositories and Update Packages
```bash
sudo apt update && sudo apt upgrade -y
```
## Install PHP and its additional libraries
```bash
sudo apt install php7.3-fpm php7.3 php7.3-mongodb php7.3-ldap php7.3-mbstring php7.3-xml php7.3-zip php7.3-ssh2 -y
```
## Change PHP user to liman
```bash
sudo sed -i "s/www-data/liman/g" /etc/php/7.3/fpm/pool.d/www.conf
```
## Fix for LDAP in PHP
```bash
echo "TLS_REQCERT     never" | sudo tee --append /etc/ldap/ldap.conf
```
## Restart php-fpm service to reload changes.
```bash
sudo systemctl restart php7.3-fpm
```
# MongoDB Installation

## Add Repository
```bash
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 9DA31620334BD75D9DCB49F368818C72E52529D4
```
### Option 1 : Ubuntu Based Distros
```bash
echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu xenial/mongodb-org/4.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-4.0.list
```
### Option 2 : Pardus and Debian Based Distros
```bash
echo "deb http://repo.mongodb.org/apt/debian stretch/mongodb-org/4.0 main" | sudo tee /etc/apt/sources.list.d/mongodb.list
```
## Retrieve Repositories and Update Packages
```bash
sudo apt update && sudo apt upgrade -y
```
## Install MongoDB Community Edition
```bash
sudo apt install -y mongodb-org
```
## Start & Enable Service
```bash
sudo systemctl start mongod
sudo systemctl enable mongod
```
# Install Nginx
```bash
sudo apt install nginx -y

sudo sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf
```
## Create Certificate
```bash
sudo openssl req \
   -new \
   -newkey rsa:4096 \
   -days 365 \
   -nodes \
   -x509 \
   -subj "/C=TR/ST=Ankara/L=Merkez/O=Havelsan/CN=liman" \
   -keyout /liman/certs/liman.key \
   -out /liman/certs/liman.crt
```
## Copy Required Configuration Files
```bash
sudo cp /liman/server/nginx.conf /etc/nginx/sites-available/liman.conf
sudo ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf
```
## Restart Nginx and Enable at Startup
```bash
sudo systemctl restart nginx
sudo systemctl enable nginx
```
# Composer Installation
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo cp composer.phar /usr/bin/composer
sudo chmod +x /usr/bin/composer
```
# Set up Liman
```bash
cd /liman/server
sudo composer install

sudo chown -R liman:liman /liman

sudo php /liman/server/artisan key:generate
sudo php /liman/server/artisan administrator
```

# Clear Caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:cache
```

# For Winrm Connection

```bash
sudo apt install python3-pip
pip3 install pypsrp
```
