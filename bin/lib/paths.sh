#!/bin/bash

# shellcheck enable=require-variable-braces

set -eo pipefail

ENV_PATH=${PARENT_PATH}/../../../ops/envs/${ENVIRONMENT}
# export LIB_PATH=${PARENT_PATH}/..
export COMPOSE_PATH=${ENV_PATH}/comp
# export CONTAINER_PATH=${ENV_PATH}/cont
