# Imagem mínima para services/memory-worker — Release 4B (Memory
# Infrastructure). Sem porta exposta: é um worker, não uma API.
FROM php:8.2-cli-alpine

RUN docker-php-ext-install pdo_mysql

WORKDIR /app

COPY packages/core packages/core
COPY packages/kernel packages/kernel
COPY packages/memory-engine packages/memory-engine
COPY services/event-bus services/event-bus
COPY services/memory-worker services/memory-worker
COPY system-manifest.yaml system-manifest.yaml

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cd services/memory-worker \
    && composer install --no-dev --no-interaction --optimize-autoloader

WORKDIR /app/services/memory-worker

CMD ["php", "bin/worker.php"]
