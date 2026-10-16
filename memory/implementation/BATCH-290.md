---
id: "BATCH-290"
title: "Deploy SSH com o dono do destino entrando como root (REQ-281)"
status: "implemented-pending-homologation"
date: "2026-10-16"
author: "executor"
target_repo: "conn2flow"
---

# req-281: rsync com o dono certo no deploy SSH

## Pedido do Humano (BL-033 do site)

Durante o deploy SSH do `temp.conn2flow.com` o site deu **500** (`require_once(gestor.php): Permission denied`) e o cron mandou "Could not open input file: cron.php". O Humano pediu que deploy e atualização sempre gravem os arquivos com o usuário do site, e não como root para corrigir depois.

## Causa

`project-transport.sh` já passava `--chown=<ssh_chown>` ao rsync (req-210), mas só com `ssh_sudo: true`. O projeto `conn2flow-site-temp` usa `ssh_user: root` sem sudo: o rsync gravava como root e ainda copiava o UID da máquina de origem (197609, Windows), e só o `chown -R` do fim da etapa devolvia a posse.

## Correção

`--chown` também quando `ssh_user` é `root` (o rsync remoto já tem privilégio para trocar o dono). Usuário comum sem sudo segue sem `--chown` (grava como ele mesmo). O `chown` final continua para o que é criado fora do rsync.

Observação: desde 15/10, produção não recebe deploy SSH (só `project:deploy` pela API e atualização pelo painel); a correção vale para os ambientes de teste por SSH e para quem ainda usar esse caminho.

## Aceite

- [x] `ProjectTransportChownReq281Test` (bash real): root sem sudo → `--chown`; usuário com sudo → `--chown` + `sudo rsync`; usuário comum sem sudo → sem `--chown`.
- [x] PHPUnit completo (1.860, 0 falhas).
- [ ] Release 3.0.8.

Branch `feat/req-281` (da `feat/req-280`), worktree `conn2flow-req277`.
