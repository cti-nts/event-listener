#!/bin/bash

set -eo pipefail

ENV_PATH=${PARENT_PATH}/../../../ops/envs/${ENVIRONMENT}
export COMPOSE_PATH=${ENV_PATH}/comp
