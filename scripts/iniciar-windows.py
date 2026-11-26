"""Arranca SICLIN en Windows e importa su respaldo inicial una sola vez."""

import json
from pathlib import Path
import subprocess
import sys
from tempfile import NamedTemporaryFile

ROOT = Path(__file__).resolve().parent.parent
PROJECT = "siclin-hito4-datos"
COMPOSE = ["docker", "compose", "-p", PROJECT]
SETTINGS = {
    "COMPOSE_PROJECT_NAME": PROJECT,
    "DB_USERNAME": "unifranz_user",
    "DB_DATABASE": "rotes_clinicos_db",
    "WEB_PORT": "18010",
    "MAILPIT_PORT": "18036",
    "APP_URL": "http://localhost:18010",
    "SESSION_COOKIE": "siclin_hito4_datos_session",
}
PSQL = [
    "exec", "-T", "postgres-db", "sh", "-ec",
    'exec psql -X -U "$POSTGRES_USER" -d "$POSTGRES_DB" -v ON_ERROR_STOP=1 "$@"',
    "psql",
]

def run(*args, **options):
    return subprocess.run([*COMPOSE, *args], cwd=ROOT, check=True, **options)

def set_options():
    subprocess.run([sys.executable, ROOT / "scripts/init-env.py"], cwd=ROOT, check=True)
    path = ROOT / ".env"
    lines = path.read_text(encoding="utf-8-sig").splitlines()
    lines = [line for line in lines if line.partition("=")[0] not in SETTINGS]
    lines.extend(f"{key}={value}" for key, value in SETTINGS.items())
    with NamedTemporaryFile(mode="w", encoding="utf-8", dir=ROOT, delete=False) as file:
        file.write("\n".join(lines) + "\n")
    Path(file.name).replace(path)

def restore_once():
    run("up", "-d", "--wait", "--wait-timeout", "180", "postgres-db")
    state = run(*PSQL, "-Atc", """
        SELECT CASE
          WHEN EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = 'migrations') THEN 'ready'
          WHEN EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public') THEN 'unexpected'
          ELSE 'empty'
        END
    """, text=True, stdout=subprocess.PIPE).stdout.strip()
    if state == "ready":
        count = run(*PSQL, "-Atc", "SELECT count(*) FROM public.migrations;",
                    text=True, stdout=subprocess.PIPE).stdout.strip()
        if count != "0":
            return
    if state != "empty":
        raise SystemExit("La base de Hito 4 ya contiene tablas. Se detuvo el inicio para conservarlas.")

    candidates = [ROOT / "copia_siclin.sql", Path.home() / "Downloads/copia_siclin.sql"]
    backup = next((p for p in candidates if p.is_file() and p.stat().st_size), None)
    if backup is None:
        raise SystemExit("Descarga copia_siclin.sql y colócalo junto a INICIAR.bat. Después inicia SICLIN otra vez.")
    print("Restaurando copia_siclin.sql en el volumen de Hito 4...", flush=True)
    with backup.open("rb") as source:
        run(*PSQL, "--single-transaction", stdin=source, stdout=subprocess.DEVNULL)

def main():
    if not (ROOT / "docker-compose.yml").is_file() or not (ROOT / "scripts/init-env.py").is_file():
        raise SystemExit("Extrae este arreglo dentro de la carpeta SICLIN que contiene docker-compose.yml.")
    set_options()
    config = json.loads(run("config", "--format", "json", text=True, stdout=subprocess.PIPE).stdout)
    if config.get("volumes", {}).get("pgdata", {}).get("name") != f"{PROJECT}_pgdata":
        raise SystemExit("No se encontró el volumen separado de PostgreSQL. Se detuvo el inicio.")
    restore_once()
    run("build", "app", "web")
    try:
        run("up", "-d", "--wait", "--wait-timeout", "180")
    except subprocess.CalledProcessError:
        subprocess.run([*COMPOSE, "logs", "--tail=30", "migrate"], cwd=ROOT)
        raise

if __name__ == "__main__":
    try:
        main()
    except FileNotFoundError:
        raise SystemExit("Abre Docker Desktop y vuelve a ejecutar INICIAR.bat.") from None
    except subprocess.CalledProcessError:
        raise SystemExit("SICLIN se detuvo. Revisa el error de Docker que aparece arriba.") from None
