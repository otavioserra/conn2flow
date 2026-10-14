"""Validate cover format, manifest integrity, strict sizes and expected ownership."""
import hashlib
import json
import sys
from pathlib import Path
from PIL import Image

expected = [('dashboard-pages', 'interface'), ('ai-agents', 'ai-assistant', 'ai-images', 'ai-admin', 'ai-fields', 'arquivos-api')]
reports = []
assert len(sys.argv) == 3, 'Provide Core and Site roots'
for root, wanted in zip(map(Path, sys.argv[1:]), expected):
    manifest = json.loads((root / 'gestor/assets/modulos/covers/manifest.json').read_text(encoding='utf-8'))
    assets = manifest['assets']
    assert len({a['id'] for a in assets}) == len(assets), 'Duplicate module IDs'
    assert set(wanted) <= {a['id'] for a in assets}, 'Missing new assets'
    for a in assets:
        file = root / a['path']
        payload = file.read_bytes()
        assert len(payload) == a['bytes'], f"Size mismatch {file}"
        assert hashlib.sha256(payload).hexdigest() == a['sha256'], f"Hash mismatch {file}"
        with Image.open(file) as im:
            assert im.format == 'WEBP' and im.size == (1024, 1024), file
        assert len(payload) < 120000, file
        if a['id'] in wanted:
            assert 60000 <= len(payload) <= 100000, file
    reports.append({'root': str(root.resolve()), 'all_covers_verified': len(assets), 'new_covers': [a for a in assets if a['id'] in wanted]})
print(json.dumps({'result': 'pass', 'repositories': reports}, indent=2))
