#!/bin/bash

set -eo pipefail

if [[ -z "${HOST_UID:-}" ]]; then
  echo "ERROR: please set the HOST_UID environment variable" >&2
  exit 1
fi

if [[ -z "${HOST_GID:-}" ]]; then
  echo "ERROR: please set the HOST_GID environment variable" >&2
  exit 1
fi

if getent passwd hostuser >/dev/null 2>&1; then
  userdel hostuser
fi

if getent group hostgroup >/dev/null 2>&1; then
  groupdel hostgroup
fi

addgroup --gid "${HOST_GID}" hostgroup
adduser --uid "${HOST_UID}" --gid "${HOST_GID}" --gecos "" --home /var/www --disabled-password hostuser
