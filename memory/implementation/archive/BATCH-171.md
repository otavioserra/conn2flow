# BATCH-171 — Transporte rsync/SSH compatível entre Cygwin e MSYS2 no Windows (req-166)

- **Status**: complete
- **Intake**: [req-166.md](../../human-requests/archive/req-166.md)
- **Data**: 2026-09-17
- **Classificação**: correção incremental do transporte SSH no Windows
- **Modo de autonomia**: supervisionado (sem commit ou push)

## Objetivo

Eliminar `dup() in/out/err failed` / rsync code 12 quando `c2f project:sync-core` ou
`project:update-all` parte de PHP `proc_open()` no Windows e atravessa Bash, rsync e SSH.

## Causa comprovada

O `rsync.exe` 3.4.1 resolvido no host vem do Chocolatey/cwRsync e usa runtime Cygwin. O `ssh.exe`
resolvido primeiro dentro do Git Bash vem do Git for Windows e usa runtime MSYS2. Ao cwRsync abrir
esse SSH incompatível como transporte filho, a duplicação dos pipes falha.

`--blocking-io`, `ssh -T` e `< /dev/null` foram avaliados. Sozinhos, não removeram o `dup()`; o
redirecionamento de stdin também não deve ser imposto. A correção determinante é parear o cwRsync
com o OpenSSH Cygwin distribuído no mesmo pacote.

## Implementação

1. `project-transport.sh` detecta Windows POSIX e o cwRsync/Chocolatey.
2. Quando o par é encontrado, o preflight e os comandos remotos usam o `ssh.exe` do pacote; o
   mesmo binário é montado em `-e`, com o `known_hosts` do perfil Windows. A ausência do cliente
   compatível falha cedo; runtimes não cwRsync continuam usando `ssh` do PATH.
3. `ssh -T` torna explícita a ausência de PTY no protocolo binário.
4. `project_transport_run_rsync()` preserva `MSYS_NO_PATHCONV=1` e converte somente argumentos que
   são caminhos locais existentes de `/c/...` para `/cygdrive/c/...`, namespace exigido pelo
   cwRsync. Destinos SSH e exclusões não são reescritos.
5. As quatro rotas já centralizadas no BATCH-169 permanecem protegidas: três rsyncs de
   `sync-core-to-project.sh` e o executor comum de `synchronize-project.sh`.
6. `ProjectSshDeployReq034Test` cobre seleção do SSH compatível, `known_hosts`, namespace Cygwin,
   preservação de stdin e os quatro pontos de uso.
7. `c2f-shell-and-windows-traps` foi corrigida nos cinco espelhos para documentar a causa e a
   solução comprovadas.

## Matriz de diagnóstico no Git Bash real

| Variante | Resultado |
| --- | --- |
| Git/MSYS2 SSH + `--blocking-io` + `-T` + `/dev/null` | `dup() in/out/err failed`, code 12 |
| OpenSSH nativo do Windows | `dup()` eliminado, mas stream fechou na ponte Cygwin/Win32 |
| SSH Cygwin do cwRsync com origem `/c/...` | transporte abriu; origem inexistente, code 23 |
| SSH Cygwin do cwRsync + origem `/cygdrive/c/...` | três transferências concluídas, exit 0 |

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `bash -n` na biblioteca e nos dois scripts | **3/3 sem erro** |
| `ProjectSshDeployReq034Test` | **24/24**, 97 asserções, exit 0 |
| `c2f project:sync-core snapphoton-local` pelo Git Bash | core + contrato runtime + CLI sincronizados; ownership restaurado; **exit 0** |
| PHPUnit completo, ordem padrão | **1.179/1.179**, 7.813 asserções, 4 skipped, exit 0 |
| Skills `.codex`, `.claude`, `.cursor`, `.gemini`, `.github` | MD5 `8E41773EF817D6F590E5061479314A5A` idêntico |

### Ambiente da suíte PHP

A primeira rodada reproduziu os 16 erros ambientais conhecidos: `pdo_sqlite`/`sqlite3` não
carregados e `OPENSSL_CONF` apontando para um XAMPP inexistente. A rodada válida usou somente
`PHP_INI_SCAN_DIR` temporário e o `openssl.cnf` da instalação WinGet; o diretório temporário foi
removido e nenhum `php.ini` ou `.env` persistente foi alterado.

## Resultado

O transporte SSH real para `snapphoton-local` executa com sucesso no Git Bash afetado. O lote está
pronto para revisão humana; nenhum commit ou push foi realizado.
