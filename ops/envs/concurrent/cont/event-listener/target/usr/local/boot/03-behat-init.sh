#!/bin/bash

# shellcheck enable=require-variable-braces disable=SC1091

set -eo pipefail

if [[ ${SKIP_COMPOSER_INSTALL} -eq 1 ]]; then
  echo "SKIP_COMPOSER_INSTALL is true. Skipping behat init."
  exit 0
fi

if [[ -f vendor/bin/behat ]]; then
  runuser -l hostuser -c "vendor/bin/behat --init"
fi
