#!/bin/bash

# Script para verificar e listar todos os recursos PT-BR do sistema
# Autor: AI Agent para Tradução
# Data: $(date '+%d/%m/%Y')

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para exibir header
show_header() {
    echo -e "${BLUE}"
    echo "=================================================="
    echo "   VERIFICADOR DE RECURSOS PT-BR - CONN2FLOW"
    echo "=================================================="
    echo -e "${NC}"
}

# Função para log
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Variáveis
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
GESTOR_DIR="$PROJECT_ROOT/gestor"
LISTA_RECURSOS_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/pt-br/lista-recursos.md"
TRADUCAO_MAIN_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/traducao-pt-br-para-en.md"

# Contadores
total_files=0
html_files=0
json_files=0
css_files=0

# Arrays para armazenar arquivos por tipo
declare -a html_array
declare -a json_array  
declare -a css_array

# Função para verificar se diretório existe
check_directory() {
    if [ ! -d "$1" ]; then
        log_error "Diretório não encontrado: $1"
        return 1
    fi
    return 0
}

# Função para processar arquivos de um diretório
process_directory() {
    local dir="$1"
    local context="$2"
    
    log_info "Processando: $context"
    
    # Buscar arquivos HTML
    while IFS= read -r -d '' file; do
        if [ -f "$file" ]; then
            rel_path="${file#$PROJECT_ROOT/}"
            html_array+=("$rel_path")
            ((html_files++))
            ((total_files++))
        fi
    done < <(find "$dir" -name "*.html" -type f -print0 2>/dev/null)
    
    # Buscar arquivos JSON
    while IFS= read -r -d '' file; do
        if [ -f "$file" ]; then
            rel_path="${file#$PROJECT_ROOT/}"
            json_array+=("$rel_path")
            ((json_files++))
            ((total_files++))
        fi
    done < <(find "$dir" -name "*.json" -type f -print0 2>/dev/null)
    
    # Buscar arquivos CSS
    while IFS= read -r -d '' file; do
        if [ -f "$file" ]; then
            rel_path="${file#$PROJECT_ROOT/}"
            css_array+=("$rel_path")
            ((css_files++))
            ((total_files++))
        fi
    done < <(find "$dir" -name "*.css" -type f -print0 2>/dev/null)
}

# Função para criar o arquivo de lista de recursos
create_resource_list() {
    log_info "Criando arquivo de lista de recursos..."
    
    # Criar diretório se não existir
    mkdir -p "$(dirname "$LISTA_RECURSOS_FILE")"
    
    # Criar conteúdo do arquivo
    cat > "$LISTA_RECURSOS_FILE" << EOF
# Lista de Recursos PT-BR para Tradução

## 📊 Estatísticas Gerais
- **Total de Arquivos**: $total_files
- **Arquivos HTML**: $html_files
- **Arquivos JSON**: $json_files  
- **Arquivos CSS**: $css_files

*Última atualização: $(date '+%d/%m/%Y %H:%M:%S')*

## 📂 Estrutura de Recursos

### 🏗️ Recursos Globais
Localização: \`gestor/resources/\`

### 🧩 Recursos de Módulos  
Localização: \`gestor/modulos/{modulo-id}/resources/\`

## 📋 Lista Completa de Arquivos

### 📄 Arquivos HTML ($html_files arquivos)
EOF

    # Adicionar arquivos HTML
    for file in "${html_array[@]}"; do
        echo "- [ ] \`$file\`" >> "$LISTA_RECURSOS_FILE"
    done
    
    # Adicionar seção JSON
    cat >> "$LISTA_RECURSOS_FILE" << EOF

### 📋 Arquivos JSON ($json_files arquivos)
EOF

    # Adicionar arquivos JSON
    for file in "${json_array[@]}"; do
        echo "- [ ] \`$file\`" >> "$LISTA_RECURSOS_FILE"
    done
    
    # Adicionar seção CSS
    cat >> "$LISTA_RECURSOS_FILE" << EOF

### 🎨 Arquivos CSS ($css_files arquivos)
EOF

    # Adicionar arquivos CSS
    for file in "${css_array[@]}"; do
        echo "- [ ] \`$file\`" >> "$LISTA_RECURSOS_FILE"
    done
    
    # Adicionar rodapé
    cat >> "$LISTA_RECURSOS_FILE" << EOF

## 🔄 Status de Tradução

### ⏳ Pendentes
Todos os arquivos listados acima estão pendentes de tradução.

### ✅ Concluídos  
Nenhum arquivo traduzido ainda.

### ❌ Com Problemas
Nenhum problema identificado ainda.

## 📝 Observações
- Lista gerada automaticamente pelo script \`verificar-recursos.sh\`
- Arquivo atualizado em: $(date '+%d/%m/%Y %H:%M:%S')
- Para atualizar esta lista, execute: \`bash ai-workspace/scripts/translates/verificar-recursos.sh\`

---
*Gerado automaticamente em: $(date '+%d/%m/%Y %H:%M:%S')*
EOF

    log_info "Arquivo de lista criado: $LISTA_RECURSOS_FILE"
}

# Função para atualizar o documento principal
update_main_document() {
    log_info "Atualizando documento principal de tradução..."
    
    # Criar backup do arquivo original
    if [ -f "$TRADUCAO_MAIN_FILE" ]; then
        cp "$TRADUCAO_MAIN_FILE" "$TRADUCAO_MAIN_FILE.bak"
        log_info "Backup criado: $TRADUCAO_MAIN_FILE.bak"
    fi
    
    # Atualizar estatísticas no documento principal
    # Isso será feito via sed para atualizar as linhas específicas
    if [ -f "$TRADUCAO_MAIN_FILE" ]; then
        # Atualizar total de arquivos
        sed -i "s/- \*\*Total de Arquivos\*\*: [0-9]* (será atualizado)/- **Total de Arquivos**: $total_files/" "$TRADUCAO_MAIN_FILE"
        
        # Atualizar arquivos HTML
        sed -i "s/- \*\*HTML\*\*: [0-9]*\/[0-9]* ([0-9]*%)/- **HTML**: 0\/$html_files (0%)/" "$TRADUCAO_MAIN_FILE"
        
        # Atualizar arquivos JSON  
        sed -i "s/- \*\*JSON\*\*: [0-9]*\/[0-9]* ([0-9]*%)/- **JSON**: 0\/$json_files (0%)/" "$TRADUCAO_MAIN_FILE"
        
        # Atualizar arquivos CSS
        sed -i "s/- \*\*CSS\*\*: [0-9]*\/[0-9]* ([0-9]*%)/- **CSS**: 0\/$css_files (0%)/" "$TRADUCAO_MAIN_FILE"
        
        log_info "Documento principal atualizado com as novas estatísticas"
    fi
}

# Função principal
main() {
    show_header
    
    log_info "Iniciando verificação de recursos PT-BR..."
    log_info "Diretório do projeto: $PROJECT_ROOT"
    
    # Verificar se os diretórios existem
    if ! check_directory "$GESTOR_DIR"; then
        log_error "Diretório gestor não encontrado!"
        exit 1
    fi
    
    # Processar recursos globais
    if [ -d "$GESTOR_DIR/resources" ]; then
        process_directory "$GESTOR_DIR/resources" "Recursos Globais (gestor/resources/)"
    else
        log_warning "Diretório de recursos globais não encontrado: $GESTOR_DIR/resources"
    fi
    
    # Processar recursos de módulos
    if [ -d "$GESTOR_DIR/modulos" ]; then
        for modulo_dir in "$GESTOR_DIR/modulos"/*; do
            if [ -d "$modulo_dir" ] && [ -d "$modulo_dir/resources" ]; then
                modulo_name=$(basename "$modulo_dir")
                process_directory "$modulo_dir/resources" "Módulo: $modulo_name"
            fi
        done
    else
        log_warning "Diretório de módulos não encontrado: $GESTOR_DIR/modulos"
    fi
    
    # Exibir resultados
    echo -e "\n${BLUE}📊 RESULTADOS DA VERIFICAÇÃO${NC}"
    echo "=================================="
    echo -e "${GREEN}Total de arquivos encontrados: $total_files${NC}"
    echo -e "  📄 HTML: $html_files"
    echo -e "  📋 JSON: $json_files"  
    echo -e "  🎨 CSS: $css_files"
    
    # Criar arquivo de lista
    create_resource_list
    
    # Atualizar documento principal
    update_main_document
    
    echo -e "\n${GREEN}✅ Verificação concluída com sucesso!${NC}"
    echo -e "${BLUE}📁 Lista de recursos criada em:${NC} $LISTA_RECURSOS_FILE"
    echo -e "${BLUE}📝 Documento principal atualizado:${NC} $TRADUCAO_MAIN_FILE"
}

# Executar função principal
main "$@"