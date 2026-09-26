---
title: "API HTTP"
description: "Rotas HTTP do Core, autenticação, respostas e famílias de endpoints."
section: reference
sources:
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# API HTTP

O front controller encaminha `/_api/...` a `api_route_request()`. As rotas públicas `status` e `health` respondem JSON; as demais regras dependem do handler. `api_rate_limit_check()` roda antes do despacho: usa a tabela `api_rate_limits`, com padrão de 100 requisições por 3600 segundos por rota/sujeito. Excesso devolve 429; falha ao avaliar o limite devolve 503.

`api_response_success()` devolve JSON com `status: success`, `data` e `message`; `api_response_error()` usa `status: error`, `message` e, quando informado, `details`. O controlador aceita JSON em `api_get_request_body()`; JSON inválido retorna 400. CORS lê `api.cors-origins` da configuração; preflight OPTIONS é tratado antes do despacho.

| Família | Rota | Contrato |
|---|---|---|
| Autenticação | [auth](auth.md) | Login móvel, logout, perfil e módulos |
| OAuth | [oauth](oauth.md) | Renovação de access token |
| Projetos | [project](project.md) | Upload e recuperação |
| Sistema | [system](system.md) | Atualização por sessão |
| Módulos | [modules](modules.md) | Hooks de módulos |
| Canal distribuído | [distributed](distributed.md) | Ações entre instalações |
| Gateways | [gateways](gateways.md) | Controlador separado `/_gateways/` |

`/_api/oauth/` sem `refresh` redireciona para `oauth-authenticate/`; não responde JSON de autorização. Consulte a [biblioteca OAuth2](../libraries/oauth2.md) para o armazenamento de tokens.
