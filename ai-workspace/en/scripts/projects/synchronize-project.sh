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

# Transporte de deploy (req-034): destino local ou VM HestiaCP via SSH/rsync.
# shellcheck source=../lib/project-transport.sh
. "$SCRIPT_DIR/../lib/project-transport.sh"

# Defaults
MODE="default"
PROJECT_TARGET_OVERRIDE=""
CONTENTS_CHOICE="Sim"

should_exclude_contents() {
  case "$CONTENTS_CHOICE" in
    [Nn]*|false|FALSE|False|0)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

usage(){
  echo "Usage: $0 [default|checksum|force] --project <PROJECT_ID> [--contents Sim|Nao]"
  echo "  --project, -p    Project identifier (overrides devEnvironment.projectTarget)"
  echo "  --contents       Include contents/ folder in synchronization (default: Sim)"
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
    --contents)
      CONTENTS_CHOICE="${2:-}"; shift 2;;
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

# Resolve the deploy transport before the destination: with deploy_mode "ssh"
# there is no local target/path_tests to read, and demanding one aborted the
# pipeline for every project migrated to the HestiaCP VM (req-034).
project_transport_resolve "$ENV_FILE" "$PROJECT_TARGET" || exit 1

if project_transport_is_ssh; then
  project_transport_check || exit 1
  DESTINO="$PT_DEST"
else
  # Read destination (target) from environment.json for this project.
  # Fallback to path_tests when target is not defined or is empty.
  TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".target // empty" "$ENV_FILE" 2>/dev/null)
  if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
    TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path_tests // empty" "$ENV_FILE" 2>/dev/null)
  fi
  if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
    log_error "Target path for project '$PROJECT_TARGET' not defined in environment.json (devProjects.<id>.target, devProjects.<id>.path_tests or deploy_mode \"ssh\")"
    exit 1
  fi

  TARGET_PATH="${TARGET_PATH%/}"
  DESTINO="$TARGET_PATH"
fi

# Normalize source path (remove trailing slash to avoid double '//' in rsync)
ORIGEM="${ORIGEM%/}"

log "Source: $ORIGEM"
log "Destination: $DESTINO"
log "Mode: $MODE"
if should_exclude_contents; then
  log "contents/ folder: excluded from synchronization"
else
  log "contents/ folder: included in synchronization"
fi

# Check source exists
if [ ! -d "$ORIGEM" ]; then
  log_error "Source project directory does not exist: $ORIGEM"
  exit 1
fi

# Files-only synchronization still needs fresh deterministic cache tokens.
ASSET_SCRIPT="$PROJECT_ROOT/gestor/controladores/agents/arquitetura/atualizacao-versoes-assets.php"
php "$ASSET_SCRIPT" --root="$ORIGEM"

# Ensure destination exists
if project_transport_is_ssh; then
  project_transport_ensure_dest || exit 1
elif [ ! -d "$DESTINO" ]; then
  log_warning "Destination does not exist. Creating: $DESTINO"
  mkdir -p "$DESTINO"
fi

# Build rsync command (do NOT delete files by default)
RSYNC_EXCLUDES=(--exclude '.git/')
if should_exclude_contents; then
  RSYNC_EXCLUDES+=(--exclude 'contents/')
fi

# Em transporte local PT_RSYNC_OPTS está vazio e a linha é a de sempre.
run_project_rsync() {
  local source="$1" destination="$2"
  shift 2
  local -a excludes=("$@") command=()

  case "$MODE" in
    default|"")
      command=(rsync -avu "${PT_RSYNC_OPTS[@]}" "${excludes[@]}" "$source/" "$destination/")
      ;;
    checksum)
      command=(rsync -av --checksum "${PT_RSYNC_OPTS[@]}" "${excludes[@]}" "$source/" "$destination/")
      ;;
    force)
      command=(rsync -av --ignore-times "${PT_RSYNC_OPTS[@]}" "${excludes[@]}" "$source/" "$destination/")
      ;;
    *)
      log_error "Invalid mode: $MODE"; return 1;
      ;;
  esac

  log "Running: ${command[*]}"
  "${command[@]}"
}

run_project_rsync "$ORIGEM" "$DESTINO" "${RSYNC_EXCLUDES[@]}"

project_transport_finalize || exit 1

# O projeto privado pode declarar um segundo plano distribuído ao lado de
# `gestor/`. Ele precisa acompanhar o mesmo pipeline oficial: o Host Manager
# lê esse diretório irmão para aplicar o overlay em cada tenant.
DISTRIBUTED_OVERLAY_SOURCE="$(dirname "$ORIGEM")/gestor-distribuido"
if [ -d "$DISTRIBUTED_OVERLAY_SOURCE" ]; then
  if project_transport_is_ssh; then
    DISTRIBUTED_OVERLAY_REMOTE_PATH="$(dirname "$PT_REMOTE_PATH")/gestor-distribuido"
    DISTRIBUTED_OVERLAY_DEST="$PT_SSH_TARGET:$DISTRIBUTED_OVERLAY_REMOTE_PATH"
    project_transport_ensure_remote_path "$DISTRIBUTED_OVERLAY_REMOTE_PATH" || exit 1
  else
    DISTRIBUTED_OVERLAY_DEST="$(dirname "$DESTINO")/gestor-distribuido"
    if [ ! -d "$DISTRIBUTED_OVERLAY_DEST" ]; then
      log_warning "Distributed overlay destination does not exist. Creating: $DISTRIBUTED_OVERLAY_DEST"
      mkdir -p "$DISTRIBUTED_OVERLAY_DEST"
    fi
  fi

  log "Distributed overlay source: $DISTRIBUTED_OVERLAY_SOURCE"
  log "Distributed overlay destination: $DISTRIBUTED_OVERLAY_DEST"
  run_project_rsync "$DISTRIBUTED_OVERLAY_SOURCE" "$DISTRIBUTED_OVERLAY_DEST" --exclude '.git/'

  if project_transport_is_ssh; then
    project_transport_finalize_path "$DISTRIBUTED_OVERLAY_REMOTE_PATH" || exit 1
  fi
fi

log_success "Project synchronized to: $DESTINO"
log "Tip: run '🗃️ Projects - Synchronize => Resources - Local' task if you need to rebuild resources for the project."

exit 0
