# BATCH-172 — Elevação do diretório de trabalho no transporte SSH (req-167)

- **Status**: complete
- **Intake**: [req-167.md](../human-requests/req-167.md)
- **Data**: 2026-09-17
- **Classificação**: correção incremental do executor remoto de projetos
- **Modo de autonomia**: supervisionado (sem commit ou push)

## Objetivo

Permitir que comandos remotos de projeto entrem no docroot restrito de um tenant HestiaCP quando
o usuário SSH autenticado só possui acesso ao diretório por meio de `sudo -u ssh_run_as`.

## Causa comprovada

`project_transport_remote_exec()` entregava ao SSH uma linha equivalente a:

```bash
cd /home/tenant/.../conn2flow-gestor && sudo -u tenant php ...
```

O shell de `otavio` executava o `cd` antes do `sudo`. Como `/home/snapphoton` é restrito ao tenant,
a linha terminava em `cd: Permission denied` sem chegar ao atualizador PHP.

## Implementação

1. O helper continua citando cada argumento remoto com `printf '%q'`.
2. A troca de diretório e o comando são reunidos em `command_in_workdir`.
3. Quando `PT_SSH_RUN_AS` está definido, a linha inteira é passada como um único argumento de
   `sudo -u <tenant> sh -c`, de modo que tanto o `cd` quanto o PHP executem como o tenant.
4. Sem `PT_SSH_RUN_AS`, `command_in_workdir` é entregue diretamente ao shell SSH, preservando o
   comportamento anterior.
5. `ProjectSshDeployReq034Test` ganhou guarda para o encapsulamento e para a remoção da forma
   vulnerável que elevava somente o executável final.
6. A skill `c2f-shell-and-windows-traps` documenta a armadilha nos cinco espelhos.

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `bash -n ai-workspace/en/scripts/lib/project-transport.sh` | sem erro, exit 0 |
| `ProjectSshDeployReq034Test` | **25/25**, 103 asserções, exit 0 |
| `c2f project:sync-db snapphoton-local` pelo Git Bash | migrações e sincronização remotas concluídas; `MANAGER_UPDATES_REGISTRADO id=44`; **exit 0**, sem `Permission denied` |
| PHPUnit completo em ordem determinística | **1.180/1.180**, 7.819 asserções, 4 skipped, exit 0 |
| Skills `.codex`, `.claude`, `.cursor`, `.gemini`, `.github` | MD5 `DFED2102AC6A192B591FB7748E5B37CA` idêntico |

### Ambiente da suíte PHP

A primeira rodada reproduziu os erros ambientais já conhecidos: OpenSSL apontando para o XAMPP
ausente e extensões SQLite não carregadas. A rodada válida usou `OPENSSL_CONF` da instalação PHP
WinGet e um `PHP_INI_SCAN_DIR` temporário para que os subprocessos também carregassem
`pdo_sqlite`/`sqlite3`. A ordem `defects` havia sido contaminada pelas rodadas ambientais e expôs o
acoplamento já registrado entre `ForcarAtualizacaoTest` e o cache estático de `schemaMetadata()`;
a validação final usou `--order-by=default --do-not-cache-result`. O INI temporário foi removido e
nenhum `php.ini` ou `.env` persistente foi alterado.

## Resultado

A etapa remota de banco do `snapphoton-local` atravessa o diretório restrito como `snapphoton` e
conclui com sucesso. O lote está pronto para revisão humana; nenhum commit ou push foi realizado.
