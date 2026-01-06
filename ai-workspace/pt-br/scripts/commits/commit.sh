#!/bin/bash

# Script para automatizar o processo de commit:
# 1. Atualiza a versão no config.php
# 2. Adiciona as mudanças ao Git
# 3. Cria um commit padronizado

# Garante que o script pare se algum comando falhar
set -e

# Verifica se a mensagem do commit foi passada como argumento
if [ -z "$1" ]; then
  echo "Erro: Argumentos insuficientes."
  echo "Uso:   ./ai-workspace/pt-br/scripts/commits/commit.sh \"Mensagem detalhada para o Commit\""
  echo "Exemplo: ./ai-workspace/pt-br/scripts/commits/commit.sh \"Corrige validação de senha\""
  exit 1
fi

RELEASE_TYPE='patch'
COMMIT_DETAILS=$1
VERSION_SCRIPT="ai-workspace/pt-br/scripts/releases/version.php"

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

# 2. Adiciona e commita as mudanças no Git
echo "Criando commit para a versão gestor-v$NEW_VERSION..."
# Adiciona ao stage o config.php modificado E quaisquer outras alterações
# no diretório de trabalho. Isso garante que o commit inclua
# todo o trabalho que foi realizado.
git add .
git commit -m "$COMMIT_DETAILS"

echo "Commit gestor-v$NEW_VERSION criado com sucesso!"
echo "Fazendo push para o repositório remoto..."
git push