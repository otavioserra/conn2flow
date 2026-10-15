#!/usr/bin/env bash
# req-277: "Liberar trava" da atualização pelo painel no Lab (conn2flow-site-local).
# O start só é chamado com trava presente (recusa), nunca dispara atualização de verdade.
# Uso (na raiz do core): bash memory/validation/req277-unlock-e2e.sh temp/agent-cookies.txt
set -u
export MSYS_NO_PATHCONV=1
B=https://conn2flow.local
L=/home/admin/web/conn2flow.local/conn2flow-gestor/temp/deploy.lock
D=$(cygpath -m "$(mktemp -d)" 2>/dev/null || mktemp -d)
cp "$1" "$D/a.txt"
falhas=0
ok(){ echo "OK   $1"; }
ko(){ echo "FALHA $1"; falhas=$((falhas+1)); }
trava(){ ssh lab "sudo bash -c 'printf %s \"{\\\"token\\\":\\\"t$RANDOM\\\",\\\"owner\\\":\\\"$1\\\",\\\"execution\\\":null,\\\"detail\\\":\\\"$2\\\",\\\"host\\\":\\\"\$(hostname)\\\",\\\"pid\\\":1,\\\"started_at\\\":\$(date +%s),\\\"expires_at\\\":\$((\$(date +%s)+7200))}\" > $L && chown admin:admin $L'"; }
existe(){ ssh lab "sudo test -f $L && echo sim || echo nao"; }
trap 'ssh lab "sudo rm -f $L"; rm -rf "$D"' EXIT

pagina=$(curl -sk -b "$D/a.txt" -c "$D/a.txt" "$B/admin-atualizacoes/")
CSRF=$(grep -oE 'name="csrf-token" content="[^"]+"' <<<"$pagina" | cut -d'"' -f4)
[ -n "$CSRF" ] && ok "tela aberta com token CSRF" || ko "sem token CSRF"
grep -q 'data-msg-unlock-confirm="' <<<"$pagina" && ok "mensagem de liberar trava na tela" || ko "sem data-msg-unlock-confirm"
X=(-H 'X-Requested-With: XMLHttpRequest' -H "X-CSRF-Token: $CSRF")
post(){ curl -sk -b "$D/a.txt" -c "$D/a.txt" "${X[@]}" --data "opcao=raiz&ajax=sim&ajaxOpcao=update&$1" "$B/admin-atualizacoes/"; }

# 1. Trava da atualização pelo painel (PID 1 vivo: não é assumida sozinha) -> recusa liberável.
trava update-system web
r=$(post 'params[acao]=start&params[modo]=full')
grep -q '"locked":true' <<<"$r" && grep -q '"unlockable":true' <<<"$r" && ok "start recusa e marca liberável" || ko "start: $(head -c 300 <<<"$r")"
r=$(post 'params[acao]=unlock')
grep -q '"unlocked":true' <<<"$r" && ok "unlock liberou" || ko "unlock: $(head -c 300 <<<"$r")"
[ "$(existe)" = nao ] && ok "arquivo da trava removido" || ko "trava continua"

# 2. Trava do pipeline -> não liberável e intocada.
trava pipeline conn2flow-site-local
r=$(post 'params[acao]=start&params[modo]=full')
grep -q '"unlockable":false' <<<"$r" && ok "trava do pipeline não é liberável" || ko "start pipeline: $(head -c 300 <<<"$r")"
r=$(post 'params[acao]=unlock')
grep -q 'outro processo' <<<"$r" && ok "unlock recusa trava do pipeline" || ko "unlock pipeline: $(head -c 300 <<<"$r")"
[ "$(existe)" = sim ] && ok "trava do pipeline continua" || ko "trava do pipeline sumiu"

echo "falhas: $falhas"
exit $falhas
