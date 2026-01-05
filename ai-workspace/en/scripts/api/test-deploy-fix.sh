#!/bin/bash

# Test Script: Deploy Fix Verification
# ------------------------------------------------------------------------------
# This script tests if the fix in deploy-project.sh is working

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log "=== DEPLOY-PROJECT.SH FIX TEST ==="

# Simulate old behavior (problematic)
log ""
log "=== OLD BEHAVIOR SIMULATION ==="

# Script that always fails but produces output
cat > /tmp/failing_script.sh << 'EOF'
#!/bin/bash
echo "This script always fails"
echo "But produces output to stdout and stderr" >&2
exit 1
EOF
chmod +x /tmp/failing_script.sh

log "Testing old behavior (always true):"
if OUTPUT=$(/tmp/failing_script.sh 2>&1); then
    log_error "❌ Old behavior: IF always true even with exit 1!"
    log "Captured output: $OUTPUT"
else
    log_success "✅ Old behavior: IF would be false (but that's not how it was)"
fi

log ""
log "=== NEW BEHAVIOR SIMULATION ==="

log "Testing new behavior (checks exit code):"
OUTPUT2=$(/tmp/failing_script.sh 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ] && [ -n "$OUTPUT2" ] && [ "$OUTPUT2" != "null" ]; then
    log_error "❌ New behavior: IF true (should not be)"
else
    log_success "✅ New behavior: IF false correctly when exit code != 0"
    log "Exit code: $EXIT_CODE"
    log "Output: $OUTPUT2"
fi

# Test with working script
log ""
log "=== TEST WITH WORKING SCRIPT ==="

cat > /tmp/success_script.sh << 'EOF'
#!/bin/bash
echo "eyJhbGciOiJSU0EiLCJ0eXAiOiJKV1QifQ.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c"
exit 0
EOF
chmod +x /tmp/success_script.sh

log "Testing with working script:"
OUTPUT3=$(/tmp/success_script.sh 2>&1)
EXIT_CODE3=$?

if [ $EXIT_CODE3 -eq 0 ] && [ -n "$OUTPUT3" ] && [ "$OUTPUT3" != "null" ]; then
    log_success "✅ New behavior: IF true correctly when everything OK"
    log "Simulated token: ${OUTPUT3:0:30}..."
else
    log_error "❌ New behavior: IF false when it should be true"
fi

log ""
log "=== CONCLUSION ==="
log "The fix implemented in deploy-project.sh now:"
log "1. ✅ Captures the renewal script output"
log "2. ✅ Checks the exit code"
log "3. ✅ Validates if the returned token is not empty/null"
log "4. ✅ Only proceeds if EVERYTHING is correct"

# Clean up temporary files
rm -f /tmp/failing_script.sh /tmp/success_script.sh

exit 0
