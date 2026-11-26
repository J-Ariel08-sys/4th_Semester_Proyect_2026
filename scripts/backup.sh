#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
umask 077
mkdir -p .local/backups
backup_path=".local/backups/siclin-$(date -u +%Y%m%dT%H%M%SZ).dump"
docker compose exec -T postgres-db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$backup_path"
printf 'Copia de PostgreSQL: %s\nConserva también .env y el volumen portal_storage en un lugar privado.\n' "$backup_path"
