# BATCH-165 — O controlador do painel Admin Cron nunca chegava a rodar (REQ-038 / Pilar 4)

- **Status**: implementado-validado-aguardando-homologacao
- **Intake**: `conn2flow-site/sdd/human-requests/host-manager/req-038-ajustes-ui-hestiacp-admin-cron-e-saneamento-legado.md`
- **Data de abertura**: 2026-09-03
- **Classificação**: bugfix de frontend JS / ordem de carregamento
- **Modo de autonomia**: supervisionado
- **Lote irmão**: `BATCH-031` no `conn2flow-site` (Pilares 1, 2, 3, 5 e 6)

> **Nota de numeração**: a REQ-038 recomendava `BATCH-162`, mas esse número já está ocupado e
> `complete` (req-161, 404 de `{{thumbnail}}` no editor HTML). O maior lote registrado é o
> `BATCH-164`, então este lote foi aberto como `BATCH-165`.

## Objetivo

Fazer `/admin-cron/` voltar a desenhar a tabela de tarefas e a responder aos cliques em
"Nova tarefa" e "Sincronizar Módulos (.json)".

## Diagnóstico

O intake registra que "o script `admin-cron.js` nunca era injetado ou incluído no HTML gerado".
A leitura do código mostra uma causa diferente e mais específica, com o mesmo sintoma:

1. `admin_cron_painel()` **já chama** `gestor_pagina_javascript_incluir()`
   (`gestor/modulos/admin-cron/admin-cron.php:181`, presente desde o commit que criou o módulo).
   `gestor_pagina_javascript_incluir()` empilha a tag em `$_GESTOR['javascript-fim']` e
   `gestor_pagina_javascript()` a injeta no marcador `<!-- pagina#js -->`.
2. Em **todos** os layouts do gestor o marcador `<!-- pagina#js -->` vive dentro do `<head>` —
   no `layout-administrativo-tailwind`, linha 30 de 102, antes de qualquer `<body>`.
3. `admin-cron.js` é uma IIFE que lê o DOM na primeira instrução:
   `var painel = document.getElementById('admin-cron-painel'); if (!painel) return;`.
   Executando no `<head>`, `#admin-cron-painel` ainda não existe: a IIFE **sempre** retorna cedo,
   sem registrar um único ouvinte e sem desenhar a tabela.

O contraste confirma a leitura: `perfil-usuario`, o outro módulo do core sobre o mesmo layout
Tailwind, embrulha tudo em `$(document).ready(...)` e funciona. Nenhum outro módulo do core
executa lógica de DOM no corpo de uma IIFE sem espera.

O efeito é exatamente o relatado: página estática, tabela vazia e botões inertes — indistinguível,
para quem olha a tela, de um script que não foi carregado.

## Slices aprovados

- [x] Slice A — regressão automatizada que reproduz o defeito: avaliar o arquivo real com
      `document.readyState === 'loading'` e sem `#admin-cron-painel` no DOM, então inserir o painel,
      disparar `DOMContentLoaded` e exigir que os botões respondam.
- [x] Slice B — inicialização adiada até o DOM estar pronto, preservando o caminho síncrono para
      quando o script já for avaliado com o documento montado.
- [x] Slice C — guarda no `AdminCronReq032Test` para que a tag do script continue sendo enfileirada
      pela página do módulo.
- [x] Slice D — suítes Vitest e PHPUnit completas.

## Contrato de validação

1. A regressão do Slice A **deve falhar** contra o arquivo atual antes da correção (validação por
   mutação: sem ela, o teste não prova nada).
2. `php -l` no PHP alterado.
3. Vitest completo aprovado.
4. PHPUnit completo aprovado.
5. Evidências registradas aqui e em `sdd/validation/VALIDATION-CHECKLIST.md`.

## Arquivos previstos

- `gestor/modulos/admin-cron/admin-cron.js`
- `gestor/modulos/admin-cron/admin-cron.min.js`
- `tests/Unit/JS/admin-cron.painel.test.js`
- `tests/Unit/PHP/AdminCronReq032Test.php`

## Fora de escopo

- Mover o marcador `<!-- pagina#js -->` dos layouts para o fim do `<body>`: alcançaria todos os
  módulos do gestor de uma vez e é mudança de contrato de layout — exige change request própria.
- Os demais pilares da REQ-038, que vivem no `conn2flow-site` (`BATCH-031`).
