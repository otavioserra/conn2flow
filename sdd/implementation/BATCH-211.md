# BATCH-211 — Segregação de recursos globais vs multilíngues (req-203)

**Status:** complete  
**Repositório:** `conn2flow`  
**Origem:** [req-203](../human-requests/req-203.md)

## Objetivo

Manter em `gestor/resources/` as sementes de tabelas sem coluna `language`, compilando-as uma única vez, e preservar em `{lang}/` apenas recursos multilíngues.

## Escopo

1. Mover `users.json`, `user_profiles_modules.json`, `user_profiles_modules_operations.json` e `categories.json` para a raiz de `gestor/resources/` e remover as cópias por idioma.
2. Marcar as quatro tabelas com `language_agnostic: true` e manter seus contratos/chaves naturais existentes.
3. Adaptar a normalização e coleta dinâmica para leitura na raiz sem iterar por idiomas nem sintetizar `language`.
4. Retirar as quatro referências do `resources.map.php`.
5. Validar a compilação completa, contagens e ausência de duplicações; cobrir a coleta agnóstica com testes PHPUnit focados.

## Critérios de aceite

- As quatro sementes existem somente na raiz de `gestor/resources/` e preservam os registros existentes.
- Os quatro contratos declaram `language_agnostic: true` e os recursos multilíngues continuam no mapa por idioma.
- O compilador lê cada seed global uma vez, sem acrescentar coluna `language`, e gera Data.json sem duplicações.
- Testes focados, sintaxe e `git diff --check` passam.

## Estado

- [x] REQ-203 lida; divergência de estado do batch identificada e corrigida no índice.
- [x] Teste de regressão prova que o compilador atual não lê metadados globais na raiz.
- [x] Sementes movidas; mapa e contrato atualizados.
- [x] Compilador adaptado e coleta global validada.
- [x] Validações e evidências registradas.

## Evidências

- As sementes oficiais na raiz têm 1 usuário, 37 vínculos perfil-módulo, 3 vínculos perfil-operação e 1 categoria; não há cópias em `pt-br/` ou `en/`. As quatro tabelas mantêm suas chaves naturais e declaram `language_agnostic: true`.
- `Req203LanguageAgnosticResourcesTest`: o coletor preserva integralmente os registros e omite `language`; `main()` executa as oito etapas com `--only`, `--skip-css`, `--no-origin-update` e `--no-assets` em diretórios temporários, gerando os Data.json esperados sem duplicações.
- `Req202ResourceCompilerTest`, `Req202ResourcesSyncTest` e `Req203LanguageAgnosticResourcesTest`: 7 testes, 68 asserções, exit 0; duas depreciações do PHPUnit.
- `php -l` nos dois PHP alterados, parse de `tables_config.json` e `git diff --check`: exit 0. O Git reportou apenas avisos de conversão LF/CRLF existentes no worktree.
- A CLI não foi executada diretamente contra o checkout, pois seus `gestor/db/data/*.json` já continham alterações locais. O pipeline completo foi validado com os mesmos contratos/seeds em diretório temporário; nenhum deploy foi executado.
