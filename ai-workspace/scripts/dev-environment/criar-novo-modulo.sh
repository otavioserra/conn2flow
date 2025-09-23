#!/bin/bash

# Script para criar um novo módulo skeleton do sistema Conn2Flow
# Uso: ./criar-novo-modulo.sh [modulo-id] [lang]

set -e  # Para o script em caso de erro

# ===== CONFIGURAÇÃO =====

# Parâmetros com valores padrão
MODULO_ID="${1:-modulo-id}"
LANG="${2:-pt-br}"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ===== VALIDAÇÃO =====

# Verificar se estamos no diretório correto
if [[ ! -f "gestor/gestor.php" ]]; then
    error "Este script deve ser executado na raiz do projeto Conn2Flow!"
    exit 1
fi

# Verificar se o módulo já existe
if [[ -d "gestor/modulos/$MODULO_ID" ]]; then
    error "O módulo '$MODULO_ID' já existe!"
    exit 1
fi

# ===== FUNÇÕES AUXILIARES =====

# Converte ID do módulo para nome de função (substitui - por _)
modulo_to_function() {
    echo "$1" | sed 's/-/_/g'
}

# Cria estrutura de pastas
create_structure() {
    log "Criando estrutura de pastas para o módulo '$MODULO_ID'..."

    # Pasta principal do módulo
    mkdir -p "gestor/modulos/$MODULO_ID"

    # Recursos
    mkdir -p "gestor/modulos/$MODULO_ID/resources/lang/$LANG/components"
    mkdir -p "gestor/modulos/$MODULO_ID/resources/lang/$LANG/layouts"
    mkdir -p "gestor/modulos/$MODULO_ID/resources/lang/$LANG/pages"

    log "Estrutura de pastas criada com sucesso!"
}

# Cria arquivo JSON de configuração
create_json_config() {
    log "Criando arquivo de configuração JSON..."

    cat > "gestor/modulos/$MODULO_ID/$MODULO_ID.json" << EOF
{
    "versao": "1.0.0",
    "bibliotecas": [
        "interface",
        "html"
    ],
    "tabela": {
        "nome": "tabela",
        "id": "id",
        "id_numerico": "id_tabela"
    },
    "resources": {
        "$LANG": {
            "layouts": [],
            "pages": [
                {
                    "name": "PaginaInicialNome",
                    "id": "pagina-inicial-id",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "pagina-inicial-path/",
                    "type": "system",
                    "option": "raiz",
                    "root": true,
                    "version": "1.0",
                    "checksum": {
                        "html": "",
                        "css": "",
                        "combined": ""
                    }
                }
            ],
            "components": [],
            "variables": []
        }
    }
}
EOF

    log "Arquivo JSON criado com sucesso!"
}

# Cria arquivo PHP do módulo
create_php_module() {
    log "Criando arquivo PHP do módulo..."

    FUNCTION_NAME=$(modulo_to_function "$MODULO_ID")

    cat > "gestor/modulos/$MODULO_ID/$MODULO_ID.php" << EOF
<?php

global \$_GESTOR;

\$_GESTOR['$MODULO_ID'] = '$MODULO_ID';
\$_GESTOR['modulo#'.\$_GESTOR['$MODULO_ID']] = json_decode(file_get_contents(__DIR__ . '/$MODULO_ID.json'), true);

// ===== Interfaces Auxiliares



// ===== Interfaces Principais

function ${FUNCTION_NAME}_raiz(){
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#'.\$_GESTOR['$MODULO_ID']];

    // ===== Lógica

    // ===== Dados de Retorno

}

// ==== Ajax

function ${FUNCTION_NAME}_ajax_opcao(){
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#'.\$_GESTOR['$MODULO_ID']];

    // ===== Lógica

    \$payload = [];

    // ===== Dados de Retorno

    if(true){
        \$_GESTOR['ajax-json'] = Array(
            'payload' => \$payload,
            'status' => 'Ok',
        );
    } else {
        \$_GESTOR['ajax-json'] = Array(
            'error' => 'Error msg'
        );
    }
}

// ==== Start

function ${FUNCTION_NAME}_start(){
    global \$_GESTOR;

    gestor_incluir_bibliotecas();

    if(\$_GESTOR['ajax']){
        interface_ajax_iniciar();

        switch(\$_GESTOR['ajax-opcao']){
            case 'opcao': ${FUNCTION_NAME}_ajax_opcao(); break;
        }

        interface_ajax_finalizar();
    } else {
        ${FUNCTION_NAME}_interfaces_padroes();

        interface_iniciar();

        switch(\$_GESTOR['opcao']){
            case 'raiz': ${FUNCTION_NAME}_raiz(); break;
        }

        interface_finalizar();
    }
}

${FUNCTION_NAME}_start();

?>
EOF

    log "Arquivo PHP criado com sucesso!"
}

# Cria arquivo JavaScript
create_js_file() {
    log "Criando arquivo JavaScript..."

    cat > "gestor/modulos/$MODULO_ID/$MODULO_ID.js" << EOF
\$(document).ready(function(){

});
EOF

    log "Arquivo JavaScript criado com sucesso!"
}

# Cria arquivo HTML da página inicial
create_html_page() {
    log "Criando arquivo HTML da página inicial..."

    mkdir -p "gestor/modulos/$MODULO_ID/resources/lang/$LANG/pages/pagina-inicial-id"

    cat > "gestor/modulos/$MODULO_ID/resources/lang/$LANG/pages/pagina-inicial-id/pagina-inicial-id.html" << EOF
<!-- Página Inicial do Módulo $MODULO_ID -->

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1>Página Inicial - $MODULO_ID</h1>
            <p>Esta é a página inicial do módulo <strong>$MODULO_ID</strong>.</p>

            <!-- Conteúdo da página aqui -->

        </div>
    </div>
</div>
EOF

    log "Arquivo HTML criado com sucesso!"
}

# Abre arquivos no VS Code
open_in_vscode() {
    log "Pulando abertura no VS Code (não disponível neste ambiente)..."

    # Em um ambiente com VS Code, descomente as linhas abaixo:
    # Arquivos a abrir
    # files=(
    #     "gestor/modulos/$MODULO_ID/$MODULO_ID.php"
    #     "gestor/modulos/$MODULO_ID/$MODULO_ID.js"
    #     "gestor/modulos/$MODULO_ID/$MODULO_ID.json"
    #     "gestor/modulos/$MODULO_ID/resources/lang/$LANG/pages/pagina-inicial-id/pagina-inicial-id.html"
    # )

    # Abrir cada arquivo no VS Code
    # for file in "${files[@]}"; do
    #     if [[ -f "$file" ]]; then
    #         code "$file"
    #         log "Aberto: $file"
    #     else
    #         warn "Arquivo não encontrado: $file"
    #     fi
    # done
}

# ===== EXECUÇÃO =====

log "🚀 Iniciando criação do módulo skeleton..."
log "📦 ID do módulo: $MODULO_ID"
log "🌍 Linguagem: $LANG"

# Executar todas as funções
create_structure
create_json_config
create_php_module
create_js_file
create_html_page

log "✅ Módulo skeleton criado com sucesso!"
log "📁 Localização: gestor/modulos/$MODULO_ID/"

# Abrir arquivos no VS Code
open_in_vscode

log "🎉 Processo concluído! Os arquivos foram abertos no VS Code para edição."