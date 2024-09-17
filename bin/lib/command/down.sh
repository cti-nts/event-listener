#!/bin/bash

# shellcheck enable=require-variable-braces disable=SC1091

set -eo pipefail

PARENT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")"
  pwd -P
)

source "${PARENT_PATH}"/../lib.sh

eval "${COMPOSE_COMMAND} down $*"
