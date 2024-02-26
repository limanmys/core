#!/usr/bin/env sh

# Update files on the persistent storage
cp -r /liman_files/* /liman
rm -rf /liman_files

# Set permissions
chown liman:liman /liman
chmod -R 700 /liman/certs
chown -R liman:liman /liman/certs
chmod -R 700 /liman/database
chown -R liman:liman /liman/database
chmod -R 755 /liman/extensions
chown liman:liman /liman/extensions
chmod -R 755 /liman/keys
chown -R liman:liman /liman/keys
chmod -R 700 /liman/logs
chown -R liman:liman /liman/logs
chmod -R 755 /liman/sandbox
chown -R liman:liman /liman/sandbox
chmod -R 700 /liman/server
chown -R liman:liman /liman/server
chmod -R 700 /liman/modules
chown -R liman:liman /liman/modules
chmod -R 700 /liman/packages
chown -R liman:liman /liman/packages
chmod -R 700 /liman/ui
chown -R liman:liman /liman/ui

chmod +x /liman/server/storage/liman_render
chmod +x /liman/server/storage/liman_system
chmod +x /liman/server/storage/limanctl

# Generate environment variables if does not exist
if [ -f "/liman/server/.env" ]; then
    echo "/liman/server/.env exists."
else
    cp /liman/server/.env.example /liman/server/.env
    chown -R liman:liman /liman/server/.env
    sleep 1
    php /liman/server/artisan key:generate
    php /liman/server/artisan jwt:secret
fi

# JWT Secret creation
JWT_EXISTS=$(grep JWT_SECRET /liman/server/.env && echo "1" || echo "0")
if [ $JWT_EXISTS == "0" ]; then
    php /liman/server/artisan jwt:secret
else
    echo "JWT secret already set."
fi


# Set container mode to true
grep -E "^CONTAINER_MODE" /liman/server/.env >/dev/null && sed -i '/^CONTAINER_MODE/d' /liman/server/.env && echo "CONTAINER_MODE=true" >> /liman/server/.env || echo "CONTAINER_MODE=true" >> /liman/server/.env

# Set redis information
grep -E "^REDIS_HOST" /liman/server/.env >/dev/null && sed -i '/^REDIS_HOST/d' /liman/server/.env && echo "REDIS_HOST=${REDIS_HOST}" >> /liman/server/.env || echo "REDIS_HOST=${REDIS_HOST}" >> /liman/server/.env
grep -E "^REDIS_PORT" /liman/server/.env >/dev/null && sed -i '/^REDIS_PORT/d' /liman/server/.env && echo "REDIS_PORT=${REDIS_PORT}" >> /liman/server/.env || echo "REDIS_PORT=${REDIS_PORT}" >> /liman/server/.env
grep -E "^REDIS_PASSWORD" /liman/server/.env >/dev/null && sed -i '/^REDIS_PASSWORD/d' /liman/server/.env && echo "REDIS_PASSWORD=${REDIS_PASS}" >> /liman/server/.env || echo "REDIS_PASSWORD=${REDIS_PASS}" >> /liman/server/.env

# Database values
sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/g" /liman/server/.env 
sed -i "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/g" /liman/server/.env 
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/g" /liman/server/.env 
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/g" /liman/server/.env 
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/g" /liman/server/.env 

# Permission fix
touch /liman/logs/liman.log
touch /liman/logs/liman_new.log
chown -R liman:liman /liman/logs

# Set needed values
sed -i "s#QUEUE_DRIVER=database#QUEUE_DRIVER=redis#g" /liman/server/.env
sed -i "s/memory_limit.*/memory_limit = 1024M/g" /etc/php/8.1/fpm/php.ini
sed -i "s/post_max_size.*/post_max_size = 128M/g" /etc/php/8.1/fpm/php.ini
sed -i "s/upload_max_filesize.*/upload_max_filesize = 100M/g" /etc/php/8.1/fpm/php.ini

# Dynamic nginx port
sed -i "s/listen 443 ssl http2.*/listen ${NGINX_PORT} ssl http2;/g" /etc/nginx/sites-available/liman.conf
#sed -i "s/127.0.0.1:8888/liman-webssh:8888/g" /etc/nginx/sites-available/liman.conf

# Generate certificate variables if does not exist
if [ -f "/liman/certs/liman.key" ]; then
    echo "/liman/certs/liman.key exists."
else
    openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj "/C=TR/ST=Ankara/L=Merkez/O=Havelsan/CN=liman" -keyout /liman/certs/liman.key -out /liman/certs/liman.crt
fi

# Laravel initialization
chown -R liman:liman /liman/server/.env
php /liman/server/artisan migrate --force 
php /liman/server/artisan cache:clear 
php /liman/server/artisan view:clear 
php /liman/server/artisan config:clear

# Re-init CA Certs
update-ca-certificates

# Set container mode to true
grep -E "^CONTAINER_MODE" /liman/server/.env >/dev/null && sed -i '/^CONTAINER_MODE/d' /liman/server/.env && echo "CONTAINER_MODE=true" >> /liman/server/.env || echo "CONTAINER_MODE=true" >> /liman/server/.env

# Start Liman services
sleep 3;
/usr/bin/supervisord -c /etc/supervisor/supervisor.conf 
