FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev unzip \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . .
RUN printf '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/startup.conf \
    && a2enconf startup \
    && printf 'display_errors=Off\nlog_errors=On\nexpose_php=Off\nsession.cookie_httponly=1\nsession.cookie_samesite=Lax\nsession.use_strict_mode=1\n' \
    > /usr/local/etc/php/conf.d/startup.ini
EXPOSE 80
