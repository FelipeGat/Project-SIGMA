# Imagem mínima para services/mission-worker — Release 6B (Planner
# Implementation). Sem porta exposta: é um worker, não uma API.
#
# Consome mission.planned, publicado pelo Planner Engine, e cria a
# Mission correspondente. Não copia packages/planner-engine: o
# acoplamento é o payload do evento, nunca código (ADR-0092/0096).
FROM php:8.2-cli-alpine

RUN docker-php-ext-install pdo_mysql

WORKDIR /app

COPY packages/core packages/core
COPY packages/kernel packages/kernel
COPY packages/mission-engine packages/mission-engine
COPY services/event-bus services/event-bus
COPY services/mission-worker services/mission-worker
COPY system-manifest.yaml system-manifest.yaml

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cd services/mission-worker \
    && composer install --no-dev --no-interaction --optimize-autoloader

WORKDIR /app/services/mission-worker

CMD ["php", "bin/worker.php"]
