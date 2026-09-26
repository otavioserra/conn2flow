---
title: "Módulo forms-submissions"
description: "Consulta, estado e resposta dos envios de formulários."
section: reference
module: forms-submissions
sources:
  - gestor/modulos/forms-submissions/forms-submissions.php
  - gestor/modulos/forms-submissions/forms-submissions.ajax.public.php
  - gestor/modulos/forms-submissions/forms-submissions.js
  - gestor/modulos/forms-submissions/forms-submissions.json
  - gestor/modulos/forms-submissions/resources
  - gestor/bibliotecas/formulario.php
  - gestor/db/migrations/20260210091400_create_forms_submissions_table.php
  - gestor/db/migrations/20260213100000_add_form_status_to_forms_submissions.php
  - gestor/db/migrations/20260211140359_add_forms_security_tables.php
verified_at: 7bf08fe1
---

# Módulo `forms-submissions`

Reúne os envios dos formulários públicos, permite consultar seus campos, mudar o estado de atendimento e responder por e-mail. A rota pública de processamento delega à biblioteca `formulario`.

## Como usar

Abra `forms-submissions/` para listar. Em `forms-submissions/view/?id=<slug>`, confira os valores, o estado do envio e o e-mail detectado entre os campos do formulário. Escolha `new` ou `responded` para atualizar o atendimento, ou informe endereço e mensagem para enviar uma resposta. A página pública `forms-submissions-process/` recebe o POST dos formulários; não é uma tela de operação. As rotas são iguais nos dois idiomas.

## Referência técnica

O controlador administrativo oferece `listar` e `visualizar`. Status e exclusão genéricos são configurados pela interface; `form_status` é um estado distinto, com opções `new` e `responded` localizadas no JSON. O AJAX `update-status` verifica se o valor pertence a essas opções. `reply` exige id, endereço válido e mensagem, lê o envio, monta o componente `prepared-response-email`, processa imagens e tenta enviar pelo serviço de comunicação. O JS administrativo chama essas ações pela URL atual.

O endpoint público tem `ajaxOpcao=forms-process`; `forms_submissions_ajax_forms_process()` inclui `formulario` e chama `formulario_processador()`. A biblioteca busca o formulário pelo id e idioma, valida honeypot, idade do timestamp, acesso, CAPTCHA e campos, e persiste o envio. A configuração de limites, destinatários e redirecionamentos vem do `fields_schema` de `forms`, com padrões centrais. Não há widget público nem template próprio em `forms-submissions`; o módulo fornece o componente de e-mail.

A tabela `forms_submissions` contém `id_forms_submissions` numérico, `form_id` e `id` de até 100 caracteres, `name` de até 255, `fields_values` JSON, `form_status` de até 100 com padrão `new`, `language`, `status`, `version`, `created_at` e `updated_at`. Há unicidade em `(id, language)` e índices em `form_id`, `language` e `form_status`. `fields_values` contém `fields` com pares `name`/`value`, estado do e-mail e, após respostas, `responses` com `message`, `date`, `email` e `status`. O JSON do módulo não declara hooks nem `hooks.api`.

## Limitações confirmadas

> [!WARNING]
> `reply` acrescenta a resposta ao histórico e define `form_status=responded` mesmo quando o envio do e-mail falha; nesse caso retorna `warning` e guarda `status=failed` na resposta. O estado de atendimento não prova entrega da mensagem.

> [!CAUTION]
> A consulta da definição do formulário em `reply` concatena `form_id` e idioma na cláusula SQL sem `banco_escape_field`. O envio costuma ser criado pelo processador, mas esse caminho deve ser tratado como risco ao manipular dados não confiáveis. O endpoint de status usa id numérico escapado e valida o novo valor.

## Veja também

- [Formulários](forms.md)
- [Formulários de busca](forms-search.md)
