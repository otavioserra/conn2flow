#!/bin/bash

# ===== Script de Renovação de Tokens OAuth
# Renova access_token usando refresh_token e atualiza environment.json

set -e  # Parar em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função de log
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Verificar se jq está instalado
if ! command -v jq &> /dev/null; then
    error "jq não está instalado. Instale com: apt-get install jq ou brew install jq"
    exit 1
fi

# Verificar se curl está instalado
if ! command -v curl &> /dev/null; then
    error "curl não está instalado. Instale com: apt-get install curl ou brew install curl"
    exit 1
fi

# Caminho para o arquivo de ambiente (relativo à raiz do projeto)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"

# Verificar se arquivo existe
if [ ! -f "$ENV_FILE" ]; then
    error "Arquivo environment.json não encontrado: $ENV_FILE"
    exit 1
fi

log "🔄 Iniciando renovação de tokens OAuth..."

# Extrair configurações atuais
PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE")
PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE")
REFRESH_TOKEN=$(jq -r '.devAPI.refresh_token' "$ENV_FILE")

if [ "$PROJECT_TARGET" = "null" ] || [ -z "$PROJECT_TARGET" ]; then
    error "projectTarget não definido no environment.json"
    exit 1
fi

if [ "$PROJECT_URL" = "null" ] || [ -z "$PROJECT_URL" ]; then
    error "URL do projeto não encontrada no environment.json"
    exit 1
fi

if [ "$REFRESH_TOKEN" = "null" ] || [ -z "$REFRESH_TOKEN" ]; then
    error "refresh_token não encontrado no environment.json"
    exit 1
fi

log "Projeto alvo: $PROJECT_TARGET"
log "URL do projeto: $PROJECT_URL"

# Endpoint de refresh
REFRESH_URL="$PROJECT_URL/_api/oauth/refresh"

log "Tentando renovar tokens via: $REFRESH_URL"

# Fazer requisição de refresh
RESPONSE=$(curl -s -X POST "$REFRESH_URL" \
    -H "Content-Type: application/json" \
    -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}" 2>/dev/null)

# Verificar se a resposta é válida JSON
if ! echo "$RESPONSE" | jq . >/dev/null 2>&1; then
    error "Resposta inválida da API (não é JSON): $RESPONSE"
    exit 1
fi

# Extrair status da resposta
STATUS=$(echo "$RESPONSE" | jq -r '.status')

if [ "$STATUS" != "success" ]; then
    ERROR_MSG=$(echo "$RESPONSE" | jq -r '.message')
    error "Falha na renovação: $ERROR_MSG"

    # Limpar tokens se refresh token também estiver expirado
    warning "Limpando tokens expirados do environment.json..."
    jq '.devAPI.access_token = null | .devAPI.refresh_token = null' "$ENV_FILE" > "${ENV_FILE}.tmp" && mv "${ENV_FILE}.tmp" "$ENV_FILE"

    exit 1
fi

# Extrair novos tokens
NEW_ACCESS_TOKEN=$(echo "$RESPONSE" | jq -r '.data.access_token')
NEW_REFRESH_TOKEN=$(echo "$RESPONSE" | jq -r '.data.refresh_token')

if [ "$NEW_ACCESS_TOKEN" = "null" ] || [ -z "$NEW_ACCESS_TOKEN" ]; then
    error "Novo access_token não recebido na resposta"
    exit 1
fi

if [ "$NEW_REFRESH_TOKEN" = "null" ] || [ -z "$NEW_REFRESH_TOKEN" ]; then
    warning "Novo refresh_token não recebido, mantendo o atual"
    NEW_REFRESH_TOKEN=$REFRESH_TOKEN
fi

log "Tokens renovados com sucesso!"

# Atualizar environment.json
jq --arg access "$NEW_ACCESS_TOKEN" --arg refresh "$NEW_REFRESH_TOKEN" \
    '.devAPI.access_token = $access | .devAPI.refresh_token = $refresh' \
    "$ENV_FILE" > "${ENV_FILE}.tmp" && mv "${ENV_FILE}.tmp" "$ENV_FILE"

success "Tokens atualizados no environment.json"
success "Access token renovado com sucesso"

# Retornar o novo access token para uso imediato
echo "$NEW_ACCESS_TOKEN"

exit 0