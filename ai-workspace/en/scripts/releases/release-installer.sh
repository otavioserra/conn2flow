#!/bin/bash

# Run: bash ./ai-workspace/en/scripts/releases/release-installer.sh TYPE "TAG_MSG" "COMMIT_MSG"

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
  echo "Usage:   ./ai-workspace/en/scripts/releases/release-installer.sh [type] \"Tag Summary\" \"Detailed Commit Message\""
  echo "Example: ./ai-workspace/en/scripts/releases/release-installer.sh patch \"Fix .env path\" \"fix(install): Fix .env path during autologin creation.\""
  exit 1
fi

RELEASE_TYPE=$1
TAG_SUMMARY=$2
COMMIT_DETAILS=$3
CONFIG_FILE="gestor-instalador/index.php"
VERSION_SCRIPT="ai-workspace/en/scripts/releases/version-installer.php"

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
