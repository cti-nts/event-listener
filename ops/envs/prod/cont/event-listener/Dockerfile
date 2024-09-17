FROM phpswoole/swoole:5.1.5-php8.3

ENV LIBRDKAFKA_VERSION 2.3.0

ENV BUILD_DEPS \
  libpcre3-dev \
  libzip-dev \
  libpq-dev

ENV PHP_EXT \
  zip \
  pgsql \
  pdo_pgsql

RUN \
  set -ex && \
  apt-get update && \
  apt-get install -y --no-install-recommends ${BUILD_DEPS} && \
  docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
  docker-php-ext-install -j$(nproc) ${PHP_EXT} && \
  rm -rf /var/lib/apt/lists/*

RUN \
  set -ex && \
  cd /tmp && \
  curl -LO https://github.com/confluentinc/librdkafka/archive/refs/tags/v${LIBRDKAFKA_VERSION}.tar.gz && \
  tar -xzf v${LIBRDKAFKA_VERSION}.tar.gz && \
  cd librdkafka-${LIBRDKAFKA_VERSION} && \
  ./configure && \
  make -j$(nproc) && \
  make install && \
  pecl install rdkafka && \
  docker-php-ext-enable rdkafka && \
  rm -rf /tmp/librdkafka-${LIBRDKAFKA_VERSION} v${LIBRDKAFKA_VERSION}.tar.gz

COPY dev /var/www

ENV TZ=Europe/Athens

RUN \
  set -ex && \
  ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && \
  echo ${TZ} > /etc/timezone && \
  mv "${PHP_INI_DIR}/php.ini-production" "${PHP_INI_DIR}/php.ini" && \
  sed -ri -e 's!;date.timezone =!date.timezone = "Europe/Athens"!g' "${PHP_INI_DIR}/php.ini" && \
  composer install --no-ansi --no-cache --no-dev --no-interaction --no-progress --optimize-autoloader
