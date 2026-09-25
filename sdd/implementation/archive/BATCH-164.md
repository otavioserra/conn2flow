# BATCH-164 — Paridade de Variáveis e Prevenção de 404 no Editor Visual de Layouts

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-162.md`
- **Data de abertura**: 2026-09-02
- **Modo**: supervisionado

## Escopo aprovado

1. Resolver variáveis globais antes de montar o `srcdoc` do editor visual de layouts.
2. Preservar marcadores de variável em atributos para que o save não fixe caminhos resolvidos.
3. Impedir que a extração de HTML clone elementos de mídia de modo a disparar requisições de rede.
4. Recompilar recursos e executar as suítes Vitest e PHPUnit.

## Implementação

- `editorHtmlVisual()` agora chama `htmlEditorRenderVars()` também quando `alvo === 'layouts'` e
  entrega `data.boxes` ao iframe. Assim, `[[pagina#url-raiz]]` deixa de alcançar atributos `src` e
  `href` como texto cru, onde o `#` quebrava a URL no navegador.
- `html_editor_boxes_variaveis()` anota cada atributo global efetivamente resolvido com
  `data-c2f-orig-<atributo>` e `data-c2f-resolved-<atributo>`. No save,
  `htmlEditorReconstructVars()` repõe o marcador somente se o atributo ainda for igual ao valor
  resolvido; edição manual prevalece e as anotações transitórias são removidas.
- `extractUserHtml()` serializa elementos do usuário para um `<template>` antes de inseri-los no
  contêiner temporário. O conteúdo permanece inerte durante a extração, preservando o HTML sem
  repetir fetches de imagens ou mídia.

## Cobertura adicionada

- Vitest: round-trip de atributo não editado, preservação de edição manual e guarda de que layouts
  usam `htmlEditorRenderVars()` antes de abrir o editor.
- Vitest: extração de imagem e vídeo com marcador preserva o HTML por caminho inerte.
- PHPUnit: atributo `src` resolvido recebe original e valor resolvido para restauração posterior.

## Evidências coletadas

- `node --check gestor/assets/interface/html-editor.js` e
  `node --check gestor/assets/interface/html-editor-interface.js` — OK.
- `php -l gestor/bibliotecas/html-editor.php` e
  `php -l tests/Unit/PHP/HtmlEditorBaselineTest.php` — OK.
- Vitest focado (`html-editor-vars.test.js`, `html-editor-embed.test.js`) — **40/40**; guarda final
  de layouts — **9/9**.
- PHPUnit focado (`HtmlEditorBaselineTest`) — **10/10**, 12 asserções.
- `./c2f resources:sync --force` — **2.844** recursos, **237/237** Tailwind compilados, 0 problemas,
  exit 0. A publicação opcional em `dist/` foi ignorada por `PUBLIC_PATH`/DocumentRoot ausente, sem
  afetar `Data.json` nem a entrega pelo controlador estático.
- Vitest completo — **28/28** arquivos, **411/411** testes, exit 0.
- PHPUnit completo — **1.114/1.114** testes, **7.598** asserções, 4 skips esperados e 2 deprecações,
  exit 0.
- `git diff --check` — OK.

## Pendência de homologação

Após a sincronização/deploy do core no ambiente que serve o layout, abrir
`/admin-layouts/editar/?id=layout-conn2flow-site`, acionar o Editor Visual e confirmar que não há
GET para `/404` ou caminho contendo `[[pagina`; salvar uma edição e confirmar que os marcadores de
atributo originais permanecem no registro.

## Restrições

- Nenhum commit, push, release ou deploy remoto foi executado.
- Os artefatos gerados pelo pipeline foram mantidos no escopo: `asset-versions.json`,
  `schema-metadata.json` e `.tailwind-build-manifest.json`.