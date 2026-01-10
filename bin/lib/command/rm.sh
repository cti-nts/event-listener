#!/bin/bash

# shellcheck disable=SC1091

set -euo pipefail

PARENT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")"
  pwd -P
)

source "${PARENT_PATH}/../lib.sh"

run_docker_compose rm --stop --force "$@"
