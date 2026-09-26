---
title: "admin-ia module"
description: "AI provider registration and connection tests."
section: reference
module: admin-ia
sources:
  - gestor/modulos/admin-ia/admin-ia.php
  - gestor/modulos/admin-ia/admin-ia.js
  - gestor/modulos/admin-ia/admin-ia.json
  - gestor/modulos/admin-ia/gemini/pt-br/data.json
  - gestor/modulos/admin-ia/gemini/en/data.json
  - gestor/db/migrations/20250930155415_create_servidores_ia_table.php
  - gestor/db/migrations/20250930155416_create_logs_testes_ia_table.php
verified_at: 98ac881d
---

# admin-ia module

This module registers AI servers/providers, protects their API keys in the database, tests connections, and shows test history. Editing a server also exposes the global list of available Gemini models.

## How to use

Open admin-ia/listar/, create at admin-ia/adicionar/, or edit at admin-ia/editar/?id=<number>. Set the name, type, and key, then choose whether it is the default for that type. The edit view can test, show the latest results, enable/disable, and delete. All three routes exist in pt-br and en; the raiz option redirects to the list if reached.

## Technical reference

The page switch handles raiz, listar-servidores, adicionar-servidor, and editar-servidor. AJAX handles salvar, editar, testar_conexao, historico_testes, excluir, ativar, desativar, and salvar_modelos_globais. OpenSSL keys encrypt the API key before storage and the edit view masks it; a Gemini test decrypts it, calls generateContent through cURL, and records outcome, error, and duration. JSON defines the Gemini URL/model; gemini/<language>/data.json provides model choices. The module has no widget, template, hook, or hooks.api.

servidores_ia has id_servidores_ia, nome, tipo, padrao, chave_api, status, and dates. logs_testes_ia has id_logs_testes_ia, id_servidores_ia, data_teste, sucesso, mensagem_erro, and tempo_resposta. Code also writes ia_user_models for id_usuarios=0; no migration for that table appears among core migrations.

## Confirmed limitations

> [!WARNING]
> Several lookups by numeric id in edit/test/delete/history concatenate a request value into SQL without escaping. Gemini is the only implemented test type, although the schema accepts other types. salvar_modelos_globais deletes global rows before reinserting them, without an explicit transaction.

> [!CAUTION]
> The edit JavaScript reads the padrao checkbox value without a guard when the checkbox is absent; this can prevent saving with it unchecked.

## See also

- [AI modes](admin-modos-ia.md)
- [AI prompts](admin-prompts-ia.md)
