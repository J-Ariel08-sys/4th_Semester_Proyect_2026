#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
if [[ $# -gt 1 || ( $# -eq 1 && "$1" != "--reset" ) ]]; then
  printf 'Uso: ./scripts/demo-up.sh [--reset]\n--reset reinicia solo la demo y guarda antes una copia de su base.\n' >&2
  exit 2
fi
python3 scripts/init-env.py
compose=(docker compose -p siclin-demo -f docker-compose.demo.yml)
"${compose[@]}" config --quiet
docker compose build app
docker compose build web
"${compose[@]}" up -d --wait --wait-timeout 180
"${compose[@]}" stop queue-mail queue-optimization scheduler
trap '"${compose[@]}" start queue-mail queue-optimization scheduler >/dev/null' EXIT
mkdir -p .local/demo-backups
chmod 700 .local .local/demo-backups
options=()
if [[ "${1:-}" == "--reset" ]]; then
  umask 077
  backup=".local/demo-backups/before-reset-$(date +%Y%m%d-%H%M%S).dump"
  "${compose[@]}" exec -T postgres-db pg_dump -U siclin_demo -d siclin_demo -Fc > "$backup"
  printf 'Copia de la base de demostración: %s\n' "$backup"
  options+=(--reset)
fi
"${compose[@]}" exec -T app php artisan siclin:demo "${options[@]}" --no-interaction
"${compose[@]}" cp app:/app/storage/app/private/demo-accounts.json .local/presentation-accounts.json
chmod 600 .local/presentation-accounts.json
printf '\nDemo: http://localhost:8010\nCorreo de la demo: http://localhost:8030\nCuentas y contraseñas: .local/presentation-accounts.json\nRecorridos: docs/GUIA_DEMOS.md y la opción Guía de demostración dentro del portal.\n'
