---
name: c2f-widget-development
description: Use ao criar ou corrigir widgets Conn2Flow, seus recursos, contratos AJAX e substituição de variáveis item#var.
user-invocable: false
---

# Desenvolvimento de widgets Conn2Flow

1. Injete CSS, head e JavaScript por `gestor_pagina_recursos_incluir([...])`; centralize a inclusão e preserve a deduplicação do helper.
2. Não chame novamente controladores de recursos que o render do widget já inclui.
3. No frontend envie `ajaxOpcao`; no backend trate a mesma ação em `$_GESTOR['ajax-opcao']` e evite nomes reservados pelo fechamento AJAX genérico.
4. Para tokens de item, aceite wrappers opcionais com `/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/` e substitua todas as ocorrências.
5. Mantenha blocos de repetição, vazio e controles compatíveis com o contrato do AI mode/template do widget.
6. Valide duas renderizações na mesma página para detectar duplicação de assets, além do caminho AJAX feliz e de erro.
