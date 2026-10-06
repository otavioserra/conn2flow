# BL-019 — O deploy não atualiza o `sitemap.xml`

- **Tipo**: Bug / SEO
- **Status**: PROMOTED (2026-10-06) — [req-188](../human-requests/archive/req-188.md) (BATCH-192 / req-191)
- **Severidade sugerida**: MÉDIA
- **Origem**: homologação da documentação online no Lab (req-184 / req-186), 2026-09-26
- **Componentes**: `gestor/bibliotecas/sitemap.php`, `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`, `gestor/controladores/arquivo-estatico/`

## Contexto observado

1. O sitemap é mantido por `sitemap_sincronizar_pagina()`, chamado nas edições do painel.
2. Páginas que entram pelo pipeline (`project:update-all`, recursos de módulo, as ~320 páginas do `docs:build`) não passam por ele: no Lab, o `sitemap.xml` ficou com 0 URLs de `/docs/`.
3. Gerar o sitemap no fim do atualizador de banco **não é seguro**: o atualizador roda em CLI, e sem `SERVER_NAME` o `config.php` cai em `localhost`; as URLs sairiam `https://localhost/...`.

## Proposta

Regenerar no contexto HTTP, onde o domínio é conhecido:

1. O atualizador grava um marcador de "sitemap desatualizado" (por exemplo, a data do último `manager_updates` com `paginas` alterada).
2. Ao servir `assets/sitemap.xml`, o controlador de estáticos compara o arquivo com o marcador e chama `sitemap_gerar_completo()` quando ele estiver mais velho.
3. Alternativa: comando `c2f sitemap:rebuild <projeto>` que chama uma rota autenticada do próprio site.

## Critérios de aceite (rascunho)

- Após `project:update-all` que cria páginas, o próximo GET de `/sitemap.xml` já as lista, com o domínio público.
- Sem páginas alteradas, o arquivo não é regenerado a cada acesso.
