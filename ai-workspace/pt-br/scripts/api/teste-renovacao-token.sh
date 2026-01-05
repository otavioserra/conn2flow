#!/bin/bash

# Script de Teste: Renovação de Tokens OAuth
# ------------------------------------------------------------------------------
# Este script testa especificamente a renovação de tokens OAuth para verificar
# se o retorno está funcionando corretamente no deploy-projeto.sh

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
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
RENEW_SCRIPT="$PROJECT_ROOT/ai-workspace/scripts/api/renovar-token.sh"

log "=== TESTE DE RENOVAÇÃO DE TOKENS OAUTH ==="
log "Script de renovação: $RENEW_SCRIPT"
log "Arquivo environment: $ENV_FILE"

# Verificar se arquivos existem
if [ ! -f "$ENV_FILE" ]; then
    log_error "Arquivo environment.json não encontrado: $ENV_FILE"
    exit 1
fi

if [ ! -f "$RENEW_SCRIPT" ]; then
    log_error "Script de renovação não encontrado: $RENEW_SCRIPT"
    exit 1
fi

# Ler configurações atuais
PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)
ACCESS_TOKEN_ANTIGO=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE" 2>/dev/null)
REFRESH_TOKEN_ANTIGO=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.refresh_token" "$ENV_FILE" 2>/dev/null)

log "Projeto alvo: $PROJECT_TARGET"
log "Access token atual (primeiros 20 chars): ${ACCESS_TOKEN_ANTIGO:0:20}..."
log "Refresh token atual (primeiros 20 chars): ${REFRESH_TOKEN_ANTIGO:0:20}..."

# Teste 1: Executar renovação e capturar saída
log ""
log "=== TESTE 1: Captura de saída do script de renovação ==="

log "Executando: $RENEW_SCRIPT"
log "Capturando saída com: NEW_TOKEN=\$(\"$RENEW_SCRIPT\" 2>&1)"

# Simular exatamente como o deploy-projeto.sh faz
if NEW_TOKEN=$("$RENEW_SCRIPT" 2>&1); then
    log_success "Renovação executada com sucesso!"
    log "Código de retorno: $?"

    # Verificar se NEW_TOKEN foi capturado
    if [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
        log_success "Token capturado com sucesso!"
        log "Novo token (primeiros 20 chars): ${NEW_TOKEN:0:20}..."
        log "Comprimento do token: ${#NEW_TOKEN} caracteres"

        # Verificar se é um JWT válido (tem 3 partes separadas por .)
        if [[ "$NEW_TOKEN" == *.*.* ]]; then
            log_success "Formato do token parece válido (JWT)"
        else
            log_warning "Formato do token pode não ser válido"
        fi
    else
        log_error "Token NÃO foi capturado!"
        log "NEW_TOKEN está vazio ou null"
        exit 1
    fi
else
    log_error "Falha na renovação de token"
    log "Código de retorno: $?"
    log "Saída do script: $NEW_TOKEN"
    exit 1
fi

# Teste 2: Verificar se o arquivo foi atualizado
log ""
log "=== TESTE 2: Verificação da atualização do environment.json ==="

ACCESS_TOKEN_NOVO=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE" 2>/dev/null)
REFRESH_TOKEN_NOVO=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.refresh_token" "$ENV_FILE" 2>/dev/null)

log "Access token após renovação (primeiros 20 chars): ${ACCESS_TOKEN_NOVO:0:20}..."
log "Refresh token após renovação (primeiros 20 chars): ${REFRESH_TOKEN_NOVO:0:20}..."

if [ "$ACCESS_TOKEN_ANTIGO" != "$ACCESS_TOKEN_NOVO" ]; then
    log_success "✅ Access token foi atualizado no environment.json!"
else
    log_error "❌ Access token NÃO foi atualizado no environment.json!"
fi

if [ "$REFRESH_TOKEN_ANTIGO" != "$REFRESH_TOKEN_NOVO" ]; then
    log_success "✅ Refresh token foi atualizado no environment.json!"
else
    log_warning "⚠️  Refresh token não foi alterado (pode ser normal)"
fi

    # Verificar se o token retornado é igual ao do arquivo
    log ""
    log "=== TESTE 3: Comparação entre token retornado e arquivo ==="

    if [ "$NEW_TOKEN" = "$ACCESS_TOKEN_NOVO" ]; then
        log_success "✅ Token retornado é IGUAL ao token no environment.json!"
    else
        log_error "❌ Token retornado é DIFERENTE do token no environment.json!"
        log "Token retornado: ${NEW_TOKEN:0:50}..."
        log "Token no arquivo: ${ACCESS_TOKEN_NOVO:0:50}..."
        
        # Imprimir token completo para debug
        log ""
        log "=== TOKEN COMPLETO RETORNADO ==="
        echo "$NEW_TOKEN"
        log "=== FIM TOKEN ==="
    fi

# Teste 4: Simulação do deploy-projeto.sh
log ""
log "=== TESTE 4: Simulação do fluxo do deploy-projeto.sh ==="

log "Simulando o código do deploy-projeto.sh..."
log "if NEW_TOKEN=\$(\"$RENEW_SCRIPT\" 2>&1); then"

# Recapturar o token (como se fosse uma nova execução)
if NEW_TOKEN_SIMULADO=$("$RENEW_SCRIPT" 2>&1); then
    log_success "Simulação: Renovação funcionou!"

    # Recarregar ACCESS_TOKEN do environment.json (como faz o deploy)
    ACCESS_TOKEN_RECARREGADO=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE")

    if [ "$ACCESS_TOKEN_RECARREGADO" = "null" ] || [ -z "$ACCESS_TOKEN_RECARREGADO" ]; then
        log_error "Simulação: Falha ao recarregar token do environment.json"
    else
        log_success "Simulação: Token recarregado com sucesso do environment.json"
        log "Token recarregado (primeiros 20 chars): ${ACCESS_TOKEN_RECARREGADO:0:20}..."

        if [ "$NEW_TOKEN_SIMULADO" = "$ACCESS_TOKEN_RECARREGADO" ]; then
            log_success "✅ Simulação: Fluxo completo funcionaria corretamente!"
        else
            log_error "❌ Simulação: Haveria problema no fluxo do deploy-projeto.sh!"
        fi
    fi
else
    log_error "Simulação: Falha na renovação"
fi

log ""
log "=== RESUMO DOS TESTES ==="
log "Se todos os testes passaram, o problema pode estar em outro lugar."
log "Se algum teste falhou, identifique qual e reporte."

exit 0