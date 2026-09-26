---
title: "API OAuth2"
description: "Renovação de tokens de acesso pelo endpoint HTTP."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/bibliotecas/oauth2.php
verified_at: e5b61f8e
---

# API OAuth2

`POST /_api/oauth/refresh` aceita JSON com `refresh_token`. Falta do campo retorna 400; token inválido ou expirado retorna 401. No sucesso, `data` contêm `access_token`, `token_type`, `expires_in`, `refresh_token` e `scope`. O refresh usa `oauth2_renovar_token()`.

A rota `/_api/oauth/` sem `refresh` redireciona para `oauth-authenticate/`. O campo `scope` é emitido, mas não há checagem de escopo por endpoint em `api_authenticate()`. Use permissões de perfil e o contrato do handler; veja [autenticação](auth.md) e [OAuth2](../libraries/oauth2.md).
