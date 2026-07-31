---
name: c2f-widget-development
description: Use ao criar ou corrigir widgets Conn2Flow, seus recursos, contratos AJAX e substituição de variáveis item#var.
---

# Desenvolvimento de widgets

1. Injete CSS, head e JavaScript por `gestor_pagina_recursos_incluir([...])`; preserve a deduplicação do helper.
2. Não chame novamente controladores de recursos que o render do widget já inclui.
3. Envie `ajaxOpcao` no frontend e trate a mesma ação em `$_GESTOR['ajax-opcao']`; evite nomes reservados pelo AJAX genérico.
4. Aceite wrappers opcionais nos tokens com `/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/`.
5. Valide duas renderizações na mesma página e os caminhos AJAX feliz e de erro.
