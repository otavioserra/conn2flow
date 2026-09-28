# Índice do Backlog

| ID | Tipo | Status | Título | Próxima ação | Atualizado em |
| --- | --- | --- | --- | --- | --- |
| [BL-001](archive/BL-001-instalador-integridade-download.md) | Architecture/Security | PROMOTED | Instalador: integridade e TLS no download do gestor (supply chain) | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-002](archive/BL-002-rng-sessao-tokens.md) | Security | PROMOTED | RNG fraco em IDs de sessão e tokens (md5/uniqid/rand) | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-003](archive/BL-003-path-traversal-arquivo-estatico.md) | Security | PROMOTED | Contenção de path traversal no servidor de arquivos estáticos | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-004](archive/BL-004-csrf-nao-aplicado.md) | Security | PROMOTED | Proteção CSRF existe mas não é aplicada (código morto) | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-005](archive/BL-005-api-cors-token-querystring.md) | Security | PROMOTED | API: CORS wildcard, token via query string e rate limit em arquivo | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-006](archive/BL-006-instalador-lock-e-residuos.md) | Security | PROMOTED | Instalador: lock/auth de execução e resíduos pós-instalação | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-007](archive/BL-007-acesso-dados-prepared-statements.md) | Architecture/Security | PROMOTED | Acesso a dados por concatenação SQL e fallback de escape frágil | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-008](archive/BL-008-oauth2-hardening.md) | Security | PROMOTED | Hardening OAuth2: validação de HMAC do token e limite de sessões | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-009](archive/BL-009-cabecalhos-seguranca-http.md) | Security | PROMOTED | Ausência de cabeçalhos de segurança HTTP (CSP, HSTS, etc.) | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-010](archive/BL-010-debito-migracao-e-codigo-morto.md) | Architecture/Maintainability | PROMOTED | Débito de migração (libs v1/v2) e código morto | Promovido para [req-107.md](../human-requests/archive/req-107.md) (BATCH-107) | 2026-08-06 |
| [BL-011](BL-011-poda-pipeline-sync-allowlist.md) | Architecture/DevOps | OPEN | Poda segura por allowlist no pipeline de sincronização (req-146) | Planejado para baseline da Linha 3.0 | 2026-08-31 |
| [BL-012](BL-012-migracao-painel-tailwind-desacoplamento-fomantic.md) | Architecture/UI | OPEN | Substituição integral do Fomantic UI por Tailwind CSS no painel administrativo e componentes UI | Planejado para o programa da Linha 3.0 | 2026-08-31 |
| [BL-013](BL-013-modularizacao-readme-changelog-ai-workspace.md) | Documentation/Architecture | PROMOTED | Poda e modularização de README e CHANGELOG para eficiência de contexto de IA | Promovido para [req-151.md](../human-requests/archive/req-151.md) (BATCH-153) | 2026-08-31 |
| [BL-014](BL-014-reset-cache-estatico-schemametadata-testes.md) | Reliability/Testing | OPEN | Desacoplamento do cache estático em `schemaMetadata()` para testes unitários | Registrado no BATCH-168; aguardando agendamento | 2026-09-17 |
| [BL-015](BL-015-pipeline-projeto-nao-sincroniza-hooks.md) | Bug/DevOps | PROMOTED | Pipeline de projeto (`project:update-all`) não sincroniza a tabela `hooks` | Promovido para [req-174.md](../human-requests/archive/req-174.md) (BATCH-179) | 2026-09-22 |
| [BL-016](BL-016-paginas-301-status-e-query-string.md) | Bug/SEO | PROMOTED | Redirecionamento de `paginas_301` descarta a query string | Promovido para [req-173.md](../human-requests/archive/req-173.md) (BATCH-178) | 2026-09-22 |
| [BL-017](BL-017-forms-widget-vaza-blocos-fragmento.md) | Bug/UI | PROMOTED | Widget de formulários vaza os blocos-fragmento do template no HTML renderizado | Promovido para [req-173.md](../human-requests/archive/req-173.md) (BATCH-178) | 2026-09-22 |
| [BL-018](BL-018-seguranca-achados-reescrita-docs.md) | Security | IN-DISCUSSION | Segurança: achados da reescrita das docs (req-181 A1–A11 e complementos: JWT, OAuth2, SQL em módulos, CDN) | A1 e A2 promovidos e corrigidos ([req-189](../human-requests/req-189.md), BATCH-193); demais itens com o Humano | 2026-09-26 |
| [BL-019](BL-019-sitemap-nao-atualizado-no-deploy.md) | Bug/SEO | IN-DISCUSSION | O deploy não atualiza o `sitemap.xml` | Parcial: deploy pela API regenera ([req-188.md](../human-requests/req-188.md), BATCH-192); falta a sincronização por SSH | 2026-09-28 |
| [BL-020](BL-020-email-so-smtps-465.md) | Bug | IN-DISCUSSION | E-mail: só SMTPS/465 funciona; `EMAIL_SECURE` ignorado | Aguardando priorização | 2026-09-26 |
| [BL-021](BL-021-cron-ignora-expressao-e-sobreposicao.md) | Bug/Feature | IN-DISCUSSION | Cron: `expressao_cron`/`hora` ignorados e sem trava de sobreposição | Aguardando priorização | 2026-09-26 |
| [BL-022](BL-022-configuracao-apaga-variaveis-post-truncado.md) | Bug/Data loss | IN-DISCUSSION | `configuracao`: POST truncado por `max_input_vars` apaga variáveis | Aguardando priorização (severidade alta) | 2026-09-26 |
| [BL-023](BL-023-pipeline-etapa2-metadado-do-core.md) | Bug/DevOps | IN-DISCUSSION | Pipeline de projeto: etapa 2 sincroniza dados do projeto com o metadado do core (colisão em `menus`) | Aguardando priorização | 2026-09-26 |
| [BL-024](BL-024-catalogo-bugs-menores-docs.md) | Bug/Maintainability | IN-DISCUSSION | Catálogo de bugs menores apontados nas docs (bibliotecas e módulos) | Humano escolhe itens para uma requisição de correções pequenas | 2026-09-26 |
| [BL-025](BL-025-docs-removidas-seguem-publicadas.md) | Bug/Docs | IN-DISCUSSION | Página de docs removida continua publicada no banco (órfã ignorada pelo pipeline) | Parcial: 17 órfãs excluídas pela lista `deletar` do site (req-188); rotina automática de exclusão fica com o Humano | 2026-09-28 |
| [BL-026](BL-026-listagem-interface-tailwind.md) | Feature/Interface | IN-DISCUSSION | Listagem do `interface` (DataTables) em Tailwind; formulários e botões já têm variante (req-190) | Prevista para a versão 3.0 | 2026-09-28 |

> Itens `PROMOTED` foram convertidos na requisição humana `sdd/human-requests/req-107.md` (BATCH-107) e arquivados em `sdd/backlog/archive/`.


