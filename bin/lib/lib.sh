#!/bin/bash

# shellcheck disable=SC1091

set -eo pipefail

if ! command -v docker >/dev/null 2>&1; then
  echo "[ERROR] docker command not found. Please install Docker." >&2
  exit 1
fi

source "${PARENT_PATH}/../config.sh"
source "${PARENT_PATH}/../paths.sh"

COMPOSE_COMMAND=" \
  HOST_UID=$(id -u) \
  HOST_GID=$(id -g) \
  docker compose \
  --env-file ${COMPOSE_PATH}/.env \
  -f ${COMPOSE_PATH}/docker-compose.yaml"

export COMPOSE_COMMAND
