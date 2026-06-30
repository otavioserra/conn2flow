# Validation Checklist

Use este checklist para validar batches no `conn2flow` sem perder de vista o baseline operacional do repositório.

## Onboarding SDD repo-wide

- [x] `CLAUDE.md` instalado na raiz do repositório
- [x] `.claude/` instalado com agents, rules, skills e settings do Claude Code
- [x] `.github/copilot-instructions.md` instalado
- [x] `.github/instructions/`, `.github/prompts/`, `.github/skills/` e `.github/agents/` com artefatos SDD do Copilot
- [x] `sdd/scripts/hooks/` criado com hooks de sessão SDD
- [x] `sdd/human-requests/` ativo
- [x] `sdd/README.md`, `process/`, `implementation/`, `validation/` e `decisions/` criados
- [x] `sdd/00-baseline-architecture.md` criado com preservação do legado

## Checklist mínimo por batch

- [ ] O batch está registrado em `sdd/implementation/BATCH-INDEX.md`
- [ ] O impacto foi comparado contra `sdd/00-baseline-architecture.md`
- [ ] A menor validação executável do slice foi definida antes de editar mais do que o necessário
- [ ] Scripts, tasks ou paths alterados continuam coerentes com `dev-environment/data/environment.json`
- [ ] Não houve reescrita ampla do legado sem mudança normativa aprovada
- [ ] O review findings-first foi feito quando a mudança ficou pronta para avaliação

## Quando o batch tocar operação local

- [ ] Validar a task do VS Code mais próxima ou o script subjacente equivalente
- [ ] Se tocar Docker, checar status, logs ou execução correspondente
- [ ] Se tocar sincronização de projeto, validar source/target/path no `environment.json`
- [ ] Se tocar plugins, validar o fluxo na árvore `dev-plugins/`

## Evidência mínima esperada

- comando executado ou checagem objetiva usada
- resultado observado
- pendências ou riscos restantes

## Regra final

Se não houver validação executável no slice atual, o batch deve registrar explicitamente por que a validação ficou documental ou manual.

## Validações de Batches Arquivados

Para manter o checklist de validações leve e eficiente, as validações e evidências dos lotes `BATCH-001` a `BATCH-017` foram movidas para o arquivo histórico **[validation-001-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-001-017.md)**, e as dos lotes `BATCH-018` a `BATCH-053` foram movidas para **[validation-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/archive/validation-018-053.md)**.

---
## BATCH-054 - Ajustes Visuais de Margem e Tooltip com Detalhe de Perfis no Módulo Menus (req-054)

- [x] **Ajuste de Margem nas Tags**:
  - [x] Margem aplicada às tags de perfis selecionados (`#condition-profile-tags` / `.condition-profile-tag`) para garantir espaçamento adequado em relação aos botões do formulário.
- [x] **Abas de Condição com Contador e Tooltip**:
  - [x] Abas de condição `perfil_usuario` exibem contador `slug (Perfil de usuário - N)`.
  - [x] Abas recebem tooltip Fomantic-UI formatado (`Usuário Perfis: Perfil 1, Perfil 2...`) com os nomes legíveis dos perfis.
  - [x] Fallback automático para os IDs se os nomes/labels dos perfis não estiverem salvos.

### Evidência de Validação (BATCH-054)

Evidência automatizada reportada pelo executor em 2026-06-22:
- Linting estático:
  - `php -l gestor\modulos\menus\menus.php` → OK
  - `php -l gestor\modulos\menus\menus.widget.php` → OK
  - `node --check gestor\modulos\menus\menus.js` → OK
- Verificação de diff: `git diff --check` passou limpo nos arquivos do lote.

### Pendências Runtime
- Sincronizar os recursos via `Update => Core`.
- Validar visualmente no navegador que as tags do CRUD possuem espaçamento inferior e não encostam no botão de confirmação.
- Validar que ao passar o mouse nas abas das condições, o tooltip do Fomantic-UI renderiza o nome legível dos perfis vinculados e o título exibe o número de itens selecionados.


## BATCH-056 - Sincronização Declarativa de Recursos, Deleção e Atualização Forçada (req-056)

- [x] **Gerador (`atualizacao-dados-recursos.php`)**:
  - [x] `normalizarConfigTabela()` aceita `config` objeto OU array de objetos (objeto → array de 1); resolve `tabela_nome`; agrega `deletar`/`forcar_atualizacao` por elemento e por bloco (retrocompat).
  - [x] `coletarConfigsTabelas()` (motor compartilhado) reaproveitado por `gerarSchemaMetadata()` e `coletarRecursos()`.
  - [x] `gerarSchemaMetadata()` consolida `deletar` e novo `forcar_atualizacao` (mapas de topo) no `schema-metadata.json`; campos de varredura ficam fora do contrato.
  - [x] Varredura `sync_resources`: `lerMetadadosDinamicos()` (externo/inline por idioma), `processarRegistroDinamico()` (`field_types` `json`/`file:<ext>`, BOM removido, colunas padronizadas), `checksumRegistroDinamico()` (reuso de versão).
  - [x] `atualizarDados()` gera `<PascalCase>Data.json` dinâmico via `dataFileNameFromTable()`, pulando as 9 tabelas fixas reservadas.
- [x] **Config global (`tables_config.json`)**: `_comment` estendido (config objeto/array, `tabela_nome`, `sync_resources`/`resources_dir`/`metadata_file`/`field_types`/`deletar`/`forcar_atualizacao`); 4 tabelas existentes mantidas (retrocompat).
- [x] **Atualizador (`atualizacoes-banco-de-dados.php`)**: `schemaMetadata()` lê `forcar_atualizacao`; `forcarAtualizacaoLista()` + `$isForced()`; bypass de `project`/`user_modified` + reset `user_modified=0` + preservação de `project` nos 3 caminhos (PK, chave natural, fallback). Correção `${var}`→`{$var}` (deprecation PHP 8.4).
- [x] **Documentação**: 6 docs × pt-br/en atualizados (Recursos, Atualizações, Módulos Detalhado, Módulos Overview, Multilíngue, Proteção de Banco).

### Evidência de Validação (BATCH-056)

Evidência automatizada reportada pelo executor em 2026-06-23 (ambiente: PHP 8.4.8, mbstring/pdo_sqlite/pdo_mysql):
- `php -l gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` → OK
- `php -l gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` → OK
- `json_decode` de `gestor/resources/tables_config.json` → VALID
- Regeneração do contrato via `SDD_NO_AUTORUN` + `gerarSchemaMetadata()` → 17 tabelas preservadas, chave de topo `forcar_atualizacao` presente (vazia, pois nada declara ainda).
- Smoke da varredura dinâmica (fixtures temporárias): `file:html`/`file:css` com BOM removido, `field_types: json` codificado, injeção de `language`/`status`/`module`, checksum estável e sensível a mudança de arquivo, leitura inline e externa → OK.
- Teste de regressão `tests/Unit/PHP/ForcarAtualizacaoTest.php` (PDO SQLite + contrato temporário): forçado por chave natural (payload completo + `user_modified→0`, `project` preservado), forçado por PK (bypass de `project`), preservação normal de `user_modified=1`, proteção de projeto → `updated=2`, `same=2`, todas as asserts PASS.
- `composer test` (suíte completa) → **48 testes, 142 assertions, OK** (4 skipped = testes de banco gated; 1 PHPUnit deprecation pré-existente, não relacionada).

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` (regenera `schema-metadata.json` + `<Pascal>Data.json` dinâmicos e recalcula checksums) e validar a sincronização em MySQL.
- Validação manual end-to-end do `forcar_atualizacao` em MySQL: configurar uma tabela com registro em `forcar_atualizacao`, marcar `user_modified=1` no banco, rodar o deploy e confirmar que o dado físico foi reintegrado e `user_modified` voltou a `0`.

---
## BATCH-057 - Correção de Tipagem e Validação de Perfil Anônimo em Menus Condicionais (req-057)

- [x] Normalização de perfil anônimo em array (converter para `false` se `id` for `'_anonimo'` ou `id_usuarios` for `0`).
- [x] Obtenção de profile ID sem warnings (ler `$params['_profile_slug']` ou `id_usuarios_perfis` quando `$perfil_usuario` for array).
- [x] Validação estática (`php -l`) no arquivo `gestor/modulos/menus/menus.widget.php` sem erros.
- [x] Suíte de testes `tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php` executada sem falhas ou warnings.

### Evidência de Validação (BATCH-057)

- Validação estática e de testes executada em 2026-06-24:
  - `php -l gestor/modulos/menus/menus.widget.php` → `No syntax errors detected`.
  - **Baseline (antes da correção)**: `MenusWidgetConditionalVisibilityTest` → 2 falhas (`testRenderizaMenuPublicoParaUsuarioAnonimo`, `testRenderizaMenuDePerfilQuandoIdEstaEntreMultiplosPerfisPermitidos`) + 1 PHP warning (`Array to string conversion` em `menus.widget.php:288`).
  - **Pós-correção**: `vendor/bin/phpunit tests/Unit/PHP/MenusWidgetConditionalVisibilityTest.php` → **OK (7 tests, 19 assertions)**, sem falhas e sem warnings.
  - Suíte PHP completa (`composer test`) → **OK (48 tests, 142 assertions, 4 skipped gated por banco)**, sem novas falhas (a única `PHPUnit Deprecation` é pré-existente e alheia a este slice).
- Arquivo alterado: `gestor/modulos/menus/menus.widget.php` (apenas a função `menus_widget_condicao_valida`).
- Decisão registrada: [DEC-065](../decisions/DECISION-LOG.md#dec-065---2026-06-24---accepted).
- Observação de escopo: o intake (req-057) cita "9 testes", mas a suíte real `MenusWidgetConditionalVisibilityTest` contém **7** testes — todos passam. Nenhum teste novo foi criado, pois o req descreve apenas a correção do widget; a divergência de contagem foi registrada para rastreabilidade.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-058 - Sistema de Recuperação e Engenharia Reversa de Recursos (Pull System) (req-058)

- [x] Endpoint `_api/project/recover` implementado em `gestor/controladores/api/api.php` com validação OAuth e compressão ZIP.
- [x] Novo orquestrador CLI de exportação `gestor/controladores/recuperacoes/recuperacao-dados-recursos.php` no servidor.
- [x] Novo descompilador genérico `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php` no cliente local.
- [x] Script de pull `ai-workspace/en/scripts/projects/recover-project.sh` integrado às VS Code Tasks.
- [x] Remoção de BOM e saneamento de metadados (`versao`, `checksum`, `user_modified`, `project` e chaves PK/auto-incremento) validados.
- [x] Suíte de testes unitários `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` cobrindo a engenharia reversa inline/externa de HTML/CSS/JSON.
- [x] Validação estática (`php -l`) de todos os arquivos PHP gerados.
- [ ] Validação manual end-to-end de pull local e git status limpo. *(pendente com o operador — requer API rodando + token OAuth do projeto)*

### Evidência de Validação (BATCH-058)

Evidência automatizada reportada pelo executor em 2026-06-25 (ambiente: PHP 8.4.8, mbstring/pdo_sqlite/pdo_mysql):
- Linting estático (`php -l`) → **OK (4/4)**:
  - `gestor/controladores/api/api.php`
  - `gestor/controladores/recuperacoes/recuperacao-dados-recursos.php`
  - `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php`
  - `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`
- Sintaxe bash (`bash -n`) do `ai-workspace/en/scripts/projects/recover-project.sh` → OK; script tornado executável (`chmod +x`).
- `.vscode/tasks.json` revalidado como JSON válido após inserir as tarefas `🗃️ Projects - Recover Current Project` e `🗃️ Projects - Recover Project -> ID`.
- Teste novo `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` → **OK (6 tests, 44 assertions)**: extração `file:<ext>` em layout PLANO com BOM removido, decodificação de campo `json`, saneamento de `versao`/`checksum`/`user_modified`/`project`/PK/`language`/`status='A'`/módulo-dono, escrita externa (global sem `resources_dir` → `<lang>/<tabela>.json`) e inline (`resources->idioma->tabela` preservando o restante do JSON raiz), e resolução de caminhos (global com/sem `resources_dir`, módulo).
- Smoke manual do descompilador com fixtures sintéticas (round-trip): `fields_schema` (json) decodificado para objeto aninhado, `html` extraído para `resources/pt-br/widgets_demo/hero.html` sem BOM e metadado externo saneado. OK.
- Suíte PHP completa (`composer test`) → **OK (54 tests, 186 assertions, 4 skipped gated por banco)**; a única `PHPUnit Deprecation` é pré-existente e alheia a este slice.

### Pendências Runtime (com o operador)
- Rodar a task `🗃️ Projects - Recover Current Project` (ou `-> ID`) contra um projeto com API ativa e token OAuth válido; confirmar download do ZIP, descompilação dos recursos e recompilação consistente.
- Verificar no Git que as alterações feitas no banco (painel admin) foram trazidas para os arquivos de recursos (globais e de módulos) com `git status` limpo de ruído (sem colunas de build).
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-059 - Refinamentos, Overrides de Projeto e Sincronização Inteligente de Contents (req-059)

- [x] Script CLI do servidor renomeado para `gestor/controladores/recuperacoes/recuperacao-banco-de-dados.php`.
- [x] Compilador e descompilador de recursos suportando overrides de `scope` e `modulo` em `tables_config.json`.
- [x] Endpoint `_api/project/recover` compactando opcionalmente o diretório `gestor/contents/` sob o caminho `contents/` no ZIP.
- [x] Descompilador realizando pull inteligente da pasta `contents/` comparando MD5 e timestamps, e aplicando `touch()`.
- [x] Relatório de conflito exibido no final do pull mostrando alertas de choque no padrão `RDR_CONFLITO` para arquivos modificados localmente que são mais recentes que a versão alterada do servidor.
- [x] Script `recover-project.sh` e VS Code Tasks atualizados com a flag `--contents`.
- [x] Suíte de testes `RecuperacaoDadosRecursosTest.php` estendida para cobrir overrides em `tables_config.json`, comparação MD5 e timestamps (conflito/choque) no pull de `contents`.
- [x] Validação estática (`php -l`) de todos os arquivos PHP alterados.

### Evidência de Validação (BATCH-059)

Evidência automatizada reportada pelo executor em 2026-06-25:
- Linting estático (`php -l`) OK:
  - `gestor/controladores/api/api.php`
  - `gestor/controladores/recuperacoes/recuperacao-banco-de-dados.php`
  - `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
  - `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php`
  - `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`
- `.vscode/tasks.json` validado por `ConvertFrom-Json`: 46 tasks.
- `git diff --check` OK.
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: **11 tests, 65 assertions**.
- `composer test` OK: **59 tests, 207 assertions**; 4 skipped gated por banco; 1 `PHPUnit Deprecation` preexistente.
- `bash -n ai-workspace/en/scripts/projects/recover-project.sh` não executado: neste Windows o comando `bash` encaminha para WSL, mas não há distribuição instalada.

### Pendências Runtime (com o operador)
- Executar a task `🗃️ Projects - Recover Current Project with Contents` contra API ativa e token OAuth válido.
- Confirmar manualmente que conflitos reais em `contents/` aparecem como `RDR_CONFLITO`/`RDR_CONFLITOS` sem sobrescrever arquivos locais mais recentes.

---
## BATCH-060 - Pipeline de Metadados de Projeto e Desacoplamento (req-060)

- [x] Compilador gerando `project-schema-metadata.json` na raiz do gestor local a partir de `tables_config.json`.
- [x] Script de deploy `deploy-project-v2.sh` incluindo `gestor/project-schema-metadata.json` no empacotamento do ZIP de release.
- [x] Endpoint da API `_api/project/recover` no servidor lendo as tabelas do `project-schema-metadata.json` para o dump.
- [x] Script CLI do servidor `recuperacao-banco-de-dados.php` lendo as tabelas do `project-schema-metadata.json`.
- [x] Documentação em português `ai-workspace/pt-br/docs/CONN2FLOW-SISTEMA-RECURSOS.md` atualizada com o funcionamento do `project-schema-metadata.json` e exemplos completos de configuração do `tables_config.json`.
- [x] Validação estática (`php -l`) de todos os arquivos PHP alterados.
- [x] Suíte de testes `RecuperacaoDadosRecursosTest.php` e compilação válidas.

### Evidência de Validação (BATCH-060)

Evidência automatizada reportada pelo executor em 2026-06-25:
- `php -l` OK:
  - `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
  - `gestor/controladores/api/api.php`
  - `gestor/controladores/recuperacoes/recuperacao-banco-de-dados.php`
  - `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: **13 tests, 71 assertions**.
- `composer test` OK: **61 tests, 213 assertions**; 4 skipped gated por banco; 1 `PHPUnit Deprecation` preexistente.
- `git diff --check` OK.
- `bash -n ai-workspace/en/scripts/projects/deploy-project-v2.sh` não executado: neste Windows o comando `bash` encaminha para WSL, mas não há distribuição instalada.

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Deploy Current Project` ou `-> ID` em ambiente com Bash/7z disponível e confirmar que `project-schema-metadata.json` chega ao servidor.
- Rodar o pull contra API ativa sem `tables` explícito e confirmar que tabelas declaradas apenas no manifesto de projeto entram no ZIP de recuperação.

---
## BATCH-061 - Renomeação para project_tables_config.json e Filtragem de Manifesto (req-061)

- [x] Configuração customizada do projeto renomeada logicamente para `gestor/resources/project_tables_config.json`.
- [x] `gestor/resources/tables_config.json` preservado como arquivo global do core.
- [x] Compilador em modo `--project-path` lendo core de `tables_config.json` e projeto de `project_tables_config.json`.
- [x] `project-schema-metadata.json` filtrado para conter apenas tabelas declaradas em `project_tables_config.json`.
- [x] Descompilador lendo `tables_config.json` e `project_tables_config.json` em ordem, com o arquivo de projeto sobrescrevendo configs globais.
- [x] Documentação pt-br/en atualizada para o novo nome do arquivo de projeto.
- [x] Testes atualizados para simular `project_tables_config.json`.
- [x] Validação estática (`php -l`) de todos os PHP alterados.

### Evidência de Validação (BATCH-061)

Evidência automatizada reportada pelo executor em 2026-06-25:
- `php -l` OK:
  - `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
  - `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php`
  - `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: **13 tests, 72 assertions**.
- `composer test` OK: **61 tests, 214 assertions**; 4 skipped gated por banco; 1 `PHPUnit Deprecation` preexistente.
- `git diff --check -- . ':(exclude)sdd/human-requests/CURRENT.md'` OK. O `git diff --check` completo acusa linha em branco final em `sdd/human-requests/CURRENT.md`, arquivo de intake humano já alterado antes da execução e não editado pelo executor.

### Pendências Runtime (com o operador)
- Em um projeto real, renomear/mover configurações customizadas de `resources/tables_config.json` para `resources/project_tables_config.json`.
- Rodar `Update => Core`/deploy em modo projeto e confirmar que `project-schema-metadata.json` contém apenas as tabelas declaradas no arquivo de projeto.

---
## BATCH-062 - Fallback de Idioma e Estruturação de Pastas de Recursos Dinâmicos (req-062)

- [x] Descompilador usando fallback `pt-br` para registros sem `language` ou `linguagem_codigo`.
- [x] Compilador lendo arquivos físicos `file:<ext>` em `<resources_dir|tabela>/<id>/<id>.<ext>`.
- [x] Descompilador escrevendo arquivos físicos `file:<ext>` em `<resources_dir|tabela>/<id>/<id>.<ext>`.
- [x] Suíte `RecuperacaoDadosRecursosTest.php` cobrindo fallback de idioma e subpastas por ID.
- [x] Documentação pt-br/en atualizada para o novo layout físico.
- [x] Validação estática (`php -l`) de todos os PHP alterados.

### Evidência de Validação (BATCH-062)

Evidência automatizada reportada pelo executor em 2026-06-25:
- `php -l` OK:
  - `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
  - `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php`
  - `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` OK: **15 tests, 78 assertions**.
- `composer test` OK: **63 tests, 220 assertions**; 4 skipped gated por banco; 1 `PHPUnit Deprecation` preexistente.
- `git diff --check -- . ':(exclude)sdd/human-requests/CURRENT.md'` OK. O `git diff --check` completo ainda herda linha em branco final em `sdd/human-requests/CURRENT.md`, arquivo de intake humano já alterado antes da execução.

### Pendências Runtime (com o operador)
- Em projeto real, mover arquivos físicos dinâmicos existentes do layout plano para `<id>/<id>.<ext>`.
- Rodar pull de tabela sem idioma, como `arquivos`, e confirmar geração em `resources/pt-br/`.

---
## BATCH-063 - Depuração e Logging Explicativo na Descompilação de Arquivos (req-063)

- [x] **Depuração de `arquivos=0`**:
  - [x] Validar que quando arquivos dinâmicos (como de `galleries`, `menus`, `publisher`) têm campos físicos (`html`, `css`) nulos ou vazios no banco/JSON de origem, a descompilação não gera arquivos físicos correspondentes no disco (comportamento esperado). Causa-raiz confirmada: registros que usam o template padrão sem customização gravam `html`/`css` nulos no banco.
- [x] **Log de Depuração no Console**:
  - [x] Log `RDR_DEBUG_FILE_EMPTY tabela=X id=Y campo=Z` emitido (modo não silencioso) quando a gravação de um arquivo físico é pulada por campo nulo/vazio. A condição antiga (`!== null`) deixava passar string vazia e gerava arquivo em branco — agora qualquer conteúdo vazio (inclusive só-BOM) é omitido.
- [x] **Validação com Testes Unitários**:
  - [x] `testCampoFileNuloOuVazioEmiteLogEnaoCriaArquivo`: campos `''`/nulos não acumulam arquivo e emitem o log por campo.
  - [x] `testProcessaMisturaCustomizadoEPadraoSemArquivoEmBranco`: cenário misto (1 customizado gera 2 arquivos, 1 padrão gera 0 + log), metadados escritos para ambos, sem arquivo em branco no disco.
- [x] **Validação Estática**:
  - [x] `php -l` OK no descompilador e no teste.

### Evidência de Validação (BATCH-063)

Evidência automatizada reportada pelo executor em 2026-06-25 (ambiente: PHP 8.4.8):
- `php -l gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php` → OK
- `php -l tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` → OK
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` → **OK (17 tests, 97 assertions)** (era 15; +2 deste lote).
- `composer test` → **OK (65 tests, 239 assertions, 4 skipped gated por banco)**; a única `PHPUnit Deprecation` é pré-existente e alheia a este slice.
- Arquivos alterados: `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php` (função `rdr_descompilar_registro` + docblock), `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`.

### Pendências Runtime (com o operador)
- Rodar o pull real (`🗃️ Projects - Recover ...`) contra a API e confirmar que `RDR_DEBUG_FILE_EMPTY` aparece no console para os registros que usam template padrão e que nenhum arquivo em branco é criado em `resources/`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-064 - Atualização de Versões dos Componentes do GitHub Actions (req-064)

- [x] Atualização de `actions/checkout@v4` para `actions/checkout@v7` nos workflows `release-gestor.yml` e `release-instalador.yml`.
- [x] Atualização de `actions/setup-node@v4` para `actions/setup-node@v6` em `release-gestor.yml`.
- [x] Atualização de `softprops/action-gh-release@v2` para `softprops/action-gh-release@v3` nos dois workflows.
- [x] Validação estática de formato YAML em ambos os workflows.

### Evidência de Validação (BATCH-064)

Evidência automatizada e estática reportada pelo executor em 2026-06-25:
- Edições aplicadas aos arquivos: `.github/workflows/release-gestor.yml` e `.github/workflows/release-instalador.yml`.
- Sintaxe YAML validada estaticamente. O teste final runtime ocorrerá no próximo disparo do pipeline via GitHub.

---
## BATCH-065 - Suporte a Colunas Customizadas de ID em Recursos Dinâmicos (req-065)

- [x] **Descompilador (`recuperacao-dados-recursos.php`)**:
  - [x] Ler dinamicamente a coluna configurada em `$cfg['id']` em `rdr_descompilar_registro()` para determinar o `$id` (helper `rdr_id_col($cfg)` com fallback `'id'`).
  - [x] Ajustar o log `RDR_REGISTRO_SEM_LANG` para ler o ID de forma dinâmica na varredura.
- [x] **Compilador (`atualizacao-dados-recursos.php`)**:
  - [x] Ajustar a leitura de `$rec[$idCol]` no laço principal de compilação dinâmica.
  - [x] Ajustar a leitura de `$rec[$idCol]` em `processarRegistroDinamico()`.
  - [x] Ajustar o cache `$existDinamicoCache` para construir o índice de busca usando a coluna de ID dinâmica da tabela.
- [x] **Testes Unitários**:
  - [x] Criar teste dinâmico simulando round-trip completo com tabela de ID customizado (`page_id` / PK `id_publisher_pages`).
  - [x] Confirmar que `composer test` passa limpo.
- [x] **Validação Estática**:
  - [x] Rodar `php -l` sem erros.

### Evidência de Validação (BATCH-065)

Evidência automatizada reportada pelo executor em 2026-06-25 (ambiente: PHP 8.4.8):
- `php -l` → OK (3/3):
  - `gestor/controladores/agents/arquitetura/recuperacao-dados-recursos.php`
  - `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
  - `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`
- `vendor/bin/phpunit tests/Unit/PHP/RecuperacaoDadosRecursosTest.php` → **OK (19 tests, 113 assertions)** (era 17; +2 deste lote).
- `composer test` → **OK (67 tests, 255 assertions, 4 skipped gated por banco)**; a única `PHPUnit Deprecation` é pré-existente e alheia a este slice.
- Testes novos:
  - `testDescompilaResolveColunaIdCustomizada`: dump com `page_id` (sem coluna `id`) gera o arquivo em `resources/pt-br/publisher_pages/sobre/sobre.html`, preserva `page_id` no metadado e remove a PK `id_publisher_pages`.
  - `testRoundTripColunaIdCustomizada` (RunInSeparateProcess): descompila `PublisherPagesData.json` (`page_id`) para arquivo físico + metadado e recompila via `processarRegistroDinamico(['page_id'=>'home'])`, reinjetando o HTML sem perda de atributos.
- Arquivos alterados: `recuperacao-dados-recursos.php` (helper `rdr_id_col` + `rdr_descompilar_registro` + log em `rdr_processar`), `atualizacao-dados-recursos.php` (`$idCol` no laço dinâmico, no cache `$existDinamicoCache` e em `processarRegistroDinamico`), `tests/Unit/PHP/RecuperacaoDadosRecursosTest.php`.

### Pendências Runtime (com o operador)
- Em um projeto real com tabela de ID customizada (ex.: `publisher_pages` com `"id": "page_id"`), rodar pull/deploy e confirmar geração dos arquivos físicos pela coluna lógica e round-trip sem perda de atributos.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-066 - Registro Dinâmico de Widgets do Sistema e Carregamento sob Demanda no Editor HTML (req-066)

- [x] **Banco de Dados**:
  - [x] Migração Phinx para criação da tabela `widgets` com as colunas especificadas e índice único `['id', 'language']` (`20260708100000_create_widgets_table.php`, inclui `project`).
- [x] **Configuração e Manifestos**:
  - [x] Registro da tabela `widgets` em `gestor/resources/tables_config.json` (contrato `natural_key` por `[language, id]`).
  - [x] Inclusão do recurso `"widgets"` nos arquivos JSON de manifesto para os módulos: `menus`, `galleries` e `publisher-index` (pt-br + en; `publisher-highlights` já existente).
- [x] **Compilador de Recursos (`atualizacao-dados-recursos.php`)**:
  - [x] Alteração para coletar e empacotar o recurso `'widgets'` dos módulos gerando o arquivo `WidgetsData.json` (pilha `$widgets`, chave `name`, `versao=1`).
  - [x] Tratamento de órfãos para o recurso `'widgets'` (índice `$idxWidgets` por `lang|id`; `db/orphans/WidgetsData.json`).
- [x] **Roteamento Backend (`html-editor.php`)**:
  - [x] Nova rota AJAX `html-editor-widget-types` para carregar as categorias/tipos de widgets ativos.
  - [x] Atualização da rota AJAX `html-editor-widgets-list` para aceitar o parâmetro `module` e carregar os registros da tabela correspondente sob demanda, aplicando filtros de `coluna_where` e sanitizando entradas (regex `^[a-zA-Z0-9_]+$`).
- [x] **Interface Frontend (`html-editor-visual-controls.js`)**:
  - [x] Remoção da lista estática `WIDGETS_MODULOS`.
  - [x] Carregamento inicial das categorias via AJAX utilizando `html-editor-widget-types`.
  - [x] Carregamento sob demanda (lazy loading) dos itens de cada categoria ao expandir o grupo, com cache local no JS (`widgetsCache[module]`) para evitar redundâncias.
- [x] **Validação Estática**:
  - [x] Executar `php -l` nos arquivos alterados sem erros.

### Evidência de Validação (BATCH-066)

Evidência automatizada reportada pelo executor em 2026-06-29 (ambiente: PHP 8.4.8, mbstring/pdo_sqlite/pdo_mysql):
- `php -l` → OK (3/3): `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`, `gestor/bibliotecas/html-editor.php`, `gestor/db/migrations/20260708100000_create_widgets_table.php`.
- `node --check gestor/assets/interface/html-editor-visual-controls.js` → OK.
- JSON válido (`ConvertFrom-Json`) → 5/5: `tables_config.json`, `menus.json`, `galleries.json`, `publisher-index.json`, `publisher-highlights.json`.
- Smoke read-only do compilador (inclui o arquivo com `SDD_NO_AUTORUN` e chama `coletarRecursos()` sem gravar): **8 widgets coletados** (4 módulos × pt-br/en), todos com `id`/`name`/`icon`/`tabela` corretos, `versao=1`, `user_modified=0`, `coluna_where=NULL`; **0 órfãos**.
- `composer test` → **OK (67 tests, 255 assertions, 4 skipped gated por banco)**; a única `PHPUnit Deprecation` é pré-existente e alheia a este slice (sem testes novos — slice de UI + pipeline declarativo).
- Bump de cache-bust: `biblioteca-html-editor` `1.3.23` → `1.3.24` (asset JS alterado).
- Arquivos alterados: migração nova; `tables_config.json`; `menus.json`/`galleries.json`/`publisher-index.json`; `atualizacao-dados-recursos.php`; `html-editor.php`; `html-editor-visual-controls.js`.

### Pendências Runtime (com o operador)
- Rodar a esteira de atualização local (`🗃️ Projects - Update => Core`, que aplica a migração Phinx + gera `WidgetsData.json` + sincroniza) e confirmar a população correta da tabela `widgets` (8 linhas).
- Validar no Editor HTML Visual que o carregamento das categorias ocorre dinamicamente (rota `html-editor-widget-types`) e os itens são trazidos via AJAX (`html-editor-widgets-list` com `module`) apenas ao expandir cada categoria, com cache ao fechar/reabrir.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-067 - Correção de Exceção de CodeMirror Indefinido e Abas Travadas no Módulo de Formulários (req-067)

- [x] **Interface & Eventos (`html-editor-interface.js`)**:
  - [x] Envolver o registro do evento `"change"` de `CodeMirrorHtml` em um bloco de verificação de existência (`typeof CodeMirrorHtml !== 'undefined' && CodeMirrorHtml`).
  - [x] Blindar as referências ao `CodeMirrorHtml`, `CodeMirrorCss` e `CodeMirrorCssCompiled` no ouvinte do evento de backup (`$('#gestor-listener').on(backupCallbackName, ...)`).
  - [x] **Novo**: Blindar as referências a `CodeMirrorHtml` e `CodeMirrorCss` em `previewHtml()`, `totalDeSessoes()`, `getUpdatedHtmlWithValues()` e `menuDeSessoes()` (early-return quando as instâncias não existem).
  - [x] **Novo**: Causa-raiz identificada e corrigida — o editor estava aninhado em abas inativas no load (`forms-template`/`forms-preview`), impedindo a instanciação do CodeMirror. Editor movido para seção raiz `Conteúdo do Formulário`/`Form Content` fora das abas (6 HTMLs editar/adicionar/clonar × pt-br/en), espelhando o `publisher-highlights`.
- [x] **Widget público (`forms.widget.php`)**:
  - [x] Fallback ao template do banco (`templates`, `target='forms'`, via `fields_schema.template_id`) quando o campo `html` do registro está vazio, eliminando o widget renderizado vazio.
- [x] **Verificação Estática**:
  - [x] `node --check gestor/assets/interface/html-editor-interface.js` → OK; `php -l gestor/modulos/forms/forms.widget.php` → OK.
- [x] **Testes de Integração**:
  - [x] `composer test` → 67/67 (255 assertions, 4 skipped) limpo.

### Evidência de Validação (BATCH-067)

**Rodada 1** (2026-06-29): guards no listener `change` e no handler de backup (`#gestor-listener`). `node --check` OK, `php -l` OK, `composer test` 67/67.

**Rodada 2 — causa-raiz + widget** (2026-06-29, escopo expandido pelo Engenheiro Chefe):
- Causa-raiz: no `forms`, o `#html-editor#` estava em `data-tab="forms-editor"` (dentro de `data-tab="forms-template"`), ambos inativos no load — diferente do `publisher-highlights`, que renderiza o editor direto na página. Sem o editor visível/estável, `CodeMirror.fromTextArea` e as abas internas não se firmavam, deixando `CodeMirrorHtml`/`CodeMirrorCss` indefinidos → `previewHtml`/`totalDeSessoes` lançavam `undefined.getDoc()` e travavam a troca de abas Fomantic.
- Correção estrutural: removida a sub-aba "Editor HTML" do `menuFormsTemplate` e movido o `#html-editor#` para `<h4 class="ui dividing header">Conteúdo do Formulário</h4>` (en: `Form Content`) fora das abas, nos 6 HTMLs (editar/adicionar/clonar × pt-br/en). `forms-view` não renderiza editor (não afetado).
- Blindagem defensiva: `previewHtml()`, `getUpdatedHtmlWithValues()`, `totalDeSessoes()`, `menuDeSessoes()` com early-return quando o CodeMirror não existe.
- Widget vazio (`forms.widget.php`): `forms_render()` retornava `''` quando `html` do banco estava vazio (template padrão não persistido por causa do mesmo bug do editor). Adicionado fallback que carrega `html`/`css`/`css_compiled`/`html_extra_head` do template (`fields_schema.template_id`).
- Cache-bust: `biblioteca-html-editor` `1.3.25` → `1.3.26`.
- `node --check` (interface.js) OK; `php -l` (forms.widget.php) OK; grep confirmou `Conteúdo do Formulário`/`Form Content` + `#html-editor#` nos 6 HTMLs e nenhum `data-tab="forms-editor"` remanescente; `composer test` 67/67 (255 assertions, 4 skipped).
- Arquivos: 6 HTMLs do `forms`, `html-editor-interface.js`, `forms.widget.php`, `html-editor.php` (versão).

### Pendências Runtime (com o operador)
- Abrir a edição de um formulário (`forms/editar/`), mudar de templates no dropdown e navegar pelas abas (incluindo o Editor HTML agora em "Conteúdo do Formulário"), confirmando ausência de erros no console e funcionamento da prévia.
- Renderizar um widget de formulário numa página publicada e confirmar que ele não vem mais vazio (incluindo forms que usam o template padrão, agora cobertos pelo fallback).
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-068 - Alinhamento Visual do CRUD e Painel de Conteúdo de Formulários com Menus (req-068)

- [x] **Modelos de CRUD (HTMLs)**:
  - [x] Remoção da aba "Template" (`forms-template`) dos 6 HTMLs de adicionar, editar e clonar (pt-br + en).
  - [x] Criação do cabeçalho de seção raiz "Conteúdo do Formulário" (en: "Form Content") fora e abaixo das abas principais.
  - [x] Inclusão do dropdown de seleção de modelo (`#template_id`) e do novo menu de abas `menuConteudoForm` com as sub-abas `forms-preview`, `forms-editor` e `forms-widget`.
- [x] **Lógica de Interface (`forms.js`)**:
  - [x] Inicialização de abas da classe `.menuConteudoForm` com persistência em `localStorage` da sub-aba ativa (`tabFormContentActive`).
  - [x] Redirecionamento da mudança de sub-aba do editor para `window.contentPageTabHandler()` quando em `forms-editor`.
- [x] **Verificação Estática**:
  - [x] Executar `node --check` nos JS modificados e `php -l` nos PHP correspondentes.
- [x] **Testes de Integração**:
  - [x] Rodar `composer test` e verificar aprovação completa.

### Evidência de Validação (BATCH-068)

Evidência automatizada e visual reportada em 2026-06-29:
- `node --check gestor\modulos\forms\forms.js` → OK.
- `php -l gestor\modulos\forms\forms.php` → OK.
- `php -l gestor\modulos\forms\forms.widget.php` → OK.
- Reestruturação de abas Fomantic: resolvido o conflito entre o menu de abas principal (`menuForms`) e o menu interno (`menuConteudoForm`) através da adição dos containers `.forms-main-tabs` e `.forms-content-tabs` no HTML, configurados como `context` na inicialização do `.tab()` no `forms.js`.
- `composer test` → **OK (67 tests, 255 assertions, 4 skipped)**.
- Cache-bust: `biblioteca-html-editor` `1.3.25` → `1.3.26`.
- Arquivos alterados: 6 HTMLs do forms, `forms.js`, `html-editor.php` (versão).

### Pendências Runtime (com o operador)
- Abrir a página de edição de formulário e checar visualmente que a aba "Template" sumiu, a seção de conteúdo e abas secundárias se alinharam com Menus, e os botões e abas funcionam de forma responsiva sem erros.
- Restrição respeitada: nenhum `git commit`/`git push` executado antes desta revisão.

---
## BATCH-069 - Integração do Módulo de Formulários com o Controlador de Submissão de Biblioteca de Formulários (req-069)

- [x] **Biblioteca de Formulários (`formulario.php`)**:
  - [x] Adaptar `formulario_controlador()` para processar múltiplos IDs de formulário (aceitando array ou string): normaliza `$formId` em `$formIds` e processa DB/reCAPTCHA/acesso/params em `foreach ($formIds as $fid)` com reset por iteração.
  - [x] Gerar as variáveis JavaScript estruturadas por ID (`$forms_js_vars[$fid]` → `gestor.form[fid]`); para ID único (string) também injeta o formato plano via segundo `gestor_js_variavel_incluir('form', ...)` (retrocompat legada). Bump de versão `biblioteca-formulario` `1.1.0` → `1.2.0` (cache-bust do `formulario.js`).
- [x] **Script Controlador Frontend (`formulario.js`)**:
  - [x] Expandir o seletor inicializador para capturar tanto `.conn2flow-form` quanto `._forms-submissions-controller` (`$('._forms-submissions-controller, .conn2flow-form')`).
  - [x] Ler a configuração individualizada de cada formulário de `gestor.form[formId]` resolvendo o ID por `form.attr('id') || form.attr('data-form-id')` (com fallback ao objeto plano `gestor.form`). `showError` passou a isolar o erro na instância (`form.prepend`) em vez do seletor global.
- [x] **Integração do Widget (`forms.widget.php`)**:
  - [x] Chamar `gestor_incluir_biblioteca('formulario')` + `formulario_controlador(['formId' => $registro['id']])` em `forms_render()`, removendo a inclusão de `forms.widget.js` (`gestor_pagina_javascript_incluir(['tipo'=>'widget','modulo_id'=>'forms',...])`).
- [x] **Saneamento de Arquivos**:
  - [x] Deletar o arquivo redundante `forms.widget.js`.
- [x] **Verificação Estática**:
  - [x] Executar `node --check` no `formulario.js` e `php -l` em `formulario.php` / `forms.widget.php`.
- [x] **Testes de Integração**:
  - [x] Rodar `composer test` e confirmar aprovação total.

### Evidência de Validação (BATCH-069)

Evidência automatizada reportada pelo executor em 2026-06-29 (ambiente: PHP 8.4.8, PHPUnit 11.5.55):
- `php -l gestor/bibliotecas/formulario.php` → `No syntax errors detected`.
- `php -l gestor/modulos/forms/forms.widget.php` → `No syntax errors detected`.
- `node --check gestor/assets/interface/formulario.js` → OK.
- `composer test` → **OK (67 tests, 255 assertions, 4 skipped gated por banco)**; a única `PHPUnit Deprecation` é pré-existente e alheia a este slice (sem regressão vs. baseline BATCH-068; nenhum teste novo — slice de fluxo de widget/JS público sem hooks de banco testáveis isoladamente).
- Grep de sanidade: `_forms-submissions-controller` no `formulario.js` restou apenas no seletor inicial duplo; `forms.widget.js` removido e sem referências remanescentes (apenas o `require_once` de `forms.widget.php`, que é o renderer).
- Decisão registrada: [DEC-072](../decisions/DECISION-LOG.md#dec-072---2026-06-29---accepted).
- Divergência de intake registrada: a prosa do req-069 §2 indicava `form.attr('id')`, porém **todos** os templates (clássico `contact.html` e os 5 de widget) usam `data-form-id` e nenhum define `id` no `<form>` — o JS lê `id` com fallback `data-form-id` para casar com as chaves de `gestor.form`.

### Pendências Runtime (com o operador)
- Adicionar mais de um widget de formulário na mesma página pública (ex.: contato no corpo + newsletter no rodapé) e verificar se as validações client-side e os envios via AJAX funcionam de forma independente para cada um, sem colisões em `gestor.form`/`localStorage` nem exceções no console.
- Confirmar que o honeypot, o timestamp anti-replay e o reCAPTCHA (v2/v3) continuam presentes no fluxo do widget agora regido pela biblioteca.
- Restrição respeitada: nenhum `git commit`/`git push` executado.


## BATCH-070 - Parametrização de Scripts de Widget no Editor HTML e Dinamização do Mapeamento do Módulo Subscriptions

- [x] **Parametrização de Scripts de Widget no Editor HTML**:
  - [x] Função `html_editor_componente` no `html-editor.php` aceita parâmetro `'widget_js_include'` (injetado em `gestor.html_editor.widget_js_include`).
  - [x] Constante `WIDGET_SCRIPT_MODULES` no `html-editor-interface.js` recupera valor de `gestor.html_editor.widget_js_include` (fallback aos 4 módulos do core).
  - [x] `montarWidgetAssetsHead` injeta os scripts declarados em `widget_js_include` mesmo sem assinatura `[[widgets#...]]` presente (preview do editor de forms = `.conn2flow-form` direto); editores de página/layout/componente (chave `null`) mantêm injeção por assinatura.
  - [x] Chamadas para `html_editor_componente` em `forms.php`, `menus.php`, `galleries.php` e `publisher-index.php` atualizadas para passar o respectivo parâmetro.
- [x] **Recriação do Script de Widget de Formulário**:
  - [x] Rota AJAX `'forms-render-editor-html'` registrada no switch do backend em `forms.php` apontando para `forms_ajax_render_editor_html()`.
  - [x] Função `forms_render_editor_html` implementada em `forms.widget.php` (reusa o builder `formulario_montar_js_vars`, extraído de `formulario_controlador` em `formulario.php`).
  - [x] Arquivo `forms.widget.js` recriado: chamada AJAX dedicada no preview, popula `window.gestor.form[formId]` e carrega assíncronamente `interface.js` e `formulario.js`.
- [x] **Dinamização do Mapeamento no Módulo Subscriptions (Lumix)**:
  - [x] Migração Phinx `20260710120000_add_signup_fields_to_subscriptions_plans.php` (colunas `is_signup`, `signup_field_email`, `signup_field_password`, `signup_field_password_confirm`, `signup_id_usuarios_perfis`).
  - [x] Variáveis de tradução adicionadas ao `subscriptions-plans.json` em `pt-br` e `en` (6 chaves) + bump de `versao` do módulo (1.0.0→1.1.0).
  - [x] CRUD adicionar/editar persiste/carrega os 5 campos e resolve `#signup_profile_name#` para o autocomplete.
  - [x] Autocomplete AJAX habilitado para o alvo `perfis` (endpoint `buscar-perfis`).
  - [x] Novo endpoint AJAX `buscar-form-campos` extrai campos ativos do `fields_schema` do formulário selecionado.
  - [x] `subscriptions-plans.js` e os 4 templates HTML (pt-br/en) com checkbox `is_signup`, container `.sig-signup-fields-container`, autocomplete de perfil e 3 selects de mapeamento (com toggle e pré-seleção).
  - [x] Checkout dinâmico (`subscriptions.php`, `form_id` por slug+idioma) e signup dinâmico (`subscriptions.ajax.public.php`: `is_signup`/campos do plano; `subscriptions_criar_usuario` com `signup_id_usuarios_perfis`).

### Evidência de Validação (BATCH-070)

Evidência automatizada reportada pelo executor em 2026-06-30 (ambiente: PHP 8.4.8):
- **conn2flow** — `php -l` OK: `html-editor.php`, `formulario.php`, `forms.php`, `forms.widget.php`, `menus.php`, `galleries.php`, `publisher-index.php`. `node --check` OK: `html-editor-interface.js`, `forms.widget.js`, `formulario.js`. `composer test` → **OK (67 tests, 255 assertions, 4 skipped)**; a única `PHPUnit Deprecation` é pré-existente. O refator de `formulario_controlador` (extração do builder `formulario_montar_js_vars`) preservou o comportamento (suíte inalterada). Cache-bust `biblioteca-html-editor` 1.3.26→1.3.27.
- **lumix** — `php -l` OK: `subscriptions.php`, `subscriptions.ajax.public.php`, `subscriptions-plans.php`, migração `20260710120000`. `node --check` OK: `subscriptions-plans.js`. JSON válido: `subscriptions-plans.json`. Sem suíte de testes no projeto (`composer test`/`phpunit.xml` ausentes — conforme antecipado no req §3). Grep de sanidade: sem referências de código a `$formToPlan`/`$planMapping`/`$schemaCheck` (apenas comentários documentais).

### Pendências Runtime (com o operador)
- Rodar a migração Phinx no `lumix` (`20260710120000`) e o deploy (`Update => Core` do projeto) para recompilar `PaginasData.json`/`VariaveisData.json`/checksums dos 4 templates e do módulo.
- Validar no Editor HTML de formulários que o preview do formulário inicializa interativamente (config via `forms-render-editor-html`) e responde a submissões no iframe.
- No CRUD de planos: habilitar "Plano de Cadastro", vincular perfil via autocomplete e mapear os campos de e-mail/senha; salvar e conferir no banco.
- Ativar `is_signup` + mapeamento nos planos que antes eram de cadastro (a migração nasce com default 0/null).
- Executar o checkout público de um plano de cadastro no `lumix` e confirmar a criação da conta com o perfil dinamicamente atribuído.
- Restrição respeitada: nenhum `git commit`/`git push` executado.


## BATCH-071 - Adição de Novos Tipos de Campos no Módulo de Formulários (req-071)

- [x] **Construtor de Formulários (Admin)**:
  - [x] Dropdown de tipos em `forms.js` atualizado para conter `password`, `date`, `url` e `hidden`.
  - [x] Textarea de opções ativa para `hidden`, `text`, `textarea`, `number` e `date` (além de `select`/`radio`/`checkbox`), via helper `typeUsesOptions`.
  - [x] Placeholders dinâmicos por tipo (`optionsPlaceholder`): "Valor padrão do campo"/"Default field value" (hidden), `min:3\nmax:100` (text/textarea), `min:18\nmax:100\nstep:1` (number), `min:2026-01-01\nmax:2026-12-31` (date), lista `valor:Rótulo` (select/radio/checkbox).
- [x] **Renderizador de Widgets (Público)**:
  - [x] `forms.widget.php` suporta os novos tipos no bloco `type-input` (o `item#type` resolve para o tipo nativo).
  - [x] Valor padrão do `hidden` resolvido das opções (placeholder `[[item#value]]` + injeção de `value` quando ausente) e renderizado **só como `<input type="hidden">`** (sem label/wrapper).
  - [x] Atributos de limite injetados a partir do campo Opções: `minlength`/`maxlength` (text/textarea), `min`/`max`/`step` (number), `min`/`max` + classe `forms-date-picker` (date).
  - [x] Toggle de senha: `password` envolto em `.forms-password-wrapper` com botão `.forms-password-toggle` (ícone `eye link icon`), estilos inline compatíveis com Fomantic/Tailwind/Vanilla.
- [x] **Validações Centralizadas (Backend `formulario.php`)**:
  - [x] Limite de 254 caracteres estendido a `password` e `url` (nos dois blocos de max-length).
  - [x] Validação de URL via `FILTER_VALIDATE_URL` (quando preenchido).
  - [x] Validações min/max via campo Opções: comprimento (text/textarea), valor numérico (number) e faixa de data (date); min-length agora genérico (`#min#`).
- [x] **Frontend (`formulario.js`)**:
  - [x] Melhoria progressiva: date picker Fomantic (`.forms-date-picker`) quando `$.fn.calendar` existe; dropdown Fomantic em `select.ui.dropdown` quando disponível; toggle de senha vanilla delegado.
  - [x] Validações client-side de comprimento/valor/data/URL parseando `field.options` (`parseFieldLimits`), com mensagens de `prompts` + fallback.
- [x] **i18n**: novas mensagens em `form-ui` (pt-br/en): prompts (`prompt-min-length`/`prompt-max-length`/`prompt-min-value`/`prompt-max-value`/`prompt-min-date`/`prompt-max-date`/`prompt-url`) e ajax-messages (`ajax-message-max-length`/`-invalid-url`/`-min-value`/`-max-value`/`-min-date`/`-max-date`; `-min-length` genérico).

### Evidência de Validação (BATCH-071)

Evidência automatizada reportada pelo executor em 2026-06-30 (ambiente: PHP 8.4.8, PHPUnit 11.5.55):
- `node --check` → OK: `gestor/modulos/forms/forms.js`, `gestor/assets/interface/formulario.js`.
- `php -l` → OK (3/3): `gestor/modulos/forms/forms.widget.php`, `gestor/bibliotecas/formulario.php`, `tests/Unit/PHP/FormsWidgetFieldTypesTest.php`.
- JSON válido: `gestor/modulos/forms/forms.json`.
- Teste novo `tests/Unit/PHP/FormsWidgetFieldTypesTest.php` → **OK (9 tests, 32 assertions)**: parse de limites, valor padrão do hidden (input-only), injeção minlength/maxlength (text/textarea), min/max/step (number), faixa + classe picker (date), `type="url"`, wrapper/toggle de senha.
- `composer test` → **OK (76 tests, 287 assertions, 4 skipped gated por banco)** (era 67; +9 deste lote); a única `PHPUnit Deprecation` é pré-existente e alheia ao slice.
- `npm run test` (vitest) → **OK (2 files, 3 tests)**, sem regressão client-side.
- Cache-bust: módulo `forms` 1.0.0→1.1.0 (`forms.js`); biblioteca `formulario` 1.2.0→1.3.0 (`formulario.js`).

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` para recompilar o componente `form-ui` (pt-br/en) com as novas mensagens e atualizar os checksums/versões.
- Criar formulário no admin contendo campos de cada um dos 4 novos tipos + limites (`text min:5/max:15`, `number min:18/max:120/step:1`, `date min:2026-06-01/max:2026-06-30`, `hidden` com valor padrão) e confirmar no preview os inputs nativos e o valor do hidden.
- Enviar o formulário no site público (Tailwind) e em ambiente Fomantic, validando toggle de senha, calendário/dropdown, e o barramento de URL inválida e dos limites tanto no front quanto no backend.
- Restrição respeitada: nenhum `git commit`/`git push` executado.


## BATCH-072 - Remoção do Campo e Metadado "hosting_plan" no Módulo de Planos de Assinatura

- [x] **Banco de Dados (lumix)**:
  - [x] Criada migração Phinx `20260710130000_remove_hosting_plan_from_subscriptions_plans.php` para dropar a coluna `hosting_plan` da tabela `subscriptions_plans` com guard `hasColumn` e `down()` reversível.
- [x] **CRUD de Planos (lumix)**:
  - [x] Removido o campo `hosting_plan` do controlador `subscriptions-plans.php` em todos os fluxos de banco e placeholders.
  - [x] Removido o input `hosting_plan` e seu respectivo HTML dos 4 templates de adicionar/editar planos (pt-br/en).
- [x] **Hidratação de Configurações (lumix)**:
  - [x] `subscriptions.hooks.php` atualizado para remover a coluna do `banco_select` e a atribuição a `$planEntry['hosting_plan']`.

### Evidência de Validação (BATCH-072)

- `php -l` OK:
  - `lumix/gestor/modulos/subscriptions-plans/subscriptions-plans.php`
  - `lumix/gestor/modulos/subscriptions/subscriptions.hooks.php`
  - `lumix/gestor/db/migrations/20260710130000_remove_hosting_plan_from_subscriptions_plans.php`
- Grep de sanidade OK nos arquivos runtime do escopo: `hosting_plan` permanece apenas na nova migração de drop/reversão.
- `composer db:migrate` tentado em `lumix`, mas o checkout local não possui `composer.json`/`vendor/bin` e o Composer retornou: `There are no commands defined in the "db" namespace.`

### Pendências Runtime
- Rodar a migração Phinx no ambiente `lumix` configurado (o checkout local usado nesta execução não expõe `composer db:migrate`).
- Validar no CRUD de planos que o campo não é mais exibido nem gera erros ao criar/editar registros.
- Rodar a sincronização/deploy para garantir a atualização dos checksums/Data.json do módulo de planos.









