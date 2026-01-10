#!/bin/bash

set -euo pipefail

readonly ENV_PATH="${PARENT_PATH}/../../../ops/envs/${ENVIRONMENT}"
readonly COMPOSE_PATH="${ENV_PATH}/comp"
export COMPOSE_PATH
