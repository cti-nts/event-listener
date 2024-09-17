#!/bin/bash

# shellcheck enable=require-variable-braces

set -eo pipefail

if [[ -z "${ENVIRONMENT}" ]]; then
  ENVIRONMENT=$1
  shift 1 || true
fi
