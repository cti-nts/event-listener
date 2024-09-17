#!/bin/bash

# shellcheck enable=require-variable-braces disable=SC1091

set -eo pipefail

if [[ ${SKIP_COMPOSER_INSTALL} -eq 1 ]]; then
  echo "SKIP_COMPOSER_INSTALL is true. Skipping composer install."
  exit 0
fi

runuser -l hostuser -c "composer install"
