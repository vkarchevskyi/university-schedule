#!/bin/sh
set -e

if [ "${SKIP_JWT_KEY_GENERATION:-0}" != "1" ]; then
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

exec "$@"
