# BATCH-234 — Conferência da UI Tailwind (req-225)

**Status:** `in-progress`. **Data:** 2026-10-04. **Autonomia:** `autonomo_monitorado`.
**Intake:** [req-225](../human-requests/req-225.md).
**Core:** `conn2flow`, autoria isolada em `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req225`, branch `feat/req-225`.
**Site:** `conn2flow-site`, autoria isolada em `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-site-req225`, branch `feat/req-225`.

## Implementação

- `admin-cron` e `perfil-usuario`: campos com rótulo e foco da biblioteca, chaves, botões e dicas; componentes de segurança/API do perfil também ajustados. Confirmação de exclusão do cron usa o diálogo assíncrono; modais restauram o foco e controlam Tab/Escape.
- `analytics-manager`, `sales-reports`, `shipping-methods` e `product-reviews`: controles padronizados em pt-br/en, bundles declarados; confirmação assíncrona no analytics. Nomes, IDs, placeholders e ganchos preservados.
- Integração do core `main` e da entrega `feat/req-222`. As seis páginas de galerias dessa entrega ainda tinham 18 `title` em botões; convertidos para `data-c2f-dica`, com versões incrementadas.
- Guard PHPUnit de deriva contra `class="ui …"` e `title` em botão; inventário reproduzível em [req225-inventory.php](../validation/req225-inventory.php).
- O build de bundles de projetos não encontrava layouts/componentes herdados do core. Resolver ajustado para procurar primeiro o projeto e depois o core quando `--project-path` está ativo, mantendo a validação das raízes permitidas. Teste cobre fallback, prioridade do projeto, isolamento fora do modo projeto e rejeição de traversal no idioma.

## Checklist vivo

- [x] Briefing, dependências e inventário preliminar das árvores compartilhadas.
- [x] Worktrees isoladas; core atual e req-222 integrados.
- [x] Ajustar os dois módulos conhecidos do core e quatro módulos nativos do site.
- [x] Testes direcionados de PHP/JS e detector de deriva.
- [x] Ajustar telas Tailwind já existentes de afiliados, cupons, pedidos e produtos, incluindo campos dinâmicos e dicas do editor de tipos.
- [x] Pipeline completo `project:update-all project-test`, sequencial, incluindo CSS final.
- [ ] Browser desktop/390 px, modais, foco, assets e console; inspecionar screenshots.
- [ ] Inventário final com todas as frentes integradas e nenhuma pendência.
- [ ] Pendências humanas no arquivo do site, revisão e consolidação das branches.

## Evidências até esta rodada

- PHP do cron/perfil: 225 testes, 1.561 asserções; verde.
- PHP req-222 + detector/core após correção das dicas: 6 testes, 575 asserções; verde. Guard do site executado com `CONN2FLOW_SITE_ROOT` apontando à worktree isolada: 1 teste, 2 asserções; verde.
- JS cron/perfil: 34 testes; verde com Node 22 (Vite da baseline exige Node mais novo que o host).
- Mocks independentes do site: sales-reports, analytics-manager (91 verificações) e product-reviews passaram; sintaxe dos quatro controllers e analytics JS válida.
- `TailwindRecursosTest`: 22 testes, 127 asserções; verde. PHPUnit informa 3 depreciações; não são falhas.
- Tentativas iniciais do pipeline falharam por Bash do Windows, comando Tailwind, resolução do layout e dependências npm ausentes na worktree do site. Causas corrigidas; manutenção foi desligada automaticamente em cada falha. A nova execução está compilando 992 recursos do site. Nenhum deploy de produção.

## Limites e dependências

A req-223 tem implementação não commitada em outra worktree, com proibição explícita de build/deploy naquela execução; não foi incorporada nem declarada entregue. REQ-100/101 do site têm alterações concorrentes nas árvores compartilhadas; a worktree isolada usa apenas a baseline commitada e os ajustes deste lote. Portanto a cobertura atual não comprova CA-1/CA-2 de todas as frentes. Não declarar conclusão final enquanto essa integração e a matriz inteira de navegador estiverem abertas.

## Validação da rodada isolada

- Inventário regenerado: 198 páginas/idiomas, zero divergências estáticas de markup/bundle na baseline isolada.
- PHPUnit direcionado integrado: 254 testes e 2.267 asserções, sem falhas; depois o teste adicional do bundle elevou `TailwindRecursosTest` a 23 testes/131 asserções.
- JS cron/perfil: 56 testes em quatro arquivos, sem falhas.
- Mocks afiliados: 10 testes + 28 verificações, sem falhas; cupons, sales-reports e product-reviews passaram; analytics-manager passou em 91 verificações.
- Browser: sete rotas em 1366/390 px passaram. Corpo publicado, biblioteca, ausência de assets Fomantic, overflow zero e console sem erros; campos com foco/rótulo destacados; modais de cron/analytics e restauração de foco do cron. [Resultados](../validation/req225-evidence/results.json); screenshots no mesmo diretório, incluindo `admin-cron-390.png`, inspecionado visualmente.
- Falhas descobertas e corrigidas: a exclusão de assets globais do projeto não contemplava o layout administrativo Tailwind (CSS antigo com MIME HTML); bundles incluíam o HTML do layout mas omitiam as dependências dele (logo da barra superior excedia 390 px). A nova compilação inclui as dependências declaradas no layout e o projeto exclui JS/CSS público do painel.
- `project:update-all project-test` terminou com exit 0 e conferência de 763 arquivos de código. A publicação opcional de assets em `dist` falhou no rsync do Windows (`C:` interpretado como origem remota); o controlador estático funcionou no browser. Esse aviso não deve ser ocultado nem tratado como publicação direta bem-sucedida.

O [inventário atual](../validation/req225-inventory.md) é uma fotografia da autoria, não do SQL. A matriz inteira ainda requer integração das frentes. O humano autorizou assumir a continuação de REQ-100/101 e consolidar a req-223 em 2026-10-04, além de commit/push; o fechamento conjunto continua nesta execução. Sem alterações normativas em SPEC e sem limpeza das memórias SDD.
