# Index — implementation

| ID | Title | Executive summary | Relative link | Status |
| --- | --- | --- | --- | --- |
| BATCH-275 | BATCH-275 — REQ-266: compatibilidade memory/ e sdd/ | Preferência por memory/ e fallback sdd/. SddSource conserva chaves públicas sdd/ e seção sdd para preservar o contrat... | [source](<BATCH-275.md>) | implemented-pending-homologation |
| BATCH-276 | Capas isométricas de Páginas de Lousa e Interface | Duas capas WebP e manifestos entregues; Lab 3.0/3.1 validado em 19/19 verificações por ambiente. | [source](<BATCH-276.md>) | implemented-pending-homologation |
| BATCH-277 | REQ-268: página com sem_permissao = 0 passa a exigir login | Roteador e telas de edição tratavam sem_permissao = 0 como página pública; agora só 1 abre a página. | [source](<BATCH-277.md>) | implemented-pending-homologation |
| BATCH-278 | REQ-269: --skip-css nos pipelines project:update-all e manager:update-all | --skip-css pula a compilação Tailwind e o css:rebuild nos dois pipelines; Lab em 36 s com a flag. | [source](<BATCH-278.md>) | implemented-pending-homologation |
| BATCH-280 | REQ-271: pagamentos_com_trava dentro do stripe.php e exclusão do pagamentos.php | Trava transacional movida para stripe.php; pagamentos.php removido; fixtures do core e do site carregam stripe. | [source](<BATCH-280.md>) | complete |
| BATCH-281 | REQ-272: release 3.0.0 e projeto de implantação do pré-lançamento | Releases gestor-v3.0.0, 3.0.1 e 3.0.2 (changelog, READMEs, notas do workflow, migração corrigida para MySQL 8) e projeto conn2flow-site-temp. | [source](<BATCH-281.md>) | implemented-pending-homologation |
| BATCH-282 | Campo domain nos formulários do Core | Tipo domain no editor, widget e validação client/server dos formulários. | [source](<BATCH-282.md>) | implemented-pending-runtime-validation |
| BATCH-283 | Passe de navegador confiável para acesso anônimo no site restrito | Cookie assinado (HMAC) deixa um navegador autorizado navegar deslogado com SITE_RESTRICTED_ACCESS ligado; autorizar, revogar este e revogar todos no admin-environment. | [source](<BATCH-283.md>) | implemented-pending-homologation |
| BATCH-284 | Visibilidade e link direto privado nas páginas (admin-paginas e publisher-pages) | Linha Visibilidade entre Tipo e Agendamento: visível publicamente (padrão) e link direto privado com Copiar e Gerar novo link; sem permissão marcado ao adicionar. | [source](<BATCH-284.md>) | implemented-pending-homologation |
| BATCH-285 | Confirmação padrão no link privado: correção do bloqueio do release 3.0.4 | Diálogo assíncrono nos dois editores de páginas; Vitest completo com 700 testes aprovados. | [source](<BATCH-285.md>) | implemented-pending-homologation |

## Directories

- [archive](<archive/index.md>)
