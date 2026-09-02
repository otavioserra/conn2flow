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

# Transporte de deploy (req-034): destino local ou VM HestiaCP via SSH/rsync.
# shellcheck source=../lib/project-transport.sh
. "$SCRIPT_DIR/../lib/project-transport.sh"

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

# Resolve o transporte antes do destino: com deploy_mode "ssh" o Gestor do
# projeto vive na VM HestiaCP e não existe target/path_tests local (req-034).
project_transport_resolve "$ENV_FILE" "$PROJECT_TARGET" || exit 1

if project_transport_is_ssh; then
  project_transport_check || exit 1
  project_transport_ensure_dest || exit 1
  TARGET_PATH="$PT_DEST"
else
  TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".target // empty" "$ENV_FILE" 2>/dev/null)
  if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
    TARGET_PATH=$(jq -r ".devProjects.\"$PROJECT_TARGET\".path_tests // empty" "$ENV_FILE" 2>/dev/null)
  fi
  if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
    log_error "Test path for project '$PROJECT_TARGET' not defined in environment.json (devProjects.<id>.target, devProjects.<id>.path_tests or deploy_mode \"ssh\")"
    exit 1
  fi

  TARGET_PATH="${TARGET_PATH%/}"

  if [ ! -d "$TARGET_PATH" ]; then
    log_error "Project test directory does not exist: $TARGET_PATH"
    exit 1
  fi
fi

# Generate Core resources, incremental Tailwind and cache tokens before syncing.
TAILWIND_CLI=$(jq -r '.devEnvironment["tailwindcss/cli"] // empty' "$ENV_FILE" 2>/dev/null)
RESOURCE_SCRIPT="$PROJECT_ROOT/gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php"
if [ -n "$TAILWIND_CLI" ] && [ "$TAILWIND_CLI" != "null" ]; then
  TAILWINDCSS_COMMAND="$TAILWIND_CLI" php "$RESOURCE_SCRIPT"
else
  php "$RESOURCE_SCRIPT"
fi

CMD=(
  rsync
  -avu
  "${PT_RSYNC_OPTS[@]}"
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

# `rsync -u` preserva arquivos locais mais novos, o que é desejável para dados
# específicos da instalação, mas não para este contrato runtime: gestor.php e
# bibliotecas/gestor.php precisam ser sempre da mesma revisão. Um release antigo
# aplicado depois do sync pode deixar a biblioteca com timestamp novo e produzir
# fatal de função indefinida. Revalida o par por conteúdo, ignorando timestamps.
RUNTIME_CONTRACT_CMD=(
  rsync
  -avc
  --relative
  "${PT_RSYNC_OPTS[@]}"
  "$CORE_SOURCE/./gestor.php"
  "$CORE_SOURCE/./bibliotecas/gestor.php"
  "$TARGET_PATH/"
)

log "Synchronizing atomic runtime contract: gestor.php + bibliotecas/gestor.php"
"${RUNTIME_CONTRACT_CMD[@]}"

project_transport_finalize || exit 1

log_success "Conn2Flow core synchronized to project test folder: $TARGET_PATH"

exit 0
