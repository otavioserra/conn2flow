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




