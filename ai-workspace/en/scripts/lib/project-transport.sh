#!/bin/bash

# Library: Project deploy transport (req-034 / BATCH-155)
# -----------------------------------------------------------------------------
# Resolve ONDE um projeto do `environment.json` é publicado e COMO os comandos
# do pipeline alcançam esse destino.
#
# Até a req-152 existia um único transporte: um diretório do sistema de arquivos
# local (`devProjects.<id>.target` ou `.path_tests`), fosse ele um mirror em
# `dev-environment/data/sites/` (modo Docker) ou uma raiz de Gestor bare-metal
# (modo host). A migração do ambiente de desenvolvimento para a VM Ubuntu +
# HestiaCP removeu esse diretório: o Gestor de `conn2flow.local` passou a viver
# em `/home/admin/web/conn2flow.local/conn2flow-gestor/` DENTRO da VM, e nenhum
# caminho do Windows aponta para lá. O pipeline morria em "Target path for
# project '<id>' not defined" antes de tocar em qualquer arquivo.
#
# O contrato novo é declarativo, e não inferido: `deploy_mode: "ssh"` no projeto
# declara o transporte, e as chaves `ssh_*` declaram o endereço. Sem
# `deploy_mode`, ou com `deploy_mode: "local"`, nada muda — os projetos Docker e
# bare-metal existentes continuam resolvendo por `target`/`path_tests`.
#
# Uso:
#   source "<repo>/ai-workspace/en/scripts/lib/project-transport.sh"
#   project_transport_resolve "$ENV_FILE" "$PROJECT_ID" || exit 1
#   if project_transport_is_ssh; then ... fi
#
# Variáveis publicadas após `project_transport_resolve`:
#   PT_MODE              local | ssh
#   PT_SSH_USER          usuário SSH
#   PT_SSH_HOST          host ou IP
#   PT_SSH_PORT          porta (padrão 22)
#   PT_SSH_TARGET        "usuario@host"
#   PT_REMOTE_PATH       caminho absoluto do Gestor na VM, sem barra final
#   PT_DEST              destino pronto para rsync ("usuario@host:/caminho")
#   PT_SSH_RUN_AS        usuário para `sudo -u` na execução remota (opcional)
#   PT_SSH_CHOWN         "dono:grupo" aplicado após o rsync (opcional)
#   PT_SSH_OPTS[]        opções do binário ssh
#   PT_RSYNC_OPTS[]      opções do rsync (transporte + privilégio)

# Evita redefinição quando dois scripts do mesmo pipeline dão source na lib.
if [ -n "${PROJECT_TRANSPORT_LOADED:-}" ]; then
  return 0 2>/dev/null || true
fi
PROJECT_TRANSPORT_LOADED=1

PT_MODE="local"
PT_SSH_USER=""
PT_SSH_HOST=""
PT_SSH_PORT=""
PT_SSH_TARGET=""
PT_REMOTE_PATH=""
PT_DEST=""
PT_SSH_RUN_AS=""
PT_SSH_CHOWN=""
PT_SSH_SUDO="false"
PT_SSH_OPTS=()
PT_RSYNC_OPTS=()

pt_log() { echo -e "\033[0;34m[transport]\033[0m $1"; }
pt_error() { echo -e "\033[0;31m[ERROR]\033[0m $1" >&2; }

# Lê uma chave do projeto sem interpolar nada dentro da expressão jq.
_pt_read_key() {
  local env_file="$1" project_id="$2" key="$3" value
  value=$(jq -r --arg p "$project_id" --arg k "$key" '.devProjects[$p][$k] // empty' "$env_file" 2>/dev/null)
  [ "$value" = "null" ] && value=""
  printf '%s' "$value"
}

project_transport_is_ssh() {
  [ "$PT_MODE" = "ssh" ]
}

# Resolve o transporte do projeto. Retorna 1 (com mensagem) em configuração
# incompleta — deixar passar produziria um rsync para um destino parcial.
project_transport_resolve() {
  local env_file="$1" project_id="$2" deploy_mode

  PT_MODE="local"
  PT_SSH_USER=""
  PT_SSH_HOST=""
  PT_SSH_PORT=""
  PT_SSH_TARGET=""
  PT_REMOTE_PATH=""
  PT_DEST=""
  PT_SSH_RUN_AS=""
  PT_SSH_CHOWN=""
  PT_SSH_SUDO="false"
  PT_SSH_OPTS=()
  PT_RSYNC_OPTS=()

  if [ ! -f "$env_file" ]; then
    pt_error "environment.json not found at $env_file"
    return 1
  fi

  if ! command -v jq >/dev/null 2>&1; then
    pt_error "jq is required to resolve the project deploy transport"
    return 1
  fi

  deploy_mode=$(_pt_read_key "$env_file" "$project_id" "deploy_mode")

  if [ -z "$deploy_mode" ] || [ "$deploy_mode" = "local" ]; then
    PT_MODE="local"
    return 0
  fi

  if [ "$deploy_mode" != "ssh" ]; then
    pt_error "Unsupported deploy_mode '$deploy_mode' for project '$project_id' (use \"local\" or \"ssh\")"
    return 1
  fi

  PT_SSH_HOST=$(_pt_read_key "$env_file" "$project_id" "ssh_host")
  PT_SSH_USER=$(_pt_read_key "$env_file" "$project_id" "ssh_user")
  PT_REMOTE_PATH=$(_pt_read_key "$env_file" "$project_id" "ssh_target_path")
  PT_SSH_PORT=$(_pt_read_key "$env_file" "$project_id" "ssh_port")
  PT_SSH_RUN_AS=$(_pt_read_key "$env_file" "$project_id" "ssh_run_as")
  PT_SSH_CHOWN=$(_pt_read_key "$env_file" "$project_id" "ssh_chown")
  PT_SSH_SUDO=$(_pt_read_key "$env_file" "$project_id" "ssh_sudo")

  if [ -z "$PT_SSH_HOST" ] || [ -z "$PT_SSH_USER" ] || [ -z "$PT_REMOTE_PATH" ]; then
    pt_error "deploy_mode \"ssh\" requires ssh_host, ssh_user and ssh_target_path in devProjects.$project_id"
    return 1
  fi

  # O caminho remoto entra em `rsync` e em `mkdir -p`. Um valor relativo cairia
  # no home do usuário SSH e um "/" apagaria a raiz do convidado no primeiro
  # deploy com --delete. Ambos são recusados aqui, não no servidor.
  case "$PT_REMOTE_PATH" in
    /) pt_error "ssh_target_path cannot be the filesystem root"; return 1 ;;
    /*) : ;;
    *) pt_error "ssh_target_path must be an absolute path (got '$PT_REMOTE_PATH')"; return 1 ;;
  esac

  PT_REMOTE_PATH="${PT_REMOTE_PATH%/}"

  if [ -z "$PT_SSH_PORT" ]; then
    PT_SSH_PORT="22"
  fi
  if [[ ! "$PT_SSH_PORT" =~ ^[0-9]+$ ]]; then
    pt_error "ssh_port must be numeric (got '$PT_SSH_PORT')"
    return 1
  fi

  PT_MODE="ssh"
  PT_SSH_TARGET="${PT_SSH_USER}@${PT_SSH_HOST}"
  PT_DEST="${PT_SSH_TARGET}:${PT_REMOTE_PATH}"

  # BatchMode: um pipeline que para num prompt de senha fica pendurado até o
  # timeout do chamador sem dizer por quê. Falhar na hora é o comportamento útil.
  PT_SSH_OPTS=(-o BatchMode=yes -o ConnectTimeout=15 -p "$PT_SSH_PORT")

  local identity
  identity=$(_pt_read_key "$env_file" "$project_id" "ssh_identity")
  if [ -n "$identity" ]; then
    PT_SSH_OPTS+=(-i "$identity")
  fi

  PT_RSYNC_OPTS=(-e "ssh ${PT_SSH_OPTS[*]}")

  # O usuário SSH normalmente NÃO é o dono do docroot no HestiaCP (lê, não
  # escreve). `--rsync-path` eleva apenas o processo remoto do rsync.
  if _pt_truthy "$PT_SSH_SUDO"; then
    PT_RSYNC_OPTS+=(--rsync-path "sudo rsync")
  fi

  return 0
}

_pt_truthy() {
  case "$1" in
    true|TRUE|True|1|sim|Sim|SIM|yes|Yes|YES) return 0 ;;
    *) return 1 ;;
  esac
}

# Destino pronto para o rsync: caminho local ou "usuario@host:/caminho".
project_transport_dest() {
  if project_transport_is_ssh; then
    printf '%s' "$PT_DEST"
  else
    printf '%s' "${1:-}"
  fi
}

# Falha cedo e com diagnóstico: sem isto o erro aparece como um rsync 255 mudo.
project_transport_check() {
  project_transport_is_ssh || return 0

  if ! command -v ssh >/dev/null 2>&1; then
    pt_error "ssh client not found in PATH"
    return 1
  fi
  if ! command -v rsync >/dev/null 2>&1; then
    pt_error "rsync not found in PATH"
    return 1
  fi

  pt_log "Checking SSH transport to $PT_SSH_TARGET:$PT_SSH_PORT"
  if ! ssh "${PT_SSH_OPTS[@]}" "$PT_SSH_TARGET" true >/dev/null 2>&1; then
    pt_error "SSH connection to $PT_SSH_TARGET failed (BatchMode). Publish your key with ssh-copy-id."
    return 1
  fi

  if ! ssh "${PT_SSH_OPTS[@]}" "$PT_SSH_TARGET" "command -v rsync >/dev/null 2>&1"; then
    pt_error "rsync not available on the remote host $PT_SSH_HOST"
    return 1
  fi

  return 0
}

# Garante o diretório de destino remoto antes do primeiro rsync.
project_transport_ensure_dest() {
  project_transport_is_ssh || return 0

  local mkdir_cmd
  mkdir_cmd="mkdir -p $(printf '%q' "$PT_REMOTE_PATH")"
  if _pt_truthy "$PT_SSH_SUDO"; then
    mkdir_cmd="sudo $mkdir_cmd"
  fi

  ssh "${PT_SSH_OPTS[@]}" "$PT_SSH_TARGET" "$mkdir_cmd"
}

# Devolve a posse ao dono do docroot. Sem isto o rsync com sudo deixa arquivos
# novos como root:root e o pool PHP-FPM do tenant perde a leitura.
project_transport_finalize() {
  project_transport_is_ssh || return 0
  [ -n "$PT_SSH_CHOWN" ] || return 0

  if [[ ! "$PT_SSH_CHOWN" =~ ^[a-zA-Z0-9._-]+(:[a-zA-Z0-9._-]+)?$ ]]; then
    pt_error "ssh_chown must be \"user\" or \"user:group\" (got '$PT_SSH_CHOWN')"
    return 1
  fi

  pt_log "Restoring ownership to $PT_SSH_CHOWN on $PT_REMOTE_PATH"
  ssh "${PT_SSH_OPTS[@]}" "$PT_SSH_TARGET" \
    "sudo chown -R $(printf '%q' "$PT_SSH_CHOWN") $(printf '%q' "$PT_REMOTE_PATH")"
}

# Executa um comando na raiz do Gestor remoto, opcionalmente sob outro usuário.
# Recebe o comando como argumentos separados e os cita um a um: interpolar a
# linha inteira deixaria qualquer valor do environment.json virar shell remoto.
project_transport_remote_exec() {
  project_transport_is_ssh || return 1

  local remote_cmd
  remote_cmd=$(printf '%q ' "$@")

  if [ -n "$PT_SSH_RUN_AS" ]; then
    if [[ ! "$PT_SSH_RUN_AS" =~ ^[a-zA-Z0-9._-]+$ ]]; then
      pt_error "ssh_run_as must be a plain user name (got '$PT_SSH_RUN_AS')"
      return 1
    fi
    remote_cmd="sudo -u $(printf '%q' "$PT_SSH_RUN_AS") $remote_cmd"
  fi

  ssh "${PT_SSH_OPTS[@]}" "$PT_SSH_TARGET" \
    "cd $(printf '%q' "$PT_REMOTE_PATH") && $remote_cmd"
}
