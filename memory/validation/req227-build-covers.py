"""Optimize req-227 generated originals and produce a verifiable asset catalog.

Usage: python sdd/validation/req227-build-covers.py <sources.json>
sources.json: [{"id": "dashboard", "path": "...", "prompt": "..."}, ...]
Originals remain untouched. No runtime mirrors or databases are written.
"""

import hashlib
import io
import json
import sys
from pathlib import Path

from PIL import Image

CORE = Path(__file__).resolve().parents[2]
SITE = CORE.parent / "conn2flow-site"
SITE_IDS = {
    "products", "products-index", "orders", "checkout", "subscriptions",
    "3d-catalog", "publisher-social-media", "marketplace-admin",
    "coupons", "affiliates", "affiliate-area", "subscriptions-plans",
    "product-reviews", "shipping-methods", "gateways-pagamentos", "3d-catalog-items",
    "presentations", "social-apps", "social-connections", "host-manager",
    "modulos-grupos-distribuido", "marketplace", "documentation",
}
CORE_IDS = {
    "dashboard", "admin-paginas", "admin-templates", "admin-componentes",
    "menus", "forms", "forms-submissions", "usuarios", "usuarios-perfis",
    "admin-arquivos", "galleries", "admin-ia", "admin-prompts-ia", "admin-cron",
}
LIMIT = 120_000


def main():
    sources = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8-sig"))
    ids = [item["id"] for item in sources]
    if len(ids) != len(set(ids)) or set(ids) != CORE_IDS | SITE_IDS:
        raise ValueError("Expected exactly the 37 distinct req-227 + site req-102 module ids")

    prepared = []
    for item in sources:
        module_id = item["id"]
        repo = SITE if module_id in SITE_IDS else CORE
        target = repo / "gestor/assets/modulos/covers" / (module_id + ".webp")
        with Image.open(item["path"]) as original:
            original.load()
            if original.width != original.height or original.width < 1024:
                raise ValueError(f"Invalid square working original for {module_id}")
            image = original.convert("RGB").resize((1024, 1024), Image.Resampling.LANCZOS)
        for quality in range(90, 59, -2):
            buffer = io.BytesIO()
            image.save(buffer, format="WEBP", quality=quality, method=6)
            data = buffer.getvalue()
            if len(data) < LIMIT:
                break
        else:
            raise ValueError(f"Cannot meet size budget for {module_id}")
        with Image.open(io.BytesIO(data)) as decoded:
            decoded.load()
            if decoded.format != "WEBP" or decoded.size != (1024, 1024):
                raise ValueError(f"Failed WebP integrity check for {module_id}")
        if target.exists() and target.read_bytes() != data:
            raise FileExistsError(f"Refusing to overwrite existing asset: {target}")
        record = {
            "id": module_id, "repository": repo.name,
            "path": target.relative_to(repo).as_posix(),
            "width": 1024, "height": 1024, "bytes": len(data),
            "quality": quality, "sha256": hashlib.sha256(data).hexdigest(),
            "prompt": item.get("prompt", ""),
        }
        prepared.append((target, data, record))

    if len({record["sha256"] for _, _, record in prepared}) != len(prepared):
        raise ValueError("Duplicate image content across module ids")

    records = []
    for target, data, record in prepared:
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(data)
        records.append(record)

    for repo in (CORE, SITE):
        manifest = {
            "version": "1.0.0", "requests": ["req-227", "site/req-102"], "date": "2026-10-04",
            "generator": "built-in image_gen", "max_bytes_exclusive": LIMIT,
            "assets": [{k: v for k, v in record.items() if k != "prompt"}
                       for record in records if record["repository"] == repo.name],
        }
        (repo / "gestor/assets/modulos/covers/manifest.json").write_text(
            json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
        )

    evidence = CORE / "sdd/validation/req227-covers.json"
    evidence.write_text(json.dumps(records, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    catalog = [
        "# Catálogo de capas — req-227 / BATCH-236 e site REQ-102 / BATCH-096", "",
        "37 capas individuais, 1024 × 1024 px, geradas com `image_gen` e convertidas em WebP.", "",
        "Identidade: 3D isométrico, vidro fosco e cerâmica, azul profundo, ciano e violeta, luz superior esquerda, sem texto.", "",
        "Prompts finais, tamanho, qualidade e SHA-256: [req227-covers.json](req227-covers.json).",
        "Originais de trabalho preservados no diretório de imagens geradas do Codex; não são dependência de execução.", "",
        "| Miniatura | Módulo | Repositório | Bytes |", "| --- | --- | --- | --- |",
    ]
    for record in records:
        prefix = "../../" if record["repository"] == CORE.name else "../../../conn2flow-site/"
        link = prefix + record["path"]
        catalog.append(f'| <img src="{link}" width="160" height="160" alt="{record["id"]}"> | `{record["id"]}` | `{record["repository"]}` | {record["bytes"]:,} |')
    (CORE / "sdd/validation/req227-catalog.md").write_text("\n".join(catalog) + "\n", encoding="utf-8")
    site_catalog = [
        "# Galeria de capas — REQ-102 / BATCH-096", "",
        "23 capas do site; a mesma referência 3D, iluminação e paleta da req-227 do core.", "",
        "Catálogo conjunto, prompts e evidências: [galeria do core](../../../conn2flow/sdd/validation/req227-catalog.md).", "",
        "| Miniatura | Módulo | Bytes |", "| --- | --- | --- |",
    ]
    for record in records:
        if record["repository"] != SITE.name:
            continue
        link = "../../" + record["path"]
        site_catalog.append(f'| <img src="{link}" width="160" height="160" alt="{record["id"]}"> | `{record["id"]}` | {record["bytes"]:,} |')
    (SITE / "sdd/validation/req102-catalog.md").write_text("\n".join(site_catalog) + "\n", encoding="utf-8")
    print(json.dumps({"count": len(records), "max_bytes": max(r["bytes"] for r in records),
                      "total_bytes": sum(r["bytes"] for r in records)}, indent=2))


if __name__ == "__main__":
    main()
