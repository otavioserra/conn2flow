# BATCH-210 — Autoria em resources e compilação seletiva (req-202)

**Status:** complete  
**Repositório:** `conn2flow`  
**Origem:** [req-202](../human-requests/req-202.md)

## Objetivo

Migrar as oito tabelas centrais restantes para autoria bilíngue em `gestor/resources/`, compilar seus Data.json deterministicamente e permitir builds seletivos sem executar Tailwind quando não necessário. Preservar as credenciais e os dados de usuário existentes conforme o contrato de `usuarios`.

## Escopo

1. Criar as sementes `pt-br`/`en` para módulos, grupos/operações, usuários, perfis e categorias; registrar os arquivos em `resources.map.php`.
2. Declarar as oito tabelas em `tables_config.json`, com chaves naturais, `sync_resources` e metadados de preservação especificados pela req-202.
3. Ajustar o compilador para gerar e ordenar os dados e `schema-metadata.json` de forma determinística, sem reserva manual dos oito Data.json.
4. Implementar `--only`, `--skip-css` e `--resource` no gerador e propagar as opções por `c2f resources:sync`.
5. Usar o manifest Tailwind para não executar o compilador quando checksums relevantes permanecerem iguais.
6. Cobrir migração, filtros, integridade dos artefatos, propagação de argumentos e cache com testes focados.

## Critérios de aceite

- As sementes bilíngues reproduzem os registros dos Data.json legados; os oito arquivos compilados mantêm conteúdo válido e ordenação estável.
- `usuarios` é `insert_only` e preserva os campos definidos na requisição quando modificados pelo usuário.
- `schema-metadata.json` representa o contrato declarativo das oito tabelas.
- `resources:sync` encaminha as opções suportadas; seletores inválidos falham com mensagem/exit code apropriados.
- `--only` limita as tabelas alvo, `--resource` limita a um recurso e `--skip-css` não invoca Tailwind.
- O caminho de cache pula Tailwind para recursos inalterados e recompila após mudança nos checksums de template/classes.
- Testes focados e `git diff --check` passam; nenhum deploy remoto ou produção faz parte deste lote.

## Sequência e validação

1. Inspecionar os dados e regras atuais; capturar baseline dos oito Data.json.
2. Migrar fontes e contrato; validar JSON e comparação com o baseline compilado.
3. Implementar seleção/encaminhamento CLI e cobrir as opções com testes focados.
4. Implementar cache Tailwind e cobrir hit/miss do manifest.
5. Rodar compilação seletiva e suíte focada; conferir somente diffs pertencentes ao batch.

## Estado

- [x] Requisição e governança SDD lidas; divergência de estado intake/índice identificada.
- [x] Fontes, contrato e compilador migrados; as 16 sementes foram comparadas aos Data.json legados.
- [x] CLI seletivo coberto por testes e compilação real dos oito alvos.
- [x] Cache Tailwind incremental coberto por hit, miss, seleção multilíngue e preservação do manifest.
- [x] Evidências registradas em `sdd/validation/VALIDATION-CHECKLIST.md`.
