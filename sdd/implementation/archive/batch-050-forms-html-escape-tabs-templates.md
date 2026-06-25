# BATCH-050 - Forms: escape HTML, abas aninhadas e novos templates

Data: 2026-06-22
Status: complete
Intake: `sdd/human-requests/req-050.md`

## Escopo

- Adicionar suporte opcional a `html_specialchars` em `html_editor_componente()`.
- Ativar o escape no fluxo administrativo do módulo `forms`.
- Isolar as abas externas/internas do Forms via `context` no Fomantic UI.
- Criar quatro novos templates Tailwind CSS em `pt-br` e `en`.
- Registrar os templates no manifest e regenerar recursos pelo compilador local.

## Implementação

- `gestor/bibliotecas/html-editor.php`
  - Aceita `html_specialchars`.
  - Quando habilitado, troca `#pagina-html#` e `#pagina-html-extra-head#` por `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` antes do retorno do componente.

- `gestor/modulos/forms/forms.php`
  - `forms_prepare_editor_page()` passa `html_specialchars`, `html` e `html_extra_head` para o editor.
  - O HTML continua bruto no submit/banco; o escape fica restrito ao preenchimento do `<textarea>` do editor.

- `gestor/modulos/forms/forms.js`
  - `.menuForms .item` usa `context: 'parent'`.
  - `.menuFormsTemplate .item` usa `context: '[data-tab="forms-template"]'`.

- `gestor/modulos/forms/resources/{pt-br,en}/templates/`
  - Criados `forms-newsletter-newsletter`.
  - Criados `forms-registro-usuario`.
  - Criados `forms-pesquisa-satisfacao`.
  - Criados `forms-suporte-tecnico`.

- `gestor/modulos/forms/forms.json`
  - Registrados os quatro templates em `pt-br` e `en`.
  - Compilador `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` executado com sucesso.

## Validação

- `php -l gestor/bibliotecas/html-editor.php` -> OK.
- `php -l gestor/modulos/forms/forms.php` -> OK.
- `node --check gestor/modulos/forms/forms.js` -> OK.
- Validação Node de `forms.json` e presença dos 8 templates -> OK.
- Validação Node dos marcadores `item`/`type-*` nos 8 templates -> OK.
- Compilador de recursos -> OK, 2222 recursos e nenhum problema detectado.
- `composer test` -> OK, 40 testes, 112 assertions, 4 skipped, 1 deprecation.
- `npm run test` -> falhou no sandbox por `Access is denied` ao carregar `vitest.config.js`; reexecutado com permissão escalada -> OK, 2 arquivos e 3 testes.

## Pendências runtime

- Validar no navegador o carregamento de HTML contendo `<textarea>` dentro do CodeMirror.
- Confirmar clique na sub-aba "Widget" sem ocultar a aba pai "Template".
- Conferir disponibilidade e preview/publicação dos 4 novos modelos em ambos os idiomas.
