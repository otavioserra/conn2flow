#!/bin/bash

# Script to automate the commit process:
# 1. Updates the version in config.php
# 2. Adds changes to Git
# 3. Creates a standardized commit

# Ensures the script stops if any command fails
set -e

# Checks if the commit message was passed as an argument
if [ -z "$1" ]; then
  echo "Error: Insufficient arguments."
  echo "Usage:   ./ai-workspace/en/scripts/commits/commit.sh \"Detailed message for the Commit\""
  echo "Example: ./ai-workspace/en/scripts/commits/commit.sh \"Fixes password validation\""
  exit 1
fi

RELEASE_TYPE='patch'
COMMIT_DETAILS=$1
VERSION_SCRIPT="ai-workspace/en/scripts/releases/version.php"

# 1. Runs the PHP script to update the version in config.php
echo "Updating version ($RELEASE_TYPE)..."
NEW_VERSION=$(php $VERSION_SCRIPT $RELEASE_TYPE)

# Checks if the PHP script was executed successfully.
# It will return a non-empty version string on success.
if [ -z "$NEW_VERSION" ]; then
  echo "Error: Failed to update version. Check the output of the version.php script."
  exit 1
fi

echo "New version is: $NEW_VERSION"

# 2. Adds and commits changes to Git
echo "Creating commit for version gestor-v$NEW_VERSION..."
# Stages the modified config.php AND any other changes
# in the working directory. This ensures that the commit includes
# all the work that has been done.
git add .
git commit -m "$COMMIT_DETAILS"

echo "Commit gestor-v$NEW_VERSION created successfully!"
echo "Pushing to remote repository..."
git push
