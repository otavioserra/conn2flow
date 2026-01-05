#!/bin/bash

# Script: Project Deploy via API
# ------------------------------------------------------------------------------
# This script automates the complete deploy of a project via OAuth API.
# Operation:
# 1. Reads the environment.json file to identify the target project
# 2. Updates project data and resources (layouts, pages, components)
# 3. Compresses the complete project folder into ZIP (excluding dynamic data)
# 4. Uploads via API to the /_api/project/update endpoint
# 5. Receives processing and update confirmation from the server

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

# Function to get OAuth token for specific project
get_oauth_token() {
    local token_file="$PROJECT_ROOT/dev-environment/data/environment.json"
    local project_target="$1"

    if [ ! -f "$token_file" ]; then
        log_error "Token file not found: $token_file"
        return 1
    fi

    ACCESS_TOKEN=$(jq -r ".devProjects.\"$project_target\".api.access_token" "$token_file" 2>/dev/null)
    if [ -z "$ACCESS_TOKEN" ] || [ "$ACCESS_TOKEN" = "null" ]; then
        log_error "Access token not found for project $project_target"
        return 1
    fi

    echo "$ACCESS_TOKEN"
    return 0
}

# Function to normalize URL (remove double slashes)
normalize_url() {
    local url="$1"
    local endpoint="$2"

    # Remove all trailing slashes from base URL
    while [[ "$url" == */ ]]; do
        url="${url%/}"
    done

    # Concatenate with endpoint (always starts with /)
    echo "${url}${endpoint}"
}

# Function to upload ZIP
upload_zip() {
    local zip_file="$1"
    local api_url="$2"
    local token="$3"
    local project_target="$4"

    if [ ! -f "$zip_file" ]; then
        log_error "ZIP file not found: $zip_file"
        return 1
    fi

    log "Sending file: $(basename "$zip_file")"
    log "API URL: $api_url"
    log "File size: $(du -h "$zip_file" | cut -f1)"

    # Use curl to upload multipart/form-data
    response=$(curl -s -w "\n%{http_code}" \
        -H "Authorization: Bearer $token" \
        -H "X-Project-ID: $project_target" \
        -F "project_zip=@$zip_file" \
        "$api_url")

    # Separate response body and HTTP code
    http_code=$(echo "$response" | tail -n1)
    response_body=$(echo "$response" | head -n -1)

    # Define global variable for HTTP code
    UPLOAD_HTTP_CODE=$http_code

    log "HTTP Code: $http_code"

    if [ "$http_code" -eq 200 ]; then
        log_success "Deploy performed successfully!"
        echo "$response_body" | jq . 2>/dev/null || echo "$response_body"
        return 0
    else
        log_error "Deploy failed (HTTP $http_code)"
        echo "$response_body"
        return 1
    fi
}

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
TEMP_DIR="$PROJECT_ROOT/temp"

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
            echo "  --project, -p PROJECT_ID    Project identifier for deploy (optional)"
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

log "Starting project deploy..."
log "Environment file: $ENV_FILE"

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
    log_error "Project directory does not exist: $PROJECT_PATH"
    exit 1
fi

# Execute data and resource update before deploy
log "Updating project data and resources..."
UPDATE_SCRIPT="$SCRIPT_DIR/update-resource-data.sh"

if [ -f "$UPDATE_SCRIPT" ]; then
    log "Executing resource update: $UPDATE_SCRIPT --project $PROJECT_TARGET"
    
    if "$UPDATE_SCRIPT" --project "$PROJECT_TARGET"; then
        log_success "Data and resources updated successfully!"
    else
        log_error "Data and resource update failed"
        exit 1
    fi
else
    log_warning "Resource update script not found: $UPDATE_SCRIPT"
    log_warning "Continuing with deploy (data may be outdated)"
fi

# Create temporary directory if it doesn't exist
if [ ! -d "$TEMP_DIR" ]; then
    mkdir -p "$TEMP_DIR"
    log "Temporary directory created: $TEMP_DIR"
fi

# ZIP file name
ZIP_FILE="$TEMP_DIR/${PROJECT_TARGET}_$(date +'%Y%m%d_%H%M%S').zip"

log "Preparing deploy package..."
log "Source directory: $PROJECT_PATH"
log "Target file: $ZIP_FILE"

# Compress the project (excluding temporary files, .git and resources folder)
# Note: The resources folder is excluded because it contains dynamically generated data
# that will be recreated by the system during the update
cd "$PROJECT_PATH"
"7z" a -tzip "$ZIP_FILE" . -xr0!*.git* -xr0!*.tmp -xr0!*.log -xr0!temp/ -xr0!logs/ -xr0!resources/ > /dev/null 2>&1

if [ ! -f "$ZIP_FILE" ]; then
    log_error "Failed to create ZIP package"
    exit 1
fi

log_success "ZIP package created successfully: $(basename "$ZIP_FILE")"

# Get OAuth token
log "Getting authentication token..."
ACCESS_TOKEN=$(get_oauth_token "$PROJECT_TARGET")

if [ $? -ne 0 ]; then
    log_error "Failed to get authentication token"
    rm -f "$ZIP_FILE"
    exit 1
fi

log "Token obtained successfully"

# Read project URL
PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE" 2>/dev/null)

if [ -z "$PROJECT_URL" ] || [ "$PROJECT_URL" = "null" ]; then
    log_error "Could not find URL for project $PROJECT_TARGET"
    exit 1
fi

log "Project URL: $PROJECT_URL"

# API URL based on project URL (normalized to avoid double slashes)
API_URL=$(normalize_url "$PROJECT_URL" "/_api/project/update")

log "Starting deploy via API..."

# Upload with token renewal attempt
if upload_zip "$ZIP_FILE" "$API_URL" "$ACCESS_TOKEN" "$PROJECT_TARGET"; then
    log_success "Deploy completed successfully!"
    # Clean temporary file
    rm -f "$ZIP_FILE"
    log "Temporary package removed: $(basename "$ZIP_FILE")"
else
    # Check if it was an authentication error (401)
    if [ "$UPLOAD_HTTP_CODE" -eq 401 ]; then
        log_warning "Token expired. Trying to renew..."

        # Path to renewal script
        RENEW_SCRIPT="$PROJECT_ROOT/ai-workspace/en/scripts/api/renew-token.sh"

        if [ -f "$RENEW_SCRIPT" ]; then
            log "Executing renewal script: $RENEW_SCRIPT"

            # Try to renew token
            NEW_TOKEN=$("$RENEW_SCRIPT" --project="$PROJECT_TARGET" --env-file="$ENV_FILE")
            RENEW_EXIT_CODE=$?

            if [ $RENEW_EXIT_CODE -eq 0 ] && [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
                log_success "Token renewed successfully!"

                # Reload ACCESS_TOKEN from environment.json
                ACCESS_TOKEN=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE")

                if [ "$ACCESS_TOKEN" = "null" ] || [ -z "$ACCESS_TOKEN" ]; then
                    log_error "Failed to get new token from environment.json"
                    exit 1
                fi

                log "Trying deploy again with renewed token..."

                # Try upload again
                if upload_zip "$ZIP_FILE" "$API_URL" "$ACCESS_TOKEN" "$PROJECT_TARGET"; then
                    log_success "Deploy performed successfully after renewal!"
                    # Clean temporary file
                    rm -f "$ZIP_FILE"
                    log "Temporary package removed: $(basename "$ZIP_FILE")"
                else
                    log_error "Deploy failed even after token renewal"
                    # Keep temporary file for debug
                    log_warning "Temporary package kept for analysis: $ZIP_FILE"
                    exit 1
                fi
            else
                log_error "Token renewal failed: $NEW_TOKEN"
                log_error "Tokens may be expired. Perform manual renewal or re-authenticate."
                exit 1
            fi
        else
            log_error "Renewal script not found: $RENEW_SCRIPT"
            exit 1
        fi
    else
        log_error "Deploy process failed (HTTP $UPLOAD_HTTP_CODE)"
        # Keep temporary file for debug
        log_warning "Temporary package kept for analysis: $ZIP_FILE"
        exit 1
    fi

fi
