#!/bin/bash
set -e
if [ -z "$1" ]; then
  echo "Uso: ./ai-workspace/git/scripts/commit.sh 'Mensagem'"
  exit 1
fi
RELEASE_TYPE='patch'
MSG="$1"
VERSION_SCRIPT="ai-workspace/git/scripts/version.php"
NEW_VERSION=$(php $VERSION_SCRIPT $RELEASE_TYPE)
echo "Nova versão plugin: $NEW_VERSION"
git add plugin/ ai-workspace/git/scripts/version.php
git commit -m "$MSG"
