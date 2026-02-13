#!/bin/bash

# Script: System Update via API
# ------------------------------------------------------------------------------
# This script automates a complete system update on a remote Conn2Flow installation
# via the OAuth-authenticated REST API.
#
# Operation:
# 1. Reads environment.json to identify the target project/installation
# 2. Initiates a system update session via /_api/system/update (action=start)
# 3. Sequentially calls deploy, db, and finalize steps
# 4. Polls for status and displays real-time progress
# 5. Handles token renewal on 401 errors
#
# Usage:
#   bash update-system.sh [--project PROJECT_ID] [--mode MODE] [--tag TAG] [--dry-run] [--local] [--debug]
#
# Modes: full (default), only-files, only-db

set -e  # Stop the script in case of error

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Log functions
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

log_step() {
    echo -e "\n${MAGENTA}${BOLD}▶ $1${NC}"
}

log_progress() {
    local percent=$1
    local label=$2
    local filled=$((percent / 5))
    local empty=$((20 - filled))
    local bar=""
    for ((i=0; i<filled; i++)); do bar+="█"; done
    for ((i=0; i<empty; i++)); do bar+="░"; done
    printf "\r  ${CYAN}[${bar}] ${percent}%%${NC} ${label}                    "
}

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"

# Default values
PROJECT_TARGET_OVERRIDE=""
UPDATE_MODE="full"
UPDATE_TAG=""
DRY_RUN=""
LOCAL_ARTIFACT=""
DEBUG_FLAG=""
NO_DB=""
FORCE_ALL=""
LOG_DIFF=""
BACKUP=""

# Argument parsing
while [[ $# -gt 0 ]]; do
    case $1 in
        --project|-p)
            PROJECT_TARGET_OVERRIDE="$2"
            shift 2
            ;;
        --mode|-m)
            UPDATE_MODE="$2"
            shift 2
            ;;
        --tag|-t)
            UPDATE_TAG="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN="1"
            shift
            ;;
        --local)
            LOCAL_ARTIFACT="1"
            shift
            ;;
        --debug)
            DEBUG_FLAG="1"
            shift
            ;;
        --no-db)
            NO_DB="1"
            shift
            ;;
        --force-all)
            FORCE_ALL="1"
            shift
            ;;
        --log-diff)
            LOG_DIFF="1"
            shift
            ;;
        --backup)
            BACKUP="1"
            shift
            ;;
        --help|-h)
            echo ""
            echo -e "${BOLD}Conn2Flow - System Update via API${NC}"
            echo ""
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --project, -p ID      Project identifier (default: from environment.json)"
            echo "  --mode, -m MODE       Update mode: full, only-files, only-db (default: full)"
            echo "  --tag, -t TAG         Specific release tag (e.g., gestor-v1.2.3)"
            echo "  --dry-run             Simulate update without applying changes"
            echo "  --local               Use local artifact instead of downloading"
            echo "  --debug               Enable verbose debug output"
            echo "  --no-db               Skip database updates"
            echo "  --force-all           Force all database updates"
            echo "  --log-diff            Log detailed diffs for database"
            echo "  --backup              Create backup before update"
            echo "  --help, -h            Show this help"
            echo ""
            echo "Examples:"
            echo "  $0                                    # Update current project (full mode)"
            echo "  $0 --project conn2flow-site-local     # Update specific project"
            echo "  $0 --mode only-files --local          # Only update files from local artifact"
            echo "  $0 --dry-run --debug                  # Simulate with debug output"
            echo ""
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use --help to see available options."
            exit 1
            ;;
    esac
done

# Validate mode
case "$UPDATE_MODE" in
    full|only-files|only-db)
        ;;
    *)
        log_error "Invalid mode: $UPDATE_MODE. Use: full, only-files, only-db"
        exit 1
        ;;
esac

# Check if files exist
if [ ! -f "$ENV_FILE" ]; then
    log_error "environment.json file not found: $ENV_FILE"
    exit 1
fi

# ============================================
# Functions
# ============================================

# Function to get OAuth token for specific project
get_oauth_token() {
    local project_target="$1"
    local token

    token=$(jq -r ".devProjects.\"$project_target\".api.access_token" "$ENV_FILE" 2>/dev/null)
    if [ -z "$token" ] || [ "$token" = "null" ]; then
        log_error "Access token not found for project $project_target"
        return 1
    fi

    echo "$token"
    return 0
}

# Function to normalize URL
normalize_url() {
    local url="$1"
    local endpoint="$2"

    while [[ "$url" == */ ]]; do
        url="${url%/}"
    done

    echo "${url}${endpoint}"
}

# Function to make API calls
api_call() {
    local api_url="$1"
    local token="$2"
    local action="$3"
    local extra_params="$4"

    local data="action=${action}"
    if [ -n "$extra_params" ]; then
        data="${data}&${extra_params}"
    fi

    response=$(curl -s -w "\n%{http_code}" \
        -X POST \
        -H "Authorization: Bearer $token" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "$data" \
        "$api_url" 2>/dev/null)

    http_code=$(echo "$response" | tail -n1)
    response_body=$(echo "$response" | head -n -1)

    API_HTTP_CODE=$http_code
    API_RESPONSE=$response_body
}

# Parse JSON field from response
json_field() {
    echo "$1" | jq -r "$2" 2>/dev/null
}

# Try to renew token
try_renew_token() {
    local project_target="$1"

    log_warning "Token expired. Trying to renew..."

    local renew_script="$PROJECT_ROOT/ai-workspace/en/scripts/api/renew-token.sh"

    if [ ! -f "$renew_script" ]; then
        log_error "Renewal script not found: $renew_script"
        return 1
    fi

    local new_token
    new_token=$("$renew_script" --project="$project_target" --env-file="$ENV_FILE" 2>/dev/null)
    local renew_exit=$?

    if [ $renew_exit -eq 0 ] && [ -n "$new_token" ] && [ "$new_token" != "null" ]; then
        log_success "Token renewed successfully!"
        ACCESS_TOKEN=$(jq -r ".devProjects.\"$project_target\".api.access_token" "$ENV_FILE")
        if [ "$ACCESS_TOKEN" = "null" ] || [ -z "$ACCESS_TOKEN" ]; then
            log_error "Failed to get new token from environment.json"
            return 1
        fi
        return 0
    else
        log_error "Token renewal failed"
        return 1
    fi
}

# ============================================
# Main Execution
# ============================================

echo ""
echo -e "${BOLD}${CYAN}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${CYAN}║       Conn2Flow - System Update via API         ║${NC}"
echo -e "${BOLD}${CYAN}╚══════════════════════════════════════════════════╝${NC}"
echo ""

log "Starting system update process..."
log "Environment file: $ENV_FILE"

# Determine target project
if [ -n "$PROJECT_TARGET_OVERRIDE" ]; then
    PROJECT_TARGET="$PROJECT_TARGET_OVERRIDE"
    log "Target project specified via argument: ${BOLD}$PROJECT_TARGET${NC}"
else
    PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)

    if [ -z "$PROJECT_TARGET" ] || [ "$PROJECT_TARGET" = "null" ]; then
        log_error "Could not find devEnvironment.projectTarget in environment file"
        log_error "Use --project to specify the project identifier"
        exit 1
    fi

    log "Target project identified: ${BOLD}$PROJECT_TARGET${NC}"
fi

# Check if project exists
PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_FILE" 2>/dev/null)
if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
    log_error "Project '$PROJECT_TARGET' not found in environment.json"
    exit 1
fi

# Read project URL
PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE" 2>/dev/null)
if [ -z "$PROJECT_URL" ] || [ "$PROJECT_URL" = "null" ]; then
    log_error "Could not find URL for project $PROJECT_TARGET"
    exit 1
fi

# Get OAuth token
log "Getting authentication token..."
ACCESS_TOKEN=$(get_oauth_token "$PROJECT_TARGET")
if [ $? -ne 0 ]; then
    log_error "Failed to get authentication token"
    exit 1
fi
log "Token obtained successfully"

# API URL
API_URL=$(normalize_url "$PROJECT_URL" "/_api/system/update")
log "API URL: ${BOLD}$API_URL${NC}"
log "Update mode: ${BOLD}$UPDATE_MODE${NC}"
if [ -n "$UPDATE_TAG" ]; then log "Release tag: ${BOLD}$UPDATE_TAG${NC}"; fi
if [ -n "$DRY_RUN" ]; then log_warning "DRY-RUN mode enabled (no changes will be applied)"; fi
if [ -n "$LOCAL_ARTIFACT" ]; then log "Using local artifact"; fi

# Build extra parameters based on mode
EXTRA_PARAMS=""
case "$UPDATE_MODE" in
    only-files) EXTRA_PARAMS="only_files=1" ;;
    only-db) EXTRA_PARAMS="only_db=1" ;;
esac
[ -n "$UPDATE_TAG" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&tag=${UPDATE_TAG}"
[ -n "$DRY_RUN" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&dry_run=1"
[ -n "$LOCAL_ARTIFACT" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&local=1"
[ -n "$DEBUG_FLAG" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&debug=1"
[ -n "$NO_DB" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&no_db=1"
[ -n "$FORCE_ALL" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&force_all=1"
[ -n "$LOG_DIFF" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&log_diff=1"
[ -n "$BACKUP" ] && EXTRA_PARAMS="${EXTRA_PARAMS}&backup=1"

# Remove leading &
EXTRA_PARAMS="${EXTRA_PARAMS#&}"

# ============================================
# Step 1: Start session
# ============================================
log_step "Step 1/4: Starting update session..."

api_call "$API_URL" "$ACCESS_TOKEN" "start" "$EXTRA_PARAMS"

# Handle 401 - try token renewal
if [ "$API_HTTP_CODE" -eq 401 ]; then
    if try_renew_token "$PROJECT_TARGET"; then
        api_call "$API_URL" "$ACCESS_TOKEN" "start" "$EXTRA_PARAMS"
    else
        log_error "Authentication failed. Cannot proceed."
        exit 1
    fi
fi

if [ "$API_HTTP_CODE" -ne 200 ]; then
    log_error "Failed to start update session (HTTP $API_HTTP_CODE)"
    echo "$API_RESPONSE" | jq . 2>/dev/null || echo "$API_RESPONSE"
    exit 1
fi

# Extract session ID
SID=$(json_field "$API_RESPONSE" '.data.sid')
EXEC_ID=$(json_field "$API_RESPONSE" '.data.exec_id')
RELEASE_TAG=$(json_field "$API_RESPONSE" '.data.release_tag')
NEXT_STEP=$(json_field "$API_RESPONSE" '.data.next')

if [ -z "$SID" ] || [ "$SID" = "null" ]; then
    log_error "Failed to obtain session ID"
    echo "$API_RESPONSE" | jq . 2>/dev/null || echo "$API_RESPONSE"
    exit 1
fi

log_success "Session started: ${BOLD}$SID${NC}"
log "Release tag: ${BOLD}$RELEASE_TAG${NC}"
log "Execution ID: $EXEC_ID"
log "Next step: $NEXT_STEP"
log_progress 10 "Bootstrap complete"
echo ""

# ============================================
# Step 2: Deploy files (if applicable)
# ============================================
if [ "$NEXT_STEP" = "deploy_files" ] || [ "$NEXT_STEP" = "deploy" ]; then
    echo ""
    log_step "Step 2/4: Deploying files..."

    api_call "$API_URL" "$ACCESS_TOKEN" "deploy" "sid=$SID"

    if [ "$API_HTTP_CODE" -ne 200 ]; then
        log_error "Deploy failed (HTTP $API_HTTP_CODE)"
        echo "$API_RESPONSE" | jq . 2>/dev/null || echo "$API_RESPONSE"
        exit 1
    fi

    ERROR=$(json_field "$API_RESPONSE" '.data.error // empty')
    if [ -n "$ERROR" ] && [ "$ERROR" != "null" ]; then
        log_error "Deploy error: $ERROR"
        exit 1
    fi

    STATS_REMOVED=$(json_field "$API_RESPONSE" '.data.stats.removed // 0')
    STATS_COPIED=$(json_field "$API_RESPONSE" '.data.stats.copied // 0')
    NEXT_STEP=$(json_field "$API_RESPONSE" '.data.next')

    log_success "Deploy completed!"
    log "  Files removed: ${BOLD}$STATS_REMOVED${NC}"
    log "  Files copied: ${BOLD}$STATS_COPIED${NC}"
    log_progress 55 "Deploy complete"
    echo ""
else
    log "Skipping deploy step (mode: $UPDATE_MODE)"
    NEXT_STEP="database"
fi

# ============================================
# Step 3: Database update (if applicable)
# ============================================
if [ "$NEXT_STEP" = "database" ] || [ "$NEXT_STEP" = "db" ]; then
    echo ""
    log_step "Step 3/4: Updating database..."

    api_call "$API_URL" "$ACCESS_TOKEN" "db" "sid=$SID"

    if [ "$API_HTTP_CODE" -ne 200 ]; then
        log_error "Database update failed (HTTP $API_HTTP_CODE)"
        echo "$API_RESPONSE" | jq . 2>/dev/null || echo "$API_RESPONSE"
        exit 1
    fi

    SKIPPED=$(json_field "$API_RESPONSE" '.data.skipped // empty')
    ERROR=$(json_field "$API_RESPONSE" '.data.error // empty')

    if [ "$SKIPPED" = "true" ]; then
        log_warning "Database update skipped (mode: $UPDATE_MODE)"
    elif [ -n "$ERROR" ] && [ "$ERROR" != "null" ]; then
        log_error "Database error: $ERROR"
        log_warning "Continuing to finalize step..."
    else
        log_success "Database update completed!"
    fi
    
    log_progress 85 "Database complete"
    echo ""
else
    log "Skipping database step"
fi

# ============================================
# Step 4: Finalize
# ============================================
echo ""
log_step "Step 4/4: Finalizing..."

api_call "$API_URL" "$ACCESS_TOKEN" "finalize" "sid=$SID"

if [ "$API_HTTP_CODE" -ne 200 ]; then
    log_error "Finalize failed (HTTP $API_HTTP_CODE)"
    echo "$API_RESPONSE" | jq . 2>/dev/null || echo "$API_RESPONSE"
    exit 1
fi

FINISHED=$(json_field "$API_RESPONSE" '.data.finished')

if [ "$FINISHED" = "true" ]; then
    log_progress 100 "Complete!"
    echo ""
    echo ""
    
    # ============================================
    # Final Summary
    # ============================================
    echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}${GREEN}║          System Update - Summary                ║${NC}"
    echo -e "${BOLD}${GREEN}╠══════════════════════════════════════════════════╣${NC}"
    echo -e "${GREEN}║${NC} Project:      ${BOLD}$PROJECT_TARGET${NC}"
    echo -e "${GREEN}║${NC} URL:          ${BOLD}$PROJECT_URL${NC}"
    echo -e "${GREEN}║${NC} Release:      ${BOLD}$RELEASE_TAG${NC}"
    echo -e "${GREEN}║${NC} Mode:         ${BOLD}$UPDATE_MODE${NC}"
    echo -e "${GREEN}║${NC} Session:      $SID"
    echo -e "${GREEN}║${NC} Execution ID: $EXEC_ID"
    if [ -n "$STATS_REMOVED" ] && [ "$STATS_REMOVED" != "null" ]; then
        echo -e "${GREEN}║${NC} Removed:      $STATS_REMOVED files"
        echo -e "${GREEN}║${NC} Copied:       $STATS_COPIED files"
    fi
    if [ -n "$DRY_RUN" ]; then
        echo -e "${GREEN}║${NC} ${YELLOW}(DRY-RUN - no changes applied)${NC}"
    fi
    echo -e "${BOLD}${GREEN}║${NC} Status:       ${GREEN}✔ COMPLETED${NC}"
    echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════╝${NC}"
    echo ""
else
    log_warning "Finalize returned but finished flag is not true"
    echo "$API_RESPONSE" | jq . 2>/dev/null || echo "$API_RESPONSE"
fi

exit 0
