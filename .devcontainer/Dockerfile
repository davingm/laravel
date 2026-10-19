FROM mcr.microsoft.com/devcontainers/php:8.3-bookworm

USER root

RUN apt-get update \
    && apt-get install --yes --no-install-recommends libsqlite3-dev \
    && if ! php -m | grep -qi '^pdo_sqlite$'; then docker-php-ext-install pdo_sqlite; fi \
    && if ! php -m | grep -qi '^sqlite3$'; then docker-php-ext-install sqlite3; fi \
    && rm -rf /var/lib/apt/lists/*

USER vscode
