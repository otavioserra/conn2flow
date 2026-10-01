#!/bin/bash
set -euo pipefail
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
fixture="$(mktemp -d -t c2f-core-owner-XXXXXX)"
trap 'rm -rf -- "$fixture"' EXIT
mkdir -p "$fixture/ai-workspace/en/scripts/dev-environment" "$fixture/ai-workspace/en/scripts/lib" "$fixture/dev-environment/data"
cat "$repo_root/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh" > "$fixture/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh"
printf '%s\n' '{"devProjects":{"demo":{"deploy_mode":"ssh"}}}' > "$fixture/dev-environment/data/environment.json"
cat > "$fixture/ai-workspace/en/scripts/lib/project-transport.sh" <<'STUB'
project_transport_resolve() { PT_SSH_TARGET=fixture; PT_REMOTE_PATH=/fixture; }
project_transport_is_ssh() { return 0; }
project_transport_check() { return 0; }
project_transport_remote_exec() { printf '%s\n' "$@" > "$C2F_OWNER_CAPTURE"; }
STUB
export C2F_OWNER_CAPTURE="$fixture/args"
script="$fixture/ai-workspace/en/scripts/dev-environment/updates-manager-database.sh"
bash "$script" --project demo --core-resources
if grep -q '^--project=' "$C2F_OWNER_CAPTURE"; then
  echo 'FAIL: core seeds were assigned to the project' >&2
  exit 1
fi
grep -q '^--debug$' "$C2F_OWNER_CAPTURE"
bash "$script" --project demo
grep -q '^--project=demo$' "$C2F_OWNER_CAPTURE"
echo 'PASS: core owner and project owner remain separate on the same target'
