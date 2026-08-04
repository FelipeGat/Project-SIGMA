# Imagem mínima para services/gateway — Release 2 (SIGMA Bootstrap).
# Sem extensão de banco de dados: nenhuma entidade de domínio existe
# ainda nesta Release (ver ADR-0053).
FROM php:8.2-cli-alpine

WORKDIR /app

COPY packages/core packages/core
COPY packages/kernel packages/kernel
COPY services/event-bus services/event-bus
COPY services/gateway services/gateway
COPY system-manifest.yaml system-manifest.yaml

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cd services/gateway \
    && composer install --no-dev --no-interaction --optimize-autoloader

WORKDIR /app/services/gateway

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
