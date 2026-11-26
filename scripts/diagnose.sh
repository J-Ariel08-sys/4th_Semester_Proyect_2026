#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
docker compose config --quiet
docker compose ps -a
docker compose exec -T app php artisan siclin:health --no-interaction
docker compose exec -T app php artisan migrate:status --no-interaction
docker compose exec -T postgres-db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "select queue,count(*) as pendientes from jobs group by queue"'
curl --fail --silent --show-error http://localhost:8000/up >/dev/null
curl --fail --silent --show-error http://localhost:8025/api/v1/info >/dev/null
printf 'Portal, PostgreSQL y Mailpit responden. Revisa la cola de correo y los workers si hay tareas pendientes.\n'
