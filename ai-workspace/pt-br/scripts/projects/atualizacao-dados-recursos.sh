#!/bin/bash

# Script: Atualização de Dados e Recursos para Projetos
# ------------------------------------------------------------------------------
# Este script automatiza a atualização de recursos para projetos específicos.
# Funcionamento:
# 1. Lê o arquivo environment.json
# 2. Identifica o projeto alvo via devEnvironment.projectTarget
# 3. Obtém o caminho do projeto via devProjects[projectTarget].path
# 4. Executa o script PHP de atualização com o caminho do projeto

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

# Caminhos
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# De projects -> scripts -> ai-workspace -> conn2flow (3 níveis)
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
PHP_SCRIPT="$PROJECT_ROOT/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"

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
            echo "  --project, -p PROJECT_ID    Identificador do projeto para atualização (opcional)"
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

if [ ! -f "$PHP_SCRIPT" ]; then
    log_error "Script PHP não encontrado: $PHP_SCRIPT"
    exit 1
fi

log "Iniciando atualização de recursos para projetos..."
log "Arquivo de ambiente: $ENV_FILE"
log "Script PHP: $PHP_SCRIPT"

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
    log_warning "Diretório do projeto não existe. Criando: $PROJECT_PATH"
    mkdir -p "$PROJECT_PATH"
fi

# Verificar e executar TailwindCSS CLI se configurado
TAILWIND_CLI=$(jq -r ".devProjects.\"$PROJECT_TARGET\".\"tailwindcss/cli\"" "$ENV_FILE" 2>/dev/null)

if [ -n "$TAILWIND_CLI" ] && [ "$TAILWIND_CLI" != "null" ]; then
    log "Executando TailwindCSS CLI para o projeto..."
    cd "$PROJECT_PATH"
    eval "$TAILWIND_CLI"
    if [ $? -eq 0 ]; then
        log_success "TailwindCSS CLI executado com sucesso!"
    else
        log_error "Falha na execução do TailwindCSS CLI"
        exit 1
    fi
fi

# Executar o script PHP com o caminho do projeto
log "Executando atualização de recursos para o projeto..."
log "Comando: php \"$PHP_SCRIPT\" --project-path=\"$PROJECT_PATH\""

php "$PHP_SCRIPT" --project-path="$PROJECT_PATH"

if [ $? -eq 0 ]; then
    log_success "Atualização de recursos concluída com sucesso!"
else
    log_error "Falha na atualização de recursos"
    exit 1
fi
