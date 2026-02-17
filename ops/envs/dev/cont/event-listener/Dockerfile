FROM phpswoole/swoole:5.1.8-php8.3

ENV BUILD_DEPS="libzip-dev libpq-dev inotify-tools"
ENV PHP_EXT="zip pgsql pdo_pgsql"

RUN \
  set -ex && \
  apt-get update && \
  apt-get install -y --no-install-recommends ${BUILD_DEPS} librdkafka-dev && \
  docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
  docker-php-ext-install -j$(nproc) ${PHP_EXT} && \
  pecl install rdkafka && \
  docker-php-ext-enable rdkafka && \
  rm -rf /var/lib/apt/lists/*

COPY target /

ENV TZ=Europe/Athens

RUN \
  set -ex && \
  ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && \
  echo ${TZ} > /etc/timezone && \
  mv "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini" && \
  grep -q ";date.timezone =" "${PHP_INI_DIR}/php.ini" && \
  sed -ri -e "s!;date.timezone =!date.timezone = \"${TZ}\"!g" "${PHP_INI_DIR}/php.ini"
