# LIMAN DOCKERFILE
# AUTHOR: Doğukan Öksüz <dogukan@liman.dev>

FROM ubuntu:jammy
EXPOSE 80 443

# DEPENDENCIES
RUN echo LIMAN.HAVELSAN.COM.TR
RUN export DEBIAN_FRONTEND=noninteractive;
ARG DEBIAN_FRONTEND=noninteractive
ENV DEBIAN_FRONTEND noninteractive
ENV TZ=Europe/Istanbul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN apt -yqq update
RUN DEBIAN_FRONTEND=noninteractive apt -yqq install software-properties-common gnupg2 ca-certificates wget curl
RUN add-apt-repository --yes ppa:ondrej/php
RUN mkdir -p /etc/apt/keyrings
RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
RUN echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list
RUN apt -yqq update

# LIMAN DEPS
RUN DEBIAN_FRONTEND=noninteractive apt -yqq install sudo nodejs gpg zip unzip nginx sysstat php8.1-redis php8.1-fpm php8.1-gd php8.1-curl php8.1 php8.1-sqlite3 php8.1-snmp php8.1-mbstring php8.1-xml php8.1-zip php8.1-posix libnginx-mod-http-headers-more-filter libssl3 supervisor php8.1-pgsql pgloader php8.1-bcmath rsync dnsutils php8.1-ldap php8.1-smbclient krb5-user php8.1-ssh2 smbclient novnc

# FILES
RUN bash -c 'mkdir -p /liman_files/{server,certs,logs,database,sandbox,keys,extensions,modules,packages,ui}'

# UI
RUN curl -s https://api.github.com/repos/limanmys/next/releases/latest | grep "browser_download_url.*zip" | cut -d : -f 2,3 | tr -d \" | wget -qi -
RUN unzip ui*.zip -d ui
RUN mv ui /liman_files/

# CORE
RUN wget "https://github.com/limanmys/core/archive/refs/heads/master.zip" -O "core.zip"
RUN unzip -qq core.zip
RUN mv core-feature-new-ui/* /liman_files/server
RUN mv core-feature-new-ui/.env.example /liman_files/server
RUN rm -rf core.zip

# PHP SANDBOX
RUN wget "https://github.com/limanmys/php-sandbox/archive/refs/heads/master.zip" -O "sandbox.zip"
RUN unzip -qq sandbox.zip
RUN mkdir -p /liman_files/sandbox/php
RUN mv php-sandbox-master/* /liman_files/sandbox/php/
RUN rm -rf sandbox.zip php-sandbox-master

# EXT TEMPLATES
RUN wget "https://github.com/limanmys/extension_templates/archive/master.zip" -O "extension_templates.zip"
RUN unzip -qq extension_templates.zip
RUN mkdir -p /liman_files/server/storage/extension_templates
RUN mv extension_templates-master/* /liman_files/server/storage/extension_templates
RUN rm -rf extension_templates.zip extension_templates-master

# RENDER ENGINE
RUN curl -s https://api.github.com/repos/limanmys/fiber-render-engine/releases/latest | grep "browser_download_url.*zip" | cut -d : -f 2,3 | tr -d \" | wget -qi -
RUN unzip liman_render*.zip
RUN mv liman_render /liman_files/server/storage/liman_render

# COMPOSER
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm -rf composer-setup.php

RUN composer install --no-dev --no-scripts -d /liman_files/server
RUN composer install --no-dev -d /liman_files/sandbox/php

# USERS
RUN groupadd -g 2800 liman
RUN useradd liman -u 2801 -g 2800 -m
RUN useradd extuser -u 2802 -g 2800 -m

# PERMS
RUN cp -f /liman_files/server/storage/limanctl /usr/bin/limanctl

# VNC SETTINGS
RUN rm -rf /liman_files/keys/vnc
RUN mkdir /liman_files/keys/vnc
RUN chmod 700 /liman_files/keys/vnc
RUN touch /liman_files/keys/vnc/config
RUN chown liman:liman /liman_files/keys/vnc /liman_files/keys/vnc/config
RUN chmod 700 /liman_files/keys/vnc/config

# SETTINGS
RUN sed -i "s/www-data/liman/g" /etc/php/8.1/fpm/pool.d/www.conf
RUN sed -i "s/www-data/liman/g" /etc/nginx/nginx.conf
COPY storage/build_tools/docker/config/nginx_default /etc/nginx/sites-available/default 
COPY storage/build_tools/docker/config/nginx.conf /etc/nginx/sites-available/liman.conf
RUN ln -s /etc/nginx/sites-available/liman.conf /etc/nginx/sites-enabled/liman.conf

# SERVICES
RUN mkdir /run/php
COPY storage/build_tools/docker/config/supervisor.conf /etc/supervisor/supervisor.conf
COPY storage/build_tools/docker/config/supervisor /etc/supervisor/conf.d

# START LIMAN
COPY storage/build_tools/docker/init.sh /tmp/init.sh
RUN ["chmod", "755", "/tmp/init.sh"]
RUN ["chmod", "+x", "/tmp/init.sh"]

# FREE UP SPACE
RUN apt clean -yqq
RUN apt autoclean -yqq

ENTRYPOINT ["/tmp/init.sh"]
