# Imagem mínima para services/auth — Release 3B (Identity Infrastructure).
# Primeira imagem do projeto com extensão de banco de dados
# (pdo_mysql) — MariaDB entra no ambiente a partir desta Release.
FROM php:8.2-cli-alpine

RUN docker-php-ext-install pdo_mysql

WORKDIR /app

COPY packages/core packages/core
COPY packages/kernel packages/kernel
COPY packages/identity-engine packages/identity-engine
COPY services/event-bus services/event-bus
COPY services/auth services/auth
COPY system-manifest.yaml system-manifest.yaml

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cd services/auth \
    && composer install --no-dev --no-interaction --optimize-autoloader

WORKDIR /app/services/auth

EXPOSE 8081

CMD ["php", "-S", "0.0.0.0:8081", "-t", "public"]
