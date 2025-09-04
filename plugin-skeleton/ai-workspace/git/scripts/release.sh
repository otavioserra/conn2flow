#!/bin/bash
set -e
if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
  echo "Uso: ./ai-workspace/git/scripts/release.sh [tipo] 'Resumo Tag' 'Mensagem Commit'"; exit 1; fi
TYPE=$1; TAG_SUM=$2; COMMIT_MSG=$3
VERSION_SCRIPT="ai-workspace/git/scripts/version.php"
NEW_VERSION=$(php $VERSION_SCRIPT $TYPE)
[ -z "$NEW_VERSION" ] && { echo "Erro ao versionar"; exit 1; }
echo "Versão plugin: $NEW_VERSION"
git add plugin/ ai-workspace/git/scripts/version.php
git commit -m "$COMMIT_MSG"
git tag -a "plugin-v$NEW_VERSION" -m "$TAG_SUM"
git push
git push --tags
