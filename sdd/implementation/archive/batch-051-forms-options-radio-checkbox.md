# BATCH-051 - Forms: escape global, opções value/label, radio e checkbox

Data: 2026-06-22
Status: complete
Intake: `sdd/human-requests/req-051.md`

## Escopo

- Tornar o escape de `html` e `html_extra_head` padrão no `html_editor_componente()` quando esses parâmetros forem informados.
- Remover a dependência do parâmetro explícito `html_specialchars` no módulo Forms.
- Permitir opções `value:label` e `value|label` no renderer público de Forms.
- Adicionar suporte público e administrativo aos tipos `radio` e `checkbox`.
- Atualizar os 5 templates de Forms em `pt-br` e `en` para blocos `type-radio` e `type-checkbox`.

## Implementação

- `gestor/bibliotecas/html-editor.php`
  - `#pagina-html#` e `#pagina-html-extra-head#` agora são escapados automaticamente com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` quando `html`/`html_extra_head` existem nos parâmetros.

- `gestor/modulos/forms/forms.php`
  - `forms_prepare_editor_page()` mantém `html` e `html_extra_head`, mas não envia mais `html_specialchars`.

- `gestor/modulos/forms/forms.widget.php`
  - `forms_widget_options_html()` interpreta `|` e `:` para separar `value` e label.
  - `radio` e `checkbox` renderizam `<input>` dentro de `<label>`.
  - `checkbox` usa `name[]`.
  - `forms_widget_render_field()` reconhece `type-radio` e `type-checkbox` e restringe `type-input` aos tipos simples.

- `gestor/modulos/forms/forms.js`
  - Dropdown de tipos inclui `radio` e `checkbox`.
  - `.forms-field-options` aparece apenas para `select`, `radio` e `checkbox`.
  - Placeholder instrutivo localizado foi adicionado à textarea de opções.
  - Leitura do tipo usa `dropdown('get value')` com fallback para `.val()`, evitando inconsistência do Fomantic.

- Templates físicos
  - Atualizados `forms-contato-basico`, `forms-newsletter-newsletter`, `forms-registro-usuario`, `forms-pesquisa-satisfacao` e `forms-suporte-tecnico` em `pt-br` e `en`.
  - Todos receberam blocos `type-radio` e `type-checkbox`.

- Modos IA de Forms
  - `resources/pt-br/ai_modes/forms/forms.md` e `resources/en/ai_modes/forms/forms.md` foram expandidos usando o modo IA de `menus` como referência de contrato.
  - Os prompts agora documentam entrada/saída em markdown, placeholders sem `@`, blocos `type-*`, variáveis globais, contrato do `<form>` e uso correto de `[[item#options]]`.

## Validação

- `php -l gestor/bibliotecas/html-editor.php` -> OK.
- `php -l gestor/modulos/forms/forms.php` -> OK.
- `php -l gestor/modulos/forms/forms.widget.php` -> OK.
- `node --check gestor/modulos/forms/forms.js` -> OK.
- Teste PHP via stdin para `forms_widget_options_html()` com select, radio e checkbox -> OK.
- Validação Node dos marcadores `type-radio`/`type-checkbox` nos 10 templates -> OK.
- Validação Node dos tokens obrigatórios nos modos IA `forms.md` pt-br/en -> OK.
- `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` -> OK, 2222 recursos e nenhum problema detectado.
- `composer test` -> OK, 40 testes, 112 assertions, 4 skipped, 1 deprecation.
- `npm run test` -> falhou no sandbox por `Access is denied` ao carregar `vitest.config.js`; reexecutado com permissão escalada -> OK, 2 arquivos e 3 testes.

## Pendências runtime

- Validar no navegador o CRUD de campo alternando entre tipos simples, select, radio e checkbox.
- Publicar/renderizar um formulário com opções `sp:São Paulo` e `rj|Rio de Janeiro`.
- Confirmar que HTML com `<textarea>` segue carregando corretamente no CodeMirror por outros módulos que passam `html` ao editor.
