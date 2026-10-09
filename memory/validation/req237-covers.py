"""Validate deliverable bytes, manifest integrity and req-237 cover counts."""
import hashlib
import json
import sys
from pathlib import Path
from PIL import Image

results = []
for directory, expected in zip(map(Path, sys.argv[1:]), [19, 14]):
    manifest = json.loads((directory / 'gestor/assets/modulos/covers/manifest.json').read_text(encoding='utf-8'))
    added = [asset for asset in manifest['assets'] if asset.get('request') == 'req-237']
    assert len(added) == expected, (directory, len(added), expected)
    assert len({asset['id'] for asset in manifest['assets']}) == len(manifest['assets'])
    for asset in manifest['assets']:
        path = directory / asset['path']
        data = path.read_bytes()
        assert len(data) == asset['bytes']
        assert hashlib.sha256(data).hexdigest() == asset['sha256']
        with Image.open(path) as image:
            assert image.format == 'WEBP' and image.size == (1024, 1024)
        if asset in added:
            assert len(data) < 100000
    results.append({'repository': str(directory.resolve().name), 'new_covers': len(added),
                    'all_covers_verified': len(manifest['assets']),
                    'max_new_bytes': max(asset['bytes'] for asset in added)})
assert len(results) == 2, 'Pass Core and Site repository roots.'
print(json.dumps(results, indent=2))
