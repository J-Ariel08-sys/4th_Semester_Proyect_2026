#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
python3 scripts/init-env.py
docker compose config --quiet
docker compose build app
docker compose build web
docker compose up -d --wait --wait-timeout 180
docker compose exec -T app php artisan siclin:setup --demo --no-interaction
mkdir -p .local
chmod 700 .local
docker compose cp app:/app/storage/app/private/demo-accounts.json .local/demo-accounts.json 2>/dev/null || true
if [ -f .local/demo-accounts.json ]; then chmod 600 .local/demo-accounts.json; fi
printf '\nPortal: http://localhost:8000\nCorreo local: http://localhost:8025\nCuentas: .local/demo-accounts.json (si se crearon en este arranque)\n'
