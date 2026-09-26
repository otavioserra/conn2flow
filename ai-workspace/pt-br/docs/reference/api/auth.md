---
title: "API de autenticação"
description: "Login móvel, logout, usuário atual e módulos disponíveis."
section: reference
sources:
  - gestor/controladores/api/api-auth.php
  - gestor/controladores/api/api.php
verified_at: e5b61f8e
---

# API de autenticação

`api-auth.php` despacha quatro subrotas de `/_api/auth/`. `login` recebe POST JSON com `usuario` e `senha`. Credenciais ausentes retornam 400; inválidas, 401; limitação de tentativas ou sessões, 429. No sucesso, emite tokens OAuth2 e dados do usuário/perfil. O controle de tentativas grava cache em disco sob logs da API.

`logout` exige POST e bearer token. Revoga o access token atual e aceita `refresh_token` opcional no corpo. `me` (normalmente GET; o handler aceita outros métodos) devolve o usuário e perfil do token. `modules` (normalmente GET; o handler aceita outros métodos) monta grupos e módulos segundo as permissões do perfil, no idioma resolvido pelo handler.

Envie `Authorization: Bearer <token>` nas rotas autenticadas. Tokens inválidos ou usuários inativos retornam 401. A resposta segue o [envelope da API](index.md). O `scope` armazenado no token não é aplicado como autorização por rota; veja a [biblioteca OAuth2](../libraries/oauth2.md). Questões de segurança em acompanhamento: req-181.
