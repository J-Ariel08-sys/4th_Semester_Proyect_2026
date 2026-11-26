#!/usr/bin/env python3
"""Prepare only the isolated test database, never reset the working database."""
import subprocess, secrets
from pathlib import Path
root = Path(__file__).resolve().parent.parent
path = root / '.env'
values = dict(line.split('=',1) for line in path.read_text().splitlines() if '=' in line and not line.startswith('#'))
password = values.get('TEST_DB_PASSWORD') or secrets.token_hex(24)
if not values.get('TEST_DB_PASSWORD'):
    with path.open('a') as f: f.write('TEST_DB_PASSWORD='+password+'\n')
assert all(c in '0123456789abcdef' for c in password)
sql = fr"""
SELECT 'CREATE ROLE siclin_test_user LOGIN PASSWORD ''{password}'''
WHERE NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'siclin_test_user')\gexec
SELECT 'CREATE DATABASE siclin_test OWNER siclin_test_user'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'siclin_test')\gexec
REVOKE ALL ON DATABASE {values['DB_DATABASE']} FROM siclin_test_user;
"""
subprocess.run(['docker','compose','exec','-T','postgres-db','psql','-v','ON_ERROR_STOP=1','-U',values['DB_USERNAME'],'-d','postgres'],input=sql,text=True,cwd=root,check=True,stdout=subprocess.DEVNULL)
print('Base de pruebas siclin_test y usuario aislado preparados.')
