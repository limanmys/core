Name: liman
Version: %VERSION%
Release: 0
License: MIT
Requires: curl, gpgme, zip, unzip, nginx, redis, php, php-fpm, php73-php-pecl-redis5, php-gd, php-snmp, php-mbstring, php-xml, php-pdo, openssl, oracle-epel-release-el8, supervisor, php-pgsql, php-bcmath, rsync, bind-utils, php-ldap, libsmbclient, samba-client, novnc, python39, postgresql13, postgresql13-server
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

/usr/pgsql-13/bin/postgresql-13-setup initdb
systemctl enable postgresql-13
systemctl start postgresql-13

# User Creation
if getent passwd liman > /dev/null 2>&1; then
    #sed -i '/liman/d' /etc/sudoers
    #echo "liman     ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers
    echo "Liman User Found."
else
    useradd liman -m
    mkdir /home/liman
    chmod -R o= /liman /home/liman
    chown -R liman:liman /liman /home/liman
    #echo "liman     ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers
    echo "Liman User Created"
fi

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
sed -i "s/www-data/liman/g" /etc/php/7.3/fpm/pool.d/www.conf
sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf

# Crontab Setting
if [ -f "/etc/cron.d/liman" ]; then
    echo "Crontab already created.";
else
    mkdir "/etc/cron.d" 2>/dev/null
    echo "* * * * * liman cd /liman/server && php artisan schedule:run >> /dev/null 2>&1" >> "/etc/cron.d/liman"
    systemctl restart cron
fi

mv /liman/server/storage/nginx.conf /etc/nginx/sites-available/liman.conf
ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf

# Nginx Auto Redirection
if grep --quiet LIMAN_SECURITY_OPTIMIZATIONS /etc/nginx/sites-available/default; then
    echo "Nginx https redirection already set up."; 
else
    echo """
#LIMAN_SECURITY_OPTIMIZATIONS
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    server_tokens off;
    more_set_headers 'Server: LIMAN MYS';
    return 301 https://\$host\$request_uri;
}
    """ > /etc/nginx/sites-available/default
fi

#Supervisor Configuration
if [ -f "/etc/supervisor/conf.d/liman-extension-worker.conf" ]; then
    rm /etc/supervisor/conf.d/liman-extension-worker.conf;
fi

echo """
#LIMAN_OPTIMIZATIONS
[program:liman-system-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /liman/server/artisan queue:work --sleep=1 --tries=3 --queue=system_updater --timeout=0
autostart=true
autorestart=true
user=liman
numprocs=1
redirect_stderr=true
stdout_logfile=/liman/logs/system_update.log
    """ > /etc/supervisor/conf.d/liman-system-worker.conf

echo """
#LIMAN_OPTIMIZATIONS
[program:liman-cron_mail-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /liman/server/artisan queue:work --sleep=1 --tries=3 --queue=cron_mail --timeout=0
autostart=true
autorestart=true
user=liman
numprocs=8
redirect_stderr=true
stdout_logfile=/liman/logs/mail.log
    """ > /etc/supervisor/conf.d/liman-cron-mail.conf
supervisorctl reread
supervisorctl update
supervisorctl start all

#Increase Php-Fpm Memory
sed -i "s/memory_limit = 128M/memory_limit = 1024M/g" /etc/php/7.3/fpm/php.ini

# Run Database Migration
php /liman/server/artisan migrate --force
php /liman/server/artisan cache:clear
php /liman/server/artisan view:clear
php /liman/server/artisan config:clear

# Delete Old Sandbox Files
rm -rf /liman/sandbox/{.git,vendor,views,.gitignore,composer.json,composer.lock,index.php}

# Set Permissions
chown -R liman:liman /liman/{server,database,certs,sandbox,logs,webssh,modules,packages,hashes}
chmod 700 -R /liman/{server,database,certs,logs,webssh,modules,packages,hashes}
chmod 755 -R /liman/sandbox
chown liman:liman /{liman,liman/extensions,liman/keys}
chmod 755 /{liman,liman/extensions,liman/keys}

# Create Systemd Service
if [ -f "/etc/systemd/system/liman-connector.service" ]; then
    rm /etc/systemd/system/liman-connector.service
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

# Create Systemd Service
if [ -f "/etc/systemd/system/liman-webssh.service" ]; then
    echo "Liman WebSSH Service Already Added.";
else
    echo """
[Unit]
Description=Liman WebSSH Service
After=network.target
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=1
User=liman
ExecStart=/usr/bin/python3 /liman/webssh/run.py

[Install]
WantedBy=multi-user.target
    """ > /etc/systemd/system/liman-webssh.service
fi

# Create Systemd Service
if [ -f "/etc/systemd/system/liman-vnc.service" ]; then
    echo "Liman VNC Service Already Added.";
else
    echo """
[Unit]
Description=Liman VNC Service
After=network.target
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=1
User=liman
ExecStart=/usr/bin/websockify --web=/usr/share/novnc 6080 --cert=/liman/certs/liman.crt --key=/liman/certs/liman.key --token-plugin TokenFile --token-source /liman/keys/vnc/config
[Install]
WantedBy=multi-user.target
    """ > /etc/systemd/system/liman-vnc.service
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

sed -i "s/upload_max_filesize.*/upload_max_filesize = 100M/g" /etc/php/7.3/fpm/php.ini
sed -i "s/post_max_size.*/post_max_size = 100M/g" /etc/php/7.3/fpm/php.ini

#Liman Helper Bash Function
sed -i '/liman/d' /etc/profile
echo "function liman(){ sudo runuser liman -c \"php /liman/server/artisan \$1\"; }" | tee --append /etc/profile >/dev/null 2>/dev/null
sed -i '/liman/d' /root/.profile
echo "function liman(){ sudo runuser liman -c \"php /liman/server/artisan \$1\"; }" | tee --append /root/.profile >/dev/null 2>/dev/null
source /etc/profile

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

systemctl enable liman-vnc 2>/dev/null
systemctl enable liman-webssh 2>/dev/null
systemctl enable liman-system 2>/dev/null
systemctl enable liman-render 2>/dev/null
systemctl disable liman-connector 2>/dev/null
systemctl enable liman-socket 2>/dev/null
systemctl enable nginx 2>/dev/null
systemctl enable php7.3-fpm 2>/dev/null

systemctl stop liman-connector
systemctl restart liman-system
systemctl restart liman-render
systemctl restart liman-vnc
systemctl restart liman-webssh
systemctl restart liman-socket
systemctl restart nginx
systemctl restart php7.3-fpm

# Optimize Liman
php /liman/server/artisan optimize:clear

# Liman Storage Link
php /liman/server/artisan storage:link

# Enable Liman
php /liman/server/artisan up

# Create Limanctl Symlink
chmod +x /liman/server/storage/limanctl
cp -f /liman/server/storage/limanctl /usr/bin/limanctl

#Finalize Installation
printf "\nKurulum Başarıyla Tamamlandı!\n\nYönetici Hesabı oluşturmak yada şifrenizi yenilemek için aşağıdaki komutu çalıştırabilisiniz\n\n\n"
printf "source /etc/profile; liman administrator\n\n\nDestek için liman.havelsan.com.tr adresini ziyaret edebilirsiniz.\n"

%clean

%files
/liman/*

%define _unpackaged_files_terminate_build 0