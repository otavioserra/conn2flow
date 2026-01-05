#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
GESTOR_DIR="$REPO_ROOT/gestor"
# Output directory now points to external docker-test repository
DOCKER_ENV_ROOT="$REPO_ROOT/dev-environment/data"
OUT_DIR="$DOCKER_ENV_ROOT/sites/localhost/conn2flow-github"
mkdir -p "$OUT_DIR"
TMP_DIR="$(mktemp -d)"
cleanup(){ rm -rf "$TMP_DIR" || true; }
trap cleanup EXIT

if [ -f "$GESTOR_DIR/composer.json" ]; then
  echo "[local-build] Installing dependencies (no dev)" >&2
  if command -v composer >/dev/null 2>&1; then
    if ! (cd "$GESTOR_DIR" && composer install --no-dev --optimize-autoloader --no-interaction); then
      echo "[local-build] WARN: composer failed (maybe openssl missing). Continuing with existing vendor." >&2
    fi
  else
    echo "[local-build] Composer not found, skipping install" >&2
  fi
fi

# Copy manager to tmp to remove resources before zipping without affecting workspace
cp -a "$GESTOR_DIR" "$TMP_DIR/gestor"
cd "$TMP_DIR/gestor"

# Generate resource updates (optional, if script exists)
if [ -f controladores/agents/arquitetura/atualizacao-dados-recursos.php ]; then
  echo "[local-build] Generating resources" >&2
  (cd controladores/agents/arquitetura && php atualizacao-dados-recursos.php || true)
fi

# Remove resources according to workflow
rm -rf resources/ 2>/dev/null || true
find modulos -type d -name resources -exec rm -rf {} + 2>/dev/null || true

# Minor cleanups
rm -rf .git* vendor/bin/.phpunit* vendor/composer/tmp-* node_modules 2>/dev/null || true
find . -maxdepth 4 -type f -name "*.log" -delete 2>/dev/null || true

# Remove sensitive .env only from root (preserve templates in autenticacoes.exemplo)
find . -maxdepth 1 -type f -name '.env*' -exec rm -f {} + 2>/dev/null || true
find autenticacoes -type f -name '.env*' -not -path '*/autenticacoes.exemplo/*' -exec rm -f {} + 2>/dev/null || true || true

cd "$TMP_DIR/gestor"

ARCHIVE_CMD="zip"
if command -v 7z >/dev/null 2>&1; then ARCHIVE_CMD="7z"; fi

if [ "$ARCHIVE_CMD" = "7z" ]; then
  echo "[local-build] Using 7z" >&2
  # 7z does not support global exclusions identical to zip -x, so we use -xr!
  # Strategy: add everything and exclude patterns.
  7z a -tzip gestor.zip \
    -xr!'.git*' \
    -xr!'vendor/bin/.phpunit*' \
    -xr!'vendor/composer/tmp-*' \
    -xr!'node_modules' \
    -xr!'*.DS_Store*' \
    -xr!'*.log*' \
    -xr!'tests' \
    -xr!'phpunit.xml*'
else
  echo "[local-build] Using zip" >&2
  zip -r gestor.zip \
    -x ".git*" \
    -x "vendor/bin/.phpunit*" \
    -x "vendor/composer/tmp-*" \
    -x "node_modules/*" \
    -x "*.DS_Store*" \
    -x "*.log*" \
    -x "tests/*" \
    -x "phpunit.xml*"
fi

if command -v sha256sum >/dev/null 2>&1; then
  sha256sum gestor.zip | awk '{print $1}' > gestor.zip.sha256
elif command -v certutil >/dev/null 2>&1; then
  certutil -hashfile gestor.zip SHA256 | sed -n '2p' | tr -d '\r\n' > gestor.zip.sha256
else
  echo "[local-build] ERROR: no sha256 tool found" >&2
  exit 1
fi
mv gestor.zip gestor.zip.sha256 "$OUT_DIR"/

echo "[local-build] Artifacts generated in: $OUT_DIR" >&2
ls -l "$OUT_DIR" | sed 's/^/[local-build] /'

docker exec conn2flow-app bash -c "chown -R www-data:www-data /var/www/sites/localhost/conn2flow-github/"
