# BATCH-098 - Área de Transferência Persistente do Editor Visual (copiar em uma página, colar em outra)

Origem: demanda direta do Engenheiro Chefe humano na sessão de 2026-07-30 (sem `req-` dedicado; complementa o req-097).
Decisão: DEC-094
Validação: VALIDATION-CHECKLIST.md#batch-098

## Problema

Os botões `he-tb-copy` / `he-tb-paste` da barra flutuante guardavam o bloco copiado **apenas em memória**
(`this.clipboardElement`). Sair do modo de edição recarrega a página e navegar para outra página destrói a
instância do motor — então a cópia se perdia. O caso real do cliente ("copiar uma seção desta página e colar
em outra") era impossível.

## Solução

Persistir a cópia como **HTML saneado** em `localStorage['c2f-he-clipboard']` (`{html, ts, origem}`):

- `copySelected()` grava (e SUBSTITUI a cópia anterior); `initClipboard()` recupera no boot do editor, para o
  botão "Colar" já nascer disponível; um listener de `storage` acompanha cópias feitas em outras abas.
- `buildClipboardMarkup()` sanea o bloco para viajar entre páginas: remove o invólucro de embed e o leitor
  PDF.js renderizado (UI de runtime), além de `data-c2f-variable`/`contenteditable` (dependem do `varMap` da
  página de origem). Os `data-c2f-marker` são preservados — é o que mantém um widget colado como widget.
- `remapPastedIds()` renumera `data-c2f-widget-id` (e reregistra wrappers clássicos no `widgetsMap`), evitando
  colisão quando a cópia é colada na mesma página do original.
- `pasteSelected()` passa a funcionar **sem seleção**: nesse caso insere no fim do conteúdo editável
  (`insertionRoot()`, do BATCH-097), nunca na raiz do layout.

Como o `localStorage` é por origem, a cópia é compartilhada entre páginas do site, entre o Live Editor e o
editor clássico (o `srcdoc` herda a origem) e entre abas.

## Validação

- `node --check` no motor; `npx vitest run` com 6 casos novos em `tests/Unit/JS/html-editor.live.test.js`.
- PHPUnit sem regressão (nenhum arquivo PHP tocado além do cache-bust da biblioteca).
