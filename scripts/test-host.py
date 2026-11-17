#!/usr/bin/env python3
import subprocess, os, sys
from pathlib import Path
root = Path(__file__).resolve().parent.parent
values = dict(line.split('=',1) for line in (root/'.env').read_text().splitlines() if '=' in line and not line.startswith('#'))
cid = subprocess.check_output(['docker','compose','ps','-q','postgres-db'], cwd=root, text=True).strip()
ip = subprocess.check_output(['docker','inspect','-f','{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}',cid],text=True).strip()
env = dict(os.environ, **values)
env.update(DB_HOST=ip, DB_DATABASE='siclin_test', DB_USERNAME='siclin_test_user', DB_PASSWORD=values['TEST_DB_PASSWORD'], APP_ENV='testing')
sys.exit(subprocess.call(['php','artisan','test','--compact','--configuration=phpunit.pgsql.xml',*sys.argv[1:]],cwd=root/'portal',env=env))
