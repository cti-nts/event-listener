#!/bin/bash

set -eo pipefail

runuser -l hostuser -c "composer install"
