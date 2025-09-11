#!/bin/bash
# Conn2Flow Plugin Commit Script
#
# This script automates the commit process for plugin development:
# 1. Updates the plugin version (using environment.json or CLI overrides)
# 2. Adds changes to Git (only for the active plugin directory)
# 3. Creates a standardized commit
#
# How it works:
# - By default, it reads the plugin list and the active plugin id from environment.json (in the parent directory).
# - It locates the manifest.json of the active plugin and bumps the version (patch by default) using version.php.
# - You can override the plugin path, manifest path, or version type via command-line arguments for automation/CI.
#
# Usage:
#   ./commit.sh "Commit message" [release_type] [plugin_path] [manifest_path]
#
#   commit_message = Commit message (required)
#   release_type   = 'major', 'minor', or 'patch' (default: patch)
#   plugin_path    = path to the plugin directory (optional, overrides environment.json)
#   manifest_path  = path to manifest.json (optional, overrides everything)
#
# Examples:
#   ./commit.sh "Fix password validation"
#   ./commit.sh "Update feature" minor
#   ./commit.sh "Update feature" patch ../plugin
#   ./commit.sh "Update feature" major ../plugin ../plugin/manifest.json
#
# The script will always use the following priority for locating the manifest:
#   1. manifest_path argument (if provided)
#   2. plugin_path argument + '/manifest.json' (if provided)
#   3. plugin path from environment.json + '/manifest.json'
#
# The active plugin is determined by 'activePlugin.id' in environment.json, which must match an entry in 'plugins'.
#
# All arguments can be provided for automation/CI. If not provided, environment.json is used as fallback.

set -e

if [ -z "$1" ]; then
  echo "Error: Insufficient arguments."
  echo "Usage:   ./commit.sh \"Commit message\" [release_type] [plugin_path] [manifest_path]"
  echo "Example: ./commit.sh \"Fix password validation\" minor ../plugin ../plugin/manifest.json"
  exit 1
fi

COMMIT_DETAILS=$1
RELEASE_TYPE=${2:-patch}
PLUGIN_PATH=${3:-}
MANIFEST_PATH=${4:-}


# Diretório do script
SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
ENV_PATH="$(dirname "$(dirname "$SCRIPT_DIR")")/environment.json"
VERSION_SCRIPT="$SCRIPT_DIR/../releases/version.php"

# Determine manifest path for version bump
if [ -n "$MANIFEST_PATH" ]; then
  MANIFEST_ARG="$MANIFEST_PATH"
elif [ -n "$PLUGIN_PATH" ]; then
  MANIFEST_ARG="$PLUGIN_PATH/manifest.json"
else
  MANIFEST_ARG=""
fi

# 1. Run the PHP script to update the version in manifest.json
echo "Updating version ($RELEASE_TYPE)..."
if [ -n "$MANIFEST_ARG" ]; then
  NEW_VERSION=$(php "$VERSION_SCRIPT" "$RELEASE_TYPE" "$PLUGIN_PATH" "$MANIFEST_PATH")
else
  NEW_VERSION=$(php "$VERSION_SCRIPT" "$RELEASE_TYPE")
fi

if [ -z "$NEW_VERSION" ]; then
  echo "Error: Failed to update version. Check the output of version.php script."
  exit 1
fi



# Get plugin id and name for commit/tag messages (usando ENV_PATH dinâmico)
if [ -n "$MANIFEST_PATH" ]; then
  if [ ! -f "$ENV_PATH" ]; then
    PLUGIN_ID="unknown"
    PLUGIN_NAME="unknown"
  else
    PLUGIN_ID=$(jq -r --arg mp "$MANIFEST_PATH" '.plugins[] | select((.path + "/manifest.json") == $mp) | .id' "$ENV_PATH")
    PLUGIN_NAME=$(jq -r --arg mp "$MANIFEST_PATH" '.plugins[] | select((.path + "/manifest.json") == $mp) | .name' "$ENV_PATH")
    [ -z "$PLUGIN_ID" ] && PLUGIN_ID="unknown"
    [ -z "$PLUGIN_NAME" ] && PLUGIN_NAME="unknown"
  fi
elif [ -n "$PLUGIN_PATH" ]; then
  if [ ! -f "$ENV_PATH" ]; then
    PLUGIN_ID="unknown"
    PLUGIN_NAME="unknown"
  else
    PLUGIN_ID=$(jq -r --arg pp "$PLUGIN_PATH" '.plugins[] | select(.path == $pp) | .id' "$ENV_PATH")
    PLUGIN_NAME=$(jq -r --arg pp "$PLUGIN_PATH" '.plugins[] | select(.path == $pp) | .name' "$ENV_PATH")
    [ -z "$PLUGIN_ID" ] && PLUGIN_ID="unknown"
    [ -z "$PLUGIN_NAME" ] && PLUGIN_NAME="unknown"
  fi
else
  if [ ! -f "$ENV_PATH" ]; then
    PLUGIN_ID="unknown"
    PLUGIN_NAME="unknown"
  else
    ACTIVE_ID=$(jq -r '.activePlugin.id' "$ENV_PATH")
    PLUGIN_ID="$ACTIVE_ID"
    PLUGIN_NAME=$(jq -r --arg id "$ACTIVE_ID" '.plugins[] | select(.id==$id) | .name' "$ENV_PATH")
    [ -z "$PLUGIN_NAME" ] && PLUGIN_NAME="unknown"
  fi
fi

echo "New plugin $PLUGIN_NAME ($PLUGIN_ID) version is: $NEW_VERSION"


# 2. Add and commit the changes in Git (only for the plugin directory)

# Sempre define o diretório do plugin como dois níveis acima do commit.sh
PLUGIN_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"

echo "Creating commit for version plugin-${PLUGIN_ID}-v$NEW_VERSION..."
# Salva diretório atual
ORIGINAL_DIR="$(pwd)"
# Entra no diretório do plugin para garantir contexto git correto
cd "$PLUGIN_DIR"
# Adiciona todas as alterações do plugin
git add .
git commit -m "[$PLUGIN_ID][$PLUGIN_NAME] $COMMIT_DETAILS (v$NEW_VERSION)"
echo "Commit plugin-${PLUGIN_ID}-v$NEW_VERSION created successfully!"
echo "Pushing to remote repository..."
git push
# Volta para o diretório original
cd "$ORIGINAL_DIR"