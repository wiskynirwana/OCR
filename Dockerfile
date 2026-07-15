FROM php:8.4-fpm

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libpq-dev \
    tesseract-ocr tesseract-ocr-ind tesseract-ocr-eng \
    python3 python3-pip python3-opencv \
    nodejs nginx supervisor gettext-base \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

RUN pip3 install numpy opencv-python-headless --break-system-packages

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

RUN npm install && npm run build

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# HF Spaces menjalankan container sebagai user non-root (uid 1000),
# jadi semua path runtime nginx/supervisor harus writable
RUN mkdir -p /tmp/nginx && chmod -R 777 /var/lib/nginx /var/log/nginx /var/log/supervisor 2>/dev/null; true

# nginx.conf lengkap (bukan cuma server block) supaya pid & temp path
# bisa ditulis oleh user non-root
RUN printf 'pid /tmp/nginx.pid;\n\
error_log /dev/stderr;\n\
events { worker_connections 1024; }\n\
http {\n\
    include /etc/nginx/mime.types;\n\
    access_log /dev/stdout;\n\
    client_body_temp_path /tmp/nginx/body;\n\
    proxy_temp_path /tmp/nginx/proxy;\n\
    fastcgi_temp_path /tmp/nginx/fastcgi;\n\
    uwsgi_temp_path /tmp/nginx/uwsgi;\n\
    scgi_temp_path /tmp/nginx/scgi;\n\
    client_max_body_size 20m;\n\
    server {\n\
        listen ${PORT};\n\
        root /var/www/public;\n\
        index index.php;\n\
        location / { try_files $uri $uri/ /index.php?$query_string; }\n\
        location ~ \\.php$ {\n\
            fastcgi_pass 127.0.0.1:9000;\n\
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
            include fastcgi_params;\n\
        }\n\
    }\n\
}\n' > /etc/nginx/nginx.conf.template

RUN printf '[supervisord]\nnodaemon=true\nlogfile=/tmp/supervisord.log\npidfile=/tmp/supervisord.pid\n\n\
[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\n\
stdout_logfile=/dev/stdout\nstdout_logfile_maxbytes=0\nstderr_logfile=/dev/stderr\nstderr_logfile_maxbytes=0\n\n\
[program:nginx]\ncommand=nginx -c /etc/nginx/nginx.conf -g "daemon off;"\nautostart=true\nautorestart=true\n\
stdout_logfile=/dev/stdout\nstdout_logfile_maxbytes=0\nstderr_logfile=/dev/stderr\nstderr_logfile_maxbytes=0\n' \
> /etc/supervisor/conf.d/app.conf

# php-fpm listen di TCP & jalan tanpa root
RUN sed -i 's|^listen = .*|listen = 127.0.0.1:9000|' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's|^user = .*|;user = www-data|; s|^group = .*|;group = www-data|' /usr/local/etc/php-fpm.d/www.conf

# Startup: render nginx config dengan PORT (HF Spaces = 7860),
# siapkan SQLite bila dipakai, jalankan migrasi, baru start supervisord
RUN printf '#!/bin/sh\n\
export PORT="${PORT:-7860}"\n\
mkdir -p /tmp/nginx\n\
envsubst "\\$PORT" < /etc/nginx/nginx.conf.template > /tmp/nginx.conf.rendered\n\
cp /tmp/nginx.conf.rendered /etc/nginx/nginx.conf || true\n\
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then\n\
  touch /var/www/database/database.sqlite\n\
  chmod 666 /var/www/database/database.sqlite\n\
fi\n\
php artisan config:clear\n\
php artisan migrate --force || true\n\
php artisan storage:link || true\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf\n' > /start.sh \
    && chmod +x /start.sh \
    && chmod 777 /var/www/database \
    && chmod 777 /etc/nginx /etc/nginx/nginx.conf 2>/dev/null; true

EXPOSE 7860

CMD ["/start.sh"]
