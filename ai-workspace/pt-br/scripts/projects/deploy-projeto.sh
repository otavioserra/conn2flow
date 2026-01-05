#!/bin/bash

# Script: Deploy de Projetos via API
# ------------------------------------------------------------------------------
# Este script automatiza o deploy completo de um projeto via API OAuth.
# Funcionamento:
# 1. Lê o arquivo environment.json para identificar o projeto alvo
# 2. Atualiza dados e recursos do projeto (layouts, páginas, componentes)
# 3. Compacta a pasta completa do projeto em ZIP (excluindo dados dinâmicos)
# 4. Faz upload via API para o endpoint /_api/project/update
# 5. Recebe confirmação de processamento e atualização no servidor

set -e  # Para o script em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função de log
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Função para obter token OAuth do projeto específico
get_oauth_token() {
    local token_file="$PROJECT_ROOT/dev-environment/data/environment.json"
    local project_target="$1"

    if [ ! -f "$token_file" ]; then
        log_error "Arquivo de tokens não encontrado: $token_file"
        return 1
    fi

    ACCESS_TOKEN=$(jq -r ".devProjects.\"$project_target\".api.access_token" "$token_file" 2>/dev/null)
    if [ -z "$ACCESS_TOKEN" ] || [ "$ACCESS_TOKEN" = "null" ]; then
        log_error "Access token não encontrado para o projeto $project_target"
        return 1
    fi

    echo "$ACCESS_TOKEN"
    return 0
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

# Função para fazer upload do ZIP
upload_zip() {
    local zip_file="$1"
    local api_url="$2"
    local token="$3"
    local project_target="$4"

    if [ ! -f "$zip_file" ]; then
        log_error "Arquivo ZIP não encontrado: $zip_file"
        return 1
    fi

    log "Enviando arquivo: $(basename "$zip_file")"
    log "URL da API: $api_url"
    log "Tamanho do arquivo: $(du -h "$zip_file" | cut -f1)"

    # Usar curl para fazer upload multipart/form-data
    response=$(curl -s -w "\n%{http_code}" \
        -H "Authorization: Bearer $token" \
        -H "X-Project-ID: $project_target" \
        -F "project_zip=@$zip_file" \
        "$api_url")

    # Separar corpo da resposta e código HTTP
    http_code=$(echo "$response" | tail -n1)
    response_body=$(echo "$response" | head -n -1)

    # Definir variável global para o código HTTP
    UPLOAD_HTTP_CODE=$http_code

    log "Código HTTP: $http_code"

    if [ "$http_code" -eq 200 ]; then
        log_success "Deploy realizado com sucesso!"
        echo "$response_body" | jq . 2>/dev/null || echo "$response_body"
        return 0
    else
        log_error "Falha no deploy (HTTP $http_code)"
        echo "$response_body"
        return 1
    fi
}

# Caminhos
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
TEMP_DIR="$PROJECT_ROOT/temp"

# Parsing de argumentos
PROJECT_TARGET_OVERRIDE=""
while [[ $# -gt 0 ]]; do
    case $1 in
        --project|-p)
            PROJECT_TARGET_OVERRIDE="$2"
            shift 2
            ;;
        --help|-h)
            echo "Uso: $0 [--project|-p PROJECT_ID]"
            echo ""
            echo "Opções:"
            echo "  --project, -p PROJECT_ID    Identificador do projeto para deploy (opcional)"
            echo "                              Se não informado, usa o valor de devEnvironment.projectTarget do environment.json"
            echo "  --help, -h                  Mostra esta ajuda"
            exit 0
            ;;
        *)
            log_error "Opção desconhecida: $1"
            echo "Use --help para ver as opções disponíveis."
            exit 1
            ;;
    esac
done

# Verificar se arquivos existem
if [ ! -f "$ENV_FILE" ]; then
    log_error "Arquivo environment.json não encontrado: $ENV_FILE"
    exit 1
fi

log "Iniciando deploy de projeto..."
log "Arquivo de ambiente: $ENV_FILE"

# Determinar o projeto alvo
if [ -n "$PROJECT_TARGET_OVERRIDE" ]; then
    PROJECT_TARGET="$PROJECT_TARGET_OVERRIDE"
    log "Projeto alvo especificado via argumento: $PROJECT_TARGET"
else
    # Ler o projectTarget do environment.json
    PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)

    if [ -z "$PROJECT_TARGET" ] || [ "$PROJECT_TARGET" = "null" ]; then
        log_error "Não foi possível encontrar devEnvironment.projectTarget no arquivo de ambiente"
        log_error "Use --project para especificar o identificador do projeto"
        exit 1
    fi

    log "Projeto alvo identificado no environment.json: $PROJECT_TARGET"
fi

# Verificar se o projeto existe no environment.json
PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_FILE" 2>/dev/null)

if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
    log_error "Projeto '$PROJECT_TARGET' não encontrado no environment.json"
    log_error "Verifique se o identificador está correto e se o projeto está configurado"
    exit 1
fi

# Ler o caminho do projeto
PROJECT_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path" "$ENV_FILE" 2>/dev/null)

if [ -z "$PROJECT_PATH" ] || [ "$PROJECT_PATH" = "null" ]; then
    log_error "Não foi possível encontrar o caminho do projeto $PROJECT_TARGET"
    exit 1
fi

log "Caminho do projeto: $PROJECT_PATH"

# Verificar se o diretório do projeto existe
if [ ! -d "$PROJECT_PATH" ]; then
    log_error "Diretório do projeto não existe: $PROJECT_PATH"
    exit 1
fi

# Executar atualização de dados e recursos antes do deploy
log "Atualizando dados e recursos do projeto..."
UPDATE_SCRIPT="$SCRIPT_DIR/atualizacao-dados-recursos.sh"

if [ -f "$UPDATE_SCRIPT" ]; then
    log "Executando atualização de recursos: $UPDATE_SCRIPT --project $PROJECT_TARGET"
    
    if "$UPDATE_SCRIPT" --project "$PROJECT_TARGET"; then
        log_success "Dados e recursos atualizados com sucesso!"
    else
        log_error "Falha na atualização de dados e recursos"
        exit 1
    fi
else
    log_warning "Script de atualização de recursos não encontrado: $UPDATE_SCRIPT"
    log_warning "Continuando com deploy (dados podem estar desatualizados)"
fi

# Criar diretório temporário se não existir
if [ ! -d "$TEMP_DIR" ]; then
    mkdir -p "$TEMP_DIR"
    log "Diretório temporário criado: $TEMP_DIR"
fi

# Nome do arquivo ZIP
ZIP_FILE="$TEMP_DIR/${PROJECT_TARGET}_$(date +'%Y%m%d_%H%M%S').zip"

log "Preparando pacote de deploy..."
log "Diretório fonte: $PROJECT_PATH"
log "Arquivo destino: $ZIP_FILE"

# Compactar o projeto (excluindo arquivos temporários, .git e pasta resources)
# Nota: A pasta resources é excluída porque contém dados gerados dinamicamente
# que serão recriados pelo sistema durante a atualização
cd "$PROJECT_PATH"
"7z" a -tzip "$ZIP_FILE" . -xr0!*.git* -xr0!*.tmp -xr0!*.log -xr0!temp/ -xr0!logs/ -xr0!resources/ > /dev/null 2>&1

if [ ! -f "$ZIP_FILE" ]; then
    log_error "Falha ao criar pacote ZIP"
    exit 1
fi

log_success "Pacote ZIP criado com sucesso: $(basename "$ZIP_FILE")"

# Obter token OAuth
log "Obtendo token de autenticação..."
ACCESS_TOKEN=$(get_oauth_token "$PROJECT_TARGET")

if [ $? -ne 0 ]; then
    log_error "Falha ao obter token de autenticação"
    rm -f "$ZIP_FILE"
    exit 1
fi

log "Token obtido com sucesso"

# Ler a URL do projeto
PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE" 2>/dev/null)

if [ -z "$PROJECT_URL" ] || [ "$PROJECT_URL" = "null" ]; then
    log_error "Não foi possível encontrar a URL do projeto $PROJECT_TARGET"
    exit 1
fi

log "URL do projeto: $PROJECT_URL"

# URL da API baseada na URL do projeto (normalizada para evitar barras duplas)
API_URL=$(normalize_url "$PROJECT_URL" "/_api/project/update")

log "Iniciando deploy via API..."

# Fazer upload com tentativa de renovação de token
if upload_zip "$ZIP_FILE" "$API_URL" "$ACCESS_TOKEN" "$PROJECT_TARGET"; then
    log_success "Deploy concluído com sucesso!"
    # Limpar arquivo temporário
    rm -f "$ZIP_FILE"
    log "Pacote temporário removido: $(basename "$ZIP_FILE")"
else
    # Verificar se foi erro de autenticação (401)
    if [ "$UPLOAD_HTTP_CODE" -eq 401 ]; then
        log_warning "Token expirado. Tentando renovar..."

        # Caminho para o script de renovação
        RENEW_SCRIPT="$PROJECT_ROOT/ai-workspace/pt-br/scripts/api/renovar-token.sh"

        if [ -f "$RENEW_SCRIPT" ]; then
            log "Executando script de renovação: $RENEW_SCRIPT"

            # Tentar renovar token
            NEW_TOKEN=$("$RENEW_SCRIPT" --project="$PROJECT_TARGET" --env-file="$ENV_FILE")
            RENEW_EXIT_CODE=$?

            if [ $RENEW_EXIT_CODE -eq 0 ] && [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
                log_success "Token renovado com sucesso!"

                # Recarregar ACCESS_TOKEN do environment.json
                ACCESS_TOKEN=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE")

                if [ "$ACCESS_TOKEN" = "null" ] || [ -z "$ACCESS_TOKEN" ]; then
                    log_error "Falha ao obter novo token do environment.json"
                    exit 1
                fi

                log "Tentando deploy novamente com token renovado..."

                # Tentar upload novamente
                if upload_zip "$ZIP_FILE" "$API_URL" "$ACCESS_TOKEN" "$PROJECT_TARGET"; then
                    log_success "Deploy realizado com sucesso após renovação!"
                    # Limpar arquivo temporário
                    rm -f "$ZIP_FILE"
                    log "Pacote temporário removido: $(basename "$ZIP_FILE")"
                else
                    log_error "Falha no deploy mesmo após renovação de token"
                    # Manter arquivo temporário para debug
                    log_warning "Pacote temporário mantido para análise: $ZIP_FILE"
                    exit 1
                fi
            else
                log_error "Falha na renovação de token: $NEW_TOKEN"
                log_error "Tokens podem estar expirados. Execute renovação manual ou reautentique."
                exit 1
            fi
        else
            log_error "Script de renovação não encontrado: $RENEW_SCRIPT"
            exit 1
        fi
    else
        log_error "Falha no processo de deploy (HTTP $UPLOAD_HTTP_CODE)"
        # Manter arquivo temporário para debug
        log_warning "Pacote temporário mantido para análise: $ZIP_FILE"
        exit 1
    fi

fi
