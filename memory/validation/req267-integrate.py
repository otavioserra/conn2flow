"""Convert generated covers and update manifests. Run with --write to integrate."""
import argparse
import hashlib
import io
import json
from pathlib import Path
from PIL import Image

parser = argparse.ArgumentParser()
parser.add_argument('--write', action='store_true')
args = parser.parse_args()
root = Path(__file__).resolve().parents[2]
parent = root.parent
generation = json.loads((Path(__file__).with_name('req267-generation.json')).read_text(encoding='utf-8'))
result = []
for item in generation['assets']:
    is_core = item['id'] in ('dashboard-pages', 'interface')
    repo = 'conn2flow' if is_core else 'conn2flow-site'
    worktree = root if is_core else parent / 'conn2flow-site-covers-123'
    original = Image.open(item['source']).convert('RGB')
    assert original.width == original.height, item['id']
    cover = original.resize((1024, 1024), Image.Resampling.LANCZOS)
    choices = []
    for quality in range(90, 69, -1):
        stream = io.BytesIO()
        cover.save(stream, format='WEBP', quality=quality, method=6)
        payload = stream.getvalue()
        if 60000 <= len(payload) <= 100000:
            choices.append((quality, payload))
    assert choices, f"No quality 70-90 satisfies size for {item['id']}"
    quality, payload = choices[0]
    entry = dict(id=item['id'], repository=repo, path=f"gestor/assets/modulos/covers/{item['id']}.webp", width=1024, height=1024, bytes=len(payload), quality=quality, sha256=hashlib.sha256(payload).hexdigest())
    for destination in (worktree, parent / repo):
        asset = destination / entry['path']
        if args.write:
            assert not asset.exists() or asset.read_bytes() == payload, f'Existing different asset: {asset}'
            asset.write_bytes(payload)
            with Image.open(asset) as check:
                assert check.format == 'WEBP' and check.size == (1024, 1024)
            manifest_path = asset.parent / 'manifest.json'
            raw = manifest_path.read_bytes()
            manifest = json.loads(raw)
            existing = [a for a in manifest['assets'] if a['id'] == entry['id']]
            assert not existing or existing == [entry], f'Existing different entry: {asset}'
            if not existing:
                manifest['assets'].append(entry)
            req = 'req-267' if is_core else 'site/req-123'
            if req not in manifest['requests']:
                manifest['requests'].append(req)
            manifest['version'] = '1.2.0'
            manifest['date'] = '2026-10-14'
            text = json.dumps(manifest, ensure_ascii=False, indent=2) + '\n'
            if b'\r\n' in raw:
                text = text.replace('\n', '\r\n')
            manifest_path.write_bytes(text.encode('utf-8'))
    result.append(entry)
    print(f"{repo}/{entry['id']}: {len(payload)} bytes, quality {quality}")
if args.write:
    Path(__file__).with_name('req267-assets.json').write_text(json.dumps(result, indent=2) + '\n', encoding='utf-8')
