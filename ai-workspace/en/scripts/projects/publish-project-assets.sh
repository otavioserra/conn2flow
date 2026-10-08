#!/usr/bin/env bash
set -euo pipefail
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$script_dir/../lib/project-transport.sh"
project_transport_resolve "$1" "$2"
project_transport_is_ssh || { pt_error 'SSH project required'; exit 1; }
public_path="$(_pt_read_key "$1" "$2" ssh_public_path)"
case "$public_path" in
  /|''|*[\'\"\;\$\`]*|*..*) pt_error 'Invalid public path'; exit 1 ;;
  /*) : ;;
  *) pt_error 'Absolute public path required'; exit 1 ;;
esac
project_transport_check
project_transport_ensure_remote_path "${public_path%/}/dist"
options=(-az)
if [ "${4:-}" = '--clean' ]; then options+=(--delete); fi
project_transport_run_rsync rsync "${options[@]}" "${PT_RSYNC_OPTS[@]}" "${3%/}/" "$PT_SSH_TARGET:${public_path%/}/dist/"
