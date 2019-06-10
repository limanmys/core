sudo mkdir -p /liman/{server,certs,logs,database,sandbox,keys,scripts,extensions}
sudo mkdir -p /liman/keys/{windows,linux}

sudo useradd liman -m
echo "liman     ALL=(ALL:ALL) NOPASSWD:ALL" | sudo tee --append /etc/sudoers
sudo mkdir /home/liman
sudo chmod -R o= /liman /home/liman
sudo chown -R liman:liman /liman /home/liman
sudo apt update
sudo apt upgrade -y
sudo apt -y install git apt-transport-https ca-certificates dirmngr python3-pip unzip
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
sudo apt update && sudo apt upgrade -y
sudo apt install php7.3-fpm php7.3 php7.3-mongodb php7.3-ldap php7.3-mbstring php7.3-xml php7.3-zip php7.3-ssh2 -y
sudo sed -i "s/www-data/liman/g" /etc/php/7.3/fpm/pool.d/www.conf
sudo echo "TLS_REQCERT     never" | sudo tee --append /etc/ldap/ldap.conf
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 9DA31620334BD75D9DCB49F368818C72E52529D4
echo "deb [ arch=amd64 ] https://repo.mongodb.org/apt/ubuntu xenial/mongodb-org/4.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-4.0.list
sudo apt update && sudo apt upgrade -y
sudo apt-get install -y mongodb-org
sudo systemctl start mongod
sudo systemctl enable mongod
sudo apt install nginx -y
sudo sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf
sudo ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf

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
sudo runuser liman -c "pip3 install pypsrp"