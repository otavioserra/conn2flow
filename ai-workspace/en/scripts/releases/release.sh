#!/bin/bash

# Run: bash ./ai-workspace/git/scripts/release.sh TYPE "TAG_MSG" "COMMIT_MSG"

# Script to automate the release process:
# 1. Updates the version in config.php
# 2. Adds changes to Git
# 3. Creates a standardized commit
# 4. Creates a Git tag with the new version

# Ensures the script stops if any command fails
set -e

# Checks if the release type (patch, minor, major) was passed as an argument
if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
  echo "Error: Insufficient arguments."
  echo "Usage:   ./ai-workspace/en/scripts/releases/release.sh [type] \"Tag Summary\" \"Detailed Commit Message\""
  echo "Example: ./ai-workspace/en/scripts/releases/release.sh patch \"Fix password validation\" \"fix(login): Fix bug preventing special characters in password.\""
  exit 1
fi

RELEASE_TYPE=$1
TAG_SUMMARY=$2
COMMIT_DETAILS=$3
CONFIG_FILE="gestor/config.php"
VERSION_SCRIPT="ai-workspace/en/scripts/releases/version.php"

# 1. Runs the PHP script to update the version in config.php
echo "Updating version ($RELEASE_TYPE)..."
NEW_VERSION=$(php $VERSION_SCRIPT $RELEASE_TYPE)

# Checks if the PHP script executed successfully.
# It will return a non-empty version string on success.
if [ -z "$NEW_VERSION" ]; then
  echo "Error: Failed to update version. Check the output of version.php script."
  exit 1
fi

echo "New version is: $NEW_VERSION"

## Removes all old tags matching gestor-v2.6.* pattern locally and remotely
set +e
OLD_TAGS=$(git tag | grep "^gestor-v2.6")
if [ -n "$OLD_TAGS" ]; then
  echo "Removing all old tags matching gestor-v2.6.* pattern: $OLD_TAGS"
  for tag in $OLD_TAGS; do
    if [ -n "$tag" ]; then
      git tag -d "$tag"
      git push --delete origin "$tag"
      gh release delete "$tag" --yes
    fi
  done
fi
set -e

# 2. Adds, commits, and creates an annotated Git tag with distinct messages
echo "Creating commit and tag for version gestor-v$NEW_VERSION..."
# Adds modified config.php AND any other changes to stage
# in the working directory. This ensures the release commit includes
# all work performed.
git add .
git commit -m "$COMMIT_DETAILS"
git tag -a "gestor-v$NEW_VERSION" -m "$TAG_SUMMARY"

echo "Release gestor-v$NEW_VERSION created successfully!"

git push
git push --tags
