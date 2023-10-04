Name: liman
Version: %VERSION%
Release: 0
License: MIT
Requires: curl, gpgme, zip, unzip, nginx, crontabs, redis, php, php-fpm, php-pecl-redis5, php-pecl-zip, php-gd, php-snmp, php-mbstring, php-xml, php-pdo, openssl, supervisor, php-pgsql, php-bcmath, rsync, bind-utils, php-ldap, libsmbclient, samba-client, php-smbclient, postgresql15, postgresql15-server, nodejs
Prefix: /liman
Summary: Liman MYS
Group: Applications/System
BuildArch: x86_64

%description
Liman MYS

%pre

%prep

%build

%install
cp -rfa %{_app_dir} %{buildroot}

%post -p /bin/bash

# Create Required Folders for Liman
mkdir -p /liman/{server,certs,logs,database,sandbox,keys,extensions,modules,packages}

# environment creation
if [ -f "/liman/server/.env" ]; then
    echo "Upgrading liman."
else
    cp /liman/server/.env.example /liman/server/.env
    php /liman/server/artisan key:generate
    echo "Installing liman."
fi

/usr/pgsql-15/bin/postgresql-15-setup initdb
systemctl enable postgresql-15
sed -i '1s/^/host    all             all             127.0.0.1\/32            md5\n/' /var/lib/pgsql/15/data/pg_hba.conf
systemctl start postgresql-15

systemctl enable crond
systemctl start crond

systemctl enable redis
systemctl start redis

# User Creation
if getent passwd liman > /dev/null 2>&1; then
    echo "Liman User Found."
else
    useradd liman -m
    mkdir /home/liman
    chmod -R o= /liman /home/liman
    chown -R liman:liman /liman /home/liman
    echo "Liman User Created"
fi

systemctl enable supervisord
systemctl start supervisord

# Delete if sudo exists
sed -i '/liman/d' /etc/sudoers

runuser liman -c '$(which gpg) --batch --yes --delete-keys aciklab@havelsan.com.tr'
runuser liman -c '$(which gpg) --import /liman/server/storage/aciklab.public'

# Certificate Creation
if [ -f "/liman/certs/liman.crt" ]; then
    echo "SSL Certificate Found."
else
    openssl req \
        -new \
        -newkey rsa:4096 \
        -days 365 \
        -nodes \
        -x509 \
        -subj "/C=TR/ST=Ankara/L=Merkez/O=Havelsan/CN=liman" \
        -keyout /liman/certs/liman.key \
        -out /liman/certs/liman.crt
    echo "SSL Certificate Created"
fi

DB_EXISTS=$(sudo -u liman psql -lqt | cut -d \| -f 1 | grep "liman" >/dev/null 2>/dev/null && echo "1" || echo "0")

# Database Creation
if [ $DB_EXISTS == "0" ]; then
    sudo -u postgres createuser liman
    sudo -u postgres createdb liman -O liman
    RANDOM_PASSWORD=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 25 ; echo)
    sudo -u postgres psql -U postgres -d postgres -c "alter user \"liman\" with password '$RANDOM_PASSWORD';"
    sed -i '/DB_PASSWORD/d' /liman/server/.env
    printf "\nDB_PASSWORD=$RANDOM_PASSWORD\n" | tee -a /liman/server/.env
else
    echo "Postgresql already set up."
fi

# Update Php and Fpm to run as liman user.
sed -i "s/listen.acl_users/;listen.acl_users/g" /etc/php-fpm.d/www.conf
sed -i "s/user =.*/user = liman/g" /etc/php-fpm.d/www.conf
sed -i "s/group =.*/group = liman/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.owner/listen.owner/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.group/listen.group/g" /etc/php-fpm.d/www.conf
sed -i "s/;listen.mode/listen.mode/g" /etc/php-fpm.d/www.conf
sed -i "s/listen.owner =.*/listen.owner = liman/g" /etc/php-fpm.d/www.conf
sed -i "s/listen.group =.*/listen.group = liman/g" /etc/php-fpm.d/www.conf
sed -i "s/listen.mode =.*/listen.mode = 660/g" /etc/php-fpm.d/www.conf
sed -i "s/pm.max_children =.*/pm.max_children = 60/g" /etc/php-fpm.d/www.conf
sed -i "s/pm.start_servers =.*/pm.start_servers = 10/g" /etc/php-fpm.d/www.conf
sed -i "s/pm.min_spare_servers =.*/pm.min_spare_servers = 5/g" /etc/php-fpm.d/www.conf
sed -i "s/pm.max_spare_servers =.*/pm.max_spare_servers = 20/g" /etc/php-fpm.d/www.conf
sed -i "s/user .*;/user liman;/g" /etc/nginx/nginx.conf

# Crontab Setting
if [ -f "/etc/cron.d/liman" ]; then
    echo "Crontab already created.";
else
    mkdir "/etc/cron.d" 2>/dev/null
    echo "* * * * * liman cd /liman/server && php artisan schedule:run >> /dev/null 2>&1" >> "/etc/cron.d/liman"
    systemctl restart crond
fi

sed -i "s/more_set_headers/#more_set_headers/g" /liman/server/storage/nginx.conf
sed -i "s/php\/php8.1-fpm.sock/php-fpm\/www.sock/g" /liman/server/storage/nginx.conf
mv /liman/server/storage/nginx.conf /etc/nginx/conf.d/liman.conf

# Nginx Auto Redirection
if [ -f "/etc/nginx/default.d/liman.conf" ]; then
    echo "Nginx https redirection already set up."; 
else
    echo """
#LIMAN_SECURITY_OPTIMIZATIONS
return 301 https://\$host\$request_uri;
    """ > /etc/nginx/default.d/liman.conf
fi

#Supervisor Configuration
if [ -f "/etc/supervisord.d/liman-extension-worker.ini" ]; then
    rm /etc/supervisord.d/liman-extension-worker.ini;
fi

if [ -f "/etc/supervisord.d/liman-cron-mail.ini" ]; then
    rm /etc/supervisord.d/liman-cron-mail.ini;
fi

supervisorctl reread
supervisorctl update
supervisorctl start all

#Increase Php-Fpm Memory
sed -i "s/memory_limit = 128M/memory_limit = 1024M/g" /etc/php.ini

#Change Queue Driver from Db to Redis
sed -i "s#QUEUE_DRIVER=database#QUEUE_DRIVER=redis#g" /liman/server/.env

#Change Render Engine Port
sed -i "s#RENDER_ENGINE_ADDRESS=https://127.0.0.1:5454#RENDER_ENGINE_ADDRESS=https://127.0.0.1:2806#g" /liman/server/.env

# JWT Secret creation
JWT_EXISTS=$(grep JWT_SECRET /liman/server/.env && echo "1" || echo "0")
if [ $JWT_EXISTS == "0" ]; then
    php /liman/server/artisan jwt:secret
else
    echo "JWT secret already set."
fi

# Run Database Migration
php /liman/server/artisan migrate --force
php /liman/server/artisan cache:clear
php /liman/server/artisan view:clear
php /liman/server/artisan config:clear

# Delete Old Sandbox Files
rm -rf /liman/sandbox/{.git,vendor,views,.gitignore,composer.json,composer.lock,index.php}

# Set Permissions
chown -R liman:liman /liman/{server,database,certs,sandbox,logs,modules,packages,hashes}
chmod 700 -R /liman/{server,database,certs,logs,modules,packages,hashes}
chmod 755 -R /liman/sandbox
chown liman:liman /{liman,liman/extensions,liman/keys}
chmod 755 /{liman,liman/extensions,liman/keys}

# Create Systemd Service
if [ -f "/etc/systemd/system/liman-connector.service" ]; then
    rm /etc/systemd/system/liman-connector.service
fi

# Create UI Systemd Service
if [ -f "/etc/systemd/system/liman-ui.service" ]; then
    echo "Liman User Interface Service Already Added.";
else
    echo """
[Unit]
Description=Liman User Interface Service
After=network.target
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=1
User=liman
WorkingDirectory=/liman/ui
ExecStart=/usr/bin/node server.js

[Install]
WantedBy=multi-user.target
    """ > /etc/systemd/system/liman-ui.service
fi

# Create Systemd Service
if [ -f "/etc/systemd/system/liman-system.service" ]; then
    echo "Liman System Service Already Added.";
else
    echo """
[Unit]
Description=Liman System Service & Extension Renderer
After=network.target
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=1
User=root
ExecStart=/liman/server/storage/liman_system

[Install]
WantedBy=multi-user.target
    """ > /etc/systemd/system/liman-system.service
fi

# Create Systemd Service
if [ -f "/etc/systemd/system/liman-render.service" ]; then
    echo "Liman Render Service Already Added.";
else
    echo """
[Unit]
Description=Liman System Service & Extension Renderer
After=network.target
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=1
User=root
ExecStart=/liman/server/storage/liman_render

[Install]
WantedBy=multi-user.target
    """ > /etc/systemd/system/liman-render.service
fi

# Remove Old WebSSH Service
if [ -f "/etc/systemd/system/liman-webssh.service" ]; then
    rm /etc/systemd/system/liman-webssh.service
fi

# Remove Old VNC Service
if [ -f "/etc/systemd/system/liman-vnc.service" ]; then
    rm /etc/systemd/system/liman-vnc.service
fi

# Create Socket Service
if [ -f "/etc/systemd/system/liman-socket.service" ]; then
    echo "Liman Socket Service Already Added.";
else
        echo """
[Unit]
Description=Liman Socket Service
After=network.target
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=1
User=liman
ExecStart=/usr/bin/php /liman/server/artisan websockets:serve --host=127.0.0.1

[Install]
WantedBy=multi-user.target
    """ > /etc/systemd/system/liman-socket.service
fi

if (systemctl -q is-active systemd-resolved.service); then
    systemctl disable systemd-resolved
    systemctl stop systemd-resolved
    rm /etc/resolv.conf
    /usr/bin/python3 /liman/server/storage/smb-dhcp-client 2> /dev/null | grep "Domain Name Server(s)" | cut -d : -f 2 |  xargs  | sed 's/ /\n/g' |sed 's/.*\..*\..*\..*/nameserver &/g' > /etc/resolv.conf
fi

sed -i "s/upload_max_filesize.*/upload_max_filesize = 100M/g" /etc/php.ini
sed -i "s/post_max_size.*/post_max_size = 100M/g" /etc/php.ini

# Prepare Folders for vnc
rm -rf /liman/keys/vnc
mkdir /liman/keys/vnc
chmod 700 /liman/keys/vnc
touch /liman/keys/vnc/config
chown liman:liman /liman/keys/vnc /liman/keys/vnc/config
chmod 700 /liman/keys/vnc/config

# Reload the systemd
systemctl daemon-reload

# Remove Legacy Service.
rm /etc/systemd/system/liman.service 2>/dev/null
systemctl disable liman 2>/dev/null
systemctl stop liman 2>/dev/null
    
grep -E "^TLS_REQCERT" /etc/openldap/ldap.conf && sed -i '/^TLS_REQCERT/d' /etc/openldap/ldap.conf && echo "TLS_REQCERT allow" >>  /etc/openldap/ldap.conf || echo "TLS_REQCERT allow" >> /etc/openldap/ldap.conf

firewall-cmd --permanent --zone=public --add-service=http
firewall-cmd --permanent --zone=public --add-service=https
firewall-cmd --reload

chcon -Rt httpd_config_t /liman/certs/liman.*
chcon -Rt httpd_config_t /etc/nginx/conf.d/liman.conf
chcon -Rt httpd_sys_content_t /liman
chcon -Rt httpd_sys_rw_content_t /liman
chcon -Rt bin_t /liman/server/storage/liman_*
setsebool -P httpd_can_network_connect 1
    
mkdir  /usr/lib/systemd/system/php-fpm.service.d
echo -e "[Service]\nPrivateTmp=no" > /etc/systemd/system/php-fpm.service.d/privatetmp.conf
systemctl daemon-reload

chown -R liman /var/lib/nginx/

systemctl enable liman-ui 2>/dev/null
systemctl enable liman-system 2>/dev/null
systemctl enable liman-render 2>/dev/null
systemctl disable liman-connector 2>/dev/null
systemctl disable liman-webssh 2>/dev/null
systemctl disable liman-vnc 2>/dev/null
systemctl enable liman-socket 2>/dev/null
systemctl enable nginx 2>/dev/null
systemctl enable php-fpm 2>/dev/null

systemctl stop liman-connector
systemctl stop liman-vnc
systemctl stop liman-webssh
systemctl restart liman-ui
systemctl restart liman-system
systemctl restart liman-render
systemctl restart liman-socket
systemctl restart nginx
systemctl restart php-fpm

# Optimize Liman
php /liman/server/artisan optimize:clear

# Liman Storage Link
php /liman/server/artisan storage:link

# Enable Liman
php /liman/server/artisan up

# Flush Redis Cache
redis-cli flushall

# Create Limanctl Symlink
chmod +x /liman/server/storage/limanctl
cp -f /liman/server/storage/limanctl /usr/bin/limanctl

#Finalize Installation
printf "\nKurulum Başarıyla Tamamlandı!\n\nYönetici Hesabı oluşturmak yada şifrenizi yenilemek için aşağıdaki komutu çalıştırabilisiniz\n\n\n"
printf "sudo limanctl administrator\n\n\nDestek için liman.havelsan.com.tr adresini ziyaret edebilirsiniz.\n"

%clean

%files
/liman/*
/etc/supervisord.d/*

%define _unpackaged_files_terminate_build 0
