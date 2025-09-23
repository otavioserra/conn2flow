#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
INSTALADOR_DIR="$REPO_ROOT/gestor-instalador"
# Diretório de saída agora aponta para repositório docker-test externo
DOCKER_ENV_ROOT="$REPO_ROOT/dev-environment/data"
OUT_DIR="$DOCKER_ENV_ROOT/sites/localhost/conn2flow-github"
mkdir -p "$OUT_DIR"
TMP_DIR="$(mktemp -d)"
cleanup(){ rm -rf "$TMP_DIR" || true; }
trap cleanup EXIT

# Copiar instalador para tmp para poder fazer limpezas antes de zipar sem afetar workspace
cp -a "$INSTALADOR_DIR" "$TMP_DIR/instalador"
cd "$TMP_DIR/instalador"

# Limpezas conforme workflow do release-instalador.yml
rm -rf .git* 2>/dev/null || true
find . -maxdepth 4 -type f -name "*.DS_Store" -delete 2>/dev/null || true
find . -maxdepth 4 -type f -name "*.log" -delete 2>/dev/null || true
rm -rf temp/ 2>/dev/null || true
rm -f .env.debug 2>/dev/null || true

cd "$TMP_DIR/instalador"

ARCHIVE_CMD="zip"
if command -v 7z >/dev/null 2>&1; then ARCHIVE_CMD="7z"; fi

if [ "$ARCHIVE_CMD" = "7z" ]; then
  echo "[local-build-instalador] Usando 7z" >&2
  # 7z não suporta exclusões globais idênticas ao zip -x, então usamos -xr!
  # Estratégia: adicionar tudo e excluir padrões.
  7z a -tzip instalador.zip \
    -xr!'.git*' \
    -xr!'*.DS_Store*' \
    -xr!'*.log*' \
    -xr!'temp' \
    -xr!'.env.debug'
else
  echo "[local-build-instalador] Usando zip" >&2
  zip -r instalador.zip . \
    -x "*.git*" \
    -x "*.DS_Store*" \
    -x "*.log*" \
    -x "temp/*" \
    -x ".env.debug"
fi

if command -v sha256sum >/dev/null 2>&1; then
  sha256sum instalador.zip | awk '{print $1}' > instalador.zip.sha256
elif command -v certutil >/dev/null 2>&1; then
  certutil -hashfile instalador.zip SHA256 | sed -n '2p' | tr -d '\r\n' > instalador.zip.sha256
else
  echo "[local-build-instalador] ERRO: nenhuma ferramenta sha256 encontrada" >&2
  exit 1
fi
mv instalador.zip instalador.zip.sha256 "$OUT_DIR"/

echo "[local-build-instalador] Artefatos gerados em: $OUT_DIR" >&2
ls -l "$OUT_DIR" | sed 's/^/[local-build-instalador] /'

docker exec conn2flow-app bash -c "chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github/"
