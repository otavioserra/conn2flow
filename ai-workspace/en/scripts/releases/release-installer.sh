#!/bin/bash

# Run: bash ./ai-workspace/en/scripts/releases/release-installer.sh TYPE "TAG_MSG" "COMMIT_MSG" [automatic|manual]

# Script to automate the GESTOR-INSTALLER release process:
# 1. Updates the version in index.php
# 2. Adds changes to Git
# 3. Creates a standardized commit
# 4. Creates a Git tag with the new version

# Ensures the script stops if any command fails
set -euo pipefail

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

if [[ ! "$RELEASE_TYPE" =~ ^(patch|minor|major)$ ]]; then
  echo "Error: Invalid release type '$RELEASE_TYPE'. Use patch, minor or major."
  exit 1
fi

if [ "$RELEASE_MODE" != "automatic" ] && [ "$RELEASE_MODE" != "manual" ]; then
  echo "Error: Invalid release mode '$RELEASE_MODE'. Use automatic or manual."
  exit 1
fi

if [ ! -f "$CONFIG_FILE" ] || [ ! -f "$VERSION_SCRIPT" ] || [ ! -f "$WORKFLOW_FILE" ]; then
  echo "Error: Run this command from the Conn2Flow Core repository root."
  exit 1
fi

if [ -n "$(git status --porcelain --untracked-files=all)" ]; then
  echo "Error: The working tree must be clean before starting a release."
  exit 1
fi

CURRENT_BRANCH=$(git branch --show-current)
if [ -z "$CURRENT_BRANCH" ]; then
  echo "Error: Cannot publish a release from a detached HEAD."
  exit 1
fi

NEXT_VERSION=$(php "$VERSION_SCRIPT" "$RELEASE_TYPE" --dry-run)
TAG_NAME="instalador-v$NEXT_VERSION"

RELEASE_DOCS=(README.md README-PT-BR.md CHANGELOG.md)
for release_doc in "${RELEASE_DOCS[@]}"; do
  if [ ! -s "$release_doc" ]; then
    echo "Error: Required release documentation is missing or empty: $release_doc"
    exit 1
  fi
done
if ! find .github/workflows -maxdepth 1 -type f \( -name '*.yml' -o -name '*.yaml' \) -print -quit | grep -q .; then
  echo "Error: No GitHub Actions workflow was found for release verification."
  exit 1
fi
grep -Fq "instalador-v$NEXT_VERSION" README.md || { echo "Error: README.md is not synchronized with instalador-v$NEXT_VERSION."; exit 1; }
grep -Fq "instalador-v$NEXT_VERSION" README-PT-BR.md || { echo "Error: README-PT-BR.md is not synchronized with instalador-v$NEXT_VERSION."; exit 1; }

if git rev-parse -q --verify "refs/tags/$TAG_NAME" >/dev/null; then
  echo "Error: Tag $TAG_NAME already exists."
  exit 1
fi

if [ "$RELEASE_MODE" = "manual" ]; then
  command -v gh >/dev/null 2>&1 || { echo "Error: GitHub CLI (gh) is required for manual releases."; exit 1; }
  gh auth status >/dev/null
fi

# 1. Runs the PHP script to update the version in index.php
echo "Updating installer version ($RELEASE_TYPE)..."
NEW_VERSION=$(php "$VERSION_SCRIPT" "$RELEASE_TYPE")

# Checks if the PHP script executed successfully.
# It will return a non-empty version string on success.
if [ -z "$NEW_VERSION" ]; then
  echo "Error: Failed to update version. Check the output of version-installer.php script."
  exit 1
fi

echo "New installer version is: $NEW_VERSION"


# 2. Adds, commits, and creates an annotated Git tag with distinct messages
echo "Creating commit and tag for version installer-v$NEW_VERSION..."
CHANGED_PATHS=$(git status --porcelain --untracked-files=all | sed 's/^...//')
if [ "$CHANGED_PATHS" != "$CONFIG_FILE" ]; then
  echo "Error: Installer release changed unexpected paths:"
  printf '%s\n' "$CHANGED_PATHS"
  exit 1
fi
git add -- "$CONFIG_FILE"
git commit -m "$COMMIT_DETAILS"
git tag -a "instalador-v$NEW_VERSION" -m "$TAG_SUMMARY"

echo "Release installer-v$NEW_VERSION created successfully!"

git push --atomic origin "$CURRENT_BRANCH" "instalador-v$NEW_VERSION"

if [ "$RELEASE_MODE" = "manual" ]; then
  TAG_NAME="instalador-v$NEW_VERSION"
  RELEASE_TITLE="Instalador $TAG_NAME"
  TMP_RELEASE_DIR=$(mktemp -d)
  BODY_FILE="$TMP_RELEASE_DIR/release-body.md"
  DEST_ZIP="$TMP_RELEASE_DIR/instalador.zip"

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

  if command -v zip >/dev/null 2>&1; then
    cd gestor-instalador
    zip -r "$DEST_ZIP" . \
      -x "*.git*" \
      -x "*.DS_Store*" \
      -x "*.log*" \
      -x "temp/*" \
      -x ".env.debug"
    cd ..
  elif command -v 7z >/dev/null 2>&1 || command -v 7za >/dev/null 2>&1 || command -v 7zz >/dev/null 2>&1; then
    TMP_DIR=$(mktemp -d)
    cp -a gestor-instalador "$TMP_DIR/gestor-instalador"

    rm -rf "$TMP_DIR/gestor-instalador/temp"
    rm -f "$TMP_DIR/gestor-instalador/.env.debug"
    find "$TMP_DIR/gestor-instalador" -name "*.DS_Store*" -type f -delete
    find "$TMP_DIR/gestor-instalador" -name "*.log*" -type f -delete
    find "$TMP_DIR/gestor-instalador" -name "*.git*" -exec rm -rf {} +

    if command -v 7z >/dev/null 2>&1; then
      (cd "$TMP_DIR/gestor-instalador" && 7z a -tzip "$DEST_ZIP" . >/dev/null)
    elif command -v 7za >/dev/null 2>&1; then
      (cd "$TMP_DIR/gestor-instalador" && 7za a -tzip "$DEST_ZIP" . >/dev/null)
    else
      (cd "$TMP_DIR/gestor-instalador" && 7zz a -tzip "$DEST_ZIP" . >/dev/null)
    fi

    rm -rf "$TMP_DIR"
  else
    TMP_DIR=$(mktemp -d)
    cp -a gestor-instalador "$TMP_DIR/gestor-instalador"

    rm -rf "$TMP_DIR/gestor-instalador/temp"
    rm -f "$TMP_DIR/gestor-instalador/.env.debug"
    find "$TMP_DIR/gestor-instalador" -name "*.DS_Store*" -type f -delete
    find "$TMP_DIR/gestor-instalador" -name "*.log*" -type f -delete
    find "$TMP_DIR/gestor-instalador" -name "*.git*" -exec rm -rf {} +

    if command -v powershell >/dev/null 2>&1; then
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
        SRC_WIN=$(cygpath -w "$TMP_DIR/gestor-instalador")
        DEST_WIN=$(cygpath -w "$DEST_ZIP")
        SCRIPT_WIN=$(cygpath -w "$PS_SCRIPT")
      else
        SRC_WIN="$TMP_DIR/gestor-instalador"
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

      SRC_UNIX="$TMP_DIR/gestor-instalador"
      DEST_UNIX="$DEST_ZIP"
      pwsh -NoProfile -File "$PS_SCRIPT" -SourceDir "$SRC_UNIX" -DestinationZip "$DEST_UNIX" >/dev/null
      rm -f "$PS_SCRIPT"
    else
      rm -rf "$TMP_DIR"
      echo "Error: Neither 'zip', '7z' nor PowerShell compression is available to create instalador.zip"
      exit 1
    fi

    rm -rf "$TMP_DIR"
  fi

  if gh release view "$TAG_NAME" >/dev/null 2>&1; then
    gh release delete "$TAG_NAME" --yes
  fi

  gh release create "$TAG_NAME" "$DEST_ZIP#instalador.zip" \
    --title "$RELEASE_TITLE" \
    --notes-file "$BODY_FILE" \
    --latest

  rm -rf "$TMP_RELEASE_DIR"

  echo "Manual release created: $TAG_NAME"
fi
