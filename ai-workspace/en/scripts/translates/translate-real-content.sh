#!/bin/bash

# Script for Real Textual Content Translation PT-BR -> EN
# Author: AI Translation Agent
# Date: $(date '+%d/%m/%Y')
# Function: Translate real text in HTML files keeping variables

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
    echo "   REAL CONTENT TRANSLATOR PT-BR -> EN - CONN2FLOW"
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
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
GESTOR_DIR="$PROJECT_ROOT/gestor"
LISTA_RECURSOS_FILE="$PROJECT_ROOT/ai-workspace/prompts/translates/pt-br/lista-recursos.md"

# Counters
total_translated=0
total_errors=0

# Function to translate textual content of an HTML file
translate_html_content() {
    local file_path="$1"
    local file_name=$(basename "$file_path")
    
    log_action "Translating content: $file_name"
    
    # Create backup
    cp "$file_path" "$file_path.backup"
    
    # More specific and contextual translations
    
    # Common buttons and actions
    sed -i 's/>Adicionar</>Add</g' "$file_path"
    sed -i 's/>Editar</>Edit</g' "$file_path"
    sed -i 's/>Excluir</>Delete</g' "$file_path"
    sed -i 's/>Salvar</>Save</g' "$file_path"
    sed -i 's/>Cancelar</>Cancel</g' "$file_path"
    sed -i 's/>Voltar</>Back</g' "$file_path"
    sed -i 's/>Confirmar</>Confirm</g' "$file_path"
    sed -i 's/>Enviar</>Send</g' "$file_path"
    sed -i 's/>Buscar</>Search</g' "$file_path"
    sed -i 's/>Filtrar</>Filter</g' "$file_path"
    sed -i 's/>Limpar</>Clear</g' "$file_path"
    sed -i 's/>Recarregar</>Reload</g' "$file_path"
    
    # Common labels and fields
    sed -i 's/>Nome</>Name</g' "$file_path"
    sed -i 's/>Descrição</>Description</g' "$file_path"
    sed -i 's/>Data</>Date</g' "$file_path"
    sed -i 's/>Ação</>Action</g' "$file_path"
    sed -i 's/>Status</>Status</g' "$file_path"
    sed -i 's/>Tipo</>Type</g' "$file_path"
    sed -i 's/>Categoria</>Category</g' "$file_path"
    sed -i 's/>Categorias</>Categories</g' "$file_path"
    sed -i 's/>Arquivo</>File</g' "$file_path"
    sed -i 's/>Arquivos</>Files</g' "$file_path"
    sed -i 's/>Lista</>List</g' "$file_path"
    sed -i 's/>Usuário</>User</g' "$file_path"
    sed -i 's/>Usuários</>Users</g' "$file_path"
    sed -i 's/>Perfil</>Profile</g' "$file_path"
    sed -i 's/>Email</>Email</g' "$file_path"
    sed -i 's/>Senha</>Password</g' "$file_path"
    sed -i 's/>Login</>Login</g' "$file_path"
    
    # Titles and administrative sections
    sed -i 's/>Admin </>Admin </g' "$file_path"
    sed -i 's/>Administração</>Administration</g' "$file_path"
    sed -i 's/>Configurações</>Settings</g' "$file_path"
    sed -i 's/>Configuração</>Configuration</g' "$file_path"
    sed -i 's/>Módulos</>Modules</g' "$file_path"
    sed -i 's/>Módulo</>Module</g' "$file_path"
    sed -i 's/>Layouts</>Layouts</g' "$file_path"
    sed -i 's/>Layout</>Layout</g' "$file_path"
    sed -i 's/>Páginas</>Pages</g' "$file_path"
    sed -i 's/>Página</>Page</g' "$file_path"
    sed -i 's/>Componentes</>Components</g' "$file_path"
    sed -i 's/>Componente</>Component</g' "$file_path"
    sed -i 's/>Plugins</>Plugins</g' "$file_path"
    sed -i 's/>Plugin</>Plugin</g' "$file_path"
    
    # Common messages and texts
    sed -i 's/>Sucesso</>Success</g' "$file_path"
    sed -i 's/>Erro</>Error</g' "$file_path"
    sed -i 's/>Aviso</>Warning</g' "$file_path"
    sed -i 's/>Informação</>Information</g' "$file_path"
    sed -i 's/>Carregando</>Loading</g' "$file_path"
    sed -i 's/>Processando</>Processing</g' "$file_path"
    sed -i 's/>Aguarde</>Please wait</g' "$file_path"
    
    # More complex phrases
    sed -i 's/Gerencie as configurações/Manage the settings/g' "$file_path"
    sed -i 's/através do arquivo/through the file/g' "$file_path"
    sed -i 's/Configurações de Usuário/User Settings/g' "$file_path"
    sed -i 's/Configurações de Email/Email Settings/g' "$file_path"
    sed -i 's/Ativar Google/Enable Google/g' "$file_path"
    sed -i 's/Ativar envio de emails/Enable email sending/g' "$file_path"
    sed -i 's/Conexão Segura/Secure Connection/g' "$file_path"
    sed -i 's/Modo Debug/Debug Mode/g' "$file_path"
    sed -i 's/Retorno do Debug/Debug Output/g' "$file_path"
    sed -i 's/Testar reCAPTCHA/Test reCAPTCHA/g' "$file_path"
    sed -i 's/Enviar Email de Teste/Send Test Email/g' "$file_path"
    sed -i 's/Save Configurações/Save Settings/g' "$file_path"
    
    # Common placeholders
    sed -i 's/placeholder="Digite/placeholder="Enter/g' "$file_path"
    sed -i 's/placeholder="Selecione/placeholder="Select/g' "$file_path"
    sed -i 's/placeholder="Ex:/placeholder="Ex:/g' "$file_path"
    sed -i 's/seu-email@/your-email@/g' "$file_path"
    sed -i 's/seudominio\.com/yourdomain.com/g' "$file_path"
    sed -i 's/Sistema Conn2Flow/Conn2Flow System/g' "$file_path"
    sed -i 's/Suporte/Support/g' "$file_path"
    
    # HTML Comments
    sed -i 's/<!-- \([^>]*\) Configurações/<!-- \1 Settings/g' "$file_path"
    sed -i 's/<!-- Botões de Action/<!-- Action Buttons/g' "$file_path"
    sed -i 's/<!-- Tab: Configurações/<!-- Tab: Settings/g' "$file_path"
    
    # Specific labels
    sed -i 's/>Site Key do/<Site Key/g' "$file_path"
    sed -i 's/>Server Key do/<Server Key/g' "$file_path"
    sed -i 's/>Host SMTP</SMTP Host</g' "$file_path"
    sed -i 's/>Usuário SMTP</SMTP User</g' "$file_path"
    sed -i 's/>Senha SMTP</SMTP Password</g' "$file_path"
    sed -i 's/>Porta SMTP</SMTP Port</g' "$file_path"
    sed -i 's/>Email Remetente</From Email</g' "$file_path"
    sed -i 's/>Name do Remetente</From Name</g' "$file_path"
    sed -i 's/>Email para Resposta</Reply-To Email</g' "$file_path"
    sed -i 's/>Name para Resposta</Reply-To Name</g' "$file_path"
    
    ((total_translated++))
    log_success "Translated: $file_name"
    return 0
}

# Function to process all HTML files
process_all_html_files() {
    log_info "Starting textual content translation..."
    
    # Search all HTML files in /en/ structure
    find "$GESTOR_DIR" -path "*/resources/en/*" -name "*.html" -type f | while read -r file; do
        translate_html_content "$file"
        
        # Short pause to avoid overload
        sleep 0.1
    done
    
    log_success "Content translation completed: $total_translated files"
}

# Main function
main() {
    show_header
    
    log_info "Starting real textual content translation..."
    log_warning "Keeping variables @[[...]]@ and #...# unchanged"
    
    # Process all HTML files
    process_all_html_files
    
    echo -e "\n${GREEN}🎉 CONTENT TRANSLATION COMPLETED!${NC}"
    echo -e "${BLUE}📁 Files with translated content in /en/ structure${NC}"
    echo -e "${YELLOW}📝 Next steps: Execute system synchronization${NC}"
    
    # Suggested commands
    echo -e "\n${CYAN}Suggested commands to execute:${NC}"
    echo "1. php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"
    echo "2. bash ./ai-workspace/scripts/dev-environment/sincroniza-gestor.sh checksum"
}

# Execute main function
main "$@"
