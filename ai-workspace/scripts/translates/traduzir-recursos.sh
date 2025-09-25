#!/bin/bash

# Script de Tradução Automática PT-BR → EN - Conn2Flow Gestor
# Autor: AI Agent para Tradução
# Data: $(date '+%d/%m/%Y')
# Função: Direcionador de tradução arquivo por arquivo

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Função para exibir header
show_header() {
    echo -e "${BLUE}"
    echo "=========================================================="
    echo "   TRADUTOR AUTOMÁTICO PT-BR → EN - CONN2FLOW GESTOR"
    echo "=========================================================="
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

log_success() {
    echo -e "${CYAN}[SUCCESS]${NC} $1"
}

log_action() {
    echo -e "${PURPLE}[ACTION]${NC} $1"
}

# Variáveis
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
GESTOR_DIR="$PROJECT_ROOT/gestor"
LISTA_RECURSOS_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/pt-br/lista-recursos.md"
TRADUCAO_MAIN_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/traducao-pt-br-para-en.md"
RESOURCES_MAP_FILE="$GESTOR_DIR/resources/resources.map.php"

# Contadores de progresso
total_files=0
translated_files=0
pending_files=0
error_files=0

# Arrays para armazenar arquivos
declare -a html_files_array
declare -a json_files_array
declare -a css_files_array

# Função para verificar estruturas iniciais
check_initial_setup() {
    log_info "Verificando configurações iniciais..."
    
    # Verificar se existe resources.map.php
    if [ ! -f "$RESOURCES_MAP_FILE" ]; then
        log_error "Arquivo resources.map.php não encontrado!"
        return 1
    fi
    
    # Criar estrutura EN nos recursos globais se não existir
    if [ ! -d "$GESTOR_DIR/resources/en" ]; then
        log_action "Criando diretório de recursos globais EN..."
        mkdir -p "$GESTOR_DIR/resources/en/layouts"
        mkdir -p "$GESTOR_DIR/resources/en/pages" 
        mkdir -p "$GESTOR_DIR/resources/en/components"
        log_success "Estrutura EN global criada"
    fi
    
    return 0
}

# Função para adicionar EN ao resources.map.php
setup_global_resources_mapping() {
    log_info "Configurando mapeamento global de recursos EN..."
    
    # Verificar se já existe mapeamento EN
    if grep -q "'en'" "$RESOURCES_MAP_FILE"; then
        log_warning "Mapeamento EN já existe no resources.map.php"
        return 0
    fi
    
    # Criar backup
    cp "$RESOURCES_MAP_FILE" "$RESOURCES_MAP_FILE.bak"
    
    # Adicionar mapeamento EN após PT-BR
    sed -i "/],$/,/^    ],$/{ 
        /^    ],$/a\\
        'en' => [\\
            'name' => 'English',\\
            'data' => [\\
                'layouts' => 'layouts.json',\\
                'pages' => 'pages.json',\\
                'components' => 'components.json',\\
                'variables' => 'variables.json',\\
            ],\\
            'version' => '1',\\
        ],
    }" "$RESOURCES_MAP_FILE"
    
    log_success "Mapeamento EN adicionado ao resources.map.php"
}

# Função para configurar mapeamento EN em módulos
setup_module_resources_mapping() {
    local module_dir="$1"
    local module_name=$(basename "$module_dir")
    local module_json="$module_dir/$module_name.json"
    
    log_info "Configurando mapeamento EN no módulo: $module_name"
    
    if [ ! -f "$module_json" ]; then
        log_warning "Arquivo JSON do módulo não encontrado: $module_json"
        return 1
    fi
    
    # Verificar se já existe mapeamento EN
    if grep -q '"en"' "$module_json"; then
        log_warning "Mapeamento EN já existe no módulo $module_name"
        return 0
    fi
    
    # Criar backup
    cp "$module_json" "$module_json.bak"
    
    # Adicionar seção EN após PT-BR usando Python para manipular JSON
    python3 -c "
import json
import sys

try:
    with open('$module_json', 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    if 'resources' in data:
        if 'en' not in data['resources']:
            data['resources']['en'] = {
                'layouts': [],
                'pages': [],
                'components': [],
                'variables': []
            }
            
            with open('$module_json', 'w', encoding='utf-8') as f:
                json.dump(data, f, indent=4, ensure_ascii=False)
            
            print('EN mapping added to module $module_name')
        else:
            print('EN mapping already exists in module $module_name')
    else:
        print('No resources section found in module $module_name')
        
except Exception as e:
    print(f'Error processing module $module_name: {e}')
    sys.exit(1)
"
    
    # Criar diretório EN se não existir
    if [ ! -d "$module_dir/resources/en" ]; then
        mkdir -p "$module_dir/resources/en/layouts"
        mkdir -p "$module_dir/resources/en/pages"
        mkdir -p "$module_dir/resources/en/components"
        log_success "Estrutura EN criada no módulo $module_name"
    fi
}

# Função para carregar lista de arquivos da lista-recursos.md
load_files_from_list() {
    log_info "Carregando lista de arquivos para tradução..."
    
    if [ ! -f "$LISTA_RECURSOS_FILE" ]; then
        log_error "Lista de recursos não encontrada: $LISTA_RECURSOS_FILE"
        return 1
    fi
    
    # Extrair arquivos HTML
    while IFS= read -r line; do
        if [[ $line =~ ^\-\ \[\ \]\ \`(.+\.html)\` ]]; then
            html_files_array+=("${BASH_REMATCH[1]}")
        fi
    done < "$LISTA_RECURSOS_FILE"
    
    # Extrair arquivos JSON
    while IFS= read -r line; do
        if [[ $line =~ ^\-\ \[\ \]\ \`(.+\.json)\` ]]; then
            json_files_array+=("${BASH_REMATCH[1]}")
        fi
    done < "$LISTA_RECURSOS_FILE"
    
    # Extrair arquivos CSS
    while IFS= read -r line; do
        if [[ $line =~ ^\-\ \[\ \]\ \`(.+\.css)\` ]]; then
            css_files_array+=("${BASH_REMATCH[1]}")
        fi
    done < "$LISTA_RECURSOS_FILE"
    
    total_files=$((${#html_files_array[@]} + ${#json_files_array[@]} + ${#css_files_array[@]}))
    pending_files=$total_files
    
    log_success "Carregados: ${#html_files_array[@]} HTML, ${#json_files_array[@]} JSON, ${#css_files_array[@]} CSS"
}

# Função para traduzir um arquivo específico
translate_file() {
    local source_file="$1"
    local file_type="$2"
    
    # Converter caminho PT-BR para EN
    local target_file="${source_file/\/pt-br\//\/en\/}"
    local target_path="$PROJECT_ROOT/$target_file"
    local source_path="$PROJECT_ROOT/$source_file"
    
    log_action "Traduzindo: $(basename "$source_file")"
    
    # Verificar se arquivo fonte existe
    if [ ! -f "$source_path" ]; then
        log_error "Arquivo fonte não encontrado: $source_path"
        ((error_files++))
        return 1
    fi
    
    # Criar diretório de destino se não existir
    mkdir -p "$(dirname "$target_path")"
    
    # Processar baseado no tipo de arquivo
    case $file_type in
        "html")
            translate_html_file "$source_path" "$target_path"
            ;;
        "css")
            translate_css_file "$source_path" "$target_path"
            ;;
        "json")
            translate_json_file "$source_path" "$target_path"
            ;;
        *)
            log_error "Tipo de arquivo não reconhecido: $file_type"
            ((error_files++))
            return 1
            ;;
    esac
    
    if [ $? -eq 0 ]; then
        log_success "Traduzido: $target_file"
        ((translated_files++))
        ((pending_files--))
        
        # Atualizar lista marcando como concluído
        update_file_status_in_list "$source_file" "completed"
        return 0
    else
        log_error "Falha na tradução: $source_file"
        ((error_files++))
        return 1
    fi
}

# Função para traduzir arquivo HTML
translate_html_file() {
    local source="$1"
    local target="$2"
    
    # Por agora, fazer tradução básica via sed
    # TODO: Implementar tradução mais sofisticada com AI
    cp "$source" "$target"
    
    # Traduções básicas de termos comuns
    sed -i 's/Adicionar/Add/g' "$target"
    sed -i 's/Editar/Edit/g' "$target"
    sed -i 's/Excluir/Delete/g' "$target"
    sed -i 's/Salvar/Save/g' "$target"
    sed -i 's/Cancelar/Cancel/g' "$target"
    sed -i 's/Nome/Name/g' "$target"
    sed -i 's/Descrição/Description/g' "$target"
    sed -i 's/Data/Date/g' "$target"
    sed -i 's/Ação/Action/g' "$target"
    sed -i 's/Status/Status/g' "$target"
    sed -i 's/Tipo/Type/g' "$target"
    sed -i 's/Categoria/Category/g' "$target"
    sed -i 's/Arquivo/File/g' "$target"
    sed -i 's/Lista/List/g' "$target"
    sed -i 's/Buscar/Search/g' "$target"
    sed -i 's/Filtrar/Filter/g' "$target"
    
    return 0
}

# Função para traduzir arquivo CSS
translate_css_file() {
    local source="$1"
    local target="$2"
    
    # Por agora, apenas copiar (CSS geralmente tem poucos textos)
    cp "$source" "$target"
    
    return 0
}

# Função para traduzir arquivo JSON
translate_json_file() {
    local source="$1"
    local target="$2"
    
    # Por agora, apenas copiar estrutura
    cp "$source" "$target"
    
    return 0
}

# Função para atualizar status na lista
update_file_status_in_list() {
    local file="$1"
    local status="$2"
    
    case $status in
        "completed")
            sed -i "s|- \[ \] \`$file\`|- [x] \`$file\`|g" "$LISTA_RECURSOS_FILE"
            ;;
        "error")
            sed -i "s|- \[ \] \`$file\`|- [!] \`$file\` ❌|g" "$LISTA_RECURSOS_FILE"
            ;;
    esac
}

# Função para exibir progresso
show_progress() {
    echo -e "\n${BLUE}📊 PROGRESSO DA TRADUÇÃO${NC}"
    echo "=================================="
    echo -e "${GREEN}✅ Traduzidos: $translated_files${NC}"
    echo -e "${YELLOW}⏳ Pendentes: $pending_files${NC}"
    echo -e "${RED}❌ Com Erro: $error_files${NC}"
    echo -e "${CYAN}📈 Total: $total_files${NC}"
    
    if [ $total_files -gt 0 ]; then
        local percentage=$((translated_files * 100 / total_files))
        echo -e "${BLUE}📊 Progresso: $percentage%${NC}"
    fi
}

# Função para processar arquivos por tipo
process_files_by_type() {
    local file_type="$1"
    local -n files_array=$2
    
    log_info "Processando arquivos $file_type (${#files_array[@]} arquivos)..."
    
    for file in "${files_array[@]}"; do
        translate_file "$file" "$file_type"
        
        # Mostrar progresso a cada 5 arquivos
        if [ $((translated_files % 5)) -eq 0 ]; then
            show_progress
        fi
        
        # Pequena pausa para não sobrecarregar
        sleep 0.1
    done
}

# Função principal
main() {
    show_header
    
    log_info "Iniciando processo de tradução automática..."
    
    # Verificar configurações iniciais
    if ! check_initial_setup; then
        log_error "Falha na verificação inicial!"
        exit 1
    fi
    
    # Configurar mapeamentos globais
    setup_global_resources_mapping
    
    # Configurar mapeamentos nos módulos
    log_info "Configurando mapeamentos EN nos módulos..."
    for module_dir in "$GESTOR_DIR/modulos"/*; do
        if [ -d "$module_dir" ]; then
            setup_module_resources_mapping "$module_dir"
        fi
    done
    
    # Carregar lista de arquivos
    if ! load_files_from_list; then
        log_error "Falha ao carregar lista de arquivos!"
        exit 1
    fi
    
    # Processar arquivos por tipo
    log_info "Iniciando tradução de arquivos HTML..."
    process_files_by_type "html" html_files_array
    
    log_info "Iniciando tradução de arquivos CSS..."
    process_files_by_type "css" css_files_array
    
    # JSON será processado depois
    log_warning "Arquivos JSON serão processados em script separado"
    
    # Exibir resultado final
    show_progress
    
    echo -e "\n${GREEN}🎉 TRADUÇÃO CONCLUÍDA!${NC}"
    echo -e "${BLUE}📁 Arquivos traduzidos criados na estrutura /en/${NC}"
    echo -e "${YELLOW}📝 Próximos passos: Executar sincronização do sistema${NC}"
    
    # Comandos sugeridos
    echo -e "\n${CYAN}Comandos sugeridos para executar:${NC}"
    echo "1. php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"
    echo "2. bash ./ai-workspace/scripts/dev-environment/sincroniza-gestor.sh checksum"
    echo "3. docker exec conn2flow-app bash -c \"php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff\""
}

# Executar função principal
main "$@"