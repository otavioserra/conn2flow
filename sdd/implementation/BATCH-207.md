# BATCH-207: Atualização segura, fase 3c — exclusão declarativa de dados (req-199)

Execução da proposta C do [BL-028](../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md) na [req-199](../human-requests/req-199.md).

**Status**: `complete` (implementado e validado no tenant isolado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`.
**Convivência**: o diretório principal do core tem trabalho não commitado da req-196 em `atualizacoes-banco-de-dados.php` (bloco no início de `sincronizarTabela`) e nos compiladores de recursos. Este lote toca esse arquivo só em dois pontos distantes (um `require` no cabeçalho e uma chamada depois de `sincronizarTabela()` em `comparacaoDados()`), para o `git pull` do outro agente mesclar sem conflito.

## Desenho

| Peça | O quê |
|---|---|
| `controladores/atualizacoes/atualizacoes-recursos-retirada.php` (novo) | manifesto por dono (`installation/manifests/recursos-<dono>.json`), chaves naturais, plano (o que saiu), aplicação no banco, passada por tabela e invólucro com log |
| Regras | só tabelas `natural_key`; o core só casa registros sem `project`, o projeto só os dele; `status='D'` se a tabela tem status, senão `DELETE`; `user_modified=1` vira choque de registro; primeira entrega só grava a linha de base; `--dry-run` simula; `--no-resource-removal` desliga |
| Choque de registro | `caminho` `db:<tabela>?<chave>`, `tipo` `registro`, motivo `retirado-editado`; `atualizacoes_choques_resolver_registro()` aplica "sobrescrever" (retira) ou "manter" |
| Sincronizador | `require` e chamada `recursos_retirada_passada()` depois de `sincronizarTabela()`; resumo no log (`RECURSOS_RETIRADA …`) |

## Validação

### Automatizada
- `RecursosRetiradaTest` (7, SQLite em memória): chaves e plano (minúsculas, `linguagem_codigo`), retirada com `status='D'`, `DELETE` sem status, editado vira choque com o caminho `db:`, dono só mexe no que é dele, simulação, manifesto por dono.
- **Suíte completa:** 1.340 testes; as falhas são as de ambiente da worktree (`CoreHelpersTest`, três `Stripe*Test`, `CssRegeneracaoTest`).

### Tenant isolado
| Verificação | Resultado |
|---|---|
| Atualização com `--force-all` | `recursos-core.json` gravado; 17 tabelas com `primeira=1` |
| Variável pt-br `updates-clash-res-mesclar` marcada `user_modified=1` | — |
| Artefato v6 sem duas variáveis pt-br | `RECURSOS_RETIRADA tabela=variaveis saidos=2 removidos=1 choques=1`; a intacta saiu (a tabela não tem `status`), a editada ficou e virou o choque 10 (`db:variaveis?language=pt-br&modulo=admin-atualizacoes&id=updates-clash-res-mesclar`); as versões em inglês, ainda entregues, ficaram |
| `update:resolve project-test 10 --acao=sobrescrever --json` | `{ok:true}`; a variável editada saiu |

### Pendências
- Deploy de projeto por API: a passada roda no mesmo sincronizador com `--project` (dono = projeto); não houve teste real com pacote de projeto que retire recurso.
- Homologação humana.
