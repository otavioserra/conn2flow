#!/bin/bash

# Run: bash ./ai-workspace/en/scripts/releases/release-installer.sh TYPE "TAG_MSG" "COMMIT_MSG" [automatic|manual]

# Script to automate the GESTOR-INSTALLER release process:
# 1. Updates the version in index.php
# 2. Adds changes to Git
# 3. Creates a standardized commit
# 4. Creates a Git tag with the new version

# Ensures the script stops if any command fails
set -e

# Checks if the release type (patch, minor, major) was passed as an argument
if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
  echo "Error: Insufficient arguments."
  echo "Usage:   ./ai-workspace/en/scripts/releases/release-installer.sh [type] \"Tag Summary\" \"Detailed Commit Message\" [automatic|manual]"
  echo "Example: ./ai-workspace/en/scripts/releases/release-installer.sh patch \"Fix .env path\" \"fix(install): Fix .env path during autologin creation.\""
  exit 1
fi

RELEASE_TYPE=$1
TAG_SUMMARY=$2
COMMIT_DETAILS=$3
RELEASE_MODE=${4:-automatic}
CONFIG_FILE="gestor-instalador/index.php"
VERSION_SCRIPT="ai-workspace/en/scripts/releases/version-installer.php"
WORKFLOW_FILE=".github/workflows/release-instalador.yml"

if [ "$RELEASE_MODE" != "automatic" ] && [ "$RELEASE_MODE" != "manual" ]; then
  echo "Error: Invalid release mode '$RELEASE_MODE'. Use automatic or manual."
  exit 1
fi

# 1. Runs the PHP script to update the version in index.php
echo "Updating installer version ($RELEASE_TYPE)..."
NEW_VERSION=$(php $VERSION_SCRIPT $RELEASE_TYPE)

# Checks if the PHP script executed successfully.
# It will return a non-empty version string on success.
if [ -z "$NEW_VERSION" ]; then
  echo "Error: Failed to update version. Check the output of version-installer.php script."
  exit 1
fi

echo "New installer version is: $NEW_VERSION"


# 2. Removes all old tags matching installer-v* pattern locally and remotely
set +e
OLD_TAGS=$(git tag | grep "^instalador-v[0-9]")
if [ -n "$OLD_TAGS" ]; then
  echo "Removing all old tags matching installer-v* pattern: $OLD_TAGS"
  for tag in $OLD_TAGS; do
    if [ -n "$tag" ]; then
      git tag -d "$tag"
      git push --delete origin "$tag"
      gh release delete "$tag" --yes
    fi
  done
fi
set -e

# 3. Adds, commits, and creates an annotated Git tag with distinct messages
echo "Creating commit and tag for version installer-v$NEW_VERSION..."
git add .
git commit -m "$COMMIT_DETAILS"
git tag -a "instalador-v$NEW_VERSION" -m "$TAG_SUMMARY"

echo "Release installer-v$NEW_VERSION created successfully!"

git push
git push --tags

if [ "$RELEASE_MODE" = "manual" ]; then
  TAG_NAME="instalador-v$NEW_VERSION"
  RELEASE_TITLE="Instalador $TAG_NAME"
  BODY_FILE="/tmp/${TAG_NAME}-release-body.md"

  echo "Manual mode enabled. Creating GitHub release directly..."

  awk '
    /body: \|/ { in_body=1; next }
    in_body && /^[[:space:]]*draft:/ { in_body=0 }
    in_body {
      sub(/^          /, "")
      print
    }
  ' "$WORKFLOW_FILE" > "$BODY_FILE"

  if [ ! -s "$BODY_FILE" ]; then
    echo "Error: Failed to extract release body from $WORKFLOW_FILE"
    exit 1
  fi

  rm -f instalador.zip

  if command -v zip >/dev/null 2>&1; then
    cd gestor-instalador
    zip -r ../instalador.zip . \
      -x "*.git*" \
      -x "*.DS_Store*" \
      -x "*.log*" \
      -x "temp/*" \
      -x ".env.debug"
    cd ..
  else
    TMP_DIR=$(mktemp -d)
    cp -a gestor-instalador "$TMP_DIR/gestor-instalador"

    rm -rf "$TMP_DIR/gestor-instalador/temp"
    rm -f "$TMP_DIR/gestor-instalador/.env.debug"
    find "$TMP_DIR/gestor-instalador" -name "*.DS_Store*" -type f -delete
    find "$TMP_DIR/gestor-instalador" -name "*.log*" -type f -delete
    find "$TMP_DIR/gestor-instalador" -name "*.git*" -exec rm -rf {} +

    if command -v powershell >/dev/null 2>&1; then
      if command -v cygpath >/dev/null 2>&1; then
        SRC_WIN=$(cygpath -w "$TMP_DIR/gestor-instalador")
        DEST_WIN=$(cygpath -w "$PWD/instalador.zip")
      else
        SRC_WIN="$TMP_DIR/gestor-instalador"
        DEST_WIN="$PWD/instalador.zip"
      fi
      powershell -NoProfile -Command "Compress-Archive -Path '$SRC_WIN\\*' -DestinationPath '$DEST_WIN' -Force" >/dev/null
    elif command -v pwsh >/dev/null 2>&1; then
      SRC_UNIX="$TMP_DIR/gestor-instalador"
      DEST_UNIX="$PWD/instalador.zip"
      pwsh -NoProfile -Command "Compress-Archive -Path '$SRC_UNIX/*' -DestinationPath '$DEST_UNIX' -Force" >/dev/null
    else
      rm -rf "$TMP_DIR"
      echo "Error: Neither 'zip' nor PowerShell compression is available to create instalador.zip"
      exit 1
    fi

    rm -rf "$TMP_DIR"
  fi

  if gh release view "$TAG_NAME" >/dev/null 2>&1; then
    gh release delete "$TAG_NAME" --yes
  fi

  gh release create "$TAG_NAME" instalador.zip \
    --title "$RELEASE_TITLE" \
    --notes-file "$BODY_FILE" \
    --latest

  echo "Manual release created: $TAG_NAME"
fi
