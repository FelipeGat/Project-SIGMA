# Imagem para services/gateway. Ganhou pdo_mysql na Release 5.5, quando
# o gateway passou a registrar identity-engine (para validar a Session
# de quem chama) e mission-engine (para atender POST /missions e
# GET /missions/{id}) — até a Release 5C não havia nenhuma entidade de
# domínio exposta aqui (ver ADR-0053 e docs/releases/0005.5-vertical-slice.md).
FROM php:8.2-cli-alpine

RUN docker-php-ext-install pdo_mysql

WORKDIR /app

COPY packages/core packages/core
COPY packages/kernel packages/kernel
COPY packages/identity-engine packages/identity-engine
COPY packages/mission-engine packages/mission-engine
COPY services/event-bus services/event-bus
COPY services/gateway services/gateway
COPY system-manifest.yaml system-manifest.yaml

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cd services/gateway \
    && composer install --no-dev --no-interaction --optimize-autoloader

WORKDIR /app/services/gateway

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
