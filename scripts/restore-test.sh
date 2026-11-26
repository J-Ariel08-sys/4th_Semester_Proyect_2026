#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
backup_path="${1:?Uso: scripts/restore-test.sh archivo.dump}"
restore_database="siclin_restore_$(date -u +%Y%m%d%H%M%S)_test"
test -f "$backup_path"
docker compose exec -T postgres-db sh -c 'createdb -U "$POSTGRES_USER" "$1"' _ "$restore_database"
docker compose exec -T postgres-db sh -c 'pg_restore --exit-on-error -U "$POSTGRES_USER" -d "$1"' _ "$restore_database" < "$backup_path"
docker compose exec -T postgres-db sh -c 'psql -U "$POSTGRES_USER" -d "$1" -c "select count(*) as usuarios_restaurados from users"' _ "$restore_database"
printf 'Restauración de prueba: %s. La base de trabajo no se modificó.\n' "$restore_database"
