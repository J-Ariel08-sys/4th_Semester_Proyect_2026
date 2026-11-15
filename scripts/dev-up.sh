#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
if [ ! -f portal/.env ]; then
  cp portal/.env.example portal/.env
fi
docker compose up -d --build
docker compose exec -T portal php artisan siclin:setup --demo
docker compose restart portal
printf '\nPortal: http://localhost:8000\nCorreo de prueba: http://localhost:8025\nGuía: docs/PRUEBAS_FUNCIONALES.md\n'
