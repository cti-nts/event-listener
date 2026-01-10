#!/bin/bash

set -euo pipefail

if [[ -z "${ENVIRONMENT:-}" ]]; then
  readonly ENVIRONMENT=${1:-}
  shift 1 || true
fi
