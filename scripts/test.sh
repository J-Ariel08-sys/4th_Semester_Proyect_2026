#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")/.."
python3 scripts/prepare-test-db.py
python3 - "$@" <<'PY'
from pathlib import Path
import subprocess,sys,os
values=dict(l.split('=',1) for l in Path('.env').read_text().splitlines() if '=' in l and not l.startswith('#'))
env=dict(os.environ, DB_PASSWORD=values['TEST_DB_PASSWORD'])
command=['docker','compose','exec','-T','-e','APP_ENV=testing','-e','DB_DATABASE=siclin_test','-e','DB_USERNAME=siclin_test_user','-e','DB_PASSWORD','app','php','vendor/bin/phpunit','--configuration=phpunit.pgsql.xml',*sys.argv[1:]]
sys.exit(subprocess.call(command,env=env))
PY
