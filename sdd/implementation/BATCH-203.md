# BATCH-203: Atualização segura, fase 2b — snapshot seletivo, verificação e rollback (req-198)

Execução da [req-198](../human-requests/req-198.md), item D do [BL-028](../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md). Continua o [BATCH-202](BATCH-202.md), na mesma worktree e no mesmo tenant isolado.

**Status**: `complete` (implementado e validado no tenant isolado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`.

## Desenho

| Peça | O quê |
|---|---|
| Snapshot seletivo | `instalacao_snapshot_criar()` guarda em `backups/atualizacoes/snapshots/exec-<id>/` só o que o plano vai sobrescrever ou remover, a lista dos novos e os manifestos; ficam os 5 mais recentes |
| Dump do banco | `backupBancoSnapshot()`: `mysqldump --single-transaction` para `banco.sql.gz`, antes da etapa de banco, com a senha em `MYSQL_PWD`. Roda pelo bash com `pipefail` quando existe (`atualizacoes_shell_pipefail`) |
| Verificação | `saudeVerificar()`: erro fatal novo no `logs/php-error.log` e HTTP da raiz abaixo de 500. DNS normal, depois `127.0.0.1`; `--health-url` / `--health-ip` (ou `.env`); sem conexão é aviso, não falha; espera 3 s pelo OPcache |
| Volta automática | verificação falhou: os arquivos voltam do snapshot, nova verificação vai para o log, código de saída 6 (`EXIT_ROLLBACK`). O banco não volta sozinho |
| Rollback manual | `--rollback=exec-<id>` volta os arquivos; com `--com-banco` (exige `--domain`), restaura o dump e marca a execução como `rolled-back` |
| Flags | `--no-health`, `--no-rollback`, `--health-url`, `--health-ip` |

## Validação

### Automatizada
- `AtualizacoesManifestoIntegracaoTest`: saúde com fatal novo e antigo; tentativas HTTP (DNS, local, inconclusivo, `--health-url`/`--health-ip`); rollback manual; gravação dos pendentes. `InstalacaoManifestoTest`: snapshot, restauração, poda.
- Totais e suíte completa no [BATCH-202](BATCH-202.md).

### Tenant isolado
| Verificação | Resultado |
|---|---|
| Dump no snapshot | `banco.sql.gz` de 0,59 MB em cada execução |
| v3 = v2 + fatal em `gestor.php` só na web, com `--health-url=https://c2f-teste.local:8443/` | "Verificação pós-atualização FALHOU: HTTP 500"; "Rollback automático dos arquivos: 2 restaurado(s)"; "Depois do rollback: OK (HTTP 200)"; código 6; execução `error` / 6 |
| `--rollback=exec-7` (só arquivos) | `gestor.php` e o marcador voltaram à v2; raiz 200 com a página real (6,8 KB) |
| `--rollback=exec-9 --com-banco` | arquivos e banco restaurados; as 2 linhas de choque gravadas depois do dump sumiram |
| `--rollback=exec-11 --com-banco` sem `--domain` | recusa: "--com-banco precisa de --domain" |
| Com `--domain` | restaurado; a execução 11 ficou `rolled-back` |

### Achados corrigidos durante a homologação
- **Dump com código 2:** o `/bin/sh` do Debian/Ubuntu é o dash, que sai com 2 no `set -o pipefail` (e o `2>/dev/null` escondia a mensagem).
- **Verificação para `127.0.0.1:443`:** no Lab caía no Caddy, que responde 200 vazio (falso OK); num HestiaCP de produção, com o nginx escutando só no IP público, a conexão seria recusada e toda atualização voltaria sozinha. Agora é DNS normal primeiro, `127.0.0.1` depois, sem conexão vira aviso, e há `--health-url`/`--health-ip`.
- **OPcache:** logo depois da troca de arquivos, o PHP-FPM ainda serviu o código antigo (`revalidate_freq=2`); a verificação espera 3 s, e a volta automática verifica de novo.
- **Execução `running` depois do `--com-banco`:** o dump é feito com a linha aberta; o rollback agora a marca.

### Pendências
- Homologação humana. A web (`admin-atualizacoes`, por etapas) grava snapshot e dump, mas a verificação com volta automática é só do CLI.
