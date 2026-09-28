# Batch Index

Este arquivo controla o estado dos batches do `conn2flow` no modelo SDD.

## Status usados aqui

- `complete`: batch fechado e validado
- `ready-for-intake`: próximo slice reservado, aguardando intake humano classificado
- `in-progress`: implementação em andamento
- `blocked`: depende de decisão, requisito ou validação adicional

## Batches

| Batch | Status | Escopo | Alvo de validação | Observações |
| --- | --- | --- | --- | --- |
| BATCH-000 a BATCH-017 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-000-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-000-017.md) | Os detalhes de implementação e validações dos primeiros 18 lotes foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-018 a BATCH-053 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-018-053.md) | Os detalhes de implementação e escopo dos lotes 18 a 53 foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-054 a BATCH-093 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-054-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-054-093.md) | Os detalhes de implementação e escopo dos lotes 54 a 93 foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-094 a BATCH-178 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-094-178.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-094-178.md) | Os detalhes de implementação e escopo dos lotes 94 a 178 foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-179 | complete | Sincronização Automática da Tabela `hooks` no Pipeline de Deploy de Projeto (req-174) | [BATCH-179.md](archive/BATCH-179.md) | Fluxo de banco sincroniza hooks e reporta métricas; `project:sync-hooks` reutiliza transporte SSH/Host/Docker. PHPUnit 1.205/1.205 e Vitest 426/426; homologação no Lab em `conn2flow-site-local` aprovada. |
| BATCH-180 | implemented-pending-homologation | Renovação Silenciosa de Token CSRF (Silent Refresh & Retry) e Recuperação Graciosa de Sessão no Core e `global.js` (req-175) | [BATCH-180.md](archive/BATCH-180.md) | Endpoint `_gestor-csrf-token`, código padronizado `CSRF_INVALID_OR_EXPIRED`, fila de retry transparente no `global.js` para `fetch`/`$.ajax`/`XMLHttpRequest` e detecção no `visibilitychange`. Implementado: PHPUnit **1.220/1.220**, Vitest **449/449**, `git diff --check` limpo; homologação runtime pendente. |
| BATCH-181 | complete | Snapshot e limpeza do legado do `ai-workspace` (req-176) | [BATCH-181.md](archive/BATCH-181.md) | FEAT-014 fase 0. Branch local `legacy/ai-workspace-pre-docs`; remove `agents-history/`, `prompts/`, `templates/`; `scripts/` intocado. |
| BATCH-182 | complete | Contrato de documentação, `docs:audit`, `docs:extract` e piloto bilíngue (req-177) | [BATCH-182.md](archive/BATCH-182.md) | FEAT-014 fase 1. 8 docs piloto com score 0; PHPUnit 8/8. |
| BATCH-183 | complete | Parser `docs:build` Markdown → recursos Tailwind (req-178) | [BATCH-183.md](archive/BATCH-183.md) | FEAT-014 fase 1. Parsedown em `cli/lib/`; idempotente; publicado no Lab (`conn2flow-site-local`, REQ-057/BATCH-050 do site); PHPUnit 12/12 docs, suíte 1.231/1.232 (CoreHelpersTest = openssl Windows). |
| BATCH-184 | complete | Migração do acervo de docs — ondas 1 e 2 (req-179) | [BATCH-184.md](BATCH-184.md) | Conceitos da onda 1 e as 41 bibliotecas; legado zerado; achados de segurança A1–A11 na req-181. Concluído em 2026-09-26; pipeline das docs passa à req-184. |
| BATCH-185 | complete | Documentação dos módulos do core — onda 3, segundo agente (req-180) | [BATCH-185.md](BATCH-185.md) | 32 módulos reescritos (pt-br + en), legado de módulos removido; auditoria final: 66 referências, nenhuma ausente, score 0. Publicação no site feita pelo BATCH-184. Concluído em 2026-09-26. |
| BATCH-186 | complete | Docs do core — guias e referência de CLI e API, onda 4 (req-182) | [BATCH-186.md](BATCH-186.md) | Agente em outra infraestrutura. Sem pipeline. |
| BATCH-187 | complete | Docs do core — conceitos, novidades e limpeza do legado, onda 5 (req-183) | [BATCH-187.md](BATCH-187.md) | Agente em outra infraestrutura. Sem pipeline. |
| BATCH-188 | complete | Publicação do SDD do core e dono do pipeline das docs, onda 6 (req-184) | [BATCH-188.md](BATCH-188.md) | Agente em outra infraestrutura. Pipeline só depois do BATCH-184 `complete`. |
| BATCH-189 | complete | Ajustes de navegação e referência nas docs online (req-185) | [BATCH-189.md](BATCH-189.md) | Agente em paralelo. Menu centralizado, anterior/próximo com rótulo do menu, descrição das funções, sem link de edição. Sem pipeline. |
| BATCH-190 | complete | Módulo `documentation` no conn2flow-site (req-186) | [BATCH-190.md](BATCH-190.md) | docs:build com módulo dono e status; tela `/documentation/`; migração das páginas; Lab 8/8; site `583acb0`. |
| BATCH-191 | complete | Correções simples da reescrita das docs e do deploy (req-187) | [BATCH-191.md](BATCH-191.md) | `auth:cookie` SSH no Windows, variável global com preferência de módulo, plano em `admin-atualizacoes`, bibliotecas fantasmas. Achados complexos em BL-018 a BL-024. |
| BATCH-192 | complete | Ajustes das docs online, sitemap no deploy e exclusão de órfãs (req-188) | [BATCH-192.md](BATCH-192.md) | Rota com ponto (whats-new 404), expandir/recolher tudo, arquivo do SDD publicado, resumo sem Markdown, sitemap no `/_api/project/update`, 17 órfãs excluídas pela lista `deletar`. |
| BATCH-193 | complete | Segurança do `interface`: listagem sem SQL injection e excluir/status com CSRF (req-189) | [BATCH-193.md](BATCH-193.md) | A1 e A2 da req-181; teste com entrada maliciosa; Lab por `project:sync-core`. |

## Regra operacional

Não abra um novo batch funcional sem atualizar este índice. Se o escopo mudar de forma normativa, registre primeiro a mudança em `sdd/change-requests/`.
