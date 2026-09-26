---
title: "API de módulos"
description: "Despacho autenticado para hooks de módulos."
section: reference
sources:
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# API de módulos

Uma rota `/_api/{modulo-id}/{action}` que não coincide com famílias internas chega a `api_handle_modulo()`. O id é normalizado para letras minúsculas, dígitos, hífen e sublinhado. A rota exige bearer token válido. O handler une dados de query string e corpo JSON, com o corpo vencendo chaves duplicadas, e chama o hook `api` do módulo alvo.

Hook ausente retorna 404. Um resultado com `erro` produz o código informado ou 500; sucesso usa `dados`, `mensagem` e `code` quando presentes. O método HTTP da ação não é imposto pelo despachante geral; cada módulo deve validar seu próprio contrato. Veja [hooks](../../concepts/hooks.md).
