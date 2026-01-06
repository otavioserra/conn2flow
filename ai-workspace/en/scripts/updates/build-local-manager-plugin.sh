#!/usr/bin/env bash
set -euo pipefail


# =====================================================================================
# Plugin Local Build (dynamic via environment.json)
#
# This script reads the fixed file dev-environment/data/environment.json,
# extracts the plugin config path based on type (devPluginEnvironmentConfig.public.path or devPluginEnvironmentConfig.private.path),
# reads this config and uses dynamic paths to build the plugin.
#
# Flags:
#   --plugin-root=/path       Defines plugin root (overrides any other)
#   --keep-resources        Does not remove resources directories before zip
#   --out-dir=/path           Target directory for artifacts (default: as per config)
#   --name=file.zip           Zip base name (default: gestor-plugin.zip)
#   --no-hash                 Do not generate .sha256 file
#   --type=public|private     Plugin type (public or private, default: public)
# =====================================================================================


SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# Fixed path of main environment.json
ENV_MAIN_JSON="$SCRIPT_DIR/../../../../dev-environment/data/environment.json"

if [[ ! -f "$ENV_MAIN_JSON" ]]; then
  echo "[build-plugin] ERROR: main environment.json not found: $ENV_MAIN_JSON" >&2
  exit 1
fi

# Defines default plugin type
PLUGIN_TYPE="public"

# Flags can override variables (processes first to define PLUGIN_TYPE)
for arg in "$@"; do
  case "$arg" in
    --type=*) PLUGIN_TYPE="${arg#*=}" ;;
    --help|-h)
      cat <<EOF
Usage: $(basename "$0") [options]
  --plugin-root=/path       Defines plugin root
  --keep-resources          Keeps resources directories in package
  --out-dir=/path           Changes output directory
  --name=file.zip           Zip name (default gestor-plugin.zip)
  --no-hash                 Do not generate SHA256 hash
  --type=public|private     Plugin type (default: public)
EOF
      exit 0;;
  esac
done

# Validates plugin type
if [[ "$PLUGIN_TYPE" != "public" && "$PLUGIN_TYPE" != "private" ]]; then
  echo "[build-plugin] ERROR: Invalid plugin type: $PLUGIN_TYPE. Use 'public' or 'private'." >&2
  exit 1
fi

echo "[build-plugin] Plugin type: $PLUGIN_TYPE" >&2

# Extracts plugin config path based on type
if command -v jq >/dev/null 2>&1; then
  PLUGIN_ENV_PATH=$(jq -r ".devPluginEnvironmentConfig.${PLUGIN_TYPE}.path" "$ENV_MAIN_JSON")
else
  PLUGIN_ENV_PATH=$(grep "\"devPluginEnvironmentConfig\"" -A 10 "$ENV_MAIN_JSON" | grep "\"${PLUGIN_TYPE}\"" -A 3 | grep '"path"' | sed -E 's/.*"path" *: *"([^"]*)".*/\1/' | head -1)
fi

if [[ -z "$PLUGIN_ENV_PATH" || "$PLUGIN_ENV_PATH" == "null" ]]; then
  echo "[build-plugin] ERROR: devPluginEnvironmentConfig.${PLUGIN_TYPE}.path not defined in $ENV_MAIN_JSON" >&2
  exit 1
fi
if [[ ! -f "$PLUGIN_ENV_PATH" ]]; then
  echo "[build-plugin] ERROR: Plugin config file not found: $PLUGIN_ENV_PATH" >&2
  exit 1
fi



# Extracts paths from plugin config and assembles active plugin path
if command -v jq >/dev/null 2>&1; then
  PLUGIN_ROOT_BASE=$(jq -r '.devEnvironment.source' "$PLUGIN_ENV_PATH")
  OUT_DIR_BASE=$(jq -r '.devEnvironment.deploys' "$PLUGIN_ENV_PATH")
  ACTIVE_PLUGIN_ID=$(jq -r '.activePlugin.id' "$PLUGIN_ENV_PATH")
  ACTIVE_PLUGIN_PATH=$(jq -r --arg id "$ACTIVE_PLUGIN_ID" '.plugins[] | select(.id==$id) | .path' "$PLUGIN_ENV_PATH")
else
  PLUGIN_ROOT_BASE=$(grep '"source"' "$PLUGIN_ENV_PATH" | sed -E 's/.*"source" *: *"([^"]*)".*/\1/')
  OUT_DIR_BASE=$(grep '"deploys"' "$PLUGIN_ENV_PATH" | sed -E 's/.*"deploys" *: *"([^"]*)".*/\1/')
  ACTIVE_PLUGIN_ID=$(grep '"activePlugin"' -A 2 "$PLUGIN_ENV_PATH" | grep '"id"' | sed -E 's/.*"id" *: *"([^"]*)".*/\1/')
  # Searches for active plugin path in plugins list
  ACTIVE_PLUGIN_PATH=$(awk -v id="$ACTIVE_PLUGIN_ID" 'BEGIN{p=0} /"plugins" *:/ {p=1} p && /"id"/ {if ($0 ~ id) f==1} f && /"path"/ {match($0, /"path" *: *"([^"]*)"/, a); print a[1]; exit}' "$PLUGIN_ENV_PATH")
fi

# Assembles active plugin path
PLUGIN_ROOT="$PLUGIN_ROOT_BASE$ACTIVE_PLUGIN_PATH"

# Defines final output directory (subfolder with plugin ID)
OUT_DIR="${OUT_DIR_BASE%/}/$ACTIVE_PLUGIN_ID"

# Defines deploys folder path (where processed files are generated)
DEPLOY_PLUGIN_ROOT="$OUT_DIR/temp"

# Discovers plugin environment.json root
PLUGIN_ENV_ROOT=$(dirname "$PLUGIN_ENV_PATH")
DATA_SCRIPT="$PLUGIN_ENV_ROOT/scripts/resources/update-data-resources-plugin.php"
ZIP_NAME="gestor-plugin.zip"
KEEP_RESOURCES=false
GEN_HASH=true

# Flags can override other variables
for arg in "$@"; do
  case "$arg" in
    --plugin-root=*) PLUGIN_ROOT="${arg#*=}" ;;
    --keep-resources) KEEP_RESOURCES=true ;;
    --out-dir=*) OUT_DIR="${arg#*=}" ;;
    --name=*) ZIP_NAME="${arg#*=}" ;;
    --no-hash) GEN_HASH=false ;;
    --type=*) ;; # Already processed above
  esac
done

# Defines initial source folder (always starts with original plugin)
SOURCE_ROOT="$PLUGIN_ROOT"
echo "[build-plugin] Initial source folder: $SOURCE_ROOT" >&2

if [[ ! -d "$SOURCE_ROOT" ]]; then
  echo "[build-plugin] ERROR: Non-existent source folder: $SOURCE_ROOT" >&2
  exit 1
fi

mkdir -p "$OUT_DIR"

echo "[build-plugin] Plugin root: $PLUGIN_ROOT" >&2
echo "[build-plugin] Deploy root: $DEPLOY_PLUGIN_ROOT" >&2
echo "[build-plugin] Out dir: $OUT_DIR" >&2

# 1. Generate multiple Data.json (always uses original plugin as source, generates in deploys folder)
if [[ -d "$DEPLOY_PLUGIN_ROOT" ]]; then
  echo "[build-plugin] Cleaning previous deploys folder: $DEPLOY_PLUGIN_ROOT" >&2
  rm -rf "$DEPLOY_PLUGIN_ROOT"/* 2>/dev/null || true
fi

if [[ -f "$DATA_SCRIPT" ]]; then
  echo "[build-plugin] Generating Data JSON (Layouts/Pages/Components/Variables)" >&2
  echo "[build-plugin] Source: $PLUGIN_ROOT" >&2
  echo "[build-plugin] Target: $DEPLOY_PLUGIN_ROOT" >&2
  php "$DATA_SCRIPT" --plugin-root="$PLUGIN_ROOT" --deploy-plugin-root="$DEPLOY_PLUGIN_ROOT" || { echo '[build-plugin] WARN: generation failed'; }
else
  echo "[build-plugin] WARNING: Generation script not found: $DATA_SCRIPT" >&2
fi

# 2. Copy to temporary directory (uses SOURCE_ROOT which can be deploys or original plugin)
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR" || true' EXIT
cp -a "$SOURCE_ROOT" "$TMP_DIR/plugin"

cd "$TMP_DIR/plugin"

# 3. Optional cleanups
if [[ "$KEEP_RESOURCES" = false ]]; then
  echo "[build-plugin] Removing resources/ (global + modules)" >&2
  rm -rf resources/ 2>/dev/null || true
  find modules -type d -name resources -exec rm -rf {} + 2>/dev/null || true
else
  echo "[build-plugin] Keeping resources as per --keep-resources flag" >&2
fi

# Logs, common caches
find . -maxdepth 4 -type f -name "*.log" -delete 2>/dev/null || true
rm -rf .git* node_modules vendor/composer/tmp-* 2>/dev/null || true

# 4. Package
ARCHIVE_PATH="$OUT_DIR/$ZIP_NAME"
HASH_PATH="$ARCHIVE_PATH.sha256"
rm -f "$ARCHIVE_PATH" "$HASH_PATH" 2>/dev/null || true

ARCHIVER="zip"
if command -v 7z >/dev/null 2>&1; then ARCHIVER="7z"; fi

echo "[build-plugin] Archiver: $ARCHIVER" >&2
if [[ "$ARCHIVER" = 7z ]]; then
  7z a -tzip "$ARCHIVE_PATH" \
    -xr!'.git*' -xr!'node_modules' -xr!'*.DS_Store*' -xr!'vendor/composer/tmp-*' -xr!'tests'
else
  zip -r "$ARCHIVE_PATH" . \
    -x "*.git*" \
    -x "node_modules/*" \
    -x "*.DS_Store*" \
    -x "vendor/composer/tmp-*" \
    -x "tests/*"
fi


# 5. Hash
if [[ "$GEN_HASH" = true ]]; then
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$ARCHIVE_PATH" | awk '{print $1}' > "$HASH_PATH"
  elif command -v certutil >/dev/null 2>&1; then
    certutil -hashfile "$ARCHIVE_PATH" SHA256 | sed -n '2p' | tr -d '\r\n' > "$HASH_PATH"
  else
    echo "[build-plugin] WARNING: sha256sum/certutil unavailable - skipping hash" >&2
  fi
fi

echo "[build-plugin] Artifacts:" >&2
ls -lh "$ARCHIVE_PATH"* 2>/dev/null || true
echo "[build-plugin] OK" >&2
