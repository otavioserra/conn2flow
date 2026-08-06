#!/bin/bash

# Run: bash ./ai-workspace/git/scripts/release.sh TYPE "TAG_MSG" "COMMIT_MSG" [automatic|manual]

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
  echo "Usage:   ./ai-workspace/en/scripts/releases/release.sh [type] \"Tag Summary\" \"Detailed Commit Message\" [automatic|manual]"
  echo "Example: ./ai-workspace/en/scripts/releases/release.sh patch \"Fix password validation\" \"fix(login): Fix bug preventing special characters in password.\""
  exit 1
fi

RELEASE_TYPE=$1
TAG_SUMMARY=$2
COMMIT_DETAILS=$3
RELEASE_MODE=${4:-automatic}
CONFIG_FILE="gestor/config.php"
VERSION_SCRIPT="ai-workspace/en/scripts/releases/version.php"
WORKFLOW_FILE=".github/workflows/release-gestor.yml"

if [ "$RELEASE_MODE" != "automatic" ] && [ "$RELEASE_MODE" != "manual" ]; then
  echo "Error: Invalid release mode '$RELEASE_MODE'. Use automatic or manual."
  exit 1
fi

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

## Removes all old tags matching the same major.minor series as NEW_VERSION
VERSION_MAJOR=$(echo "$NEW_VERSION" | cut -d'.' -f1)
VERSION_MINOR=$(echo "$NEW_VERSION" | cut -d'.' -f2)

if [ -z "$VERSION_MAJOR" ] || [ -z "$VERSION_MINOR" ]; then
  echo "Error: Invalid version format returned by version.php: $NEW_VERSION"
  exit 1
fi

TAG_SERIES="${VERSION_MAJOR}.${VERSION_MINOR}"
OLD_TAG_PATTERN="gestor-v${TAG_SERIES}.*"

set +e
OLD_TAGS=$(git tag --list "$OLD_TAG_PATTERN")
if [ -n "$OLD_TAGS" ]; then
  echo "Removing all old tags matching $OLD_TAG_PATTERN: $OLD_TAGS"
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
# Never commit local release artifacts by accident.
rm -f gestor.zip gestor.zip.sha256 instalador.zip
# Adds modified config.php AND any other changes to stage
# in the working directory. This ensures the release commit includes
# all work performed.
git add .
git commit -m "$COMMIT_DETAILS"
git tag -a "gestor-v$NEW_VERSION" -m "$TAG_SUMMARY"

echo "Release gestor-v$NEW_VERSION created successfully!"

git push
git push --tags

if [ "$RELEASE_MODE" = "manual" ]; then
  TAG_NAME="gestor-v$NEW_VERSION"
  RELEASE_TITLE="Gestor $TAG_NAME"
  TMP_RELEASE_DIR=$(mktemp -d)
  BODY_FILE="$TMP_RELEASE_DIR/release-body.md"
  DEST_ZIP="$TMP_RELEASE_DIR/gestor.zip"
  DEST_SHA="$TMP_RELEASE_DIR/gestor.zip.sha256"

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

  TMP_DIR=$(mktemp -d)
  cp -a gestor "$TMP_DIR/gestor"

  # Align manual packaging with the workflow cleanup to avoid sensitive/unneeded files.
  rm -rf "$TMP_DIR/gestor/.git" "$TMP_DIR/gestor/.gitignore" "$TMP_DIR/gestor/.gitattributes"
  # Composer may have installed dependencies from source, leaving nested Git
  # repositories inside vendor. They are never needed at runtime and can add
  # roughly 100 MB to the release artifact.
  find "$TMP_DIR/gestor" -type d -name '.git' -prune -exec rm -rf {} +
  rm -rf "$TMP_DIR/gestor/vendor/bin/.phpunit"* "$TMP_DIR/gestor/vendor/composer/tmp-"*
  rm -rf "$TMP_DIR/gestor/tests"
  find "$TMP_DIR/gestor" -type f -name 'phpunit.xml*' -delete
  rm -rf "$TMP_DIR/gestor/resources"
  find "$TMP_DIR/gestor/modulos" -type d -name "resources" -exec rm -rf {} + 2>/dev/null || true
  rm -rf "$TMP_DIR/gestor/node_modules"
  rm -f "$TMP_DIR/gestor/package.json" "$TMP_DIR/gestor/package-lock.json"
  find "$TMP_DIR/gestor" -maxdepth 1 -type f -name '.env*' -delete

  if [ -d "$TMP_DIR/gestor/autenticacoes" ]; then
    find "$TMP_DIR/gestor/autenticacoes" -type f -name '.env*' -not -path '*/autenticacoes.exemplo/*' -delete
  fi

  find "$TMP_DIR/gestor" -name "*.DS_Store*" -type f -delete
  find "$TMP_DIR/gestor" -name "*.log*" -type f -delete

  # Defensive check: never publish an artifact containing Git metadata.
  if [ -n "$(find "$TMP_DIR/gestor" -type d -name '.git' -print -quit)" ]; then
    rm -rf "$TMP_DIR"
    echo "Error: Nested .git directory found in manual release staging"
    exit 1
  fi

  if command -v zip >/dev/null 2>&1; then
    cd "$TMP_DIR/gestor"
    zip -r "$DEST_ZIP" .
    cd - >/dev/null
  elif command -v 7z >/dev/null 2>&1; then
    cd "$TMP_DIR/gestor"
    7z a -tzip "$DEST_ZIP" . >/dev/null
    cd - >/dev/null
  elif command -v 7za >/dev/null 2>&1; then
    cd "$TMP_DIR/gestor"
    7za a -tzip "$DEST_ZIP" . >/dev/null
    cd - >/dev/null
  elif command -v 7zz >/dev/null 2>&1; then
    cd "$TMP_DIR/gestor"
    7zz a -tzip "$DEST_ZIP" . >/dev/null
    cd - >/dev/null
  elif command -v powershell >/dev/null 2>&1; then
    PS_SCRIPT=$(mktemp)
    cat > "$PS_SCRIPT" <<'PS'
param(
  [string]$SourceDir,
  [string]$DestinationZip
)

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

if (Test-Path -LiteralPath $DestinationZip) {
  Remove-Item -LiteralPath $DestinationZip -Force
}

$zip = [System.IO.Compression.ZipFile]::Open($DestinationZip, 1)
try {
  Get-ChildItem -LiteralPath $SourceDir -Recurse -File | ForEach-Object {
    $full = $_.FullName
    $relative = $full.Substring($SourceDir.Length).TrimStart('\\', '/').Replace('\\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $full, $relative, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
  }
}
finally {
  $zip.Dispose()
}
PS

    if command -v cygpath >/dev/null 2>&1; then
      SRC_WIN=$(cygpath -w "$TMP_DIR/gestor")
      DEST_WIN=$(cygpath -w "$DEST_ZIP")
      SCRIPT_WIN=$(cygpath -w "$PS_SCRIPT")
    else
      SRC_WIN="$TMP_DIR/gestor"
      DEST_WIN="$DEST_ZIP"
      SCRIPT_WIN="$PS_SCRIPT"
    fi
    powershell -NoProfile -ExecutionPolicy Bypass -File "$SCRIPT_WIN" -SourceDir "$SRC_WIN" -DestinationZip "$DEST_WIN" >/dev/null
    rm -f "$PS_SCRIPT"
  elif command -v pwsh >/dev/null 2>&1; then
    PS_SCRIPT=$(mktemp)
    cat > "$PS_SCRIPT" <<'PS'
param(
  [string]$SourceDir,
  [string]$DestinationZip
)

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

if (Test-Path -LiteralPath $DestinationZip) {
  Remove-Item -LiteralPath $DestinationZip -Force
}

$zip = [System.IO.Compression.ZipFile]::Open($DestinationZip, 1)
try {
  Get-ChildItem -LiteralPath $SourceDir -Recurse -File | ForEach-Object {
    $full = $_.FullName
    $relative = $full.Substring($SourceDir.Length).TrimStart('\\', '/').Replace('\\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $full, $relative, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
  }
}
finally {
  $zip.Dispose()
}
PS

    SRC_UNIX="$TMP_DIR/gestor"
    DEST_UNIX="$DEST_ZIP"
    pwsh -NoProfile -File "$PS_SCRIPT" -SourceDir "$SRC_UNIX" -DestinationZip "$DEST_UNIX" >/dev/null
    rm -f "$PS_SCRIPT"
  else
    rm -rf "$TMP_DIR"
    echo "Error: Neither 'zip', '7z' nor PowerShell compression is available to create gestor.zip"
    exit 1
  fi

  rm -rf "$TMP_DIR"

  sha256sum "$DEST_ZIP" | awk '{print $1}' > "$DEST_SHA"

  if gh release view "$TAG_NAME" >/dev/null 2>&1; then
    gh release delete "$TAG_NAME" --yes
  fi

  gh release create "$TAG_NAME" "$DEST_ZIP#gestor.zip" "$DEST_SHA#gestor.zip.sha256" \
    --title "$RELEASE_TITLE" \
    --notes-file "$BODY_FILE" \
    --latest

  rm -rf "$TMP_RELEASE_DIR"

  echo "Manual release created: $TAG_NAME"
fi
