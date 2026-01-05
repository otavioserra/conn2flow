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

# Verificar se deve executar em modo silencioso e processar argumentos
QUIET_MODE=false
PROJECT_TARGET_ARG=""
ENV_FILE_ARG=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --quiet|--silent)
            QUIET_MODE=true
            shift
            ;;
        --project=*)
            PROJECT_TARGET_ARG="${1#*=}"
            shift
            ;;
        --env-file=*)
            ENV_FILE_ARG="${1#*=}"
            shift
            ;;
        *)
            # Ignorar argumentos desconhecidos
            shift
            ;;
    esac
done

# Flag de sucesso da renovação
RENEW_SUCCESS=1

# Função de log condicional
log() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" >&2
    fi
}

success() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${GREEN}✅ $1${NC}" >&2
    fi
}

error() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${RED}❌ $1${NC}" >&2
    fi
}

warning() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${YELLOW}⚠️  $1${NC}" >&2
    fi
}

# Função para normalizar URL (remover barras duplas)
normalize_url() {
    local url="$1"
    local endpoint="$2"

    # Remover todas as barras finais da URL base
    while [[ "$url" == */ ]]; do
        url="${url%/}"
    done

    # Concatenar com endpoint (sempre começa com /)
    echo "${url}${endpoint}"
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

# Caminho para o arquivo de ambiente
if [ -n "$ENV_FILE_ARG" ]; then
    # Usar caminho passado por parâmetro (para integração com outros scripts)
    ENV_FILE="$ENV_FILE_ARG"
else
    # Calcular automaticamente baseado na localização do script
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
    ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
fi

# Verificar se arquivo existe
if [ ! -f "$ENV_FILE" ]; then
    error "Arquivo environment.json não encontrado: $ENV_FILE"
    exit 1
fi

log "🔄 Iniciando renovação de tokens OAuth..."

# Extrair configurações atuais
if [ -n "$PROJECT_TARGET_ARG" ]; then
    # Usar projeto passado por parâmetro
    PROJECT_TARGET="$PROJECT_TARGET_ARG"
else
    # Ler do environment.json
    PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE")
fi

PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE")
REFRESH_TOKEN=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.refresh_token" "$ENV_FILE")

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

# Endpoint de refresh (normalizado para evitar barras duplas)
REFRESH_URL=$(normalize_url "$PROJECT_URL" "/_api/oauth/refresh")

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

    # AVISO: Não limpar tokens automaticamente para evitar perda de tokens válidos
    # em caso de falhas temporárias de rede ou API
    warning "Mantendo tokens existentes. Execute renovação manual se necessário."
    warning "Possível causa: $ERROR_MSG"

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
jq --arg access "$NEW_ACCESS_TOKEN" --arg refresh "$NEW_REFRESH_TOKEN" --arg project "$PROJECT_TARGET" \
    '.devProjects[$project].api.access_token = $access | .devProjects[$project].api.refresh_token = $refresh' \
    "$ENV_FILE" > "${ENV_FILE}.tmp" && mv "${ENV_FILE}.tmp" "$ENV_FILE"

success "Tokens atualizados no environment.json"
success "Access token renovado com sucesso"

# Marcar como sucesso
RENEW_SUCCESS=0

# Se estiver em modo quiet, retornar o novo access_token para integração com outros scripts
if [ "$QUIET_MODE" = true ]; then
    if [ $RENEW_SUCCESS -eq 0 ]; then
        echo "$NEW_ACCESS_TOKEN"
    fi
    # Em modo quiet, não retornar nada em caso de falha (exit code já indica falha)
else
    # Modo normal: sempre retornar o token se renovação foi bem-sucedida
    if [ $RENEW_SUCCESS -eq 0 ]; then
        echo "$NEW_ACCESS_TOKEN"
    fi
fi

exit 0