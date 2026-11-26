#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
python3 scripts/init-env.py
python3 scripts/prepare-test-db.py
compose=(docker compose -p siclin-e2e -f docker-compose.e2e.yml)
"${compose[@]}" up -d --force-recreate --wait --wait-timeout 180
"${compose[@]}" stop queue-mail queue-optimization
"${compose[@]}" exec -T app php artisan siclin:e2e-reset --no-interaction
"${compose[@]}" exec -T app php artisan db:seed --class=ClinicalDemoSeeder --no-interaction
"${compose[@]}" start queue-mail queue-optimization
mkdir -p .local
"${compose[@]}" cp app:/app/storage/app/private/demo-accounts.json .local/e2e-accounts.json
chmod 600 .local/e2e-accounts.json
cd tests/browser
npm ci --ignore-scripts
npx playwright install chromium
npx playwright test
