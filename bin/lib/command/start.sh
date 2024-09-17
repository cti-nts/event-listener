#!/bin/bash

# shellcheck disable=SC1091

set -eo pipefail

PARENT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")"
  pwd -P
)

source "${PARENT_PATH}/../lib.sh"
source "${PARENT_PATH}/build.sh"

eval "${COMPOSE_COMMAND} start $*"
