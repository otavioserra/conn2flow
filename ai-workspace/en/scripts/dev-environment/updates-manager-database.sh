#!/bin/bash
# Script to run database migrations/updates inside the Docker environment
# Reads the Docker path dynamically from environment.json
#
# Usage:
#   bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh
#
# The dockerPath is read from dev-environment/data/environment.json:
#   - devEnvironment.dockerPath: path inside the Docker container (e.g. /var/www/sites/localhost/conn2flow-site/)
#
# This allows each developer to configure their own paths without editing the script.

# Load variables from environment.json
ENV_JSON="$(dirname "$0")/../../../../dev-environment/data/environment.json"
if [ ! -f "$ENV_JSON" ]; then
  echo "❌ Error: environment.json not found at $ENV_JSON"
  exit 1
fi

# Try to use jq, fallback to grep/sed if not available
if command -v jq >/dev/null 2>&1; then
  PATH_DOCKER=$(jq -r '.devEnvironment.dockerPath' "$ENV_JSON")
else
  PATH_DOCKER=$(grep '"dockerPath"' "$ENV_JSON" | sed -E 's/.*"dockerPath" *: *"([^"]*)".*/\1/')
fi

# Validate variable
if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "null" ]; then
  echo "❌ Error: 'dockerPath' not set in environment.json (devEnvironment.dockerPath)"
  exit 1
fi

# Build the full PHP command path
PHP_SCRIPT="${PATH_DOCKER}controladores/atualizacoes/atualizacoes-banco-de-dados.php"

# Show info
echo "🐳 Docker Path: $PATH_DOCKER"
echo "📄 PHP Script:  $PHP_SCRIPT"
echo "🔄 Running database updates..."

# Execute database updates inside Docker
docker exec conn2flow-app bash -c "php ${PHP_SCRIPT} --debug --log-diff"

# Final message
if [ $? -eq 0 ]; then
  echo "✅ Database updates completed successfully!"
else
  echo "❌ An error occurred during database updates."
fi
