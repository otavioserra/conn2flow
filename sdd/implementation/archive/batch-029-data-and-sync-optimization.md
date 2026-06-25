# BATCH-029 - Reestruturação e Otimização de Dados e Sincronização

## Escopo do Lote
Este lote implementa a reestruturação arquitetural de dados do Conn2Flow, separando a compilação de metadados em desenvolvimento da persistência genérica e otimizada em produção, além de padronizar colunas de controle, unificar logs via biblioteca oficial do sistema, suportar ganchos locais `data-hooks.php` e deleção imperativa de dados.

> **Nota de escopo (DEC-042)**: o req §1 listava 7 tabelas para migrar `linguagem_codigo`→`language`, mas todas já usavam `language`. A única tabela pendente era `variaveis` — migrada nesta rodada sob aprovação explícita do Engenheiro Chefe Humano. A sub-chave de metadados dentro de `"tabela"` foi nomeada `config` (decisão do Chefe).

## Checklist de Implementação

### 1. Banco de Dados e Migrações (Phinx)
- [x] Mapear todas as migrações antigas que usam `linguagem_codigo` (apenas `create_variaveis_table` + índice em `alter_recursos_add_plugin_id`; as 7 tabelas do req já usavam `language`).
- [x] Alterar as classes de migração sob [migrations/](../../gestor/db/migrations/) para usar `language` (criação da `variaveis` + índice composto `idx_variaveis_plugin_id_language`).
- [x] Nova migração corretiva idempotente `20260705100000_rename_variaveis_linguagem_codigo_to_language.php` (`renameColumn` com guards `hasColumn`/`hasTable`).
- [x] Atualizar todas as referências à coluna em todo o código (`configuracao.php`, `gestor.php`, `plugins-installer.php`, gerador, atualizador, plugin-banco) e regenerar `VariaveisData.json` (1488 entradas).

### 2. Metadados nos Arquivos de Origem (Dev)
- [x] Adicionar o bloco `"tabela"."config"` com regras de sincronização (`strategy`, `natural_key_columns`, `preserve_on_user_modified`, `insert_only`) nos JSONs de 13 módulos (admin-paginas/layouts/componentes/templates, forms, modulos, modulos-grupos, modulos-operacoes, usuarios, usuarios-perfis, admin-prompts-ia, admin-modos-ia, admin-categorias).
- [x] Criar o arquivo de metadados das tabelas globais [tables_config.json](../../gestor/resources/tables_config.json) (variaveis, usuarios_perfis_modulos, usuarios_perfis_modulos_operacoes, alvos_ia).
- [x] `resources.map.php` permaneceu inalterado (apenas direcionamento de arquivos).
- [x] Implementar a chave `"deletar"` nos blocos `"tabela"` locais e no global (placeholder `[]`/`{}` pronto para uso).

### 3. Refatoração do Script Gerador (`atualizacao-dados-recursos.php`)
- [x] Motor de varredura genérico (Registry Pattern) que consolida `tabela.config` dos módulos + `tables_config.json` global. *Nota: a geração específica dos `*Data.json` por recurso foi preservada (decisão de baixo risco — DEC-042); o Registry aplica-se à consolidação do contrato.*
- [x] Ler e consolidar as regras de `"tabela"` dos módulos e do arquivo global.
- [x] Agregar e consolidar as listas de deleção imperativa.
- [x] Exportar o arquivo [schema-metadata.json](../../gestor/db/data/schema-metadata.json) (17 tabelas).
- [x] Suporte ao carregamento/execução em cadeia de `data-hooks.php` (globais e por módulo) pós-geração.
- [x] Logs via `log_disco_local()` (envelopa `log_disco()` da biblioteca oficial).
- [x] Substituir `@` cego por `ensureDir()` com validação/log (require de log, mkdir de LOG_DIR/DB_DATA_DIR/jsonWrite/órfãos).

### 4. Refatoração do Script Atualizador (`atualizacoes-banco-de-dados.php`)
- [x] Leitura dinâmica de [schema-metadata.json](../../gestor/db/data/schema-metadata.json) (`schemaMetadata()` com cache).
- [x] Remover arrays hardcoded (`$preserveMap`, `$tabelasChaveNatural`, `$tabelasInsertOnly`) — agora derivados do contrato.
- [x] Query para obter `max_allowed_packet` dinamicamente (`maxAllowedPacket()`).
- [x] Loteador threshold-based (`inserirEmLote`): multi-row agrupado por assinatura de colunas, chunk a 70% do pacote, fallback fixo 16MB, fallback individual para duplicatas.
- [x] Motor genérico de chave natural (`naturalKeyGenerica`) + WHERE genérico null-safe (`<=>`). *Nota: os fluxos PK e Chave Natural permanecem como branches distintos, ambos dirigidos pelo contrato (não foram fundidos num único motor para preservar comportamento).*
- [x] Deleção física do bloco de deleção imperativa do contrato (`executarDelecoes`).
- [x] Transações PDO completas (`beginTransaction`/`commit`/`rollBack`) envolvendo sincronização + deleção.
- [x] Remover `@` cego nas gravações (mantido o tratamento existente; foco do saneamento no gerador).

### 5. Unificação e Visibilidade de Logs de Banco de Dados
- [x] `log_unificado()` em `atualizacoes-banco-de-dados.php` e `atualizacao-plugin-banco-de-dados.php` (implementado por outro agente que tocou o 029 sem querer; incorporado sem reverter — ver DEC-042).
- [x] Injeção em `$GLOBALS['EXTERNAL_LOGGER']` por referência quando definido.
- [x] Remoção da execução via subprocesso ao rodar atualização de banco: `atualizacoes-sistema.php` (`executarAtualizacaoBanco`) agora roda estritamente inline (`require`). O `passthru()` remanescente é o auto-bootstrap do deploy (não-alvo). `plugins-installer.php` já integra `EXTERNAL_LOGGER`.
- [x] Impressão no stdout sob `PHP_SAPI === 'cli'` (dentro de `log_unificado`).
- [x] `api.php` (`api_executar_atualizacao_banco`) captura logs e retorna `db_logs`; endpoint aceita `full_log` (resumo vs completo via `api_filtrar_db_logs`).
- [x] `atualizacoes-sistema.php` registra os logs do banco prefixados com `[BANCO]`.

## Evidência de Validação (2026-06-12)

- **Validação estática**: `php -l` OK em 11 arquivos (gerador, atualizador, plugin-banco, api.php, atualizacoes-sistema, configuracao, gestor, plugins-installer, 3 migrações); `json_decode` OK em 34 JSONs (13 módulos + tables_config + schema-metadata + VariaveisData + demais módulos).
- **Geração do contrato**: `gerarSchemaMetadata()` executada isoladamente → `schema-metadata.json` com 17 tabelas, `strategy`/`natural_key_columns`/`preserve`/`insert_only` espelhando o hardcode anterior (todas as chave-natural, `usuarios` pk+insert_only, `categorias` pk, preserveMap correto).
- **Testes de unidade (PHP)**: `naturalKeyGenerica` 8/8 (paridade com o switch antigo: paginas/variaveis/permissões/modulos, lowercase, alias `linguagem_codigo`, colunas obrigatórias vs opcionais); `inserirEmLote` em PDO SQLite (no container) → batch de 50 com chunking, dedup fallback (inserted=2 same=1), simulate sem gravação; `schemaMetadata` 17 tabelas + `naturalKeyColumns`.
- **Teste end-to-end contra MySQL 8.0 real** (após o operador habilitar os drivers PDO no PHP do host; banco dedicado `conn2flow_test`, dropado ao fim — `conn2flow` real intacto): **6/6 OK** — `sincronizarTabela` em modo natural key (`modulos`: INSERT em lote de 3, UPDATE de 1 divergente + NO-CHANGE de 2), PRESERVE de `user_modified` em `variaveis` (valor do usuário não sobrescrito), INSERT_ONLY em `usuarios` (modo PK não atualiza), transação PDO com ROLLBACK desfazendo o insert, e deleção imperativa via `executarDelecoes` consumindo o bloco `deletar` do contrato.
- **Remoção de CLI exec**: 0 `exec/shell_exec/passthru` de banco; único `passthru` restante é o auto-bootstrap (não-alvo).
- **Residual `linguagem_codigo` como coluna SQL**: 0 (fora da migração de rename).
- Decisão registrada: [DEC-042](../decisions/DECISION-LOG.md#dec-042---2026-06-12---accepted).

## Pendências com o operador
- Rodar `atualizacao-dados-recursos.php` / `🗃️ Projects - Update => Core` para regenerar `schema-metadata.json` no pipeline e recalcular checksums.
- Aplicar as migrações (incl. `rename` da `variaveis`) em ambiente MySQL e validar runtime: sincronização completa (insert/update/preserve/deleção), loteador em volume real, transações e captura de `db_logs` no deploy via API (com e sem `full_log`).
