# BATCH-138 — Preservação do HTML no Live Editor e mapeamento de widgets vazios

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-111.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / Live Editor / widgets / entrega HTML

## Objetivo

Preservar comentários, marcadores e formatação do HTML quando a Dashboard Site Toolbar estiver ativa,
sem alterar a higienização das páginas públicas, e tornar selecionáveis widgets vazios ou diretamente
associados à raiz editável do layout.

## Slice aprovado

1. Fazer `gestor_pagina_higienizar_ativo()` retornar `false` antes de avaliar `HTML_SANITIZE` quando
   `gestor_dashboard_toolbar_ativo()` existir e estiver ativo.
2. Cobrir o gate em produção com e sem Live Editor, preservando os contratos atuais de `auto|on|off`.
3. Em `mapTree()`, mapear no contêiner pai um widget delimitado corretamente cuja expansão esteja
   vazia, com os mesmos atributos usados no modo-pai existente.
4. Permitir o modo-pai no próprio `#c2f-layout-root` quando ele for a raiz editável.
5. Fazer `reconstructOriginal()` considerar a própria raiz clonada ao reconstruir widgets, evitando
   que atributos `data-c2f-*` vazem para o HTML salvo.
6. Cobrir em Vitest o widget vazio, o widget na raiz do layout e o round-trip de reconstrução.

## Fora do escopo

- Alterar o algoritmo de higienização de HTML, CSS ou JavaScript criado no BATCH-134.
- Desligar a higienização para usuários autenticados sem a Dashboard Site Toolbar efetivamente ativa.
- Alterar renderizadores de widgets, o endpoint AJAX do editor ou o formato dos marcadores.
- Sincronizar recursos, fazer deploy, commit ou push.

## Contrato de validação

- `php -l` limpo nos arquivos PHP alterados.
- `node --check` limpo em `dashboard.toolbar.js`.
- `HtmlSanitizeTest` cobre produção com Live Editor ativo e inativo.
- `dashboard.toolbar.test.js` cobre widget vazio e widget mapeado no `#c2f-layout-root`, incluindo save.
- Suítes PHPUnit e Vitest aplicáveis aprovadas.
- `git diff --check` limpo.

## Evidências

- `php -l`: **2/2** arquivos limpos (`gestor.php` da biblioteca e teste PHP).
- `node --check`: **2/2** arquivos limpos (`dashboard.toolbar.js` e teste JS).
- Teste PHP focado: **37/37**, 59 asserções.
- Teste JS focado: **31/31**.
- Suíte PHPUnit completa: **776/776**, 3.326 asserções, 4 skips e 1 depreciação preexistente.
- Suíte Vitest completa: **345/345** em 22 arquivos.
- Runtime Photon local em produção (`HTML_SANITIZE=auto`):
  - visitante anônimo: 85.164 bytes, **0 comentários**, **0 marcadores de widget**, **0 linhas
    indentadas** e nenhuma toolbar;
  - administrador: 115.520 bytes, **22 comentários**, **647 linhas indentadas**, toolbar,
    `#c2f-layout-root` e `#c2f-page-content` presentes;
  - `pages-index-search/`: **2 marcadores de widget** preservados na resposta autenticada.
- Playwright headless em `pages-index-search/`: Editbar em modo de edição, widget `pages-index`
  anotado com `data-c2f-widget-id`/`data-c2f-widget-root`, zero erros de console e screenshot em
  `temp/req-111-live-editor.png`.
- `git diff --check`: limpo; review findings-first sem findings.
- O sync canônico foi executado pelo Git Bash porque o wrapper WSL não encontrou `/bin/bash`; o
  projeto foi restaurado e confirmado em `DEVELOPMENT_ENV=false` antes da inspeção final.
- Nível 1 respeitado: nenhum commit, push ou deploy remoto executado.
