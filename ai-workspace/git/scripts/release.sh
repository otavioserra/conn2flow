#!/bin/bash

# Executar: bash ./ai-workspace/git/scripts/release.sh TIPO "TAG_MSG" "COMMIT_MSG"

# Script para automatizar o processo de release:
# 1. Atualiza a versão no config.php
# 2. Adiciona as mudanças ao Git
# 3. Cria um commit padronizado
# 4. Cria uma tag Git com a nova versão

# Garante que o script pare se algum comando falhar
set -e

# Verifica se o tipo de release (patch, minor, major) foi passado como argumento
if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ]; then
  echo "Erro: Argumentos insuficientes."
  echo "Uso:   ./ai-workspace/git/scripts/release.sh [tipo] \"Resumo para a Tag\" \"Mensagem detalhada para o Commit\""
  echo "Exemplo: ./ai-workspace/git/scripts/release.sh patch \"Corrige validação de senha\" \"fix(login): Corrige bug que impedia o uso de caracteres especiais na senha.\""
  exit 1
fi

RELEASE_TYPE=$1
TAG_SUMMARY=$2
COMMIT_DETAILS=$3
CONFIG_FILE="gestor/config.php"
VERSION_SCRIPT="ai-workspace/scripts/version.php"

# 1. Roda o script PHP para atualizar a versão no config.php
echo "Atualizando a versão ($RELEASE_TYPE)..."
NEW_VERSION=$(php $VERSION_SCRIPT $RELEASE_TYPE)

# Verifica se o script PHP foi executado com sucesso.
# Ele retornará uma string de versão não vazia em caso de sucesso.
if [ -z "$NEW_VERSION" ]; then
  echo "Erro: Falha ao atualizar a versão. Verifique a saída do script version.php."
  exit 1
fi

echo "Nova versão é: $NEW_VERSION"

# 2. Adiciona, commita e cria uma tag anotada no Git com mensagens distintas
echo "Criando commit e tag para a versão gestor-v$NEW_VERSION..."
# Adiciona ao stage o config.php modificado E quaisquer outras alterações
# no diretório de trabalho. Isso garante que o commit do release inclua
# todo o trabalho que foi realizado.
git add .
git commit -m "$COMMIT_DETAILS"
git tag -a "gestor-v$NEW_VERSION" -m "$TAG_SUMMARY"

echo "Release gestor-v$NEW_VERSION criado com sucesso!"
echo "Não se esqueça de rodar 'git push' e 'git push --tags' para enviar ao repositório remoto."