# BATCH-249 — REQ-240: harmonização global do Design System Tailwind (Core)

- Status: in-progress
- Projeto: conn2flow
- Raiz de trabalho: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow-req240` (worktree, branch `feat/req-240`, base `origin/main` `06586038`)
- Requisição: [REQ-240](../human-requests/req-240.md). Coordenação: conn2flow-site, REQ-106 / [BATCH-100](../../../conn2flow-site/sdd/implementation/BATCH-100.md).
- Autonomia: `autonomo_monitorado`. Nenhum deploy, commit ou push feito até aqui.

## Coordenação com a REQ-239 / BATCH-248 (2026-10-05, agente da REQ-240)

A REQ-239 roda em paralelo na árvore compartilhada (`feat/req-239`), ainda sem commit. Divisão acordada com o Engenheiro Chefe (modo híbrido):

- **Não toco** enquanto a REQ-239 não estiver na `main`: `publisher`, `publisher-highlights`, `publisher-index`, `publisher-pages`, `admin-arquivos`, `admin-ia`, `cookie-consent`, `variables`, `dashboard`, `widget-imagem-tailwind`, `controles.css`, `controles.js`, `interface-tailwind.js`, `admin-tailwind.js`, `html-editor-*`.
- **`interface.php`**: alterei só `interface_botao_tailwind_icone()` (34 linhas acrescentadas ao mapa). A REQ-239 mexe em outras funções do arquivo (linhas 965 a 1880); não há sobreposição de texto.
- **Lab**: não rodo `project:update-all` até o BATCH-248 estar fechado. Um único dono do pipeline por vez.
- **Pilar 4 depende da REQ-239**: `c2fc-campo-selecao` e `c2fc-alerta` só existem no `controles.css` dela.

Achado para a REQ-239: `.c2fc-abas` (sem `-lista`) não tem regra CSS em nenhuma das árvores, e o `cookie-consent` dela passou a depender só dessa classe na barra de abas (as utilities `flex … border-b` foram retiradas). O que tem CSS é `c2fc-abas-lista` (+ `c2fc-anexa`) e `c2fc-painel-aba`.

## Live Todo List

- [x] Auditoria estática dos 36 módulos por pilar.
- [x] Pilar 1, raiz: mapa Fomantic → Lucide de 29 para 62 nomes, com teste de guarda.
- [x] Pilar 1, HTML: `admin-environment`, `admin-categorias`, `pages-index`, `usuarios-perfis`, `forms`.
- [x] Pilar 2: abas anexadas em `admin-environment`, `forms`, `forms-search`, `galleries`, `menus`.
- [x] Pilar 2: `admin-plugins` (folha canônica e estrutura corrigida). `perfil-usuario` conferido e mantido: já usa a forma canônica da REQ-240 (`nav` com `border-b`, abas `border-b-2 -mb-px`) como navegação de página sobre cards, e tem Vitest amarrado a essas classes.
- [ ] `c2fc-aba item` usado em itens de lista de `forms` e `forms-search` (45 ocorrências): conferir no navegador antes de trocar.
- [ ] Pilares 3, 5 e 6: dependem de inspeção no navegador (Lab).
- [ ] Depois da REQ-239: rebase, Pilar 4, módulos dela, ícones montados em JS.
- [ ] `resources:sync`, versões dos recursos, minificação e pipeline do Lab.
- [ ] Suíte final, revisão e evidências antes/depois.

## O que a auditoria mostrou

Os "círculos pretos" não nascem do HTML dos módulos. São o ícone Lucide `circle` usado como fallback em três pontos:

1. Ações de listagem: `interface-listar-tailwind.js` desenha `opcao.lucide || 'circle'`, e `opcao.lucide` vem de `interface_botao_tailwind_icone()`. Todo ícone Fomantic fora do mapa virava círculo. Eram 13 usos no core e 31 no site.
2. Ícones Fomantic remanescentes no HTML (`<i class="… icon">`): `admin-tailwind.js` reaproveita o nome Fomantic; nome composto vira `circle`, nome simples que o Lucide não conhece fica invisível.
3. Placeholder literal `data-lucide="circle"` deixado pela conversão: 30 no core (18 em `cookie-consent`, da REQ-239; 8 em `forms`; 4 em `usuarios-perfis`).

O "gap desconectado" das abas vinha de `.c2fc-abas-lista { margin-bottom: 12px }` somado ao `py-2` das páginas, com o painel repetindo a borda superior.

## Implementação

- `gestor/bibliotecas/interface.php`: 33 traduções novas em `interface_botao_tailwind_icone()`. Cobre todos os ícones declarados pelos módulos do core e do site.
- `tests/Unit/PHP/IconesLucideReq240Test.php`: falha se um módulo declarar ícone sem tradução ou se a tradução não existir no pacote Lucide embarcado (0.544.0).
- `admin-environment` (pt-br, en): barra em `c2fc-abas-lista c2fc-anexa`, abas em `c2fc-aba`, painéis em `c2fc-painel-aba`; 33 ícones com `data-lucide` explícito; títulos e botões alinham ícone e texto. No JS, o estado ativo passa a ser só `aria-selected`.
- `forms`, `forms-search`, `galleries`, `menus` (pt-br, en): 40 barras e 116 painéis na folha anexada, sem utilities novas (nada a recompilar para esse efeito).
- `admin-categorias`, `pages-index`, `usuarios-perfis`, `forms`: ícones explícitos no lugar do Fomantic e do placeholder.
- `admin-plugins` (adicionar e editar, pt-br e en): no pt-br do `adicionar` os três painéis estavam dentro da barra de abas, num `<div class="field">` com o rótulo literal "Arquivo ZIP do Plugin", e o arquivo abria 5 `<div>` e fechava 2; os outros três arquivos deixavam o `<div>` raiz aberto. Eram as únicas páginas desbalanceadas do core. Passam a ter barra `c2fc-abas-lista c2fc-anexa`, painéis `c2fc-painel-aba` fora da barra e marcação balanceada. O PHP deixa de emitir as utilities da aba ativa (o marcador `_active` saiu da página) e o JS deixa de alterná-las; ambos mantêm `aria-selected`.

IDs, `name`, `data-tab`, marcadores e classes-gancho (`menuX`, `tab segment`, `top attached tabular menu`, `admin-environment-tab`, `_gestor-*`) não mudaram.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `IconesLucideReq240Test` antes da correção | falha, listando os 9 nomes sem tradução do core |
| `IconesLucideReq240Test` depois | 3 testes aprovados; site com 0 usos sem tradução (171 de 171) |
| Contratos Tailwind dos módulos tocados | 64 testes, 1.609 asserções aprovados |
| PHPUnit completo (worktree, Windows) | 1.594 testes, 15.840 asserções; 3 erros e 1 falha |
| Vitest completo | 45 arquivos, 537 testes aprovados |
| Mesmos 4 testes sem as mudanças (`git stash`) | os mesmos 3 erros `Stripe*Test` e a falha de CRLF do `CssRegeneracaoTest` |
| `git diff --stat` com e sem `--ignore-cr-at-eol` | idênticos: sem ruído de fim de linha |

Ainda não executado: `resources:sync`, minificação de `admin-environment.js` e `admin-plugins.js`, pipeline e inspeção no navegador. Os derivados (`*.precompiled.css`, `*Data.json`, `.min.js`, versões) estão desatualizados de propósito até o rebase sobre a REQ-239.
