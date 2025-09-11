#!/bin/bash
# Conn2Flow Plugin Release Script
#
# This script bumps the plugin version and creates a Git commit and tag for the release.
#
# How it works:
# - By default, it reads the plugin list and the active plugin id from environment.json (in the parent directory).
# - It locates the manifest.json of the active plugin and bumps the version (major, minor, or patch) using version.php.
# - You can override the plugin path, manifest path, or version type via command-line arguments for automation/CI.
#
# Usage:
#   ./release.sh [type] [tag_summary] [commit_message] [plugin_path] [manifest_path]
#
#   type          = 'major', 'minor', or 'patch' (default: patch)
#   tag_summary   = Tag summary/description (required)
#   commit_message = Commit message (required)
#   plugin_path   = path to the plugin directory (optional, overrides environment.json)
#   manifest_path = path to manifest.json (optional, overrides everything)
#
# Examples:
#   ./release.sh minor "Release vX.Y.Z" "Bump version and release"
#   ./release.sh patch "Release vX.Y.Z" "Bump version" ../plugin
#   ./release.sh major "Release vX.Y.Z" "Major release" ../plugin ../plugin/manifest.json
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

if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
  echo "Usage: ./release.sh [type] 'Tag Summary' 'Commit Message' [plugin_path] [manifest_path]"; exit 1;
fi
TYPE=$1
TAG_SUM=$2
COMMIT_MSG=$3
PLUGIN_PATH=${4:-}
MANIFEST_PATH=${5:-}


SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
# Caminho do environment.json do plugin sempre 2 níveis acima
ENV_PATH="$(dirname "$(dirname "$SCRIPT_DIR")")/environment.json"
VERSION_SCRIPT="$SCRIPT_DIR/version.php"

if [ -n "$MANIFEST_PATH" ]; then
  MANIFEST_ARG="$MANIFEST_PATH"
elif [ -n "$PLUGIN_PATH" ]; then
  MANIFEST_ARG="$PLUGIN_PATH/manifest.json"
else
  MANIFEST_ARG=""
fi


if [ -n "$MANIFEST_ARG" ]; then
  NEW_VERSION=$(php "$VERSION_SCRIPT" "$TYPE" "$PLUGIN_PATH" "$MANIFEST_PATH")
else
  NEW_VERSION=$(php "$VERSION_SCRIPT" "$TYPE")
fi

if [ -z "$NEW_VERSION" ]; then
  echo "Error bumping version"; exit 1;
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

# Sempre define o diretório do plugin como dois níveis acima do release.sh
PLUGIN_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"

# Salva diretório atual
ORIGINAL_DIR="$(pwd)"
# Entra no diretório do plugin para garantir contexto git correto
cd "$PLUGIN_DIR"

## Remove todas as tags antigas do padrão plugin-${PLUGIN_ID}-v* localmente e remotamente
set +e
OLD_TAGS=$(git tag | grep "^plugin-${PLUGIN_ID}-v")
if [ -n "$OLD_TAGS" ]; then
  echo "Removendo todas as tags antigas do padrão plugin-${PLUGIN_ID}-v*: $OLD_TAGS"
  for tag in $OLD_TAGS; do
    if [ -n "$tag" ]; then
      git tag -d "$tag"
      git push --delete origin "$tag"
      if command -v gh >/dev/null 2>&1; then
        gh release delete "$tag" --yes
      fi
    fi
  done
fi
set -e

# Adiciona todas as alterações do plugin
git add .
git commit -m "[$PLUGIN_ID][$PLUGIN_NAME] $COMMIT_MSG (v$NEW_VERSION)"
git tag -a "plugin-${PLUGIN_ID}-v$NEW_VERSION" -m "[$PLUGIN_ID][$PLUGIN_NAME] $TAG_SUM (v$NEW_VERSION)"
git push
git push --tags
# Volta para o diretório original
cd "$ORIGINAL_DIR"
