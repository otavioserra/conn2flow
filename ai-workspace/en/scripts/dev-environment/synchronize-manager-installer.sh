#!/bin/bash
# Safe synchronization script for the manager installer to the Docker environment
# Copies only new or modified files, never deletes anything from the source
# Usage: bash ./ai-workspace/scripts/dev-environment/synchronize-manager-installer.sh checksum
#
# Source:   gestor-instalador/
# Target:  dev-environment/data/sites/localhost/public_html/instalador/
#
# Load variables from environment.json
# Safe synchronization script for the gestor-installer to the Docker environment
# Copies only new or modified files, never deletes anything from the source
#
# Usage:
#   bash ./ai-workspace/scripts/dev-environment/synchronize-manager-installer.sh [default|checksum|force]
#
# All paths (source, target, dockerPath) are read from dev-environment/data/environment.json:
#   - devInstallerEnvironment.source:     local source folder (e.g. gestor-instalador/)
#   - devInstallerEnvironment.target:     local target folder (e.g. dev-environment/data/sites/localhost/public_html/instalador/)
#   - devInstallerEnvironment.dockerPath: path inside the Docker container
#
# This allows each developer to configure their own paths without editing the script.

# Load variables from environment.json
ENV_JSON="$(dirname "$0")/../../../dev-environment/data/environment.json"
if [ ! -f "$ENV_JSON" ]; then
  echo "Error: environment.json not found at $ENV_JSON"
  exit 1
fi

# Try to use jq, fallback to grep/sed if not available
if command -v jq >/dev/null 2>&1; then
  ORIGEM=$(jq -r '.devInstallerEnvironment.source' "$ENV_JSON")
  DESTINO=$(jq -r '.devInstallerEnvironment.target' "$ENV_JSON")
  PATH_DOCKER=$(jq -r '.devInstallerEnvironment.dockerPath' "$ENV_JSON")
else
  ORIGEM=$(grep '"source"' "$ENV_JSON" | sed -E 's/.*"source" *: *"([^"]*)".*/\1/')
  DESTINO=$(grep '"target"' "$ENV_JSON" | sed -E 's/.*"target" *: *"([^"]*)".*/\1/')
  PATH_DOCKER=$(grep '"dockerPath"' "$ENV_JSON" | sed -E 's/.*"dockerPath" *: *"([^"]*)".*/\1/')
fi

# Validate variables
if [ -z "$ORIGEM" ] || [ "$ORIGEM" = "null" ]; then
  echo "Error: 'source' not set in environment.json (devInstallerEnvironment.source)"
  exit 1
fi
if [ -z "$DESTINO" ] || [ "$DESTINO" = "null" ]; then
  echo "Error: 'target' not set in environment.json (devInstallerEnvironment.target)"
  exit 1
fi
if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "null" ]; then
  echo "Error: 'dockerPath' not set in environment.json (devInstallerEnvironment.dockerPath)"
  exit 1
fi

# Synchronization mode: default, checksum, force
MODO=${1:-default}

case "$MODO" in
  default|"" )
    echo "🔄 Mode: default (date/time, does not overwrite newer files in target)"
    CMD=(rsync -avu "$ORIGEM" "$DESTINO")
    ;;
  checksum )
    echo "🔄 Mode: checksum (compares file content)"
    CMD=(rsync -av --checksum "$ORIGEM" "$DESTINO")
    ;;
  force )
    echo "🔄 Mode: force overwrite of all files (ignores date/time)"
    CMD=(rsync -av --ignore-times "$ORIGEM" "$DESTINO")
    ;;
  * )
    echo "❌ Invalid mode. Use: default | checksum | force"
    exit 1
    ;;
esac

# Show source and target paths
echo "📤 Source: $ORIGEM"
echo "📥 Target: $DESTINO"
echo "🐳 Docker Path: $PATH_DOCKER"

# Execute chosen command
"${CMD[@]}"

# Update folder permissions
docker exec conn2flow-app bash -c "chown -R www-data:www-data $PATH_DOCKER"

# Remove .htaccess if present
docker exec conn2flow-app bash -c "rm -f $PATH_DOCKER/.htaccess"

# Final message
if [ $? -eq 0 ]; then
  echo "✅ Synchronization completed successfully!"
else
  echo "❌ An error occurred during synchronization."
fi
