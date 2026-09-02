#!/bin/bash
# Script to run database migrations/updates in Docker or directly on the host
# Resolves the execution mode dynamically from environment.json
#
# Usage:
#   bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh
#   bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project <PROJECT_ID>
#   bash ./ai-workspace/en/scripts/dev-environment/updates-manager-database.sh --project <PROJECT_ID> --force-all

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

# Transporte de deploy (req-034): destino local ou VM HestiaCP via SSH.
# shellcheck source=../lib/project-transport.sh
. "$SCRIPT_DIR/../lib/project-transport.sh"

PROJECT_TARGET_OVERRIDE=""
PROJECT_TARGET=""
FORCE_ALL=false
TABLES=""
EXECUTION_MODE="docker"
PATH_DOCKER=""
PATH_HOST=""

usage() {
  echo "Usage: $0 [--project|-p PROJECT_ID] [--tables TABLE_A,TABLE_B] [--force-all]"
  echo "  --project, -p    Project identifier"
  echo "  --tables         Restrict synchronization to a comma-separated table list"
  echo "  --force-all      Force all data tables even when manager_updates checksums match"
  echo "  --help, -h       Show this help"
}

while [[ $# -gt 0 ]]; do
  case $1 in
    --project|-p)
      PROJECT_TARGET_OVERRIDE="$2"
      shift 2
      ;;
    --force-all)
      FORCE_ALL=true
      shift
      ;;
    --tables)
      TABLES="$2"
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

  # The identifier is interpolated into jq expressions and later forwarded to PHP.
  if [[ ! "$PROJECT_TARGET" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    log_error "Invalid project identifier. Use only letters, digits, hyphen or underscore."
    exit 1
  fi

  PROJECT_EXISTS=$(jq -r ".devProjects.\"$PROJECT_TARGET\" | length" "$ENV_JSON" 2>/dev/null || echo "0")
  if [ "$PROJECT_EXISTS" = "0" ] || [ -z "$PROJECT_EXISTS" ]; then
    log_error "Project '$PROJECT_TARGET' not found in environment.json (devProjects)."
    exit 1
  fi

  # `deploy_mode: "ssh"` tem precedência sobre qualquer inferência de caminho: o
  # Gestor do projeto vive na VM HestiaCP e o atualizador precisa rodar LÁ, onde
  # estão o `.env`, o MariaDB e as migrações (req-034).
  project_transport_resolve "$ENV_JSON" "$PROJECT_TARGET" || exit 1

  if project_transport_is_ssh; then
    project_transport_check || exit 1
    EXECUTION_MODE="ssh"
    log "SSH execution selected from deploy_mode (target: $PT_SSH_TARGET)"
  fi

  PATH_DOCKER=$(jq -r ".devProjects.\"$PROJECT_TARGET\".dockerPath // empty" "$ENV_JSON" 2>/dev/null)

  if [ "$EXECUTION_MODE" = "ssh" ]; then
    :
  elif [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "null" ]; then
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
        EXECUTION_MODE="docker"
        ;;
      *)
        HOST_PHP_SCRIPT="${TARGET_PATH}controladores/atualizacoes/atualizacoes-banco-de-dados.php"
        if [ -f "$HOST_PHP_SCRIPT" ]; then
          PATH_HOST="$TARGET_PATH"
          EXECUTION_MODE="host"
        else
          log_error "Project '$PROJECT_TARGET' has neither dockerPath nor an executable host target: $TARGET_PATH"
          exit 1
        fi
        ;;
    esac

    if [ "$EXECUTION_MODE" = "docker" ]; then
      log "dockerPath derived from project target/path_tests"
    else
      log "Host execution selected from project target/path_tests"
    fi
  else
    EXECUTION_MODE="docker"
    log "dockerPath read from project configuration"
  fi
else
  if command -v jq >/dev/null 2>&1; then
    PATH_DOCKER=$(jq -r '.devEnvironment.dockerPath // empty' "$ENV_JSON" 2>/dev/null)
  else
    PATH_DOCKER=$(grep '"dockerPath"' "$ENV_JSON" | sed -E 's/.*"dockerPath" *: *"([^"]*)".*/\1/' | head -n 1)
  fi
fi

if [ "$EXECUTION_MODE" = "ssh" ]; then
  # Caminho RELATIVO à raiz do Gestor remoto: `project_transport_remote_exec`
  # entra nela com `cd` antes de chamar o PHP, e o bootstrap do config.php exige
  # esse diretório de trabalho (req-152).
  PHP_SCRIPT="controladores/atualizacoes/atualizacoes-banco-de-dados.php"
  log "Remote Path: $PT_SSH_TARGET:$PT_REMOTE_PATH"
elif [ "$EXECUTION_MODE" = "host" ]; then
  PATH_HOST="${PATH_HOST%/}/"
  PHP_SCRIPT="${PATH_HOST}controladores/atualizacoes/atualizacoes-banco-de-dados.php"
  log "Host Path: $PATH_HOST"
else
  PATH_DOCKER="${PATH_DOCKER%/}/"

  if [ -z "$PATH_DOCKER" ] || [ "$PATH_DOCKER" = "/" ] || [ "$PATH_DOCKER" = "null/" ]; then
    log_error "'dockerPath' not set in environment.json and could not be derived"
    exit 1
  fi

  PHP_SCRIPT="${PATH_DOCKER}controladores/atualizacoes/atualizacoes-banco-de-dados.php"
  log "Docker Path: $PATH_DOCKER"
fi

log "Execution Mode: $EXECUTION_MODE"
log "PHP Script: $PHP_SCRIPT"
log "Running database updates..."

PHP_ARGS=(--debug --log-diff)
if [ -n "$TABLES" ]; then
  if [[ ! "$TABLES" =~ ^[a-zA-Z0-9_,]+$ ]]; then
    log_error "Invalid --tables value. Use only table names separated by commas."
    exit 1
  fi
  PHP_ARGS+=("--tables=$TABLES")
  log "Tables filter: $TABLES"
fi
if [ "$FORCE_ALL" = true ]; then
  PHP_ARGS+=(--force-all)
  log "Force all tables: enabled"
fi

# req-131 (BATCH-133): repassar a identidade do projeto ao atualizador.
#
# `sincronizarTabela()` lê `CLI_OPTS['project']` e é ele que separa os dois fluxos documentados em
# CONN2FLOW-PROJECT-DATABASE-PROTECTION.md:
#
#   - deploy DE projeto  -> sobrescreve o recurso e o marca com o id do projeto;
#   - atualização normal -> respeita a marcação e não toca em recurso de projeto.
#
# Este script recebia `--project` (usa-o para resolver o dockerPath) e NÃO o repassava ao PHP. O
# deploy local de um projeto era então tratado como atualização normal e ficava bloqueado pela
# marcação que ele mesmo havia gravado: a alteração parava no `Data.json`, o relatório contava a
# linha como "sem alteração" e a rotina terminava com sucesso. Nenhum aviso em lugar nenhum.
#
# O caminho remoto nunca teve o defeito — `api.php` monta `CLI_OPTS['project']` a partir do
# cabeçalho `X-Project-ID`. Era uma assimetria entre os dois deploys do MESMO projeto.
if [ -n "$PROJECT_TARGET" ]; then
  # O identificador já foi validado antes de ser interpolado em jq ou repassado ao PHP.
  PHP_ARGS+=("--project=$PROJECT_TARGET")
  log "Project deploy: resources will be overwritten and marked with '$PROJECT_TARGET'"
fi

if [ "$EXECUTION_MODE" = "host" ] && ! command -v php >/dev/null 2>&1; then
  log_error "PHP CLI is required for host execution."
  exit 1
fi

run_database_update() {
  if [ "$EXECUTION_MODE" = "ssh" ]; then
    project_transport_remote_exec php "$PHP_SCRIPT" "${PHP_ARGS[@]}"
  elif [ "$EXECUTION_MODE" = "host" ]; then
    (cd "$PATH_HOST" && php "$PHP_SCRIPT" "${PHP_ARGS[@]}")
  else
    docker exec conn2flow-app php "$PHP_SCRIPT" "${PHP_ARGS[@]}"
  fi
}

if run_database_update; then
  log_success "Database updates completed successfully!"
else
  log_error "An error occurred during database updates."
  exit 1
fi
