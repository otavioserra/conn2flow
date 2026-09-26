---
title: "Módulo forms"
description: "Criação, renderização e envio de formulários públicos."
section: reference
module: forms
sources:
  - gestor/modulos/forms/forms.php
  - gestor/modulos/forms/forms.js
  - gestor/modulos/forms/forms.widget.php
  - gestor/modulos/forms/forms.widget.js
  - gestor/modulos/forms/forms.json
  - gestor/modulos/forms/resources
  - gestor/bibliotecas/formulario.php
  - gestor/db/migrations/20260210091358_create_forms_table.php
  - gestor/db/migrations/20260216100000_add_project_to_forms_table.php
  - gestor/db/migrations/20260706110000_add_html_and_css_to_forms_table.php
verified_at: 7bf08fe1
---

# Módulo `forms`

Define formulários reutilizáveis do site. O editor guarda os campos e a configuração em `fields_schema`; o widget monta o HTML público e a biblioteca `formulario` valida e processa o envio.

## Como usar

1. Abra `forms/` e `forms/adicionar/`. Informe nome, template e campos; ajuste descrição, destino, limites de acesso, CAPTCHA, e-mail e redirecionamentos quando necessários.
2. Confira a prévia e o código do widget. As rotas `forms/view/`, `forms/editar/` e `forms/clonar/` consultam, alteram e duplicam o registro. `forms/test-email-page/` fornece uma página de teste.
3. Insira o marcador na página desejada:

```html
<!-- widgets#forms->render({"form_id":"contato"}) < -->
<div>Mockup</div>
<!-- widgets#forms->render({"form_id":"contato"}) > -->
```

As rotas são as mesmas nos dois idiomas; o registro e o template são procurados no idioma da requisição.

## Referência técnica

O controlador trata `listar`, `visualizar`, `adicionar`, `editar`, `clonar` e `test-email-page`; status e exclusão são ações da interface compartilhada. O AJAX administrativo oferece `template-load`, `widget-preview`, `buscar-componentes` e `forms-render-editor-html`. Este último entrega a configuração JS usada no preview do editor. O JS do painel mantém campos ordenáveis, sincroniza o JSON oculto e solicita prévias com CSRF.

`forms` tem `id_forms` numérico, `id` de até 100 caracteres, `name` de até 255, `description`, `template_id`, `fields_schema` JSON, `module`, `plugin`, `project`, `language`, `status`, `version`, datas e flags de atualização. Migrações posteriores acrescentam HTML, CSS e recursos compilados. `(id, language)` é único. A lista de campos contém objetos com `type`, `name`, `label`, `required`, `placeholder` e `options`; o schema também aceita `field_name`, `field_email`, `form_action`, `access_max_simple`, `access_max`, `force_recaptcha`, `email` e `redirects`.

O widget exige registro ativo. Usa HTML próprio ou o template ativo de alvo `forms`; herda CSS pré-compilado do template quando faltar no formulário. Repete o bloco `<!-- item < -->` para cada campo e substitui `item#label`, `item#name`, `item#type`, `item#value`, `item#options`, `form_id`, `form_action` e `force_recaptcha`. Blocos `type-select`, `type-textarea`, `type-radio`, `type-checkbox` e `type-input` controlam o tipo de campo. Templates fornecidos: `forms-contato-basico`, `forms-newsletter-newsletter`, `forms-registro-usuario`, `forms-pesquisa-satisfacao` e `forms-suporte-tecnico`. O JSON não declara hooks nem `hooks.api` próprios.

O envio usa `forms-submissions-process/` por padrão. O widget chama `formulario_controlador`, que injeta a configuração pública; seu JS busca essa configuração via `forms-render-editor-html` somente no preview quando ela falta. A biblioteca `formulario_processador` verifica `_formId`, honeypot, timestamp, acesso, CAPTCHA, campos e limites; insere em `forms_submissions`, registra acesso e tenta e-mail conforme o schema. Valores de campos `password` são gravados vazios na submissão, embora permaneçam no POST durante a requisição.

## Limitações confirmadas

> [!WARNING]
> Um `form_action` personalizado muda o destino do POST. Ele precisa apontar para um processador compatível; o caminho padrão é `forms-submissions-process/`. O timestamp antirreplay rejeita formulários enviados mais de dois dias após a geração.

> [!CAUTION]
> O template é uma dependência do runtime: se não houver HTML próprio nem template ativo no idioma, o widget retorna vazio. Os limites e o CAPTCHA dependem também da configuração central e do estado de acesso; `force_recaptcha` sozinho não descreve toda a política.

## Veja também

- [Envios de formulários](forms-submissions.md)
- [Formulários de busca](forms-search.md)
