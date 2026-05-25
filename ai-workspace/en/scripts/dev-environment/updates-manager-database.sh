#!/bin/bash
# Script to run database migrations/updates inside the Docker environment
# Reads the Docker path dynamically from environment.json
#
# Usage:
#   bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh
#   bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project <PROJECT_ID>

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1" >&2; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
ENV_JSON="$PROJECT_ROOT/dev-environment/data/environment.json"
LOCAL_DOCKER_ROOT="$PROJECT_ROOT/dev-environment/data/sites/"
DOCKER_ROOT="/var/www/sites/"

PROJECT_TARGET_OVERRIDE=""

usage() {
  echo "Usage: $0 [--project|-p PROJECT_ID]"
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

if [ ! -f "$ENV_JSON" ]; then
  log_error "environment.json not found at $ENV_JSON"
  exit 1
fi

resolve_project_test_path() {
  local project_id="$1"
  local target_path

  target_path=$(jq -r ".devProjects.\"$project_id\".target // empty" "$ENV_JSON" 2>/dev/null)
  if [ -z "$target_path" ] || [ "$target_path" = "null" ]; then
    target_path=$(jq -r ".devProjects.\"$project_id\".path_tests // empty" "$ENV_JSON" 2>/dev/null)
  fi

  echo "$target_path"
}

if [ -n "$PROJECT_TARGET_OVERRIDE" ]; then
  if ! command -v jq >/dev/null 2>&1; then
    log_error "jq is required when using --project to resolve project-specific dockerPath"
    exit 1
  fi

  PROJECT_TARGET="$PROJECT_TARGET_OVERRIDE"
  log "Project specified via argument: $PROJECT_TARGET"

  PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_JSON" 2>/dev/null || echo "0")
  if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
    log_error "Project '$PROJECT_TARGET' not found in environment.json (devProjects)."
    exit 1
  fi

  PATH_DOCKER=$(jq -r ".devProjects.\"$PROJECT_TARGET\".dockerPath // empty" "$ENV_JSON" 2>/dev/null)

  if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "null" ]; then
    TARGET_PATH=$(resolve_project_test_path "$PROJECT_TARGET")
    if [ -z "$TARGET_PATH" ] || [ "$TARGET_PATH" = "null" ]; then
      log_error "Could not determine project test path for '$PROJECT_TARGET' (devProjects.<id>.target or devProjects.<id>.path_tests)"
      exit 1
    fi

    TARGET_PATH="${TARGET_PATH%/}/"
    case "$TARGET_PATH" in
      "$LOCAL_DOCKER_ROOT"*)
        RELATIVE_DOCKER_PATH="${TARGET_PATH#"$LOCAL_DOCKER_ROOT"}"
        PATH_DOCKER="${DOCKER_ROOT}${RELATIVE_DOCKER_PATH}"
        ;;
      *)
        log_error "Could not derive dockerPath for '$PROJECT_TARGET' from test path: $TARGET_PATH"
        exit 1
        ;;
    esac

    log "dockerPath derived from project target/path_tests"
  else
    log "dockerPath read from project configuration"
  fi
else
  if command -v jq >/dev/null 2>&1; then
    PATH_DOCKER=$(jq -r '.devEnvironment.dockerPath // empty' "$ENV_JSON" 2>/dev/null)
  else
    PATH_DOCKER=$(grep '"dockerPath"' "$ENV_JSON" | sed -E 's/.*"dockerPath" *: *"([^"]*)".*/\1/' | head -n 1)
  fi
fi

PATH_DOCKER="${PATH_DOCKER%/}/"

if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "/" ] || [ "$PATH_DOCKER" = "null/" ]; then
  log_error "'dockerPath' not set in environment.json and could not be derived"
  exit 1
fi

PHP_SCRIPT="${PATH_DOCKER}controladores/atualizacoes/atualizacoes-banco-de-dados.php"

log "Docker Path: $PATH_DOCKER"
log "PHP Script: $PHP_SCRIPT"
log "Running database updates..."

if docker exec conn2flow-app bash -c "php ${PHP_SCRIPT} --debug --log-diff"; then
  log_success "Database updates completed successfully!"
else
  log_error "An error occurred during database updates."
  exit 1
fi
