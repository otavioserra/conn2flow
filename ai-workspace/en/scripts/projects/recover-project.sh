#!/bin/bash

# Script: Project Recover via API (Pull System / Reverse Engineering)
# ------------------------------------------------------------------------------
# This script automates the reverse synchronization (Pull) of a project via OAuth API.
# Operation:
# 1. Reads the environment.json file to identify the target project (URL + OAuth token)
# 2. POSTs to the /_api/project/recover endpoint and downloads a ZIP with raw *Data.json dumps
# 3. Extracts the ZIP into a temporary folder (temp/recover_extract/)
# 4. Runs the local resource decompiler to rebuild physical files (HTML/CSS/MD) and metadata
# 5. Runs the local resource compiler to regenerate *Data.json and ensure full consistency
# 6. Cleans up the temporary extraction folder
#
# req-058 / BATCH-058.

set -e  # Stop the script in case of error

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Log functions
log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1" >&2; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }

# Function to get OAuth token for specific project
get_oauth_token() {
    local token_file="$1"
    local project_target="$2"
    local token
    token=$(jq -r ".devProjects.\"$project_target\".api.access_token" "$token_file" 2>/dev/null)
    if [ -z "$token" ] || [ "$token" = "null" ]; then
        return 1
    fi
    echo "$token"
    return 0
}

# Function to normalize URL (remove double slashes between base and endpoint)
normalize_url() {
    local url="$1"
    local endpoint="$2"
    while [[ "$url" == */ ]]; do url="${url%/}"; done
    echo "${url}${endpoint}"
}

# Function to download the recover ZIP. Sets RECOVER_HTTP_CODE.
download_recover() {
    local zip_file="$1"
    local api_url="$2"
    local token="$3"
    local project_target="$4"
    local recover_contents="$5"

    log "Requesting recover package from API..."
    log "API URL: $api_url"
    if [ "$recover_contents" = "true" ]; then
        log "Including contents/ in recover package."
    fi

    # -o writes the (binary) body to file; -w prints only the HTTP code to stdout.
    RECOVER_HTTP_CODE=$(curl -s -o "$zip_file" -w "%{http_code}" \
        -X POST \
        -H "Authorization: Bearer $token" \
        -H "X-Project-ID: $project_target" \
        -H "Content-Type: application/json" \
        -d "{\"recover_contents\":$recover_contents}" \
        "$api_url")

    log "HTTP Code: $RECOVER_HTTP_CODE"
    [ "$RECOVER_HTTP_CODE" -eq 200 ]
}

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
TEMP_DIR="$PROJECT_ROOT/temp"
EXTRACT_DIR="$TEMP_DIR/recover_extract"
DECOMPILER="$PROJECT_ROOT/gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php"
COMPILER="$PROJECT_ROOT/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"

# Argument parsing
PROJECT_TARGET_OVERRIDE=""
RECOVER_CONTENTS="false"
while [[ $# -gt 0 ]]; do
    case $1 in
        --project|-p)
            PROJECT_TARGET_OVERRIDE="$2"
            shift 2
            ;;
        --contents|-c)
            RECOVER_CONTENTS="true"
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [--project|-p PROJECT_ID] [--contents|-c]"
            echo ""
            echo "Options:"
            echo "  --project, -p PROJECT_ID    Project identifier for recover (optional)"
            echo "                              If not provided, uses devEnvironment.projectTarget from environment.json"
            echo "  --contents, -c              Also recover gestor/contents with smart MD5/timestamp sync"
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

# Check required files
if [ ! -f "$ENV_FILE" ]; then
    log_error "environment.json file not found: $ENV_FILE"
    exit 1
fi
if [ ! -f "$DECOMPILER" ]; then
    log_error "Decompiler script not found: $DECOMPILER"
    exit 1
fi

log "Starting project recover (Pull System)..."
log "Environment file: $ENV_FILE"

# Determine target project
if [ -n "$PROJECT_TARGET_OVERRIDE" ]; then
    PROJECT_TARGET="$PROJECT_TARGET_OVERRIDE"
    log "Target project specified via argument: $PROJECT_TARGET"
else
    PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)
    if [ -z "$PROJECT_TARGET" ] || [ "$PROJECT_TARGET" = "null" ]; then
        log_error "Could not find devEnvironment.projectTarget in environment file"
        log_error "Use --project to specify the project identifier"
        exit 1
    fi
    log "Target project identified in environment.json: $PROJECT_TARGET"
fi

# Check if project exists
PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_FILE" 2>/dev/null)
if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
    log_error "Project '$PROJECT_TARGET' not found in environment.json"
    exit 1
fi

# Read project path and URL
PROJECT_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path" "$ENV_FILE" 2>/dev/null)
if [ -z "$PROJECT_PATH" ] || [ "$PROJECT_PATH" = "null" ]; then
    log_error "Could not find path for project $PROJECT_TARGET"
    exit 1
fi
PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE" 2>/dev/null)
if [ -z "$PROJECT_URL" ] || [ "$PROJECT_URL" = "null" ]; then
    log_error "Could not find URL for project $PROJECT_TARGET"
    exit 1
fi

log "Project path: $PROJECT_PATH"
log "Project URL: $PROJECT_URL"

if [ ! -d "$PROJECT_PATH" ]; then
    log_error "Project directory does not exist: $PROJECT_PATH"
    exit 1
fi

# Prepare temp dirs
mkdir -p "$TEMP_DIR"
rm -rf "$EXTRACT_DIR"
mkdir -p "$EXTRACT_DIR"
ZIP_FILE="$TEMP_DIR/${PROJECT_TARGET}_recover_$(date +'%Y%m%d_%H%M%S').zip"

# Get OAuth token
log "Getting authentication token..."
ACCESS_TOKEN=$(get_oauth_token "$ENV_FILE" "$PROJECT_TARGET") || {
    log_error "Failed to get authentication token"
    exit 1
}

# API URL
API_URL=$(normalize_url "$PROJECT_URL" "/_api/project/recover")

# Download with token renewal on 401
if ! download_recover "$ZIP_FILE" "$API_URL" "$ACCESS_TOKEN" "$PROJECT_TARGET" "$RECOVER_CONTENTS"; then
    if [ "$RECOVER_HTTP_CODE" -eq 401 ]; then
        log_warning "Token expired. Trying to renew..."
        RENEW_SCRIPT="$PROJECT_ROOT/ai-workspace/en/scripts/api/renew-token.sh"
        if [ -f "$RENEW_SCRIPT" ]; then
            NEW_TOKEN=$("$RENEW_SCRIPT" --project="$PROJECT_TARGET" --env-file="$ENV_FILE")
            if [ $? -eq 0 ] && [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
                ACCESS_TOKEN=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE")
                log "Retrying recover with renewed token..."
                if ! download_recover "$ZIP_FILE" "$API_URL" "$ACCESS_TOKEN" "$PROJECT_TARGET" "$RECOVER_CONTENTS"; then
                    log_error "Recover failed even after token renewal (HTTP $RECOVER_HTTP_CODE)"
                    [ -f "$ZIP_FILE" ] && cat "$ZIP_FILE" && rm -f "$ZIP_FILE"
                    exit 1
                fi
            else
                log_error "Token renewal failed: $NEW_TOKEN"
                exit 1
            fi
        else
            log_error "Renewal script not found: $RENEW_SCRIPT"
            exit 1
        fi
    else
        log_error "Recover request failed (HTTP $RECOVER_HTTP_CODE)"
        # On error the body is JSON, not a ZIP — show it for debugging.
        [ -f "$ZIP_FILE" ] && cat "$ZIP_FILE"
        rm -f "$ZIP_FILE"
        exit 1
    fi
fi

log_success "Recover package downloaded: $(basename "$ZIP_FILE")"

# Extract ZIP
log "Extracting recover package..."
if command -v unzip >/dev/null 2>&1; then
    unzip -o -q "$ZIP_FILE" -d "$EXTRACT_DIR"
else
    "7z" x -y -o"$EXTRACT_DIR" "$ZIP_FILE" > /dev/null 2>&1
fi

# Run decompiler (rebuild physical files + metadata into the project's resources)
log "Decompiling resources into project repository..."
php "$DECOMPILER" --source-dir="$EXTRACT_DIR" --project-path="$PROJECT_PATH"

# Run compiler to regenerate *Data.json and ensure consistency
if [ -f "$COMPILER" ]; then
    log "Recompiling resource data for full consistency..."
    php "$COMPILER" --project-path="$PROJECT_PATH"
else
    log_warning "Compiler script not found: $COMPILER (skipping consistency recompile)"
fi

# Cleanup
rm -rf "$EXTRACT_DIR"
rm -f "$ZIP_FILE"
log_success "Project recover completed successfully for: $PROJECT_TARGET"
