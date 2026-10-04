"""Prepare or remove explicitly named fixtures on the isolated req-225 Lab tenant."""
from pathlib import Path
import json
import subprocess
import sys

root = Path(__file__).resolve().parents[2]
mode = sys.argv[1] if len(sys.argv) > 1 else 'prepare'
if mode not in ('prepare', 'cleanup'):
    raise SystemExit('Usage: python sdd/validation/req225-lab-fixtures.py [prepare|cleanup]')
source = Path(__file__).with_suffix('.php').read_text(encoding='utf-8')
source = source.replace("$fixtureMode = 'prepare';", "$fixtureMode = '" + mode + "';")
result = subprocess.run(
    ['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15',
     'otavio@lab.conn2flow.local', 'sudo', '-u', 'c2ftest', 'php'],
    input=source.encode('utf-8'), capture_output=True, timeout=60)
if result.returncode:
    print(result.stderr.decode('utf-8', errors='replace'))
    raise SystemExit(result.returncode)
rows = json.loads(result.stdout)
if mode == 'prepare':
    (root / 'temp').mkdir(exist_ok=True)
    (root / 'temp/req225-fixtures.json').write_bytes(result.stdout)
    print('Fixture entries:', sum(len(r) for r in rows.values()))
    print('Created:', sum(int(x['created']) for r in rows.values() for x in r.values()))
else:
    (root / 'temp/req225-fixture-cleanup.json').write_bytes(result.stdout)
    print('Removed:', sum(r['removed'] for r in rows.values()))
