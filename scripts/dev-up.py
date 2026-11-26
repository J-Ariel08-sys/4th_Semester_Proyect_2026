#!/usr/bin/env python3
"""Start the working instance of SICLIN."""
from pathlib import Path
import subprocess
import sys

root = Path(__file__).resolve().parent.parent
subprocess.run([sys.executable, "scripts/init-env.py"], cwd=root, check=True)

for args in (
    ["config", "--quiet"],
    ["build", "app"],
    ["build", "web"],
    ["up", "-d", "--wait", "--wait-timeout", "180"],
):
    subprocess.run(["docker", "compose", *args], cwd=root, check=True)

print("\nPortal: http://localhost:8000\nCorreo local: http://localhost:8025")
