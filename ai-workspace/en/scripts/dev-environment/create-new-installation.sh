#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../" && pwd)"

echo "[new-installation] Starting creation of new installation..."

# 1. Execute installer build
echo "[new-installation] Generating installer..."
"$SCRIPT_DIR/../updates/build-local-gestor-instalador.sh"

# 2. Read environment.json configuration
ENV_FILE="$REPO_ROOT/dev-environment/data/environment.json"
if [ ! -f "$ENV_FILE" ]; then
    echo "[new-installation] ERROR: environment.json file not found: $ENV_FILE" >&2
    exit 1
fi

# Extract devInstallerEnvironment target using sed
INSTALLER_TARGET=$(sed -n '/"devInstallerEnvironment"/,/}/p' "$ENV_FILE" | grep '"target"' | sed 's/.*"target"[[:space:]]*:[[:space:]]*"//' | sed 's/".*//')

if [ -z "$INSTALLER_TARGET" ]; then
    echo "[new-installation] ERROR: Could not extract devInstallerEnvironment.target from environment.json" >&2
    exit 1
fi

echo "[new-installation] Destination folder: $INSTALLER_TARGET"

# 3. Delete all files from target folder
if [ -d "$INSTALLER_TARGET" ]; then
    echo "[new-installation] Removing existing files..."
    # Removes visible and hidden files, but preserves the directory
    find "$INSTALLER_TARGET" -mindepth 1 -delete
else
    echo "[new-installation] Creating destination directory..."
    mkdir -p "$INSTALLER_TARGET"
fi

# 4. Copy .zip to target folder
ZIP_SOURCE="$REPO_ROOT/dev-environment/data/sites/localhost/conn2flow-github/instalador.zip"
if [ ! -f "$ZIP_SOURCE" ]; then
    echo "[new-installation] ERROR: instalador.zip file not found: $ZIP_SOURCE" >&2
    exit 1
fi

echo "[new-installation] Copying instalador.zip..."
cp "$ZIP_SOURCE" "$INSTALLER_TARGET/"

# 5. Unzip the file
echo "[new-installation] Unzipping installer..."
cd "$INSTALLER_TARGET"
unzip -q instalador.zip

# 6. Remove the .zip
echo "[new-installation] Removing zip file..."
rm instalador.zip

# 7. Adjust permissions in Docker (if available)
if command -v docker >/dev/null 2>&1; then
    echo "[new-installation] Adjusting permissions in Docker..."
    docker exec conn2flow-app bash -c "chown -R www-data:www-data /var/www/sites/localhost/public_html/instalador/" 2>/dev/null || true
fi

echo "[new-installation] ✅ New installation created successfully!"
echo "[new-installation] 📁 Location: $INSTALLER_TARGET"
echo "[new-installation] 🌐 URL: http://localhost/instalador/"
