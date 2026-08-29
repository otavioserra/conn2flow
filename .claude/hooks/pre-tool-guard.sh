#!/usr/bin/env bash
set -euo pipefail

payload="$(cat || true)"

if [[ -z "$payload" ]]; then
    exit 0
fi

command=""
if command -v jq >/dev/null 2>&1; then
    command="$(echo "$payload" | jq -r '.tool_input.command // empty' 2>/dev/null || true)"
elif command -v python3 >/dev/null 2>&1; then
    command="$(echo "$payload" | python3 -c 'import sys, json; data=json.load(sys.stdin); print(data.get("tool_input", {}).get("command", ""))' 2>/dev/null || true)"
elif command -v php >/dev/null 2>&1; then
    command="$(echo "$payload" | php -r '$d=json_decode(file_get_contents("php://stdin"),true); echo $d["tool_input"]["command"]??"";' 2>/dev/null || true)"
fi

if [[ -z "$command" ]]; then
    exit 0
fi

# 1. Bloqueio de cópia manual para ambientes de teste/espelho
if echo "$command" | grep -Eq '\b(cp|copy|Copy-Item|xcopy)\b.*(dev-environment/data/sites|/sites/)'; then
    msg="Bloqueado por Governança: É proibido sincronizar por cópia manual. Utilize 'c2f manager:update-all' (sistema) ou 'c2f project:update-all <id>' (projeto)."
    echo "$msg" >&2
    echo "{\"permissionDecision\":\"deny\",\"reason\":\"$msg\"}"
    exit 1
fi

# 2. Bloqueio de git add indiscriminado (-A, ., -u, --all)
if echo "$command" | grep -Eq '\bgit\s+add(\s+-[a-zA-Z0-9_-]+)*\s+(\.|\-A|\-u|\-\-all)(\s|$|[;&|])'; then
    msg="Bloqueado por Governança: É proibido 'git add -A' ou 'git add .'. Utilize 'git add <caminhos-especificos>'."
    echo "$msg" >&2
    echo "{\"permissionDecision\":\"deny\",\"reason\":\"$msg\"}"
    exit 1
fi

exit 0
