---
title: "API de módulos distribuídos"
description: "Canal HMAC entre instalação central e distribuída."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/controladores/api/api-module-central.php
  - gestor/controladores/api/api-module-distributed.php
  - gestor/bibliotecas/modulo-distribuido.php
verified_at: e5b61f8e
---

# API de módulos distribuídos

O roteador aceita `/_api/modulo-distribuido/{slug}/{acao}` e a variante `/_api/v1/modulo-distribuido/{slug}/{acao}`. O handler valida a assinatura HMAC do corpo via `X-C2F-Signature` usando o segredo configurado no canal, sem bearer OAuth para o transporte entre instalações.

`signin`, `refresh` e `permissao` são atendidos pelo lado central; `db` e `ping` pelo distribuído. O roteador também encaminha `ativar` ao central, mas o switch do handler central não oferece essa ação e retorna 404. `db` executa a operação empacotada no banco local do cliente. Use apenas o cliente do canal que assina a mensagem; não envie credenciais ou SQL avulso. Veja [biblioteca do módulo distribuído](../libraries/modulo-distribuido.md).
