# Liman Sunucu Yönetimi

### Kurulum

Kurulum Ubuntu 16.04 üzerinden anlatılmıştır.

##### Dosyaların İndirilmesi
**Yalnızca İnternet bağlantınız ssl sertifikası yüzünden indirmenize izin vermiyorsa** aşağıdaki komutu çalıştırıp devam edin.
```bash
export GIT_SSL_NO_VERIFY=1
```
Github'dan kodların alınması
```bash
sudo git clone https://github.com/mertcelen/liman.git /var/www/liman
```
##### PHP 7.2 Kurulumu

```bash
echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu xenial main" | sudo tee /etc/apt/sources.list.d/ondrej.list
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 4F4EA0AAE5267A6C
sudo apt update
sudo apt install php php-fpm composer php-mongodb php-ldap php-mbstring php-xml php-zıp
```

##### Nginx Kurulumu

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
```

##### Self-Signed Sertifika Oluşturma

```bash
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/nginx.key -out /etc/ssl/certs/nginx.crt
```

##### MongoDB Kurulumu

```bash
sudo apt install mongodb
sudo mkdir -p /data/db/
sudo chown `id -u` /data/db
sudo systemctl enable mongodb
```
##### Liman Ayarları

```bash
sudo apt install sshpass -y
cd /var/www/liman
```

**Yalnızca İnternet bağlantınız ssl sertifikası yüzünden indirmenize izin vermiyorsa** aşağıdaki komutu çalıştırıp devam edin.
```bash
composer config --global disable-tls true
composer config --global secure-http false
```
Yetki Ayarları
```bash
sudo chown -R `id -u`:`id -u` ~/.composer
sudo mv .env.example .env
sudo mv nginx.conf /etc/nginx/nginx.conf
sudo composer install
sudo php artisan key:generate
sudo useradd liman -M
sudo chmod -R o= .*
sudo chown -R liman:liman .*
sudo systemctl restart nginx
```
