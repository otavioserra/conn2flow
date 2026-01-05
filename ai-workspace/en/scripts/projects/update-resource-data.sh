#!/bin/bash

# Script: Data and Resource Update for Projects
# ------------------------------------------------------------------------------
# This script automates resource updates for specific projects.
# Operation:
# 1. Reads the environment.json file
# 2. Identifies the target project via devEnvironment.projectTarget
# 3. Gets the project path via devProjects[projectTarget].path
# 4. Executes the PHP update script with the project path

set -e  # Stop the script in case of error

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Log function
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

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# From projects -> scripts -> ai-workspace -> conn2flow (3 levels)
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
PHP_SCRIPT="$PROJECT_ROOT/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"

# Argument parsing
PROJECT_TARGET_OVERRIDE=""
while [[ $# -gt 0 ]]; do
    case $1 in
        --project|-p)
            PROJECT_TARGET_OVERRIDE="$2"
            shift 2
            ;;
        --help|-h)
            echo "Usage: $0 [--project|-p PROJECT_ID]"
            echo ""
            echo "Options:"
            echo "  --project, -p PROJECT_ID    Project identifier for update (optional)"
            echo "                              If not provided, uses the value of devEnvironment.projectTarget from environment.json"
            echo "  --help, -h                  Shows this help"
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use --help to see available options."
            exit 1
            ;;
    esac
done

# Check if files exist
if [ ! -f "$ENV_FILE" ]; then
    log_error "environment.json file not found: $ENV_FILE"
    exit 1
fi

if [ ! -f "$PHP_SCRIPT" ]; then
    log_error "PHP script not found: $PHP_SCRIPT"
    exit 1
fi

log "Starting resource update for projects..."
log "Environment file: $ENV_FILE"
log "PHP Script: $PHP_SCRIPT"

# Determine target project
if [ -n "$PROJECT_TARGET_OVERRIDE" ]; then
    PROJECT_TARGET="$PROJECT_TARGET_OVERRIDE"
    log "Target project specified via argument: $PROJECT_TARGET"
else
    # Read projectTarget from environment.json
    PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)

    if [ -z "$PROJECT_TARGET" ] || [ "$PROJECT_TARGET" = "null" ]; then
        log_error "Could not find devEnvironment.projectTarget in environment file"
        log_error "Use --project to specify the project identifier"
        exit 1
    fi

    log "Target project identified in environment.json: $PROJECT_TARGET"
fi

# Check if project exists in environment.json
PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_FILE" 2>/dev/null)

if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
    log_error "Project '$PROJECT_TARGET' not found in environment.json"
    log_error "Check if the identifier is correct and if the project is configured"
    exit 1
fi

# Read project path
PROJECT_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path" "$ENV_FILE" 2>/dev/null)

if [ -z "$PROJECT_PATH" ] || [ "$PROJECT_PATH" = "null" ]; then
    log_error "Could not find path for project $PROJECT_TARGET"
    exit 1
fi

log "Project path: $PROJECT_PATH"

# Check if project directory exists
if [ ! -d "$PROJECT_PATH" ]; then
    log_warning "Project directory does not exist. Creating: $PROJECT_PATH"
    mkdir -p "$PROJECT_PATH"
fi

# Check and execute TailwindCSS CLI if configured
TAILWIND_CLI=$(jq -r ".devProjects.\"$PROJECT_TARGET\".\"tailwindcss/cli\"" "$ENV_FILE" 2>/dev/null)

if [ -n "$TAILWIND_CLI" ] && [ "$TAILWIND_CLI" != "null" ]; then
    log "Executing TailwindCSS CLI for the project..."
    cd "$PROJECT_PATH"
    eval "$TAILWIND_CLI"
    if [ $? -eq 0 ]; then
        log_success "TailwindCSS CLI executed successfully!"
    else
        log_error "TailwindCSS CLI execution failed"
        exit 1
    fi
fi

# Execute PHP script with project path
log "Executing resource update for the project..."
log "Command: php \"$PHP_SCRIPT\" --project-path=\"$PROJECT_PATH\""

php "$PHP_SCRIPT" --project-path="$PROJECT_PATH"

if [ $? -eq 0 ]; then
    log_success "Resource update completed successfully!"
else
    log_error "Resource update failed"
    exit 1
fi
