# Образ IdeaJob: FrankenPHP отдаёт public/ напрямую, без связки nginx + php-fpm.
# Один образ поднимает три роли — сайт, воркер очереди и Reverb (см. compose.yaml).

# --- PHP и зависимости Composer -------------------------------------------------
FROM dunglas/frankenphp:php8.4 AS app

# intl и zip — Filament, pdo_mysql — база, pcntl — корректная остановка queue:work и Reverb.
RUN install-php-extensions \
        intl \
        zip \
        bcmath \
        pdo_mysql \
        pcntl \
        opcache

WORKDIR /app

# Значения по умолчанию, чтобы контейнер поднимался и без внешних переменных.
# compose перекрывает их своими.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=mysql \
    DB_HOST=mysql \
    DB_PORT=3306 \
    DB_DATABASE=ideajob \
    DB_USERNAME=ideajob \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    BROADCAST_CONNECTION=reverb

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Сначала только манифесты — слой с зависимостями переиспользуется между сборками.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY . .

# Скрипты composer пропущены (dev-пакеты не установлены), поэтому нужное — вручную:
# обнаружение пакетов и публикация JS/CSS Filament в public/.
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && php artisan filament:assets \
    && sed -i 's/\r$//' docker/entrypoint.sh \
    && chmod +x docker/entrypoint.sh

# --- Фронтенд: Vite + Tailwind ---------------------------------------------------
FROM node:22-slim AS assets

WORKDIR /app

# Ключ и адрес Reverb вшиваются в JS при сборке — браузер подключается к ним напрямую.
ARG VITE_APP_NAME=IdeaJob
ARG VITE_REVERB_APP_KEY=ideajob-local-key
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http
ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources resources
COPY app/Enums app/Enums
# Tailwind сканирует шаблоны пагинации Laravel из vendor (см. resources/css/app.css).
COPY --from=app /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

# --- Итоговый образ ---------------------------------------------------------------
FROM app

COPY --from=assets /app/public/build public/build

EXPOSE 8000 8080

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["serve"]
