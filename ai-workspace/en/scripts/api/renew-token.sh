#!/bin/bash

# ===== OAuth Token Renewal Script
# Renews access_token using refresh_token and updates environment.json

set -e  # Stop on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if should run in silent mode and process arguments
QUIET_MODE=false
PROJECT_TARGET_ARG=""
ENV_FILE_ARG=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --quiet|--silent)
            QUIET_MODE=true
            shift
            ;;
        --project=*)
            PROJECT_TARGET_ARG="${1#*=}"
            shift
            ;;
        --env-file=*)
            ENV_FILE_ARG="${1#*=}"
            shift
            ;;
        *)
            # Ignore unknown arguments
            shift
            ;;
    esac
done

# Renewal success flag
RENEW_SUCCESS=1

# Conditional log function
log() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" >&2
    fi
}

success() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${GREEN}✅ $1${NC}" >&2
    fi
}

error() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${RED}❌ $1${NC}" >&2
    fi
}

warning() {
    if [ "$QUIET_MODE" = false ]; then
        echo -e "${YELLOW}⚠️  $1${NC}" >&2
    fi
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

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    error "jq is not installed. Install with: apt-get install jq or brew install jq"
    exit 1
fi

# Check if curl is installed
if ! command -v curl &> /dev/null; then
    error "curl is not installed. Install with: apt-get install curl or brew install curl"
    exit 1
fi

# Path to environment file
if [ -n "$ENV_FILE_ARG" ]; then
    # Use path passed by parameter (for integration with other scripts)
    ENV_FILE="$ENV_FILE_ARG"
else
    # Calculate automatically based on script location
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
    ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
fi

# Check if file exists
if [ ! -f "$ENV_FILE" ]; then
    error "environment.json file not found: $ENV_FILE"
    exit 1
fi

log "🔄 Starting OAuth token renewal..."

# Extract current configurations
if [ -n "$PROJECT_TARGET_ARG" ]; then
    # Use project passed by parameter
    PROJECT_TARGET="$PROJECT_TARGET_ARG"
else
    # Read from environment.json
    PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE")
fi

PROJECT_URL=$(jq -r ".devProjects.\"$PROJECT_TARGET\".url" "$ENV_FILE")
REFRESH_TOKEN=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.refresh_token" "$ENV_FILE")

if [ "$PROJECT_TARGET" = "null" ] || [ -z "$PROJECT_TARGET" ]; then
    error "projectTarget not defined in environment.json"
    exit 1
fi

if [ "$PROJECT_URL" = "null" ] || [ -z "$PROJECT_URL" ]; then
    error "Project URL not found in environment.json"
    exit 1
fi

if [ "$REFRESH_TOKEN" = "null" ] || [ -z "$REFRESH_TOKEN" ]; then
    error "refresh_token not found in environment.json"
    exit 1
fi

log "Target project: $PROJECT_TARGET"
log "Project URL: $PROJECT_URL"

# Refresh endpoint (normalized to avoid double slashes)
REFRESH_URL=$(normalize_url "$PROJECT_URL" "/_api/oauth/refresh")

log "Trying to renew tokens via: $REFRESH_URL"

# Make refresh request
RESPONSE=$(curl -s -X POST "$REFRESH_URL" \
    -H "Content-Type: application/json" \
    -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}" 2>/dev/null)

# Check if response is valid JSON
if ! echo "$RESPONSE" | jq . >/dev/null 2>&1; then
    error "Invalid API response (not JSON): $RESPONSE"
    exit 1
fi

# Extract response status
STATUS=$(echo "$RESPONSE" | jq -r '.status')

if [ "$STATUS" != "success" ]; then
    ERROR_MSG=$(echo "$RESPONSE" | jq -r '.message')
    error "Renewal failed: $ERROR_MSG"

    # WARNING: Do not clear tokens automatically to avoid losing valid tokens
    # in case of temporary network or API failures
    warning "Keeping existing tokens. Perform manual renewal if necessary."
    warning "Possible cause: $ERROR_MSG"

    exit 1
fi

# Extract new tokens
NEW_ACCESS_TOKEN=$(echo "$RESPONSE" | jq -r '.data.access_token')
NEW_REFRESH_TOKEN=$(echo "$RESPONSE" | jq -r '.data.refresh_token')

if [ "$NEW_ACCESS_TOKEN" = "null" ] || [ -z "$NEW_ACCESS_TOKEN" ]; then
    error "New access_token not received in response"
    exit 1
fi

if [ "$NEW_REFRESH_TOKEN" = "null" ] || [ -z "$NEW_REFRESH_TOKEN" ]; then
    warning "New refresh_token not received, keeping current"
    NEW_REFRESH_TOKEN=$REFRESH_TOKEN
fi

log "Tokens renewed successfully!"

# Update environment.json
jq --arg access "$NEW_ACCESS_TOKEN" --arg refresh "$NEW_REFRESH_TOKEN" --arg project "$PROJECT_TARGET" \
    '.devProjects[$project].api.access_token = $access | .devProjects[$project].api.refresh_token = $refresh' \
    "$ENV_FILE" > "${ENV_FILE}.tmp" && mv "${ENV_FILE}.tmp" "$ENV_FILE"

success "Tokens updated in environment.json"
success "Access token renewed successfully"

# Mark as success
RENEW_SUCCESS=0

# If in quiet mode, return the new access_token for integration with other scripts
if [ "$QUIET_MODE" = true ]; then
    if [ $RENEW_SUCCESS -eq 0 ]; then
        echo "$NEW_ACCESS_TOKEN"
    fi
    # In quiet mode, return nothing in case of failure (exit code already indicates failure)
else
    # Normal mode: always return the token if renewal was successful
    if [ $RENEW_SUCCESS -eq 0 ]; then
        echo "$NEW_ACCESS_TOKEN"
    fi
fi

exit 0
