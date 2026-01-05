#!/bin/bash

# Test Script: OAuth Token Renewal
# ------------------------------------------------------------------------------
# This script specifically tests OAuth token renewal to verify
# if the return is working correctly in deploy-project.sh

set -e  # Stop script on error

# Colors for output
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
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
RENEW_SCRIPT="$PROJECT_ROOT/ai-workspace/scripts/api/renew-token.sh"

log "=== OAUTH TOKEN RENEWAL TEST ==="
log "Renewal script: $RENEW_SCRIPT"
log "Environment file: $ENV_FILE"

# Check if files exist
if [ ! -f "$ENV_FILE" ]; then
    log_error "environment.json file not found: $ENV_FILE"
    exit 1
fi

if [ ! -f "$RENEW_SCRIPT" ]; then
    log_error "Renewal script not found: $RENEW_SCRIPT"
    exit 1
fi

# Read current settings
PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)
ACCESS_TOKEN_OLD=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE" 2>/dev/null)
REFRESH_TOKEN_OLD=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.refresh_token" "$ENV_FILE" 2>/dev/null)

log "Target project: $PROJECT_TARGET"
log "Current access token (first 20 chars): ${ACCESS_TOKEN_OLD:0:20}..."
log "Current refresh token (first 20 chars): ${REFRESH_TOKEN_OLD:0:20}..."

# Test 1: Execute renewal and capture output
log ""
log "=== TEST 1: Capture renewal script output ==="

log "Executing: $RENEW_SCRIPT"
log "Capturing output with: NEW_TOKEN=\$(\"$RENEW_SCRIPT\" 2>&1)"

# Simulate exactly how deploy-project.sh does it
if NEW_TOKEN=$("$RENEW_SCRIPT" 2>&1); then
    log_success "Renewal executed successfully!"
    log "Return code: $?"

    # Check if NEW_TOKEN was captured
    if [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
        log_success "Token captured successfully!"
        log "New token (first 20 chars): ${NEW_TOKEN:0:20}..."
        log "Token length: ${#NEW_TOKEN} characters"

        # Check if it is a valid JWT (has 3 parts separated by .)
        if [[ "$NEW_TOKEN" == *.*.* ]]; then
            log_success "Token format seems valid (JWT)"
        else
            log_warning "Token format may not be valid"
        fi
    else
        log_error "Token was NOT captured!"
        log "NEW_TOKEN is empty or null"
        exit 1
    fi
else
    log_error "Token renewal failed"
    log "Return code: $?"
    log "Script output: $NEW_TOKEN"
    exit 1
fi

# Test 2: Verify if file was updated
log ""
log "=== TEST 2: Verify environment.json update ==="

ACCESS_TOKEN_NEW=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE" 2>/dev/null)
REFRESH_TOKEN_NEW=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.refresh_token" "$ENV_FILE" 2>/dev/null)

log "Access token after renewal (first 20 chars): ${ACCESS_TOKEN_NEW:0:20}..."
log "Refresh token after renewal (first 20 chars): ${REFRESH_TOKEN_NEW:0:20}..."

if [ "$ACCESS_TOKEN_OLD" != "$ACCESS_TOKEN_NEW" ]; then
    log_success "✅ Access token was updated in environment.json!"
else
    log_error "❌ Access token was NOT updated in environment.json!"
fi

if [ "$REFRESH_TOKEN_OLD" != "$REFRESH_TOKEN_NEW" ]; then
    log_success "✅ Refresh token was updated in environment.json!"
else
    log_warning "⚠️  Refresh token was not changed (may be normal)"
fi

    # Verify if returned token is equal to file token
    log ""
    log "=== TEST 3: Comparison between returned token and file ==="

    if [ "$NEW_TOKEN" = "$ACCESS_TOKEN_NEW" ]; then
        log_success "✅ Returned token is EQUAL to token in environment.json!"
    else
        log_error "❌ Returned token is DIFFERENT from token in environment.json!"
        log "Returned token: ${NEW_TOKEN:0:50}..."
        log "Token in file: ${ACCESS_TOKEN_NEW:0:50}..."
        
        # Print full token for debug
        log ""
        log "=== FULL RETURNED TOKEN ==="
        echo "$NEW_TOKEN"
        log "=== END TOKEN ==="
    fi

# Test 4: Simulation of deploy-project.sh
log ""
log "=== TEST 4: Simulation of deploy-project.sh flow ==="

log "Simulating deploy-project.sh code..."
log "if NEW_TOKEN=\$(\"$RENEW_SCRIPT\" 2>&1); then"

# Recapture token (as if it were a new execution)
if NEW_TOKEN_SIMULATED=$("$RENEW_SCRIPT" 2>&1); then
    log_success "Simulation: Renewal worked!"

    # Reload ACCESS_TOKEN from environment.json (as deploy does)
    ACCESS_TOKEN_RELOADED=$(jq -r ".devProjects.\"$PROJECT_TARGET\".api.access_token" "$ENV_FILE")

    if [ "$ACCESS_TOKEN_RELOADED" = "null" ] || [ -z "$ACCESS_TOKEN_RELOADED" ]; then
        log_error "Simulation: Failed to reload token from environment.json"
    else
        log_success "Simulation: Token reloaded successfully from environment.json"
        log "Reloaded token (first 20 chars): ${ACCESS_TOKEN_RELOADED:0:20}..."

        if [ "$NEW_TOKEN_SIMULATED" = "$ACCESS_TOKEN_RELOADED" ]; then
            log_success "✅ Simulation: Full flow would work correctly!"
        else
            log_error "❌ Simulation: There would be a problem in deploy-project.sh flow!"
        fi
    fi
else
    log_error "Simulation: Renewal failed"
fi

log ""
log "=== TEST SUMMARY ==="
log "If all tests passed, the problem may be elsewhere."
log "If any test failed, identify which one and report."

exit 0
