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
| BATCH-286 | Hotfix do atualizador pelo painel: trava órfã e memória da sincronização | Erro fatal numa etapa web libera a trava; etapas pesadas com 1 GB de memória; dono morto no mesmo servidor libera a trava; painel oferece liberar e tentar de novo. | [source](<BATCH-286.md>) | implemented-pending-homologation |
| BATCH-287 | Atualizador do core sempre entregue, mesmo com manifesto desatualizado | Arquivos do atualizador (camada core) são sempre escritos quando mudam; manifesto desatualizado não segura mais o hotfix do atualizador. | [source](<BATCH-287.md>) | implemented-pending-homologation |
| BATCH-288 | wait_timeout da sessão de banco da atualização (REQ-279) | A 3.0.6 entrou inteira (29 arquivos, sem preservados) e a memória não estourou, mas a etapa de banco falhou: `&#91;BANCO&#93;... | [source](<BATCH-288.md>) | implemented-pending-homologation |
| BATCH-289 | Trava da atualização pelo painel: dono morto sob open_basedir e etapa com erro (REQ-280) | A atualização para a 3.0.7 recusou: "Outro deploy está em execução … (PID 1386087), desde 16:44:44". Era a sessão das... | [source](<BATCH-289.md>) | complete |
| BATCH-290 | Deploy SSH com o dono do destino entrando como root (REQ-281) | Durante o deploy SSH do `temp.conn2flow.com` o site deu **500** (`require_once(gestor.php): Permission denied`) e o c... | [source](<BATCH-290.md>) | complete |
| BATCH-291 | Estabilização do teste assíncrono de XHR no global-csrf-refresh (REQ-282) |  | [source](<BATCH-291.md>) | complete |
| BATCH-292 | Motor canônico de CRUD da API e RBAC — Fase 1 (REQ-283) | Motor api-crud.php, fallback no api.php, RBAC por perfil e escopo, admin-categorias habilitado. | [source](<BATCH-292.md>) | ready-for-review |

## Directories

- [archive](<archive/index.md>)
