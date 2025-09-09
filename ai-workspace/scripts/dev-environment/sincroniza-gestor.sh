#!/bin/bash
# Script de sincronização segura do gestor para o ambiente Docker
# Copia apenas arquivos novos ou modificados, nunca apaga nada da origem
# Uso: bash ./ai-workspace/scripts/dev-environment/sincroniza-gestor.sh checksum
#
# Origem:   gestor/
# Destino:  dev-environment/data/sites/localhost/conn2flow-gestor/
# 
# Load variables from environment.json
# Safe synchronization script for the gestor to the Docker environment
# Copies only new or modified files, never deletes anything from the source
#
# Usage:
#   bash ./ai-workspace/scripts/dev-environment/sincroniza-gestor.sh [padrao|checksum|forcar]
#
# All paths (source, target, dockerPath) are read from dev-environment/data/environment.json:
#   - devEnvironment.source:     local source folder (e.g. gestor/)
#   - devEnvironment.target:     local target folder (e.g. dev-environment/data/sites/localhost/conn2flow-gestor/)
#   - devEnvironment.dockerPath: path inside the Docker container
#
# This allows each developer to configure their own paths without editing the script.

# Load variables from environment.json
ENV_JSON="$(dirname "$0")/../../dev-environment/data/environment.json"
if [ ! -f "$ENV_JSON" ]; then
  echo "Error: environment.json not found at $ENV_JSON"
  exit 1
fi

# Try to use jq, fallback to grep/sed if not available
if command -v jq >/dev/null 2>&1; then
  ORIGEM=$(jq -r '.devEnvironment.source' "$ENV_JSON")
  DESTINO=$(jq -r '.devEnvironment.target' "$ENV_JSON")
  PATH_DOCKER=$(jq -r '.devEnvironment.dockerPath' "$ENV_JSON")
else
  ORIGEM=$(grep '"source"' "$ENV_JSON" | sed -E 's/.*"source" *: *"([^"]*)".*/\1/')
  DESTINO=$(grep '"target"' "$ENV_JSON" | sed -E 's/.*"target" *: *"([^"]*)".*/\1/')
  PATH_DOCKER=$(grep '"dockerPath"' "$ENV_JSON" | sed -E 's/.*"dockerPath" *: *"([^"]*)".*/\1/')
fi

# Validate variables
if [ -z "$ORIGEM" ] || [ "$ORIGEM" = "null" ]; then
  echo "Error: 'source' not set in environment.json (devEnvironment.source)"
  exit 1
fi
if [ -z "$DESTINO" ] || [ "$DESTINO" = "null" ]; then
  echo "Error: 'target' not set in environment.json (devEnvironment.target)"
  exit 1
fi
if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "null" ]; then
  echo "Error: 'dockerPath' not set in environment.json (devEnvironment.dockerPath)"
  exit 1
fi

# Modo de sincronização: padrao (default), checksum, forcar
MODO=${1:-padrao}

case "$MODO" in
  padrao|"" )
    echo "🔄 Modo: padrão (data/hora, não sobrescreve arquivos mais novos no destino)"
    CMD=(rsync -avu "$ORIGEM" "$DESTINO")
    ;;
  checksum )
    echo "🔄 Modo: checksum (compara conteúdo dos arquivos)"
    CMD=(rsync -av --checksum "$ORIGEM" "$DESTINO")
    ;;
  forcar )
    echo "🔄 Modo: forçar sobrescrita de todos os arquivos (ignora data/hora)"
    CMD=(rsync -av --ignore-times "$ORIGEM" "$DESTINO")
    ;;
  * )
    echo "❌ Modo inválido. Use: padrao | checksum | forcar"
    exit 1
    ;;
esac

# Executa o comando escolhido
"${CMD[@]}"

# Atualiza permissões de pasta
docker exec conn2flow-app bash -c "chown -R www-data:www-data $PATH_DOCKER"

# Mensagem final
if [ $? -eq 0 ]; then
  echo "✅ Sincronização concluída com sucesso!"
else
  echo "❌ Ocorreu um erro na sincronização."
fi
