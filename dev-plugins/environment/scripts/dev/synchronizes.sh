#!/bin/bash
# Safe synchronization script for the gestor to the Docker environment
# Copies only new or modified files, never deletes anything from the source
# Usage: bash ./ai-workspace/scripts/dev-environment/sincroniza-gestor.sh checksum
#
# Source:   gestor/
# Target:   dev-environment/data/sites/localhost/conn2flow-gestor/


# Load variables from environment.json
ENV_JSON="$(dirname "$0")/../../environment.json"
if [ ! -f "$ENV_JSON" ]; then
  echo "Error: environment.json not found at $ENV_JSON"
  exit 1
fi

# Extract values using jq (must be installed)
SOURCE=$(jq -r '.devEnvironment.source' "$ENV_JSON")
TARGET=$(jq -r '.devEnvironment.target' "$ENV_JSON")
DOCKER_PATH=$(jq -r '.devEnvironment.dockerPath' "$ENV_JSON")

# Validate variables
if [ -z "$SOURCE" ] || [ "$SOURCE" = "null" ]; then
  echo "Error: 'source' not set in environment.json (devEnvironment.source)"
  exit 1
fi
if [ -z "$TARGET" ] || [ "$TARGET" = "null" ]; then
  echo "Error: 'target' not set in environment.json (devEnvironment.target)"
  exit 1
fi
if [ -z "$DOCKER_PATH" ] || [ "$DOCKER_PATH" = "null" ]; then
  echo "Error: 'dockerPath' not set in environment.json (devEnvironment.dockerPath)"
  exit 1
fi

# Sync mode: default, checksum, force
MODE=${1:-default}

case "$MODE" in
  default|"" )
    echo "🔄 Mode: default (date/time, does not overwrite newer files in target)"
    CMD=(rsync -avu "$SOURCE" "$TARGET")
    ;;
  checksum )
    echo "🔄 Mode: checksum (compares file contents)"
    CMD=(rsync -av --checksum "$SOURCE" "$TARGET")
    ;;
  force )
    echo "🔄 Mode: force overwrite all files (ignores date/time)"
    CMD=(rsync -av --ignore-times "$SOURCE" "$TARGET")
    ;;
  * )
    echo "❌ Invalid mode. Use: default | checksum | force"
    exit 1
    ;;
esac

# Execute the chosen command
"${CMD[@]}"

# Update folder permissions
docker exec conn2flow-app bash -c "chown -R www-data:www-data $DOCKER_PATH"

# Final message
if [ $? -eq 0 ]; then
  echo "✅ Synchronization completed successfully!"
else
  echo "❌ An error occurred during synchronization."
fi
