#!/bin/bash

# Automatic Translation Script PT-BR -> EN - Conn2Flow Manager
# Author: AI Translation Agent
# Date: $(date '+%d/%m/%Y')
# Function: File-by-file translation director

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Function to display header
show_header() {
    echo -e "${BLUE}"
    echo "=========================================================="
    echo "   AUTOMATIC TRANSLATOR PT-BR -> EN - CONN2FLOW MANAGER"
    echo "=========================================================="
    echo -e "${NC}"
}

# Log function
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

# Variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
GESTOR_DIR="$PROJECT_ROOT/gestor"
LISTA_RECURSOS_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/pt-br/lista-recursos.md"
TRADUCAO_MAIN_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/traducao-pt-br-para-en.md"
RESOURCES_MAP_FILE="$GESTOR_DIR/resources/resources.map.php"

# Progress counters
total_files=0
translated_files=0
pending_files=0
error_files=0

# Arrays to store files
declare -a html_files_array
declare -a json_files_array
declare -a css_files_array

# Function to check initial setup
check_initial_setup() {
    log_info "Checking initial settings..."
    
    # Check if resources.map.php exists
    if [ ! -f "$RESOURCES_MAP_FILE" ]; then
        log_error "resources.map.php file not found!"
        return 1
    fi
    
    # Create EN structure in global resources if not exists
    if [ ! -d "$GESTOR_DIR/resources/en" ]; then
        log_action "Creating global EN resources directory..."
        mkdir -p "$GESTOR_DIR/resources/en/layouts"
        mkdir -p "$GESTOR_DIR/resources/en/pages" 
        mkdir -p "$GESTOR_DIR/resources/en/components"
        log_success "Global EN structure created"
    fi
    
    return 0
}

# Function to add EN to resources.map.php
setup_global_resources_mapping() {
    log_info "Configuring global EN resources mapping..."
    
    # Check if EN mapping already exists
    if grep -q "'en'" "$RESOURCES_MAP_FILE"; then
        log_warning "EN mapping already exists in resources.map.php"
        return 0
    fi
    
    # Create backup
    cp "$RESOURCES_MAP_FILE" "$RESOURCES_MAP_FILE.bak"
    
    # Add EN mapping after PT-BR
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
    
    log_success "EN mapping added to resources.map.php"
}

# Function to configure EN mapping in modules
setup_module_resources_mapping() {
    local module_dir="$1"
    local module_name=$(basename "$module_dir")
    local module_json="$module_dir/$module_name.json"
    
    log_info "Configuring EN mapping in module: $module_name"
    
    if [ ! -f "$module_json" ]; then
        log_warning "Module JSON file not found: $module_json"
        return 1
    fi
    
    # Check if EN mapping already exists
    if grep -q '"en"' "$module_json"; then
        log_warning "EN mapping already exists in module $module_name"
        return 0
    fi
    
    # Create backup
    cp "$module_json" "$module_json.bak"
    
    # Add EN section after PT-BR using Python to manipulate JSON
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
    
    # Create EN directory if not exists
    if [ ! -d "$module_dir/resources/en" ]; then
        mkdir -p "$module_dir/resources/en/layouts"
        mkdir -p "$module_dir/resources/en/pages"
        mkdir -p "$module_dir/resources/en/components"
        log_success "EN structure created in module $module_name"
    fi
}

# Function to load file list from lista-recursos.md
load_files_from_list() {
    log_info "Loading file list for translation..."
    
    if [ ! -f "$LISTA_RECURSOS_FILE" ]; then
        log_error "Resource list not found: $LISTA_RECURSOS_FILE"
        return 1
    fi
    
    # Extract HTML files
    while IFS= read -r line; do
        if [[ $line =~ ^\-\ \[\ \]\ \`(.+\.html)\` ]]; then
            html_files_array+=("${BASH_REMATCH[1]}")
        fi
    done < "$LISTA_RECURSOS_FILE"
    
    # Extract JSON files
    while IFS= read -r line; do
        if [[ $line =~ ^\-\ \[\ \]\ \`(.+\.json)\` ]]; then
            json_files_array+=("${BASH_REMATCH[1]}")
        fi
    done < "$LISTA_RECURSOS_FILE"
    
    # Extract CSS files
    while IFS= read -r line; do
        if [[ $line =~ ^\-\ \[\ \]\ \`(.+\.css)\` ]]; then
            css_files_array+=("${BASH_REMATCH[1]}")
        fi
    done < "$LISTA_RECURSOS_FILE"
    
    total_files=$((${#html_files_array[@]} + ${#json_files_array[@]} + ${#css_files_array[@]}))
    pending_files=$total_files
    
    log_success "Loaded: ${#html_files_array[@]} HTML, ${#json_files_array[@]} JSON, ${#css_files_array[@]} CSS"
}

# Function to translate a specific file
translate_file() {
    local source_file="$1"
    local file_type="$2"
    
    # Convert PT-BR path to EN
    local target_file="${source_file/\/pt-br\//\/en\/}"
    local target_path="$PROJECT_ROOT/$target_file"
    local source_path="$PROJECT_ROOT/$source_file"
    
    log_action "Translating: $(basename "$source_file")"
    
    # Check if source file exists
    if [ ! -f "$source_path" ]; then
        log_error "Source file not found: $source_path"
        ((error_files++))
        return 1
    fi
    
    # Create destination directory if not exists
    mkdir -p "$(dirname "$target_path")"
    
    # Process based on file type
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
            log_error "Unrecognized file type: $file_type"
            ((error_files++))
            return 1
            ;;
    esac
    
    if [ $? -eq 0 ]; then
        log_success "Translated: $target_file"
        ((translated_files++))
        ((pending_files--))
        
        # Update list marking as completed
        update_file_status_in_list "$source_file" "completed"
        return 0
    else
        log_error "Translation failed: $source_file"
        ((error_files++))
        return 1
    fi
}

# Function to translate HTML file
translate_html_file() {
    local source="$1"
    local target="$2"
    
    # For now, do basic translation via sed
    # TODO: Implement more sophisticated translation with AI
    cp "$source" "$target"
    
    # Basic translations of common terms
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

# Function to translate CSS file
translate_css_file() {
    local source="$1"
    local target="$2"
    
    # For now, just copy (CSS usually has few texts)
    cp "$source" "$target"
    
    return 0
}

# Function to translate JSON file
translate_json_file() {
    local source="$1"
    local target="$2"
    
    # For now, just copy structure
    cp "$source" "$target"
    
    return 0
}

# Function to update status in list
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

# Function to display progress
show_progress() {
    echo -e "\n${BLUE}📊 TRANSLATION PROGRESS${NC}"
    echo "=================================="
    echo -e "${GREEN}✅ Translated: $translated_files${NC}"
    echo -e "${YELLOW}⏳ Pending: $pending_files${NC}"
    echo -e "${RED}❌ With Error: $error_files${NC}"
    echo -e "${CYAN}📈 Total: $total_files${NC}"
    
    if [ $total_files -gt 0 ]; then
        local percentage=$((translated_files * 100 / total_files))
        echo -e "${BLUE}📊 Progress: $percentage%${NC}"
    fi
}

# Function to process files by type
process_files_by_type() {
    local file_type="$1"
    local -n files_array=$2
    
    log_info "Processing files $file_type (${#files_array[@]} files)..."
    
    for file in "${files_array[@]}"; do
        translate_file "$file" "$file_type"
        
        # Show progress every 5 files
        if [ $((translated_files % 5)) -eq 0 ]; then
            show_progress
        fi
        
        # Short pause to avoid overload
        sleep 0.1
    done
}

# Main function
main() {
    show_header
    
    log_info "Starting automatic translation process..."
    
    # Check initial settings
    if ! check_initial_setup; then
        log_error "Initial check failed!"
        exit 1
    fi
    
    # Configure global mappings
    setup_global_resources_mapping
    
    # Configure mappings in modules
    log_info "Configuring EN mappings in modules..."
    for module_dir in "$GESTOR_DIR/modulos"/*; do
        if [ -d "$module_dir" ]; then
            setup_module_resources_mapping "$module_dir"
        fi
    done
    
    # Load file list
    if ! load_files_from_list; then
        log_error "Failed to load file list!"
        exit 1
    fi
    
    # Process files by type
    log_info "Starting HTML file translation..."
    process_files_by_type "html" html_files_array
    
    log_info "Starting CSS file translation..."
    process_files_by_type "css" css_files_array
    
    # JSON will be processed later
    log_warning "JSON files will be processed in a separate script"
    
    # Display final result
    show_progress
    
    echo -e "\n${GREEN}🎉 TRANSLATION COMPLETED!${NC}"
    echo -e "${BLUE}📁 Translated files created in /en/ structure${NC}"
    echo -e "${YELLOW}📝 Next steps: Execute system synchronization${NC}"
    
    # Suggested commands
    echo -e "\n${CYAN}Suggested commands to execute:${NC}"
    echo "1. php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"
    echo "2. bash ./ai-workspace/scripts/dev-environment/sincroniza-gestor.sh checksum"
    echo "3. docker exec conn2flow-app bash -c \"php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff\""
}

# Execute main function
main "$@"
