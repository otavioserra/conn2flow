#!/bin/bash

# Script: Synchronize Conn2Flow core gestor to a project test folder
# -----------------------------------------------------------------
# Usage:
#   ./sync-core-to-project.sh --project <PROJECT_ID>
#   ./sync-core-to-project.sh -p <PROJECT_ID>

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1" >&2; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"
CORE_SOURCE="$PROJECT_ROOT/gestor"

PROJECT_TARGET_OVERRIDE=""

usage() {
  echo "Usage: $0 --project <PROJECT_ID>"
  echo "  --project, -p    Project identifier"
  echo "  --help, -h       Show this help"
}

while [[ $# -gt 0 ]]; do
  case $1 in
    --project|-p)
      PROJECT_TARGET_OVERRIDE="$2"
      shift 2
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      log_error "Unknown option: $1"
      usage
      exit 1
      ;;
  esac
done

if [ ! -f "$ENV_FILE" ]; then
  log_error "environment.json not found: $ENV_FILE"
  exit 1
fi

if [ ! -d "$CORE_SOURCE" ]; then
  log_error "Core gestor directory not found: $CORE_SOURCE"
  exit 1
fi

if [ -n "$PROJECT_TARGET_OVERRIDE" ]; then
  PROJECT_TARGET="$PROJECT_TARGET_OVERRIDE"
  log "Project specified via argument: $PROJECT_TARGET"
else
  PROJECT_TARGET=$(jq -r '.devEnvironment.projectTarget' "$ENV_FILE" 2>/dev/null)
  if [ -z "$PROJECT_TARGET" ] || [ "$PROJECT_TARGET" = "null" ]; then
    log_error "Could not determine project target from environment.json (devEnvironment.projectTarget). Use --project to specify."
    exit 1
  fi
  log "Project determined from environment.json: $PROJECT_TARGET"
fi

PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_FILE" 2>/dev/null || echo "0")
if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
  log_error "Project '$PROJECT_TARGET' not found in environment.json (devProjects)."
  exit 1
fi

TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".target // empty" "$ENV_FILE" 2>/dev/null)
if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
  TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path_tests // empty" "$ENV_FILE" 2>/dev/null)
fi
if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
  log_error "Test path for project '$PROJECT_TARGET' not defined in environment.json (devProjects.<id>.target or devProjects.<id>.path_tests)"
  exit 1
fi

TARGET_PATH="${TARGET_PATH%/}"

if [ ! -d "$TARGET_PATH" ]; then
  log_error "Project test directory does not exist: $TARGET_PATH"
  exit 1
fi

CMD=(
  rsync
  -avu
  --exclude ".git/"
  --exclude "/logs/"
  --exclude "/temp/"
  --exclude "resources.map.php"
  "$CORE_SOURCE/"
  "$TARGET_PATH/"
)

log "Core source: $CORE_SOURCE"
log "Project test destination: $TARGET_PATH"
log "Running: ${CMD[*]}"

"${CMD[@]}"

log_success "Conn2Flow core synchronized to project test folder: $TARGET_PATH"

exit 0