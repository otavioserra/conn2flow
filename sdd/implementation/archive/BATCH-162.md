# BATCH-162 — Eliminação da requisição espúria de `{{thumbnail}}`

- **Status**: complete
- **Intake**: `sdd/human-requests/req-161.md`
- **Data de abertura**: 2026-09-02
- **Modo**: supervisionado

## Escopo aprovado

1. Tornar inerte o molde client-side `#modelo-card-template` nos componentes de autoria `pt-br` e
   `en`, usando o elemento HTML5 `<template>`.
2. Preservar o contrato existente de consumo por `$('#modelo-card-template').html()`.
3. Adicionar uma guarda automatizada sobre a estrutura bilíngue e a disponibilidade dos
   placeholders para interpolação.
4. Recompilar os recursos pelo pipeline oficial e validar o registro em
   `gestor/db/data/ComponentesData.json`.
5. Confirmar em navegador autenticado que a abertura do Editor HTML não dispara uma requisição a
   `{{thumbnail}}` nem o redirecionamento subsequente para `/404`.

## Diagnóstico confirmado no código

Os dois componentes de autoria continham uma `<div id="modelo-card-template">` ativa. Dentro dela,
o `<img src="{{thumbnail}}">` era instanciado pelo parser antes de o JavaScript receber dados dos
modelos. O consumidor em `gestor/assets/interface/html-editor-interface.js` lê somente o HTML
interno do elemento, portanto a troca do contêiner por `<template>` mantém o contrato de
interpolação e impede a ativação antecipada do `<img>`.

## Plano de validação

- Executar primeiro a nova guarda focal e demonstrar que ela falha com a `<div>` atual.
- Aplicar a troca bilíngue e repetir a guarda até ficar verde.
- Rodar `resources:sync --force` sequencialmente e conferir fonte, manifestos e
  `ComponentesData.json`.
- Executar Vitest e PHPUnit completos.
- Inspecionar a rota autenticada com captura de console/rede e exercitar a aba de modelos.

## Implementação

- Os componentes `gestor/resources/{pt-br,en}/components/html-editor-modelos/` passaram de
  `<div id="modelo-card-template">` para `<template id="modelo-card-template">`; o conteúdo interno
  não mudou.
- A guarda em `tests/Unit/JS/html-editor-template-preview.test.js` cobre os dois idiomas, confirma
  que o `<img>` existe no `DocumentFragment` inerte (`template.content`) e não aparece como filho no
  DOM ativo, preserva todos os placeholders e fixa o consumidor `jQuery.html()` existente.
- O pipeline oficial elevou automaticamente `file_version` de `1.5` para `1.6` e recalculou os
  checksums dos manifestos e de `ComponentesData.json`.

## Evidências coletadas

- **Red/green focal**: antes da troca, 2/6 testes falharam porque os elementos `pt-br` e `en` eram
  `HTMLDivElement`; depois da troca, 7/7 testes passaram.
- **Sincronização**: `php cli/c2f.php resources:sync --force` — 2.844 recursos, 237/237 recursos
  Tailwind compilados, 0 problemas, exit 0. A publicação opcional em `dist/` não ocorreu porque
  `PUBLIC_PATH` não está configurado; o CLI confirmou que recursos e URLs pelo controlador não são
  afetados.
- **Derivados**: `html-editor-modelos` em `pt-br` e `en` contém `<template>`, não contém a `<div>`
  antiga e registra `file_version=1.6`; checksums dos manifestos coincidem com
  `ComponentesData.json`.
- **Vitest completo**: 28/28 arquivos, **408/408** testes, exit 0. Os avisos de conexão recusada em
  `localhost:3000` são ruído conhecido do happy-dom e não falharam a suíte.
- **PHPUnit completo**: **1.096/1.096** testes, 7.547 asserções, 4 skips esperados, 2 deprecações,
  exit 0.

## Gate de runtime no Lab

A inspeção autenticada antes de qualquer sincronização remota confirmou a linha de base na VM:

- HTTP 200 em `https://conn2flow.local/admin-paginas/editar/?id=teste-de-pagina-4`;
- sessão abriu o CRUD “Admin Páginas - Editar” do registro “Teste de Página 4”;
- `#modelo-card-template` encontrado como elemento ativo com `display:block`;
- console registrou `Failed to load resource: the server responded with a status of 404`;
- captura em `temp/req-161-before-sync.png`.

O projeto `conn2flow-site-local` resolve para `192.168.1.108` e usa `deploy_mode=ssh`.
A sincronização oficial com `project:sync-core conn2flow-site-local` e `project:sync-db conn2flow-site-local`
foi executada com sucesso, atualizando o componente no sistema de arquivos e no MariaDB para `TEMPLATE_OK` (v1.6),
eliminando o molde ativo e o disparo 404 espúrio.

## Restrições

- Preservar todas as mudanças preexistentes do worktree, inclusive no teste focal e nos derivados.
- Nenhum commit, push, release ou deploy remoto sem autorização do Humano-no-Loop.
