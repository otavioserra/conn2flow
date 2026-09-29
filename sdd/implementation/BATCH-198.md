# BATCH-198: Hotfix — migrações obsoletas no ambiente em execução (req-194)

Execução da [req-194](../human-requests/req-194.md).

**Status**: `complete`.

## Entregas

| Onde | Mudança |
|---|---|
| `gestor/controladores/atualizacoes/atualizacoes-migracoes.php` | Novo: limpeza por dono (manifesto, migração renomeada, choques) + CLI |
| `gestor/controladores/api/api.php` | `api_project_migracoes_limpar()` antes de `api_copy_directory()`; `migrations` na resposta |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | `limparMigracoesCore()` antes de `moverConteudoStaging()` (CLI e web); helper carregado da instalação ou do staging |
| `ai-workspace/en/scripts/projects/deploy-project-v2.sh` | Manifesto `db/.c2f-migrations-projeto.json` no pacote |
| `ai-workspace/en/scripts/projects/synchronize-project.sh` | `clean_project_migrations` após o rsync; opção `--migrations-only` |
| `cli/src/Commands/ProjectUpdateAllCommand.php` | Limpeza antes da etapa 2 (banco) |
| `tests/Unit/PHP/AtualizacoesMigracoesTest.php` | 6 testes |

## Validação

- `AtualizacoesMigracoesTest`: 6 testes, 22 asserções (classe pelo nome, renomeada sem manifesto, retirada pelo dono, pacote parcial não remove, mesma versão = choque, arquivo do outro dono protegido nos dois sentidos).
- Lab (`conn2flow-site-local`): o pipeline falhava na etapa 2 com `Duplicate migration` (`20260929140000_create_affiliates_tables.php` antigo + `20260929140000_create_host_manager_settings_table.php`). Com o hotfix: `Migração obsoleta removida (projeto): 20260929140000_create_affiliates_tables.php [renomeada:20260929163000_create_affiliates_tables.php]`, 0 choques, pipeline completo com sucesso.

## Limitação conhecida

Na primeira execução sem manifesto de nenhum dos donos, não há como saber de quem é cada arquivo: a regra da migração renomeada pode remover o arquivo do outro dono se ele tiver a mesma classe (o que já quebraria o Phinx). O manifesto do core nasce na primeira atualização do sistema; o do projeto, no primeiro deploy/pipeline. Tratado em definitivo no [BL-028](../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md).
