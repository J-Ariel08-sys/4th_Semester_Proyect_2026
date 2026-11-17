#!/usr/bin/env python3
"""Create stable local secrets without printing or replacing existing values."""
from pathlib import Path
import base64, secrets, os
root = Path(__file__).resolve().parent.parent
path = root / '.env'
def read(p):
    return dict(line.split('=', 1) for line in p.read_text().splitlines() if '=' in line and not line.startswith('#')) if p.exists() else {}
values = read(root / '.env.example')
existing = read(path)
if not path.exists():
    old = read(root / 'portal/.env')
    for key in ('APP_KEY', 'DB_PASSWORD', 'DB_DATABASE', 'DB_USERNAME'):
        if old.get(key): values[key] = old[key]
values.update(existing)
for key in ('APP_KEY', 'OTP_SECRET', 'DB_PASSWORD'):
    if not values.get(key):
        values[key] = 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode() if key == 'APP_KEY' else secrets.token_hex(32)
path.touch(mode=0o600, exist_ok=True)
path.write_text(''.join(f'{k}={v}\n' for k,v in values.items()))
os.chmod(path, 0o600)
print('Configuración privada preparada en .env (valores conservados).')
