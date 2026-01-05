#!/bin/bash

# Script to create a new skeleton module for the Conn2Flow system
# Usage: ./create-new-module.sh [module-id] [lang]

set -e  # Stop script on error

# ===== CONFIGURATION =====

# Parameters with default values
MODULE_ID="${1:-module-id}"
LANG="${2:-en}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function for colored log
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ===== VALIDATION =====

# Check if we are in the correct directory
if [[ ! -f "gestor/gestor.php" ]]; then
    error "This script must be executed at the root of the Conn2Flow project!"
    exit 1
fi

# Check if the module already exists
if [[ -d "gestor/modulos/$MODULE_ID" ]]; then
    error "The module '$MODULE_ID' already exists!"
    exit 1
fi

# ===== AUXILIARY FUNCTIONS =====

# Converts module ID to function name (replaces - with _)
module_to_function() {
    echo "$1" | sed 's/-/_/g'
}

# Creates folder structure
create_structure() {
    log "Creating folder structure for module '$MODULE_ID'..."

    # Main module folder
    mkdir -p "gestor/modulos/$MODULE_ID"

    # Resources
    mkdir -p "gestor/modulos/$MODULE_ID/resources/$LANG/components"
    mkdir -p "gestor/modulos/$MODULE_ID/resources/$LANG/layouts"
    mkdir -p "gestor/modulos/$MODULE_ID/resources/$LANG/pages"

    log "Folder structure created successfully!"
}

# Creates JSON configuration file
create_json_config() {
    log "Creating JSON configuration file..."

    cat > "gestor/modulos/$MODULE_ID/$MODULE_ID.json" << EOF
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
                    "name": "HomePageName",
                    "id": "home-page-id",
                    "layout": "layout-administrativo-do-gestor",
                    "path": "home-page-path/",
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

    log "JSON file created successfully!"
}

# Creates PHP module file
create_php_module() {
    log "Creating PHP module file..."

    FUNCTION_NAME=$(module_to_function "$MODULE_ID")

    cat > "gestor/modulos/$MODULE_ID/$MODULE_ID.php" << EOF
<?php

global \$_GESTOR;

\$_GESTOR['modulo-id'] = '$MODULE_ID';
\$_GESTOR['modulo#'.\$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/$MODULE_ID.json'), true);

// ===== Auxiliary Interfaces



// ===== Main Interfaces

function ${FUNCTION_NAME}_raiz(){
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#'.\$_GESTOR['modulo-id']];

    // ===== Include JS Module
	
	gestor_pagina_javascript_incluir();

    // ===== Logic

    // ===== Return Data

}

function ${FUNCTION_NAME}_interfaces_padroes(){
	global \$_GESTOR;
	
	\$modulo = \$_GESTOR['modulo#'.\$_GESTOR['modulo-id']];
	
	switch(\$_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function ${FUNCTION_NAME}_ajax_opcao(){
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#'.\$_GESTOR['modulo-id']];

    // ===== Logic

    \$payload = [];

    // ===== Return Data

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

    log "PHP file created successfully!"
}

# Creates JavaScript file
create_js_file() {
    log "Creating JavaScript file..."

    cat > "gestor/modulos/$MODULE_ID/$MODULE_ID.js" << EOF
\$(document).ready(function(){

});
EOF

    log "JavaScript file created successfully!"
}

# Creates HTML home page file
create_html_page() {
    log "Creating HTML home page file..."

    mkdir -p "gestor/modulos/$MODULE_ID/resources/$LANG/pages/home-page-id"

    cat > "gestor/modulos/$MODULE_ID/resources/$LANG/pages/home-page-id/home-page-id.html" << EOF
<!-- Home Page of Module $MODULE_ID -->

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1>Home Page - $MODULE_ID</h1>
            <p>This is the home page of module <strong>$MODULE_ID</strong>.</p>

            <!-- Page content here -->

        </div>
    </div>
</div>
EOF

    log "HTML file created successfully!"
}

# Opens files in VS Code
open_in_vscode() {
    log "Opening files in VS Code..."

    # Check if 'code' command is available
    if command -v code &> /dev/null; then
        # Files to open
        files=(
            "gestor/modulos/$MODULE_ID/$MODULE_ID.php"
            "gestor/modulos/$MODULE_ID/$MODULE_ID.js"
            "gestor/modulos/$MODULE_ID/$MODULE_ID.json"
            "gestor/modulos/$MODULE_ID/resources/$LANG/pages/home-page-id/home-page-id.html"
        )

        # Open each file in VS Code
        for file in "${files[@]}"; do
            if [[ -f "$file" ]]; then
                code "$file"
                log "Opened: $file"
            else
                warn "File not found: $file"
            fi
        done
    else
        log "VS Code not found. Skipping automatic file opening."
        log "Created files:"
        log "  - gestor/modulos/$MODULE_ID/$MODULE_ID.php"
        log "  - gestor/modulos/$MODULE_ID/$MODULE_ID.js"
        log "  - gestor/modulos/$MODULE_ID/$MODULE_ID.json"
        log "  - gestor/modulos/$MODULE_ID/resources/$LANG/pages/home-page-id/home-page-id.html"
    fi
}

# ===== EXECUTION =====

log "🚀 Starting creation of skeleton module '$MODULE_ID'..."

create_structure
create_json_config
create_php_module
create_js_file
create_html_page

log "✅ Skeleton module created successfully!"
log "📁 Location: gestor/modulos/$MODULE_ID/"

# Open files in VS Code
open_in_vscode

log "🎉 Process completed! The files were opened in VS Code for editing."
