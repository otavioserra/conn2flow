# Index — backlog

| ID | Title | Executive summary | Relative link | Status |
| --- | --- | --- | --- | --- |
| BACKLOG-INDEX | Índice do Backlog |  | [source](<BACKLOG-INDEX.md>) | indexed |
| BL-013 | BL-013 — Poda e modularização de documentação raiz (README e CHANGELOG) para eficiência de contexto de IA |  | [source](<BL-013-modularizacao-readme-changelog-ai-workspace.md>) | OPEN |
| BL-014 | BL-014 — Desacoplamento e reset do cache estático em `schemaMetadata()` para testes unitários PHPUnit |  | [source](<BL-014-reset-cache-estatico-schemametadata-testes.md>) | OPEN |
| BL-015 | BL-015 — Pipeline de projeto não sincroniza a tabela `hooks` | `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`, `gestor/controladores/atualizacoes/atualizacoes-... | [source](<BL-015-pipeline-projeto-nao-sincroniza-hooks.md>) | PROMOTED (promovido para &#91;req-174.md&#93;(../human-requests/archive/req-174.md), BATCH-179) |
| BL-016 | BL-016 — Redirecionamento de `paginas_301` descarta a query string | `gestor/bibliotecas/gestor.php` (`gestor_redirecionar()`, linha 2477) | [source](<BL-016-paginas-301-status-e-query-string.md>) | PROMOTED (promovido para &#91;req-173.md&#93;(../human-requests/archive/req-173.md), BATCH-178) |
| BL-017 | BL-017 — Widget de formulários vaza os blocos-fragmento do template no HTML renderizado | templates com `target=forms` em `gestor/modulos/forms/resources/*/templates/` | [source](<BL-017-forms-widget-vaza-blocos-fragmento.md>) | PROMOTED (promovido para &#91;req-173.md&#93;(../human-requests/archive/req-173.md), BATCH-178) |
| BL-018 | BL-018 — Segurança: achados da reescrita da documentação (req-181 e complementos) | A req-181 descreve onze achados verificados no código, com onde, o quê, impacto e correção proposta: | [source](<BL-018-seguranca-achados-reescrita-docs.md>) | IN-DISCUSSION |
| BL-019 | BL-019 — O deploy não atualiza o `sitemap.xml` | Regenerar no contexto HTTP, onde o domínio é conhecido: | [source](<BL-019-sitemap-nao-atualizado-no-deploy.md>) | PROMOTED (2026-10-06) — &#91;req-188&#93;(../human-requests/archive/req-188.md) (BATCH-192 / req-191) |
| BL-020 | BL-020 — E-mail: só SMTPS (porta 465) funciona; `EMAIL_SECURE` é ignorado |  | [source](<BL-020-email-so-smtps-465.md>) | IN-DISCUSSION |
| BL-021 | BL-021 — Cron: `expressao_cron`/`hora` ignorados e sem trava de sobreposição |  | [source](<BL-021-cron-ignora-expressao-e-sobreposicao.md>) | IN-DISCUSSION |
| BL-022 | BL-022 — `configuracao`: POST truncado por `max_input_vars` apaga variáveis |  | [source](<BL-022-configuracao-apaga-variaveis-post-truncado.md>) | IN-DISCUSSION |
| BL-023 | BL-023 — Pipeline de projeto: a etapa 2 sincroniza dados do projeto com o `schema-metadata.json` do core |  | [source](<BL-023-pipeline-etapa2-metadado-do-core.md>) | IN-DISCUSSION |
| BL-024 | BL-024 — Catálogo de bugs menores apontados na reescrita das docs | O Humano escolhe os itens; os de uma linha podem ser agrupados numa requisição de "correções pequenas", como a req-187. | [source](<BL-024-catalogo-bugs-menores-docs.md>) | IN-DISCUSSION |
| BL-025 | BL-025 — Página de docs removida continua publicada no banco |  | [source](<BL-025-docs-removidas-seguem-publicadas.md>) | IN-DISCUSSION |
| BL-026 | BL-026 — Listagem do `interface` em Tailwind |  | [source](<BL-026-listagem-interface-tailwind.md>) | IN-DISCUSSION |
| BL-027 | BL-027 — Sincronização de banco carrega tabelas inteiras na memória | A req-191 contornou o sintoma elevando o `memory_limit` do endpoint para 1024 MB. A causa continua: cada tabela é lid... | [source](<BL-027-sincronizacao-banco-memoria.md>) | IN-DISCUSSION |
| BL-028 | BL-028 — Atualização segura: core canibalizável, choques, exclusão de dados, backup e rollback | O core pode ser **canibalizado**: um projeto (e um plugin, que roda sobre o core) pode sobrepor **qualquer** arquivo ... | [source](<BL-028-atualizacao-segura-choques-backup-rollback.md>) | PROMOTED (2026-10-06) — &#91;req-197&#93;(../human-requests/archive/req-197.md), &#91;req-198&#93;(../human-requests/archive/req-198.md), &#91;req-199&#93;(../human-requests/archive/req-199.md), &#91;req-200&#93;(../human-requests/archive/req-200.md), &#91;req-201&#93;(../human-requests/archive/req-201.md) e &#91;req-245&#93;(../human-requests/archive/req-245.md) (BATCH-254) |
| BL-029 | BL-029 — Erro intermitente na suíte PHPUnit no Windows (suspeita: bloqueio do antivírus) | Há dois ou três dias o Windows mostra um aviso de vírus encontrado; a verificação manual do antivírus não acha nada. ... | [source](<BL-029-phpunit-erro-intermitente-windows.md>) | RESOLVIDO (2026-10-07) — causa identificada no mesmo dia; não era o antivírus nem defeito do produto |
| BL-030 | BL-030 — Aposentar ou marcar como legado os layouts de e-mail, impressão e iframe | Os três não estão sem uso; nenhum deles é layout de página de site. | [source](<BL-030-layouts-legados-email-impressao-iframe.md>) | ICEBOX |
| BL-031 | BL-031: Eventos duráveis dos gateways e unificação dos e-commerces | O humano pediu que esta melhoria permaneça no backlog para implementação futura. BATCH-274 / BATCH-116 devem somente ... | [source](<BL-031-eventos-gateways-ecommerce-unificado.md>) | ICEBOX |

## Directories

- [archive](<archive/index.md>)
