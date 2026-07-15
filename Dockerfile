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
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

RUN printf 'server {\n\
    listen ${PORT};\n\
    root /var/www/public;\n\
    index index.php;\n\
    location / { try_files $uri $uri/ /index.php?$query_string; }\n\
    location ~ \\.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
    }\n\
}\n' > /etc/nginx/sites-available/default.template

RUN printf '[supervisord]\nnodaemon=true\n\n\
[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\n\n\
[program:nginx]\ncommand=nginx -g "daemon off;"\nautostart=true\nautorestart=true\n' \
> /etc/supervisor/conf.d/app.conf

# Startup: render nginx config dengan PORT dari platform (Render/Railway),
# jalankan migrasi, baru start supervisord
RUN printf '#!/bin/sh\n\
export PORT="${PORT:-80}"\n\
envsubst "\\$PORT" < /etc/nginx/sites-available/default.template > /etc/nginx/sites-available/default\n\
php artisan config:clear\n\
php artisan migrate --force || true\n\
php artisan storage:link || true\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf\n' > /start.sh \
    && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
