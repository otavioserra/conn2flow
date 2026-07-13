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


## BATCH-073 - Parametrização Dinâmica de E-mails e Integração de Estágios de Serviço em Planos

- [x] **Processamento de Checkout (`subscriptions` em `lumix`)**:
  - [x] A função `subscriptions_ajax_forms_process()` carrega o template de e-mail a partir de `$schema['email']['message_component']` com fallback a `order-confirmation-email`.
  - [x] A função utiliza o assunto do e-mail do campo `$schema['email']['subject']` realizando a substituição de `#name#`, `#code#`, `#formName#` (com fallback a variáveis do módulo).
  - [x] O status inicial e cor do e-mail de confirmação são baseados na flag `is_free` do plano cadastrado.
- [x] **Autocomplete de Componentes no forms (`conn2flow`)**:
  - [x] Rota AJAX `buscar-componentes` adicionada em `forms.php`.
  - [x] Inputs `#email_message_component` encapsulados em `.sig-autocomplete` nos 6 templates HTML de `forms` (pt-br/en, adicionar/editar/clonar).
  - [x] JS `forms.js` inicializa e delega os eventos de busca, seleção e limpeza do autocomplete.
- [x] **Layout do CRUD de Planos (`subscriptions-plans` em `lumix`)**:
  - [x] Os 4 templates HTML de planos reorganizados em segmentos lógicos (`Geral`, `Integração`, `Serviço`, `Pagamentos / Gateways`, `Mapeamento de Cadastro`).
  - [x] Restrições e sobras de layouts anteriores limpas dos arquivos do módulo.
- [x] **Estágios e Banco (`subscriptions-plans` em `lumix`)**:
  - [x] Migração de banco criada adicionando colunas `is_free` (boolean) e `initial_stage` (varchar) em `subscriptions_plans`.
  - [x] Dropdown select `#select-initial_stage#` adicionado na interface do CRUD e lógica do backend.
  - [x] AJAX `buscar-estagios` implementado no backend para filtrar estágios por `stage_type` e idioma.
  - [x] JS `subscriptions-plans.js` carrega e repopula dinamicamente os estágios iniciais ao mudar o "Tipo de serviço".

### Evidência de Validação (BATCH-073)

- `php -l` OK:
  - `conn2flow/gestor/modulos/forms/forms.php`
  - `lumix/gestor/modulos/subscriptions/subscriptions.ajax.public.php`
  - `lumix/gestor/modulos/subscriptions/subscriptions.php`
  - `lumix/gestor/modulos/subscriptions/subscriptions.hooks.php`
  - `lumix/gestor/modulos/subscriptions-plans/subscriptions-plans.php`
  - `lumix/gestor/db/migrations/20260710140000_add_free_and_initial_stage_to_subscriptions_plans.php`
- `node --check` OK:
  - `conn2flow/gestor/modulos/forms/forms.js`
  - `lumix/gestor/modulos/subscriptions-plans/subscriptions-plans.js`
- JSON válido: `lumix/gestor/modulos/subscriptions-plans/subscriptions-plans.json`, `conn2flow/gestor/modulos/forms/forms.json`.
- Grep de sanidade: sem sobras de `isCustomPlan`, `isCloudNano`, `emailSubjectId`, `custom-order-confirmation-email`, `email-subject-custom-confirmation` ou `form-pro-cloud-nano` em `subscriptions.ajax.public.php`.
- `composer test` em `conn2flow`: OK, 76 testes, 287 assertions, 4 skipped, 1 PHPUnit deprecation.
- `composer db:migrate` tentado em `lumix`, mas o checkout local retornou: `There are no commands defined in the "db" namespace.`

### Pendências Runtime
- Rodar a migração Phinx no ambiente `lumix` configurado (este checkout local não expõe `composer db:migrate`).
- Validar o autocomplete de componentes de e-mail no construtor de formulários.
- Validar o agrupamento visual de segmentos no CRUD de planos e a carga dinâmica de estágios por tipo de serviço.


## BATCH-074 - Dinamização Completa de Planos e Saneamento de Hardcodes no Subscriptions

- [x] **Saneamento de Dicionários Estáticos de Tradução de Nomes de Planos (§1)**:
  - [x] Helper `subscriptions_obter_nome_plano($planSlug, $lang)` em `subscriptions.hooks.php` consulta `subscriptions_plans.name` por `id`+`language` (status≠'D'), com fallback humanizado do slug.
  - [x] Substituídos os 5 dicionários estáticos (`$paypalPlanNames` ×2 nos hooks; `$paypalPlanNames`/`$planDisplayNames`/`$planNames` ×3 no controlador) pelo helper.
- [x] **Dinamização de Filtros por Prefixo de Formulário (`form-pro-%`) (§2)**:
  - [x] Helper `subscriptions_obter_form_ids_planos()` (form_ids distintos de planos ativos) + `subscriptions_obter_form_ids_clausula()` (gera `form_id IN (...)` escapado, fallback `LIKE 'form-pro-%'` quando vazio).
  - [x] Atualizados os 3 filtros (`subscriptions.hooks.php` ×2 do IPN/retorno + `subscriptions.php` ×1) para usar a cláusula dinâmica.
- [x] **Dinamização do Template de Checkout (`subscription-checkout.html`) (§3)**:
  - [x] Unificados os 6 blocos por plano em uma única estrutura com placeholders (`#plan_name#`, `#plan_description#`, `#plan_price_card#`, `#checkout_form#`) — ~990→~135 linhas (pt-br/en).
  - [x] **Divergência aprovada (orientação do usuário humano)**: em vez de blocos fixos `plan_fields_signup`/`plan_fields_custom`, o formulário do plano é renderizado dinamicamente via `forms_render(['form_id'=>...])` (lê `forms.html` por `form_id`), preservando os campos específicos de cada plano. Ver DEC-077.
- [x] **Simplificação e Saneamento do Template da Landing Page (`subscription.html`) (§4)**:
  - [x] Removido o marketing estático legado (Hero, Filosofia, VPS, “Por que pagar”, FAQ) — ~690→~55 linhas.
  - [x] Mantidos apenas header + grid dinâmico (`custom_layout`/`plans_grid`/`plan_card` + placeholders) + rodapé minimalista (pt-br/en).
- [x] **Saneamento do Template de Callback (`subscription-start.html`) (§5)**:
  - [x] Neutralizados os textos das seções de status (jargão hospedagem/cloud/projeto/“our subscription platform” → linguagem de assinatura), sem alterar marcadores nem placeholders.
  - [x] Preservadas as variáveis dinâmicas (`#plan_display_name#`/`#plan_name#`/`#customer_name#`/`#txn_id#`).
- [x] **Proteção de Campos Password em Submissões (`formulario.php`, conn2flow) (§6)**:
  - [x] Valor de campos `type==='password'` gravado como `''` em `forms_submissions.fields_values` (chave preservada); valor em texto plano mantido em `$_POST` para o signup.
  - [x] Defesa em profundidade: campo `password` omitido também no laço do e-mail de notificação/confirmação.
- [x] **Dinamização do Loop de Células de Plano na Página de Checkout (§7)**:
  - [x] Removidos `$plans`/`$planCelMap`; resolução dinâmica em `subscriptions_plans` por slug + aliases legados (`aceleracao`/`mentoria`/`personalizado`); gate de pagamento por `total_payments > 0`.

### Evidência de Validação (BATCH-074)

Evidência automatizada reportada pelo executor em 2026-06-30 (ambiente: PHP 8.4.8):
- `php -l` → OK (3/3): `subscriptions.php`, `subscriptions.hooks.php` (lumix), `gestor/bibliotecas/formulario.php` (conn2flow).
- Integridade de marcadores HTML balanceada (1 abre + 1 fecha cada): checkout (`valid_plan`/`invalid_plan`/`step_form`/`step_payment`/`step_success`) e landing (`custom_layout`/`plans_grid`/`plan_card`) nos dois idiomas; placeholders presentes (`#plan_name#`/`#plan_description#`/`#plan_price_card#`/`#checkout_form#`/`#paypal_primary_*#` no checkout; `#plan_*#` na landing).
- Grep de sanidade: sem `$paypalPlanNames`/`$planDisplayNames`/`$planNames`/`form-pro-%` (exceto o fallback intencional na cláusula) e sem `$activePlanCel`/`$planCelMap` (exceto comentário) no módulo.
- `composer test` (conn2flow) → **OK (76 tests, 287 assertions, 4 skipped gated por banco)**; sem regressão da §6 (a única `PHPUnit Deprecation` é pré-existente). Sem suíte no `lumix` (`phpunit.xml`/`composer test` ausentes, conforme batches anteriores).

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` no `lumix` para recompilar `PaginasData.json` + checksums dos templates alterados (checkout/landing/callback) e sincronizar.
- Confirmar que os formulários referenciados pelos planos têm `html` salvo na tabela `forms` (o checkout renderiza via `forms_render`; sem `html` o bloco do formulário sai vazio).
- Executar o checkout público de um plano pago, um gratuito (`is_free`) e um sob medida (`total_payments=0`): formulário renderizado, card de preço correto, fluxo form→payment/success sem avisos de PHP.
- Simular um retorno/IPN de webhook e confirmar que a submissão é localizada pela cláusula dinâmica `form_id IN (...)`.
- Confirmar no banco que submissões com campo `password` gravam `value` vazio em `fields_values` e que o signup continua criando a conta (senha lida de `$_POST`).
- Restrição respeitada: nenhum `git commit`/`git push` executado.


## BATCH-075 - Dashboard Site Toolbar, Agendamento de Páginas e Extensões do Editor HTML (req-075)

Lote consolidado (6 metas) implementado em slices sequenciais. Plano em [BATCH-075.md](../implementation/BATCH-075.md).

### Slice 1 — Meta 1: Botão "Acessar Site" no Layout Administrativo

- [x] Âncora `#menu-site-btn` adicionada ao grupo `ui icon buttons` da `.menu-controls` (primeiro item; ordem Acessar site → Dashboard 3D → Fechar), nos layouts admin `pt-br` e `en`.
- [x] `href="@[[pagina#url-raiz]]@"` (raiz pública), ícone `external alternate`, tooltip localizado ("Acessar site" / "Visit site"), `data-position="bottom left"` alinhado aos botões irmãos.
- [x] Notação `@[[...]]@` literal preservada (cópia do banco — não remover arrobas).

#### Evidência de Validação (Slice 1)

Evidência automatizada reportada em 2026-07-09:
- Balanceamento de tags: `<a>`/`</a>` = 2/2 em cada layout (pt-br e en) após inserção do elemento completo — sem desbalanceamento.
- Grep confirma `menu-site-btn` presente nos dois arquivos com o `href="@[[pagina#url-raiz]]@"` e os tooltips corretos por idioma.
- Arquivos alterados: `gestor/resources/pt-br/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.html`, `gestor/resources/en/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.html`.

#### Notas de escopo (Slice 1)

- **Posicionamento**: o intake diz "ao lado direito dos botões `#menu-dashboard3d-btn` e `#menu-close-btn`". Interpretação aplicada: inserido como primeiro item do mesmo grupo `ui icon buttons` (Acessar site → Dashboard 3D → Fechar), mantendo o "X" (Fechar) como último por UX. Ajuste trivial caso a chefia prefira posição diferente.
- **Alvo do link**: sem `target` (navega na mesma aba), espelhando o link de dashboard já existente no layout (linha 60). Fácil trocar para `target="_blank"` se preferir manter o admin aberto.

#### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` para recompilar `PaginasData.json`/layouts e sincronizar (o layout é recurso de banco).
- Validar no navegador que o botão aparece na barra de controles do menu admin e redireciona para a raiz pública do site.

### Slice 3a — Meta 2: Dashboard Site Toolbar (iframe + rota + injeção)

Reordenada à frente das Metas 5/6 porque o operador já havia scaffoldado a estrutura (rota, switch, funções vazias, refactor de `gestor_acesso`). Construído sobre esses esqueletos.

- [x] Página iframe `dashboard-site-toolbar` (pt-br/en) preenchida com Tailwind: barra de altura total (~30px via iframe), acesso ao painel, `<!-- menu -->`, botões "Editar Página" (`#c2f-toolbar-edit`, live edit — Meta 3, comportamento pendente) e "Editar no Painel" (`#c2f-toolbar-edit-advanced`, `target="_parent"`). Rota `dashboard-site-toolbar/` já registrada no `dashboard.json` (layout `layout-pagina-simples`, `framework_css: tailwindcss`).
- [x] `dashboard_site_toolbar()` (dashboard.php): lê `page_id`/`publisher_id` da query string (contexto da página hospedeira) e monta a URL de edição avançada — a página é carregada por `id` (=paginas.id) em ambos os módulos, mas o publicador precisa TAMBÉM do `publisher_id` (distinto do id da página) p/ fixar o contexto: `publisher-pages/editar/?id=<paginas.id>&publisher_id=<paginas.publisher_id>` quando publicador, senão `admin-paginas/editar/?id=<paginas.id>`.
- [x] `dashboard_site_toolbar_menu()` (dashboard.php): dropdown Tailwind (base `menus-dropdown`) com os módulos que o perfil pode acessar (link para a página raiz do módulo, `target="_parent"`), label i18n (`Módulos`/`Modules`).
- [x] `gestor_dashboard_toolbar()` (gestor.php): injeção do iframe fixo (top, 100% largura, 30px, `z-index` máximo) logo após `<body>` + inclusão do `dashboard.toolbar.js` (offset `margin-top:30px` na hospedeira) via `gestor_pagina_javascript_incluir(tipo=toolbar, modulo_id=dashboard)`. **Gating**: só para `usuario-id > 0` **e** `gestor_acesso('editar','admin-paginas')` **e** página pública (exclui layout `layout-administrativo-do-gestor` e `paginaIframe`). Resolve `id`/`publisher_id` da hospedeira consultando `paginas` por `caminho`+`language`. Chamada no bloco de finalização de `gestor.php` **antes** de `gestor_pagina_extra_head_e_javascript()` (após `gestor_pagina_widgets()`) p/ o JS auxiliar ser incluído, passando `caminho`.

#### Evidência de Validação (Slice 3a)

Evidência automatizada reportada em 2026-07-09 (PHP 8.4.8):
- `php -l gestor/gestor.php` → OK; `php -l gestor/modulos/dashboard/dashboard.php` → OK.
- `node --check gestor/modulos/dashboard/dashboard.toolbar.js` → OK.
- `dashboard.json` → JSON VALID.
- `composer test` → **76/76 (287 assertions, 4 skipped, 1 deprecation pré-existente)** — sem regressão após a injeção no core `gestor.php`.

#### Notas de escopo / decisões (Slice 3a)

- **Gating do render compartilhado**: a finalização em `gestor.php` (~L2180, `echo $_GESTOR['pagina']`) é usada por páginas públicas **e** admin (o `interface_finalizar()` não dá `exit`, retorna). Por isso o gating é explícito (exclui layout admin + iframe). Para anônimos, `gestor_dashboard_toolbar()` retorna antes de qualquer query (checagem de `usuario-id`), sem custo.
- **`dashboard.toolbar.js` via `gestor_pagina_javascript_incluir`** (correção do Chefe): o JS é incluído pela função padrão (`tipo=toolbar`, `modulo_id=dashboard` → URL `dashboard/toolbar.js`, mapeada ao físico `dashboard.toolbar.js` por `arquivo-estatico.php`; `tipo` sem ponto p/ casar `/^[A-Za-z0-9-]+$/`). Como ela empilha em `javascript-fim` (despejado por `gestor_pagina_extra_head_e_javascript()`), a chamada da toolbar roda ANTES dessa etapa.
- **URL de edição avançada (Meta 4)** (correção do Chefe): a página carrega por `id` (=paginas.id), mas o id da página e o `publisher_id` (id da publicação vinculada) **são distintos** — então o publicador recebe os dois: `publisher-pages/editar/?id=<paginas.id>&publisher_id=<paginas.publisher_id>` (o `publisher_id` fixa o contexto via `publisher_pages_publisher()`); comum: `admin-paginas/editar/?id=<paginas.id>`.
- **Pendente nesta Meta**: comportamento do botão "Editar Página" (edição live in-place) é a **Meta 3** (slice próprio); a detecção/URL de publicador pode ser refinada na **Meta 4**.

#### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` (recompila `PaginasData.json` da nova página `dashboard-site-toolbar` + remove `testes-do-dashboard`, aplica migração se houver) e sincronizar.
- Logar como usuário com permissão de editar páginas, abrir uma página pública do site e confirmar: a barra de ~30px aparece no topo, o conteúdo é empurrado 30px, o dropdown de módulos lista os módulos acessíveis, e "Editar no Painel" abre `admin-paginas`/`publisher-pages` conforme a origem da página.
- Confirmar que anônimos e páginas do painel administrativo **não** recebem a toolbar.

### Slice 3b — Meta 3: Edição visual live via toolbar (Approach B / Path Y — fundação)

Estratégia aprovada pelo Chefe: **Path Y** — o editor carrega o HTML ORIGINAL da tabela (com `@[[var]]@` e comentários de widget preservados; o html-editor já os trata como placeholders opacos), e o preview é que deve ganhar fidelidade visual à página live. Não se edita o DOM renderizado (isso queimaria vars/widgets). Sem motor de reconciliação.

- [x] **UX (aval do Chefe): editar NÃO abre overlay que substitui a página — revela controles ABAIXO da toolbar.** Botão "Editar Página" (`#c2f-toolbar-edit`) faz toggle de uma segunda linha `#c2f-toolbar-editbar` (44px, oculta) dentro do próprio iframe, e posta `c2f-toolbar:resize`/`edit-start`/`edit-cancel`/`edit-save` (pt-br/en).
- [x] **Toolbar dinâmica**: o iframe base 30px é redimensionado pelo host ao receber `c2f-toolbar:resize {height}` — mesma estratégia do seletor de linguagens (`global.js`). A toolbar mede dropdown de módulos (hover) + editbar aberta via `computeHeight()`. Transparência (`html,body{background:transparent}` + iframe `background:transparent`/`allowtransparency`) faz a área expandida mostrar a página atrás; só barra/dropdown/editbar ficam opacos.
- [x] **Refinamentos visuais**: marca usa `@[[pagina#url-raiz]]@images/LogomarcaIcone.png` + texto "Dashboard"; menu (PHP) recebeu `id="c2f-toolbar-menu"`; z-index da toolbar 2147483000.
- [x] **Motor marker-based (estratégia FINAL)** — decisão registrada: editor mostra a página RENDERIZADA; `@[[var]]@`/widgets viram caixas de destaque protegidas guardando o marcador; save reconstrói determinístico (caixa→marcador, resto→editado).
- [x] **Backend — endpoint de save**: `dashboard_ajax_site_toolbar_save()` (ajax-opcao `site-toolbar-save`) grava `html`/`css`/`css_compiled`/`html_extra_head` em `paginas` por `id`+`language`, permissão `gestor_acesso('editar','admin-paginas')`, `banco_update_campo` escapando. `php -l` OK, `composer test` 76/76. *(Teste unitário gated por banco — não adicionado, seguindo precedente de endpoints acoplados a banco/POST.)*
- [x] **Motor completo (marker-based)**:
  - Backend `site-toolbar-render` (`dashboard_ajax_site_toolbar_render`): lê `paginas.html` original, envolve widgets (wrapper por comentário + inline `@[[widgets#...]]@`) e variáveis `@[[var]]@` (em texto) em **caixas de destaque** `.c2f-dyn-box` (`data-c2f-marker` = base64 do marcador); vars em atributo resolvem para valor. Helpers `dashboard_site_toolbar_box/_render_widget/_boxes_widgets/_resolver_var/_boxes_variaveis`.
  - Wrapper `#c2f-page-content` no conteúdo da página (em `gestor_pagina_layout`, gated por `gestor_dashboard_toolbar_ativo()` — mesmo gate da injeção).
  - **Editor visual REAL (correção do Chefe — não `contentEditable`)**: `html-editor.js` adaptado para aceitar `contentRoot` (default `document.body` → clássico intacto); escopa `resolveEditable`/`getUserContentNodes`/TreeWalkers/inserção ao root; auto-init guardado por `window.__c2fHtmlEditorNoAutoInit`; expõe `window.HtmlEditorClass`. `dashboard.toolbar.js` (edit-start): fetch render → troca `#c2f-page-content` → carrega jQuery (se ausente) + `interface/html-editor.js` (noAutoInit) → `new HtmlEditorClass({contentRoot:#c2f-page-content})` → editor real ativo (caixas tracejadas de seleção, floating toolbar, styler, DnD). Save: `getCleanHtml()` → `DOMParser` → `reconstructOriginal` (caixas→marcadores) → POST → reload. Cancel = reload.
  - **Modal fallback vanilla (autorizado)**: `ensureFallbackModal()` injeta `#html-editor-modal` vanilla (inline styles portáveis) com `#text-field`/`#image-field`/`#code-field`+textareas quando ausente; `showModal`/`hideModal` vanilla quando não há Fomantic (`usaFomanticModal`); botões Salvar/Cancelar/backdrop ligados; code-edit usa `#element-code` sem CodeMirror. Bump `biblioteca-html-editor` 1.3.28→1.3.29. **Dependência dura**: só jQuery (carregado on-demand); Fomantic/CodeMirror opcionais (degradam graciosamente).

#### Evidência de Validação (Slice 3b — fundação)

Evidência automatizada em 2026-07-09 (PHP 8.4.8):
- `node --check gestor/modulos/dashboard/dashboard.toolbar.js` → OK.
- `php -l gestor/gestor.php` → OK; `php -l gestor/modulos/dashboard/dashboard.php` → OK.
- `composer test` → **76/76 (287 assertions, 4 skipped)** — sem regressão.

#### Pendências (refino + runtime)
- **Refino (próximo)**: fidelidade visual do preview do editor à página live (`html-editor-interface.js` `editorHtmlVisualConteudo`/srcdoc) — incluir head/CSS/framework/layout da página para eliminar a leve diferença de CSS.
- **Runtime (operador)**: após `Update => Core`, logar como editor, abrir página pública, clicar "Editar Página" → a barra de edição abre abaixo da toolbar e a página fica editável (caixas de var/widget protegidas); "Salvar alterações" persiste e recarrega; confirmar que `@[[var]]@` e widgets seguem preservados no `paginas.html` após salvar.

### Slice 2 — Meta 5: Agendamento de Páginas e Datas Retroativas

- [x] Migração `20260712100000_add_publish_window_to_paginas.php` (colunas `data_publicacao_inicio`/`data_publicacao_fim` datetime null, guards `hasColumn`, `down()` reversível). `paginas` já tinha `data_criacao`/`data_modificacao`.
- [x] Helpers globais `gestor_parse_datetime_br()` (dd/mm/yyyy HH:mm ou ISO → DATETIME; null se vazio/inválido) e `gestor_datetime_para_input()`.
- [x] Roteamento 404 (`gestor.php`): a query de página ativa (principal + fallback de idioma) filtra `(data_publicacao_inicio IS NULL OR <= NOW())` e `(data_publicacao_fim IS NULL OR >= NOW())` → fora da janela = 0 linhas = 404. Páginas de sistema (janela NULL) sempre passam.
- [x] Controladores `admin-paginas` e `publisher-pages` (adicionar/clonar): campos `data_publicacao_inicio/fim` (se informados) + `data_criacao`/`data_modificacao` retroativas (valor informado ou NOW()). Editar (diff): idem, com preserve-on-empty (só altera janela quando data válida; `*_limpar` para NULL) e `data_modificacao` respeitando override.
- [x] 12 forms (admin-paginas + publisher-pages × adicionar/editar/clonar × pt-br/en) com a seção "Agendamento e datas" (`<!-- agendamento-datas -->`, 4 `<input type="datetime-local">`) inserida após `<!-- permissao-pagina > --></div>`.

#### Evidência de Validação (Slice 2)
- `php -l` OK: `gestor.php`, `admin-paginas.php`, `publisher-pages.php`, migração. `composer test` **76/76**. Seção inserida nos 12 forms (perl bytes-crus preservou acentos `criação`/`modificação`).

#### Limitações / Pendências (Slice 2)
- Editar não pré-preenche os valores atuais das datas (campos vazios; preserve-on-empty evita apagar). Alteração **só de agendamento** (sem outro campo mudar) pode não disparar o save no editar (bloco dentro do finalize de update). Formato friendly dd/mm/yyyy é aceito pelo parser mas os inputs usam `datetime-local` (ISO). **Runtime**: migração via `Update => Core`; testar página agendada/expirada → 404; datas retroativas gravadas.

### Slice 4 — Meta 6: Atalho de Edição Administrativa de Widget no Editor HTML

- [x] Botão `he-tb-widget-admin` (ícone `external alternate`) no `#html-editor-floating-toolbar` (`html-editor.js`), visível só quando um `.conn2flow-widget-wrapper` está selecionado (`updateWidgetAdminButton` em `updateSelectionUI`).
- [x] `openWidgetAdmin()`: abre `<raiz><data-widget-type>/editar/?id=<data-widget-slug>` em nova aba (raiz via `window.parent.gestor.raiz` com fallback). Cache-bust `biblioteca-html-editor` 1.3.27→1.3.28.

#### Evidência de Validação (Slice 4)
- `node --check gestor/assets/interface/html-editor.js` → OK. `composer test` **76/76**. **Runtime**: selecionar um widget no editor e confirmar que o botão abre o módulo (ex.: `menus/editar/?id=<slug>`).

### Slice 5 — Rodada de Correções (pontos 1-5 + edição de layout + user_modified)

Feedback do Chefe após teste da fundação. Consolidado em slice único conforme a instrução "faça tudo sem parar até terminar".

- [x] **Ponto 1 — controles no `#c2f-toolbar-editbar`** (sempre visível durante a edição): botões `screenPagina` (desktop 100% / tablet 768px / mobile 375px), `html-editor-undo-btn`, `html-editor-redo-btn` e `html-editor-add-btn`. O undo/redo/add postam `c2f-toolbar:edit-undo`/`edit-redo`/`edit-add` ao host, que aciona `c2fEditor.undo()/redo()` e abre o painel "+" (`openAddPanel`). `screenPagina` posta `c2f-toolbar:edit-screen {width}` → `setEditScreen` redimensiona o `#c2f-layout-root`/`#c2f-page-content` na hospedeira.
- [x] **Ponto 1b — painel de inserção de widgets no add-btn**: o `html-editor-add-btn` abre painel host (`openAddPanel`) que lista elementos básicos **e** grupos de widgets (reuso de `html_editor_ajax_widget_types`/`html_editor_ajax_widgets_list` via endpoints `site-toolbar-widget-types`/`site-toolbar-widgets-list` no `dashboard.php`, que incluem `gestor_incluir_biblioteca('html-editor')`). Inserção via `c2f-toolbar:edit-insert` (elemento) e inserção direta de widget no editor.
- [x] **Ponto 2 — imagepick**: `requestBackgroundImage()` no `html-editor.js` cai em `window.prompt` de URL quando não há config `html_editor.imagepick` (contexto da toolbar não tem o módulo admin-paginas). O botão `he-bgimage-pick` volta a responder.
- [x] **Ponto 3 — datas via `formato.php`** (helpers removidos do `gestor.php`): `formato_data_hora_br_para_datetime()` aceita **BR** (`DD/MM/AAAA [HH:MM]`) **e ISO** (`AAAA-MM-DD[THH:MM]`, o `datetime-local` foi **mantido**), e `formato_data_hora_datetime_para_input()` faz o inverso (DATETIME→`Y-m-d\TH:i`). `admin-paginas.php`/`publisher-pages.php` passam a incluir `formato` e usar essas fns.
- [x] **CodeMirror in-place**: `dashboard.toolbar.js` (`ensureCodeMirror`/`initCodeMirrorField`) inicializa o CodeMirror sobre o `#element-code` do modal do editor live (carrega CSS/JS on-demand), espelhando o `admin-paginas`.
- [x] **Ponto 4 — edição do LAYOUT** (via `layout_id`, só o body-inner): `dashboard_ajax_site_toolbar_render` retorna também `layout_id`/`layout_html` (body-inner do registro `layouts` do banco, renderizado com caixas e `$preservar_atributos=true` para manter `@[[pagina#url-raiz]]@` etc. literais); `#c2f-layout-root` (`data-layout-id`) envolve o body-inner na hospedeira. `saveEdit` divide: conteúdo (`#c2f-page-content`)→`paginas`; layout (`#c2f-layout-root` com o `#c2f-page-content` recolocado como `__C2F_CORPO__`→`@[[pagina#corpo]]@`)→`layouts` via `dashboard_site_toolbar_salvar_layout` (safeguard exige `@[[pagina#corpo]]@` no body-inner salvo).
- [x] **Ponto 5-save — histórico + backup**: `dashboard_ajax_site_toolbar_save` grava, além do campo, `interface_backup_campo_incluir` + `interface_historico_incluir` no módulo-dono (`publisher-pages` se `publisher_id`, senão `admin-paginas`), espelhando o `admin-paginas`.
- [x] **Ponto 5-dropdown — restauração de backups no editbar**: botão `#c2f-backups-btn` posta `c2f-toolbar:edit-backups`; host `openBackupPanel` busca `site-toolbar-backups` (lista `id`/`versao`/`data` de `backup_campos` do `id_paginas`+módulo-dono+`campo='html'`), e ao clicar busca `site-toolbar-backup-get` (valor renderizado com caixas) e injeta em `#c2f-page-content`.
- [x] **user_modified=1**: o save marca `user_modified=1` (+`versao`+1 +`data_modificacao=NOW()`) tanto em `paginas` quanto em `layouts` (quando o layout é alterado no mesmo salvamento).

#### Evidência de Validação (Slice 5)

Evidência automatizada em 2026-07-09 (PHP 8.4.8):
- `node --check gestor/modulos/dashboard/dashboard.toolbar.js` → OK.
- `php -l` OK: `gestor/modulos/dashboard/dashboard.php`, `gestor/gestor.php`, `gestor/bibliotecas/formato.php`.
- `composer test` → **76/76 (287 assertions, 4 skipped, 1 deprecation pré-existente)** — sem regressão.
- Cache-bust `biblioteca-html-editor` até `1.3.30`.
- Arquivos alterados: `dashboard.php`, `dashboard.toolbar.js`, `dashboard-site-toolbar.html` (pt-br/en), `html-editor.js`, `html-editor.php` (versão), `formato.php`, `gestor.php`, `admin-paginas.php`, `publisher-pages.php`.

#### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` (aplica migração de janela de publicação + recompila `PaginasData.json`/layouts) e sincronizar.
- Logar como editor, abrir página pública, "Editar Página": confirmar controles no editbar (telas responsivas, undo/redo, add com widgets, backups), imagepick por URL, edição de conteúdo **e** de layout (body-inner), salvamento com histórico/backup e `user_modified=1` em `paginas`/`layouts`, e restauração de um backup pelo dropdown.

---
## BATCH-076 - Opção de Exclusão de contents/ em Tarefas de Deploy e Sincronização (req-076)

- [x] **Configuração de Tarefas (`.vscode/tasks.json`)**:
  - [x] Adicionar o input seletor `contentsChoice` (Sim/Não) com valor padrão "Sim".
  - [x] Atualizar as tarefas `"🗃️ Projects - Deploy Current Project"`, `"🗃️ Projects - Deploy Project -> ID"` e `"🗃️ Projects - Synchronize => Files -> ID"` para aceitar e propagar o parâmetro `--contents ${input:contentsChoice}`.
- [x] **Script de Deploy (`deploy-project-v2.sh`)**:
  - [x] Aceitar o parâmetro `--contents` e tratar o valor "Não" para excluir a pasta `contents/` da compressão 7z.
  - [x] Quando `gitDeploy` estiver ativo e a opção for "Não", filtrar a lista de arquivos alterados para excluir qualquer alteração vinda de `contents/`.
- [x] **Script de Sincronização (`synchronize-project.sh`)**:
  - [x] Aceitar o parâmetro `--contents` e tratar o valor "Não" para adicionar `--exclude "contents/"` no comando `rsync`.
- [x] **Validação**:
  - [x] Sintaxe JSON e bash (`bash -n`) corretas.
  - [x] Confirmar que a exclusão da pasta de uploads de fato ocorre no ZIP e no Rsync em testes manuais rápidos.

### Evidência de Validação (BATCH-076)

Evidência automatizada e estática reportada pelo executor em 2026-07-09:
- Sintaxe de `.vscode/tasks.json` validada com sucesso.
- Sintaxe bash de `deploy-project-v2.sh` e `synchronize-project.sh` validada via `bash -n` e opções de `--help` testadas com sucesso.
- `git diff --check` executado sem erros nos arquivos modificados.

---
## BATCH-077 - Desacoplamento de Scripts do Iframe e Mapeamento Inteligente de Variáveis no Live Editor (req-077)

### Slice 1 — Desacoplamento do script da toolbar
- [x] Lógica JS da toolbar extraída do `<script>` inline para o arquivo estático `gestor/modulos/dashboard/dashboard.iframe-toolbar.js`.
- [x] Tag `<script>…</script>` removida dos 2 templates `dashboard-site-toolbar.html` (pt-br/en).
- [x] Injeção via `gestor_pagina_javascript_incluir(Array('tipo'=>'iframe-toolbar','modulo_id'=>'dashboard'))` em `dashboard_site_toolbar()` (URL `dashboard/iframe-toolbar.js` → físico `dashboard.iframe-toolbar.js`, casando a regex `/^[A-Za-z0-9-]+$/` do `arquivo-estatico.php`).
- [x] Órfão vazio `dashboard.iframe.toolbar.js` (0 bytes, sem referências) removido.

### Slice 2 — Mapeamento inteligente de variáveis/atributos
- [x] Backend `dashboard_ajax_site_toolbar_render` devolve o HTML CRU original (`content_raw` = `paginas.html`; `layout_raw` = body-inner do layout com o slot `@[[pagina#corpo]]@` preenchido por `#c2f-raw-content`). Campos legados (`html`/`layout_html`) preservados p/ retrocompat.
- [x] `startEdit` (`dashboard.toolbar.js`) **não** substitui mais `root.innerHTML`; guarda o cru em `#paginaHTMLAntesEdicao` (display:none) e chama `mapTree(root, backup)` preservando o DOM vivo.
- [x] Variável em **atributo** → tag viva marcada com `data-c2f-variable="ID_VAR_N"` + `varMap[ID]={param,variable,valor}` (valor resolvido segue visível no editor).
- [x] Variável em **texto** → `annotateTextVars` envolve o trecho num `span.c2f-var-box` protegido (`contenteditable=false`).
- [x] **Widget** → `mapTree` envolve a expansão viva num `div.c2f-widget-box` atômico via âncora estrutural.
- [x] `saveEdit`/`reconstructOriginal` restaura `data-c2f-variable`→`@[[…]]@` (só quando o valor não foi alterado), caixas→marcador original, e **separa** `#c2f-page-content`→`paginas` do resto do layout (`#c2f-page-content`→`@[[pagina#corpo]]@`)→`layouts`.

### Evidência de Validação (BATCH-077)

Evidência automatizada reportada pelo executor em 2026-07-10:
- `node --check` → OK (2/2): `dashboard.iframe-toolbar.js`, `dashboard.toolbar.js`.
- `php -l gestor/modulos/dashboard/dashboard.php` → `No syntax errors detected`.
- Balanceamento de tags nos 2 HTMLs: `<div>` 5/5, `<script>` 0 (script removido).
- **Teste de lógica do motor** (harness `happy-dom` carregando o `dashboard.toolbar.js` REAL e exercitando o fluxo via message-bus `edit-start`/`edit-save` com `fetch` mockado): **16/16 checks OK**, cobrindo:
  - **Atributos (caso de aceite — página raiz do sistema)**: `<a>`/`<img>` com `@[[pagina#url-raiz]]@` recebem `data-c2f-variable`; valor resolvido preservado no editor; no save os `@[[pagina#url-raiz]]@` são reconstruídos em `href`/`src`, `data-c2f-variable` removido e `<h1>` intacto.
  - **Variável de texto**: `@[[usuario#nome]]@` vira caixa protegida com o valor resolvido; save reconstrói o marcador no texto.
  - **Widget**: bloco de widget vira `.c2f-widget-box` atômica contendo o render vivo; `<footer>` fora da caixa preservado; save reconstrói o marcador `<!-- widgets#… -->` (com o mockup) e **não** grava o HTML renderizado.
- Decisão registrada: [DEC-079](../decisions/DECISION-LOG.md#dec-079---2026-07-10---accepted).
- Arquivos: novo `dashboard.iframe-toolbar.js`; `dashboard.toolbar.js`; `dashboard.php`; `dashboard-site-toolbar.html` (pt-br/en); removido `dashboard.iframe.toolbar.js`.

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` e sincronizar (as páginas `dashboard-site-toolbar` são recurso de banco — a remoção do `<script>` do template e o novo JS estático só refletem após o deploy).
- Logar como editor, abrir uma página pública com `@[[pagina#url-raiz]]@` (ex.: raiz do sistema), "Editar Página" e confirmar: (1) o iframe da toolbar inicializa sem erros no console (script agora estático); (2) o layout/scripts/CSS do site vivo continuam funcionando durante a edição (sem quebra); (3) após editar e salvar, as imagens/links com `@[[pagina#url-raiz]]@` permanecem preservados no banco (não queimados) — inspecionar `paginas.html`/`layouts.html`.
- Confirmar o comportamento do editor visual real (`html-editor.js`): que `getCleanHtml()` preserva as caixas `.c2f-dyn-box` e os atributos `data-c2f-variable` do DOM anotado antes do `reconstructOriginal`.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-078 - Correções no Live Editor: Trava de Widgets, Submenu do Painel "+" e Isolamento de Estilos (req-078)

### Correção 1 — Travar edição/seleção de elementos internos de widgets
- [x] `resolveEditable` (`html-editor.js`) reconhece `.c2f-dyn-box` como bloco atômico (`element.closest('.c2f-dyn-box')` → retorna a caixa), paridade com `.conn2flow-widget-wrapper`.
- [x] CSS (`injectStyles`): `.c2f-dyn-box{user-select:none}` + `.c2f-dyn-box *{pointer-events:none!important;user-select:none!important}` deixam o conteúdo interno (texto/imagem/links do widget) inerte a clique/seleção.

### Correção 2 — Painel "+" expande a categoria sem fechar/roubar o clique
- [x] Causa-raiz: `#c2f-add-panel` fora de `isEditorOwned` → o clique em *capture* do editor usava `elementsFromPoint`, atravessava o painel, selecionava o conteúdo atrás e fazia `stopPropagation()`, matando o `toggleWidgetGroup`.
- [x] `isEditorOwned` reconhece `#c2f-add-panel` e `#c2f-backup-panel` (via `closest`) → early-return no clique/hover; o clique propaga normal para o handler do painel (lógica de toggle do `dashboard.toolbar.js` inalterada).

### Correção 3 — Blindagem de CSS dos controles injetados na página hospedeira
- [x] `injectStyles`: hardening com `!important` de `color`/`background`/`-webkit-text-fill-color`/`font`/`caret-color` em `#html-editor-floating-toolbar .he-tb-btn` (+ ícones `<i>` e `he-tb-deselect`), `#html-editor-tailwind-styler` (+ `input`) e `#html-editor-modal textarea,input[type="text"],label`.
- [x] `!important` no container do styler não vaza para filhos com cor própria (herança CSS não propaga `!important`) → tags/rótulos coloridos preservados.

### Cache-bust
- [x] Live: `dashboard.toolbar.js` carrega `interface/html-editor.js?v=c2f1` → `?v=c2f2`.
- [x] Admin: `biblioteca-html-editor` `1.3.30` → `1.3.31` (`html-editor.php`).

### Evidência de Validação (BATCH-078)

#### Rodada 1 (R1)
Evidência automatizada reportada pelo executor em 2026-07-10 (ambiente: PHP 8.4.8):
- `node --check` → OK (2/2): `gestor/assets/interface/html-editor.js`, `gestor/modulos/dashboard/dashboard.toolbar.js` (garante que a template literal do CSS injetado fecha corretamente).
- `php -l` → OK (2/2): `gestor/bibliotecas/html-editor.php`, `gestor/modulos/dashboard/dashboard.php`.
- `composer test` → **OK (76 tests, 287 assertions, 4 skipped gated por banco)**; a única `PHPUnit Deprecation` é pré-existente e alheia a este slice. Sem regressão.
- Sem teste unitário novo: slice de UI/CSS (precedente BATCH-066/068). As correções 1 e 2 são reconhecimento de classe via `closest` (baixo risco); a 3 é CSS. Instanciar `HtmlEditorClass` em happy-dom exigiria jQuery + bootstrap pesado do `init()` (frágil, jQuery ausente no node_modules); o motor de mapeamento já foi coberto no BATCH-077.
- Decisão registrada: [DEC-080](../decisions/DECISION-LOG.md#dec-080---2026-07-10---accepted).
- Arquivos: `gestor/assets/interface/html-editor.js` (`isEditorOwned`, `resolveEditable`, `injectStyles`); `gestor/modulos/dashboard/dashboard.toolbar.js` (cache-bust); `gestor/bibliotecas/html-editor.php` (versão).

#### Rodada 2 (R2) — Widgets Sem Wrapper, Aparência e Ícones SVG (2026-07-10)
- [x] **R2-P4 (Widget Sem Wrapper)**: Mapeamento de elementos-raiz do render do widget com atributos `data-c2f-widget-id`/`root`/`marker` em vez de criar um contêiner `.c2f-widget-box` (preservando herança e seletores CSS do site hospedeiro).
- [x] **R2-P4 (Reconstrução)**: `reconstructOriginal` reconstrói o marcador original a partir do elemento marcado com `data-c2f-marker` e remove os elementos irmãos do mesmo grupo.
- [x] **R2-P1 (Proteção Interna)**: `resolveEditable` reconhece `[data-c2f-widget-id]` como atômico e o CSS injetado desabilita ponteiros/seleção nos nós internos (`pointer-events: none`, `user-select: none`).
- [x] **R2-P1 (Outline e Label)**: Exibição visual do widget via `outline: 1px dashed #fbbf24!important` e label absoluto flutuante `::before` (identificando tipo e slug do widget) sem interferir no fluxo/layout da página (utiliza `position: relative`).
- [x] **R2-P2 (Atalho Admin)**: Botão `#he-tb-widget-admin` adaptado para identificar `data-widget-type` unificando o tratamento de wrappers clássicos e os novos elementos-raiz do live editor.
- [x] **R2-P3 (Ícones SVG Inline)**: Substituição de tags `<i class="... icon">` do Fomantic por SVGs inline embutidos no floating toolbar (9 botões) e no styler, garantindo que apareçam em qualquer site público independente das fontes da UI administrativa.
- [x] **Cache-Bust**: Query-string atualizada para `?v=c2f3` (`dashboard.toolbar.js`) e biblioteca setada para `1.4.0` (`html-editor.php`).

**Evidência de Teste R2**:
* Criado e executado o script de smoke test [`_smoke_batch078_r2.mjs`](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/_smoke_batch078_r2.mjs) em `happy-dom` simulando a carga do live editor, mapeamento de widget sem wrapper, anotação de atributos fora do widget e salvamento (reconstruct).
* Resultado: **18/18 checks aprovados (PASS)** sem qualquer vazamento de atributos ou tags de wrapper no HTML reconstruído.
* `composer test` mantido verde (**76/76 testes aprovados**).

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` e sincronizar (o novo `html-editor.js`/`dashboard.toolbar.js` e a versão da biblioteca só refletem após o deploy; limpar cache do navegador ajuda, embora o `?v=c2f3` já force o reload no live).
- **Widgets**: entrar em modo de edição e confirmar que widgets (como menus e destaques) aparecem com outline amarelo tracejado e label identificador no topo ao passar o cursor, e que seus elementos internos **não** podem ser focados/editados.
- **Painel "+"**: clicar no "+" da toolbar, clicar no grupo "Menus" e confirmar que o submenu expande sem fechar o painel.
- **Estilos e Ícones**: confirmar que os ícones do floating toolbar aparecem perfeitamente renderizados e que os campos/textareas no modal de edição exibem fontes com contraste correto e legível, livre de herança do CSS do site.
- Restrição respeitada: nenhum `git commit`/`git push` executado.


---
## BATCH-079 - Mapeamento no Pai de Widgets Múltiplos, Image Picker, Filtro/Agrupamento de Módulos e Backups Página×Layout (req-079)

- [x] **Item 1+7 — Marcadores preservados + mapeamento no PAI (marker-based, ver adendo DEC-081)**: `gestor_pagina_widgets` mantém os comentários `<!-- widgets#SIG < --> … > -->` no DOM vivo quando `gestor_dashboard_toolbar_ativo()`; `mapTree` delimita cada widget pela fronteira exata desses comentários (sem heurística de alinhamento) — marca o `liveParent` (`data-c2f-widget-parent`) quando o widget é o único conteúdo do contêiner, senão os elementos-raiz; `reconstructOriginal` substitui o `innerHTML` do pai pelo marcador cru (preserva `<nav>`/`<ul>`). Widgets duplicados/consecutivos ficam separados. Guarda contra marcar a raiz editável.
- [x] **Item 2 — Ícones SVG**: helper `svgIcon()` substitui as `<i class="… icon">` restantes em `html-editor.js` (accordion `dropdown`, bgimage `folder open`/`trash`, botões `kind:'icon'` do styler).
- [x] **Item 3 — Image picker no modal do live editor**: botão `_html-editor-imagepick-btn` ao lado do `#element-src`; `openLiveImagePicker` monta iframe → `admin-arquivos/?paginaIframe=sim` e a seleção preenche o input (`raiz` via construtor).
- [x] **Item 4 — Modal redimensionável**: `resize:both;overflow:auto` + min/max na caixa do modal fallback.
- [x] **Item 5 — Filtro de módulos**: `#c2f-modules-filter` no topo do dropdown + listener `input` (case-insensitive) em `dashboard.iframe-toolbar.js`.
- [x] **Item 6 — Agrupamento por categoria**: `dashboard_site_toolbar_menu` agrupa por `modulos_grupos` (cabeçalhos `.c2f-group-header` + itens `.c2f-menu-item`); o filtro oculta cabeçalhos de grupos vazios.
- [x] **Item 8 — Backups Página × Layout**: backend retorna `page_backups`/`layout_backups` e `backup-get` aceita `type=page|layout`; frontend com 2 colunas e restauração independente (layout preserva o `#c2f-page-content` vivo).

### Evidência de Validação (BATCH-079)

Reportada pelo executor em 2026-07-10 (PHP 8.4.8). **Rodada 2 (marker-based)** após teste visual do Chefe (um `<nav>` com dois `menu-header-padrao` idênticos ainda marcava o `<a>`):
- `node --check` → OK: `dashboard.toolbar.js`, `dashboard.iframe-toolbar.js`, `html-editor.js`.
- `php -l` → OK: `gestor.php`, `dashboard.php`, `html-editor.php`.
- Vitest `tests/Unit/JS/dashboard.toolbar.test.js` (happy-dom, carrega o `dashboard.toolbar.js` real via hook injetado) → **3/3 specs**:
  - Cenário A: widget único é o único conteúdo do `<nav>` → marca o PAI, comentários removidos do vivo, reconstrução por `innerHTML` com o mockup cru e a tag `<nav>` preservada.
  - Cenário B: 2 widgets idênticos consecutivos no MESMO `<nav>` → 2 `data-c2f-widget-id` distintos (nav não vira parent), reconstrução com 2 marcadores.
  - Cenário C: widget + rodapé estático no mesmo contêiner → widget por-elemento, rodapé preservado, 1 marcador.
- `composer test` → **76/76 (287 assertions, 4 skipped)** sem regressão.
- `npm run test` (vitest) → **6/6** total aprovados (incluindo as 3 specs do live editor e 3 widgets legados).
- Cache-bust: `biblioteca-html-editor` `1.4.1`→`1.4.2`.

### Pendências Runtime (com o operador)
- Rodar `🗃️ Projects - Update => Core` + limpar cache (o `dashboard.toolbar.js`/`gestor.php` mudaram; hard-refresh no navegador).
- **Item 1/7**: abrir uma página com um menu de links (widget) que é o único conteúdo de um `<nav>` e confirmar que o outline amarelo envolve todo o `<nav>` com um único label; num `<nav>` com dois menus idênticos, confirmar dois widgets independentes.
- **Item 2**: confirmar que todos os ícones do styler/bgimage/accordion renderizam no live editor (sem Fomantic).
- **Item 3/4**: editar uma imagem, abrir o selecionador, escolher um arquivo do `admin-arquivos` e ver a URL preencher o `#element-src`; redimensionar o modal pelo canto.
- **Item 5/6**: abrir o dropdown de módulos, ver as categorias e filtrar por texto (cabeçalhos vazios somem).
- **Item 8**: abrir o painel de backups e confirmar as 2 colunas; restaurar um backup de layout preservando o conteúdo vivo, e um de página sem afetar o layout.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-080 - Integração de Modelos de Sessão e Assistente IA no Live Editor (req-080)

- [x] **Fix BATCH-079 — widget atômico**: `resolveEditable` resolve qualquer elemento do grupo para a raiz (`data-c2f-widget-root`); `positionOverlay`/`updateSelectionUI` usam a união dos bounding boxes (`elementRect`/`unionRect`); CSS marca todos os `[data-c2f-widget-id]`. Não se seleciona link a link; o outline cobre o widget inteiro.
- [x] **Item 1/2 — Modelos**: botão `.he-tb-templates` (só no live), painel `#c2f-tpl-panel` com busca `#modelos-search-input` (debounce), select de framework, relação Substituir/Antes/Depois, grid `#modelos-cards` paginado; `insertTemplate` insere HTML no DOM vivo + CSS em `<style id="c2f-templates-css">`. AJAX `site-toolbar-templates-load`.
- [x] **Item 3 — Assistente IA**: botão `.he-tb-ai`, painel `#c2f-ai-panel` (abas Prompt/Modo/Config), selects via `site-toolbar-ia-init` (`ia_editor_dados`), texto de prompt/modo via `site-toolbar-ia-prompt`/`-ia-mode`, geração via `site-toolbar-ia-request`; `applyAiResult` troca o elemento + injeta CSS.
- [x] **Item 4 — Background picker**: `requestBackgroundImage` no live usa `openLiveImagePicker` (`imagePickerTarget='background'`); `bindLiveImagePicker` roteia p/ `applyBackgroundImage`. `.he-bgimage-clear` → `clearBackgroundImage`.
- [x] **Item 5 — Backend**: 5 rotas `site-toolbar-*` no `dashboard.php` reusando `html-editor.php`/`ia.php`; `ia_editor_dados` novo; busca de templates retrocompatível.

### Evidência de Validação (BATCH-080)

Reportada pelo executor em 2026-07-10:
- `node --check` → OK: `html-editor.js`, `dashboard.toolbar.js`.
- `php -l` → OK: `dashboard.php`, `ia.php`, `html-editor.php`.
- Vitest `npm run test` → **11/11** (4 arquivos); novo `tests/Unit/JS/html-editor.live.test.js` → **5/5**: botões Modelos/IA presentes só no live (com `raiz`) e ausentes no admin; painéis Modelos e IA abrem; `resolveEditable` de qualquer link do grupo → raiz (widget atômico); `requestBackgroundImage` roteia p/ `background`.
- `composer test` → **76/76 (287 assertions, 4 skipped)** sem regressão.
- Cache-bust: `interface/html-editor.js?v=c2f4`→`?v=c2f5` + `biblioteca-html-editor` `1.4.2`→`1.4.3`.

### Pendências Runtime (com o operador)
- Deploy `🗃️ Projects - Update => Core` + hard-refresh (o `html-editor.js`/`dashboard.php`/`ia.php` mudaram).
- **Modelos**: selecionar um elemento, abrir Modelos, buscar/paginar, escolher "Inserir depois" e ver a seção com CSS aplicado no DOM vivo.
- **IA**: abrir o Assistente IA (exige conexão configurada em `servidores_ia`), dar uma instrução curta, gerar e ver o elemento alterado na hora.
- **Background**: no styler, `.he-bgimage-pick` abre o gerenciador de arquivos e aplica a imagem como `background-image`; `.he-bgimage-clear` remove.
- **Widget atômico**: no `<nav>` com 2 menus idênticos, cada widget seleciona como um bloco só (outline cobrindo os 7 links, um label), sem selecionar link a link.
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-081 - CodeMirror no Assistente IA, Correção no Save, Dropdowns da Toolbar e Painel "+" em Duas Colunas (req-081)

- [x] **§1 — CodeMirror + resize no Assistente IA**: `initAiCodeMirror` instancia CodeMirror (markdown) em `#c2f-ai-instruction`/`#c2f-ai-mode-text` quando `window.CodeMirror` existe (dedup por `.CodeMirror` irmão), com helpers `aiGet/aiSet` (fallback ao textarea) usados por `submitAi`/`loadAiInit`/handlers; `dashboard.toolbar.js` carrega `mode/markdown` + `mode/javascript`. CSS `.c2f-he-live-box{resize:both;overflow:auto}` (Modelos e IA redimensionáveis).
- [x] **§2 — Save intermitente (causa-raiz) + deseleção**: blindagem do corpo AJAX no `gestor.php` (`ob_start`/`ob_end_clean` + `JSON_INVALID_UTF8_SUBSTITUTE`) elimina o corpo não-JSON por warning/notice espúrio (origem do `.catch` → "Erro ao salvar a página." sem erro de rede/log). Cliente (`dashboard.toolbar.js`): `deselectAll()` antes do `getCleanHtml`, guarda de prontidão do editor e parse tolerante com diagnóstico. Novo `deselectAll()` no `html-editor.js`.
- [x] **§3 — Dropdowns de Usuário e Página**: 2 HTMLs (pt-br/en) com dropdowns clicáveis; Usuário (`@[[usuario#nome]]@` + Perfil/Sair); Página (`#page_title#` + Editar Página/Avançado + Criar Nova/Clonar dinâmicos publisher×admin resolvidos em `dashboard_site_toolbar()`); toggle/altura em `dashboard.iframe-toolbar.js`.
- [x] **§4 — CRUD de prompts**: botões `ai-prompt-new/edit/del/clear` + handlers reusando 3 rotas novas `site-toolbar-ia-prompt-new/-edit/-del` (→ `ia_ajax_prompt_novo/_edit/_del`); select atualizado após cada operação.
- [x] **§5 — Código Customizado**: item no painel "+" → `openCustomCodePanel()` (CodeMirror htmlmixed) → `insertCustomHtml()` insere no DOM vivo; `#c2f-custom-panel` em `isEditorOwned`.
- [x] **§6 — Painel "+" 2 colunas**: Elementos × Widgets (subcolunas grupos × itens), clique no grupo, autocomplete (debounce → AJAX cross-grupo), "Carregar mais"; backend `html_editor_widgets_buscar()` (paginação/busca/anti-injection), opção `site_toolbar.widgets_por_pagina` (padrão 10) no `dashboard.json`; editor clássico inalterado.

### Evidência de Validação (BATCH-081)

Reportada pelo executor em 2026-07-11 (ambiente: PHP 8.4.8):
- `php -l` → OK (4/4): `gestor/gestor.php`, `gestor/modulos/dashboard/dashboard.php`, `gestor/bibliotecas/html-editor.php`, `gestor/bibliotecas/ia.php`.
- `node --check` → OK (3/3): `gestor/assets/interface/html-editor.js`, `gestor/modulos/dashboard/dashboard.toolbar.js`, `gestor/modulos/dashboard/dashboard.iframe-toolbar.js`.
- `dashboard.json` → JSON válido (com `site_toolbar.widgets_por_pagina`).
- Vitest `npm run test` → **14/14** (4 arquivos); `tests/Unit/JS/html-editor.live.test.js` → **8/8** (+3 do lote): CRUD de prompts presentes e `aiPromptClear`; `deselectAll` limpa seleção/overlay; `openCustomCodePanel`+`insertCustomHtml` (painel montado, `isEditorOwned`, inserção após a seleção). Stub de CodeMirror em `setup.js` estendido com `setValue/getValue` diretos (espelha CM5 real).
- `composer test` → **76/76 (287 assertions, 4 skipped)** sem regressão (1 PHPUnit Deprecation pré-existente e alheia).
- Cache-bust: `interface/html-editor.js?v=c2f7`→`?v=c2f8`; `biblioteca-html-editor` `1.4.6`→`1.4.7`; `dashboard.json` versao `1.0.8`→`1.0.9`.

### Pendências Runtime (com o operador)
- Deploy `🗃️ Projects - Update => Core` + hard-refresh (mudaram `gestor.php`, `dashboard.php`, `html-editor.php`, `ia.php`, os 2 HTMLs do toolbar, `dashboard.json` e os JS do live/toolbar).
- **§1**: abrir o Assistente IA e confirmar CodeMirror nos campos de instrução/modo e a caixa redimensionável pelo canto.
- **§2**: reproduzir o cenário de save (editar e salvar sem mudar nada, repetidas vezes) e confirmar que **não** há mais o alerta intermitente "Erro ao salvar a página."; salvar com elemento selecionado/hover ativo e confirmar sucesso.
- **§3**: abrir os dropdowns de Usuário (Perfil/Sair) e Página (Editar/Avançado/Criar Nova/Clonar), validando os links dinâmicos publisher×admin e o nome do usuário.
- **§4**: criar/editar/excluir um prompt pelo painel do assistente e ver o select refletir.
- **§5**: inserir um bloco via "Código Customizado" e vê-lo entrar no DOM vivo.
- **§6**: no painel "+", validar as duas colunas, grupos×itens, o autocomplete e o "Carregar mais" (limite configurável).
- Restrição respeitada: nenhum `git commit`/`git push` executado.

---
## BATCH-082 - Carregamento de Widgets, Seleção de Modelos, Restauração de Backups e Isolamento Multi-usuário no Live Editor (req-082)

- [x] **§1 — Widget dinâmico**: rota `site-toolbar-widget-render` → `dashboard_ajax_site_toolbar_widget_render()` (delega a `html_editor_ajax_widget_render()`); listener dedicado no `dashboard.toolbar.js` intercepta a string JSON `c2f-he:widget-render`, faz POST na rota e posta de volta `c2f-he:widget-rendered` (o motor chama `applyWidgetRender`).
- [x] **§2 — Seleção do modelo**: `insertTemplate` rastreia o 1º nó de elemento (`nodeType===1`) nas 3 relações (replace/before/after) e o seleciona (`selectElement`).
- [x] **§3 — Restauração de backups**: `dashboard_site_toolbar_boxes_widgets` embrulha widgets em `<!-- widgets#SIG < -->{render}<!-- widgets#SIG > -->`; novo `dashboard_site_toolbar_resolver_variaveis` (valor puro, sem `.c2f-var-box`); `backup-get` devolve `raw`; `restorePageBackup`/`restoreLayoutBackup` re-injetam html+raw, re-rodam `mapTree` e chamam `restoreChrome` (varMap preservado — DEC-084 §3).
- [x] **§4 — Isolamento multi-usuário (hook core↔projeto)**: o core só emite o filtro `dashboard`/`site-toolbar.permissao-pagina` (helper `dashboard_site_toolbar_verificar_permissao_pagina` → `hook_apply_filters`, default `true`) nas rotas `render`/`save`/`backups` (selecionam `id_usuarios`) e `backup-get` (valida a página do contexto via `page_id`). O handler que aplica o isolamento vive no projeto (`conn2flow-site`: `multiusuario.hooks.php` + `hooks.json`).

### Evidência de Validação (BATCH-082)

Reportada pelo executor em 2026-07-13 (ambiente: PHP 8.4.8):
- `php -l` → OK: `gestor/modulos/dashboard/dashboard.php`, `gestor/bibliotecas/html-editor.php` (core); `gestor/project/hooks/controllers/multiusuario.hooks.php` (conn2flow-site).
- JSON válido: `conn2flow-site/.../project/hooks/hooks.json` (registro `controllers.dashboard` + `filters.dashboard["site-toolbar.permissao-pagina"]`).
- `node --check` → OK: `gestor/assets/interface/html-editor.js`, `gestor/modulos/dashboard/dashboard.toolbar.js`.
- Vitest `npx vitest run` → **19/19** (4 arquivos):
  - `html-editor.live.test.js` → **10/10** (+2 do lote): `insertTemplate` seleciona o 1º elemento em replace/before/after; ignora nós de texto e seleciona o 1º ELEMENTO.
  - `dashboard.toolbar.test.js` → **6/6** (+3 do lote): ponte widget-render→AJAX→widget-rendered (URL/body/postMessage corretos); guarda de assinatura/wrapper vazios (sem fetch); `restorePageBackup` re-anota o widget (marca no pai, consome comentários).
- `composer test` → **76/76 (287 assertions, 4 skipped)** sem regressão (1 PHPUnit Deprecation pré-existente e alheia).
- Cache-bust: `interface/html-editor.js?v=c2f9`→`?v=c2f10`; `biblioteca-html-editor` `1.4.9`→`1.4.10`.

### Pendências Runtime (com o operador)
- Deploy `🗃️ Projects - Update => Core` + hard-refresh (mudaram `dashboard.php`, `html-editor.php`, `html-editor.js`, `dashboard.toolbar.js`).
- **§1**: inserir um widget pelo painel "+" e confirmar que renderiza de imediato (sem ficar em "Carregando widget…").
- **§2**: adicionar um modelo e confirmar que o 1º elemento vem selecionado (substituir/antes/depois).
- **§3**: restaurar backups de página e de layout e confirmar que os widgets/variáveis continuam selecionáveis/interativos (sem `.c2f-widget-box` morta) e que o save posterior preserva marcadores.
- **§4**: **deploy do projeto `conn2flow-site`** (para sincronizar `hooks.json` → tabela `hooks` via `hooks_registrar_projeto`, senão o filtro não é carregado em runtime). Depois, logar como usuário restrito e tentar editar/salvar/restaurar backup de página de outro proprietário — confirmar o bloqueio (mensagem "Sem permissão…"); e como admin (acesso completo) confirmar acesso total. No core (sem o filtro registrado) o comportamento é permissivo (default `true`). Cobertura automatizada não incluída (o handler é de projeto privado).
- Restrição respeitada: nenhum `git commit`/`git push` executado.
