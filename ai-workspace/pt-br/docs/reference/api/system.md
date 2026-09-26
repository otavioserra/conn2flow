---
title: "API de atualização do sistema"
description: "Ações da atualização administrativa por sessão."
section: reference
sources:
  - gestor/controladores/api/api.php
  - gestor/controladores/atualizacoes/atualizacoes-sistema.php
verified_at: e5b61f8e
---

# API de atualização do sistema

`POST /_api/system/update` exige bearer token e campo `action`. Ações aceitas: `start`, `deploy`, `db`, `finalize`, `status` e `cancel`. As cinco últimas exigem `sid` da sessão iniciada. A ação `start` repassa parâmetros como `domain`, `tag`, `dry_run`, `only_files`, `only_db`, `backup` e `tables` ao controlador de atualizações; `domain` usa o nome do servidor quando omitido.

A resposta usa o [envelope JSON](index.md). Erro de sessão inválida retorna 400; demais erros do atualizador, 500. Como a ação `status` também usa POST e exige `sid`, uma consulta GET de progresso não corresponde ao contrato implementado. O endpoint inclui o controlador de atualizações no processo da requisição.
