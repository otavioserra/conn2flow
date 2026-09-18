# BATCH-173 — Encapsulamento de `cd` sob `sudo -u` no CLI SSH (req-168)

- **Status**: complete
- **Intake**: [req-168.md](../human-requests/req-168.md)
- **Data**: 2026-09-17
- **Classificação**: correção incremental dos executores SSH em PHP
- **Modo de autonomia**: supervisionado (sem commit ou push)

## Objetivo

Aplicar ao transporte SSH em PHP a mesma elevação do diretório de trabalho já validada no helper
Bash, permitindo que o usuário SSH de infraestrutura execute comandos dentro do docroot restrito
de um tenant HestiaCP por meio de `sudo -u ssh_run_as`.

## Causa comprovada

`SshRemoteTransport::buildRemoteCommand()` e `AuthCookieCommand::generateOverSsh()` montavam linhas
equivalentes a:

```bash
cd /home/tenant/.../conn2flow-gestor && sudo -u tenant php ...
```

O shell do usuário SSH executava o `cd` antes da elevação. Como `otavio` não atravessa diretamente
`/home/snapphoton`, a etapa 6/8 do `project:update-all` terminava em `Permission denied`.

## Implementação

1. `SshRemoteTransport::buildRemoteCommand()` reúne o `cd` citado e todos os argumentos remotos
   citados antes de aplicar `sudo -u <tenant> sh -c <linha>`.
2. Sem `runAs`, o transporte preserva a forma direta `cd <diretório> && <comando>`.
3. `AuthCookieCommand::generateOverSsh()` aplica o mesmo encapsulamento ao gerador PHP; o `chmod`
   posterior continua fora da shell do tenant e sob o privilégio já usado pelo fluxo.
4. `ProjectSshPublicPathReq050Test` protege a montagem exata do comando e rejeita a forma antiga
   `cd ... && sudo -u`.
5. `CliProjectEnvironmentTest` exercita o fluxo SSH de `auth:cookie` com runner isolado e confirma a
   ordem `sudo -u` → `sh -c` → `cd` → `php`.

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `php -l` nos 2 arquivos de código e 2 testes alterados | sem erro, exit 0 |
| Testes focados (`ProjectSshPublicPathReq050Test` + `CliProjectEnvironmentTest`) | **23/23**, 77 asserções, exit 0 |
| PHPUnit completo em ordem determinística | **1.181/1.181**, 7.830 asserções, 4 skipped, exit 0 |
| `project:update-all snapphoton-local --confirmar-remoto` pelo Git Bash | etapas **1/8–8/8** concluídas, exit 0 |
| Etapa 6/8 — CSS derivado | comando sob `sudo -u 'snapphoton' sh -c`; 2 analisados, 2 regenerados, 0 erros e nenhum `Permission denied` |

### Ambiente da suíte PHP

A primeira rodada reproduziu os 16 erros ambientais conhecidos: extensões `pdo_sqlite`/`sqlite3`
não carregadas e OpenSSL apontando para configuração ausente. A rodada válida usou
`PHP_INI_SCAN_DIR` temporário, `OPENSSL_CONF` da instalação PHP WinGet,
`--order-by=default --do-not-cache-result`. O INI temporário foi removido; nenhum `php.ini` ou
`.env` persistente foi alterado.

### Entry point do pipeline no Windows

A primeira tentativa chamou o alias `bash` do PowerShell, resolvido para WSL, cujo `/usr/bin/ssh`
não possui a chave do Lab; ela falhou no preflight da etapa 1/8 sem sincronizar arquivos. A rodada
válida usou `C:\Program Files\Git\bin\bash.exe`, selecionou o SSH compatível do cwRsync e concluiu
integralmente. Esta distinção não altera o código do lote, mas evita interpretar uma falha de
ambiente como regressão do transporte.

## Resultado

O pipeline completo atravessa o diretório restrito como `snapphoton`, a etapa 6/8 regenera o CSS
sem avisos de permissão e a suíte permanece verde. O lote está pronto para revisão humana; nenhum
commit ou push foi realizado.
