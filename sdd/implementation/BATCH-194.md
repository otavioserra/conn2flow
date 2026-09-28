# BATCH-194: Formulários do `interface` em Tailwind — inclusão, botões e exclusão

Execução da [req-190](../human-requests/req-190.md).

**Status**: `complete`.

## Entregas

| Item | Onde | Mudança |
|---|---|---|
| 1 | `gestor/bibliotecas/interface.php` (`interface_adicionar_finalizar`) | Componente pela variante |
| 1 | `gestor/resources/{pt-br,en}/components/interface-formulario-inclusao-tailwind/` + `components.json` | Componente novo |
| 2 | `interface.php` | `interface_botoes_html()`, `interface_botao_tailwind_classes()`, `interface_botao_tailwind_icone()`; cabeçalho e rodapé delegam |
| 2 | `interface-formulario-{edicao,inclusao}-tailwind.html` | `<template data-c2f-botoes>` com a paleta: os botões são montados em PHP, e é por ele que as utilities chegam ao pré-compilado |
| 3 | `gestor/assets/interface/interface-tailwind.js` (+ `.min.js`) | Clique em `.excluir[data-href]`, `urlCsrf()` na confirmação |

## Validação

- `InterfaceBotoesTailwindTest`: 7 testes, 122 asserções (markup Fomantic intacto, utilities e Lucide no Tailwind, excluir com `data-href`, tooltip ausente sem aviso, paleta presente nos quatro componentes).
- `interface-tailwind.test.js`: 35 testes (3 novos: clique no excluir, token na confirmação, URL que não muda).
- `c2f resources:sync`: componente novo compilado; sem problemas.
- Suíte PHP: 1264 testes, 1 erro conhecido (`CoreHelpersTest`, OpenSSL do Windows). Vitest: 452 OK. `docs:audit`: 268 docs, 0 avisos (`docs:extract interface`).
- Lab: telas de produtos e pedidos do conn2flow-site (REQ-058/060 do site) em `conn2flow-site-local`.
