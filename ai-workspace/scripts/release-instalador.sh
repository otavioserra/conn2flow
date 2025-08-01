#!/bin/bash

# Script para automatizar o processo de release do GESTOR-INSTALADOR:
# 1. Atualiza a versão no index.php
# 2. Adiciona as mudanças ao Git
# 3. Cria um commit padronizado
# 4. Cria uma tag Git com a nova versão

# Garante que o script pare se algum comando falhar
set -e

# Verifica se o tipo de release (patch, minor, major) foi passado como argumento
if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
  echo "Erro: Argumentos insuficientes."
  echo "Uso:   ./ai-workspace/scripts/release-instalador.sh [tipo] \"Resumo para a Tag\" \"Mensagem detalhada para o Commit\""
  echo "Exemplo: ./ai-workspace/scripts/release-instalador.sh patch \"Corrige caminho do .env\" \"fix(install): Corrige o caminho do .env durante a criação do autologin.\""
  exit 1
fi

RELEASE_TYPE=$1
TAG_SUMMARY=$2
COMMIT_DETAILS=$3
CONFIG_FILE="gestor-instalador/index.php"
VERSION_SCRIPT="ai-workspace/scripts/version-instalador.php"

# 1. Roda o script PHP para atualizar a versão no index.php
echo "Atualizando a versão do instalador ($RELEASE_TYPE)..."
NEW_VERSION=$(php $VERSION_SCRIPT $RELEASE_TYPE)

# Verifica se o script PHP foi executado com sucesso.
# Ele retornará uma string de versão não vazia em caso de sucesso.
if [ -z "$NEW_VERSION" ]; then
  echo "Erro: Falha ao atualizar a versão. Verifique a saída do script version-instalador.php."
  exit 1
fi

echo "Nova versão do instalador é: $NEW_VERSION"

# 2. Adiciona, commita e cria uma tag anotada no Git com mensagens distintas
echo "Criando commit e tag para a versão instalador-v$NEW_VERSION..."
git add .
git commit -m "$COMMIT_DETAILS"
git tag -a "instalador-v$NEW_VERSION" -m "$TAG_SUMMARY"

echo "Release instalador-v$NEW_VERSION criado com sucesso!"
echo "Não se esqueça de rodar 'git push' e 'git push --tags' para enviar ao repositório remoto."
