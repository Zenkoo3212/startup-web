FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . .
RUN printf '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/startup.conf \
    && a2enconf startup \
    && printf 'display_errors=Off\nlog_errors=On\nexpose_php=Off\nsession.cookie_httponly=1\nsession.cookie_samesite=Lax\nsession.use_strict_mode=1\n' \
    > /usr/local/etc/php/conf.d/startup.ini
EXPOSE 80
