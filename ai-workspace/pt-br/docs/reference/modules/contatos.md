---
title: "Módulo contatos"
description: "Página pública de contato baseada no sistema de formulários."
section: reference
module: contatos
sources:
  - gestor/modulos/contatos/contatos.php
  - gestor/modulos/contatos/contatos.js
  - gestor/modulos/contatos/contatos.json
  - gestor/modulos/contatos/resources
  - gestor/bibliotecas/formulario.php
  - gestor/db/migrations/20260210091358_create_forms_table.php
  - gestor/db/migrations/20260210091400_create_forms_submissions_table.php
verified_at: a9a226e0
---

# Módulo contatos

Entrega a página pública de contato usando a definição form-contact do sistema de formulários. O envio é processado pela biblioteca formulario e usa os registros de forms e forms_submissions; o módulo não possui uma tabela de contatos própria.

## Como usar

O visitante abre contact/, preenche nome, e-mail e mensagem e envia. O esquema define os três campos como obrigatórios e redireciona sucesso para contact/success/ e erro para contact/. Ambas as páginas têm without_permission no JSON e existem em pt-br e en. A página de sucesso inclui meta robots noindex. O operador consulta os envios no módulo forms-submissions.

## Referência técnica

O controlador trata somente a opção contact, que chama formulario_controlador() com formId form-contact. Não há caso AJAX ativo no controlador de contatos; o comportamento de envio vem da biblioteca de formulários e da classe de formulário na página. A opção contact-success é um recurso de página estático, sem ramo próprio no switch PHP. O JSON semeia forms.id=form-contact com fields_schema contendo field_name=name, field_email=email, fields e redirects. Também fornece os componentes base-email e prepared-email em pt-br e prepared-email em en; não declara widget, template, hook ou hooks.api próprio.

forms guarda id, name, description, template_id, fields_schema JSON, module, plugin, language, status, version, datas e flags de modificação; (id, language) é único. forms_submissions guarda form_id, name, id, fields_values JSON, language, status, version e datas; (id, language) é único e form_id é indexado.

## Limitações confirmadas

> [!CAUTION]
> contact/success/ não passa por uma rotina PHP de contato; depende do HTML semeado como página. O armazenamento, validação e eventual entrega de e-mail pertencem ao processador de formulários, não a um fluxo independente deste módulo.

## Veja também

- [Formulários](forms.md)
- [Envios de formulários](forms-submissions.md)
