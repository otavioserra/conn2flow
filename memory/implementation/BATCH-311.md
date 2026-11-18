---
id: "BATCH-311"
title: "REQ-302 — Deslocamento dinâmico da Editbar"
status: "complete"
date: "2026-11-18"
author: "executor"
target_repo: "conn2flow"
request: "REQ-302"
topology: "solo"
autonomy: "autonomo_monitorado"
summary_short: "Toolbar medida no iframe, deslocamento do fluxo e de cabeçalhos, Vitest 721/721 e Chromium autenticado."
summary_medium: "O operador concluiu o pipeline por SSH. O Chromium autenticado confirmou a geometria na home e em uma rota sem elementos fixos, servidas com os minificados publicados."
---

# BATCH-311 — Deslocamento dinâmico da Editbar

Repositório `conn2flow` em `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`, branch `main`. Implementação da [REQ-302](../human-requests/req-302.md) em topologia solo.

## Live Todo List

- [x] Medir a caixa persistente do iframe com `ResizeObserver` e `getBoundingClientRect`.
- [x] Aplicar o offset ao fluxo, à rolagem por âncoras e ao topo de elementos ancorados ao viewport. Preservar a UI do editor e o HTML de autoria.
- [x] Atualizar os minificados, o manifesto e o metadado do dashboard para `1.0.31` com `asset_version` `0c7373829b459e31`.
- [x] Cobrir o comportamento com Vitest, rodar a suíte completa e conferir o layout administrativo com PHPUnit.
- [x] Inspecionar no Chromium autenticado a home e uma resposta 404 sem elementos fixos, com os novos minificados entregues pelo Playwright.
- [x] Conferir o HTML e o layout no POST real do editor, interceptado antes de chegar ao servidor.
- [x] Conferir a versão servida pelo laboratório após a publicação por SSH.

## Implementação

- O iframe arredonda para cima a altura da barra persistente e mede à parte a extensão do menu flutuante. O host recebe os dois valores em `c2f-toolbar:resize`.
- O host soma `--c2f-toolbar-offset` à margem e ao `scroll-padding-top` da raiz. Uma folha temporária no `head` desloca os elementos elegíveis sem editar seus atributos. Alterações no DOM e redimensionamentos reavaliam o posicionamento. O cálculo usa o destino de transições CSS para evitar reutilizar um `top` interpolado. Declarações inline com `!important` mantêm a precedência da cascata.
- Os layouts administrativos em português e inglês usam o offset para reservar a altura da barra no shell. As regras anteriores de `top` fixo foram removidas para evitar a soma duplicada.

## Evidências

- `npm test` aprovou **721 testes em 65 arquivos**, incluindo cinco testes da REQ-302.
- `php vendor/bin/phpunit tests/Unit/PHP/LayoutAdministrativoTailwindTest.php` aprovou **15 testes e 304 asserções**.
- `php cli/c2f.php assets:minify --verificar` confirmou **74 fontes e zero derivados desatualizados**.
- Playwright em Chromium autenticado na home real de `https://ia-parceria.local/`. Na versão servida, cabeçalho e progresso estavam em y=0 sob o iframe de 30 px. Com os minificados do lote entregues via interceptação, o offset foi **30 → 74 → 30 px** ao abrir e cancelar a Editbar. O menu chegou a **422 px** de iframe com offset de **30 px**. Uma mudança de **53,5 px** na barra produziu offset de **54 px**. Cabeçalho, progresso e fluxo acompanharam as medidas, com o cabeçalho preservando sua transição CSS. [Medições](../validation/evidence/batch-311/medicoes.json) · [Recolhida](../validation/evidence/batch-311/ia-parceria-recolhida.png) · [Editbar aberta](../validation/evidence/batch-311/ia-parceria-edicao.png) · [Mobile 390 px](../validation/evidence/batch-311/ia-parceria-mobile.png)
- A resposta 404 do mesmo host tinha zero elementos `fixed` além do iframe. O fluxo começava a **30 px**, com margem e `scroll-padding-top` na mesma medida. [Captura do fluxo](../validation/evidence/batch-311/ia-parceria-fluxo.png)
- O POST `site-toolbar-save` interceptado entregou **53.907 bytes** de HTML e **17.539 bytes** de layout. O cabeçalho preservou a marcação de autoria. Os campos enviados estavam livres de estilos ou atributos temporários da compensação. A sessão de teste interceptou o POST no navegador e a persistência no banco segue pendente.

## Pipelines

| Comando | Resultado |
| --- | --- |
| `./c2f manager:update-all` | Em árvore temporária com `HEAD` e arquivos do lote, compilação de recursos e cópia para espelho isolado executadas. Etapa 4/6 saiu com código 1 porque o ambiente nativo do laboratório não fornecia o Docker `conn2flow-app` exigido pelo script. |
| `bash ./c2f project:update-all ia-parceria --no-maintenance` | Checagem de 111 migrações aprovada e minificação concluída. Etapa 2/8 saiu com código 1 antes da cópia. A entrada de `ia-parceria` em `dev-environment/data/environment.json` tem `path_tests` vazio e carece de `target` ou transporte SSH para o destino. |

## Validação pós-publicação

Em 2026-11-18 o operador informou a conclusão de `./c2f project:update-all ia-parceria` após configurar o destino SSH. A entrada local passou a apontar para o laboratório. O Chromium autenticado recebeu os dois minificados do próprio site. Os SHA-256 das respostas HTTP coincidiram com o checkout e com os arquivos do Gestor no destino.

A home publicada passou no ciclo **30 → 74 → 30 px**, no menu flutuante (**422 px** de iframe com **30 px** de offset), no mobile de **390 px** e na simulação de altura de **53,5 px** que produziu **54 px** de offset. A resposta 404 manteve o fluxo a **30 px**, sem elementos fixos da página. O Chromium registrou zero erros JavaScript. O POST publicado foi interceptado e conferido com os mesmos **53.907 bytes** de HTML e **17.539 bytes** de layout limpos. A comprovação em banco depende de um salvamento efetivo.

[Medições e hashes HTTP](../validation/evidence/batch-311/publicado/medicoes.json) · [Home em edição](../validation/evidence/batch-311/publicado/ia-parceria-edicao.png) · [Mobile](../validation/evidence/batch-311/publicado/ia-parceria-mobile.png) · [Fluxo normal](../validation/evidence/batch-311/publicado/ia-parceria-fluxo.png)
