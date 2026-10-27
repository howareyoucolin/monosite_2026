#!/usr/bin/env bash
# Create a fresh local WordPress installation, then activate the custom theme.
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
if [[ -f .env ]]; then
    source ./.env
fi

docker compose up -d --build --wait db wordpress phpmyadmin
if ! docker compose run --rm wpcli core is-installed; then
    docker compose run --rm wpcli core install \
        --url=http://localhost:8400 \
        --title=Lexington116 \
        --admin_user="${LOCAL_ADMIN_USER:-admin}" \
        --admin_password="${LOCAL_ADMIN_PASSWORD:-lexington116-local-only}" \
        --admin_email="${LOCAL_ADMIN_EMAIL:-admin@lexington116.test}" \
        --skip-email
fi
docker compose run --rm wpcli theme activate lexington116_2026
printf '\nLexington116 is ready at http://localhost:8400 (admin: /wp-admin).\n'
