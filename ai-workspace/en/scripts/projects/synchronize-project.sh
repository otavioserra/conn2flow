#!/bin/bash

# Script: Synchronize Project Folder (developer -> dev-environment/data/projects)
# -----------------------------------------------------------------------------
# Usage:
#   ./synchronize-project.sh [default|checksum|force] --project <PROJECT_ID>
#   ./synchronize-project.sh --project <PROJECT_ID> --mode checksum
#
# Behavior:
# - Reads project path from dev-environment/data/environment.json (devProjects.<id>.path)
# - Copies files from project source to dev-environment/data/projects/<project-id>
# - By default copies only new/modified files (does NOT delete files in the destination)
# - Modes: default | checksum | force

set -e

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1" >&2; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }

# Paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/dev-environment/data/environment.json"

# Defaults
MODE="default"
PROJECT_TARGET_OVERRIDE=""

usage(){
  echo "Usage: $0 [default|checksum|force] --project <PROJECT_ID>"
  echo "  --project, -p    Project identifier (overrides devEnvironment.projectTarget)"
  echo "  default          Use date/time to decide (non-destructive)"
  echo "  checksum         Compare file contents by checksum"
  echo "  force            Overwrite files regardless of mtime"
  echo "  --help, -h       Show this help"
}

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --project|-p)
      PROJECT_TARGET_OVERRIDE="$2"; shift 2;;
    default|checksum|force)
      MODE="$1"; shift;;
    --mode)
      MODE="$2"; shift 2;;
    --help|-h)
      usage; exit 0;;
    *)
      log_error "Unknown option: $1"; usage; exit 1;;
  esac
done

# Validate environment.json
if [ ! -f "$ENV_FILE" ]; then
  log_error "environment.json not found: $ENV_FILE"
  exit 1
fi

# Determine project target
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

# Verify project exists in environment.json
PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_FILE" 2>/dev/null || echo "0")
if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
  log_error "Project '$PROJECT_TARGET' not found in environment.json (devProjects)."
  exit 1
fi

# Read source path from environment.json
ORIGEM=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path" "$ENV_FILE" 2>/dev/null)
if [ -z "$ORIGEM" ] || [ "$ORIGEM" = "null" ]; then
  log_error "Path for project '$PROJECT_TARGET' not defined in environment.json"
  exit 1
fi

# Read destination (target) from environment.json for this project — REQUIRED
TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".target" "$ENV_FILE" 2>/dev/null)
if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
  log_error "Target path for project '$PROJECT_TARGET' not defined in environment.json (devProjects.<id>.target)"
  exit 1
fi

# Normalize paths (remove trailing slashes to avoid double '//' in rsync)
ORIGEM="${ORIGEM%/}"
TARGET_PATH="${TARGET_PATH%/}"

DESTINO="$TARGET_PATH"

log "Source: $ORIGEM"
log "Destination: $DESTINO"
log "Mode: $MODE"

# Check source exists
if [ ! -d "$ORIGEM" ]; then
  log_error "Source project directory does not exist: $ORIGEM"
  exit 1
fi

# Ensure destination exists
if [ ! -d "$DESTINO" ]; then
  log_warning "Destination does not exist. Creating: $DESTINO"
  mkdir -p "$DESTINO"
fi

# Build rsync command (do NOT delete files by default)
case "$MODE" in
  default|"")
    CMD=(rsync -avu --exclude '.git/' "$ORIGEM/" "$DESTINO/")
    ;;
  checksum)
    CMD=(rsync -av --checksum --exclude '.git/' "$ORIGEM/" "$DESTINO/")
    ;;
  force)
    CMD=(rsync -av --ignore-times --exclude '.git/' "$ORIGEM/" "$DESTINO/")
    ;;
  *)
    log_error "Invalid mode: $MODE"; exit 1;
    ;;
esac

log "Running: ${CMD[*]}"
"${CMD[@]}"
RSYNC_EXIT=$?

if [ $RSYNC_EXIT -ne 0 ]; then
  log_error "rsync finished with exit code $RSYNC_EXIT"
  exit $RSYNC_EXIT
fi

log_success "Project synchronized to: $DESTINO"
log "Tip: run '🗃️ Projects - Synchronize => Resources - Local' task if you need to rebuild resources for the project."

exit 0
