# Batch Index

Este arquivo controla o estado dos batches do `conn2flow` no modelo SDD.

## Status usados aqui

- `complete`: batch fechado e validado
- `ready-for-intake`: próximo slice reservado, aguardando intake humano classificado
- `in-progress`: implementação em andamento
- `blocked`: depende de decisão, requisito ou validação adicional

## Batches

| Batch | Status | Escopo | Alvo de validação | Observações |
| --- | --- | --- | --- | --- |
| BATCH-000 a BATCH-017 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-000-017.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-000-017.md) | Os detalhes de implementação e validações dos primeiros 18 lotes foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-018 a BATCH-053 | complete | Batches históricos arquivados | Ver arquivo histórico [batches-018-053.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/archive/batches-018-053.md) | Os detalhes de implementação e escopo dos lotes 18 a 53 foram arquivados para manter a eficiência do contexto de IA. |
| BATCH-054 | complete | Ajustes Visuais de Margem e Tooltip com Detalhe de Perfis no Módulo Menus (req-054) | VALIDATION-CHECKLIST.md#batch-054 | Espaçamento vertical entre tags de perfis de usuário e botões de ação, e tooltip/contador dinâmicos exibindo perfis selecionados nas abas de visibilidade condicional. Fechado em 2026-06-22. |
| BATCH-055 | ready-for-intake | Correção na Preservação do Estado de Disponibilidade do Menu no Carregamento do CRUD (req-055) | VALIDATION-CHECKLIST.md#batch-055 | Correção de bug no JS (`menus.js`) para restaurar e selecionar corretamente a opção de disponibilidade salva (`schema.availability`) no carregamento inicial da página. |
| BATCH-056 | complete | Sincronização Declarativa de Recursos, Deleção e Atualização Forçada - Módulos e Globais (req-056) | VALIDATION-CHECKLIST.md#batch-056 | `config` objeto/array + `coletarConfigsTabelas()` consolidando `deletar`/`forcar_atualizacao` no `schema-metadata.json` (novo mapa de topo); varredura `sync_resources` com `field_types` (`json`/`file:ext`), metadados externos/inline e geração dinâmica de `<Pascal>Data.json`; `forcar_atualizacao` no atualizador (bypass `project`/`user_modified` + reset→0, project preservado) nos 3 caminhos de update; `tables_config.json` documentado; 12 docs pt-br/en. Ver DEC-064. Validação: `php -l` (2/2) + `composer test` 48/142 OK + teste novo `ForcarAtualizacaoTest` + smoke da varredura dinâmica; deploy (`Update => Core`) e validação runtime pendentes com o operador. Plano em [BATCH-056.md](BATCH-056.md). Fechado em 2026-06-23. |
| BATCH-057 | complete | Correção de Tipagem e Validação de Perfil Anônimo em Menus Condicionais (req-057) | VALIDATION-CHECKLIST.md#batch-057 | Correção de tipagem em `menus.widget.php` (`menus_widget_condicao_valida`): normaliza perfis anônimos em array (`id='_anonimo'`/`id_usuarios=0` → `false`) e obtém o profile ID de forma segura (`_profile_slug` ou `id_usuarios_perfis`), eliminando o warning `Array to string conversion` e as 2 falhas. Ver DEC-065. Validação: `php -l` OK + `MenusWidgetConditionalVisibilityTest` 7/7 (19 assertions) + `composer test` 48/48 OK. Fechado em 2026-06-24. |
| BATCH-058 | complete | Sistema de Recuperação e Engenharia Reversa de Recursos (Pull System) (req-058) | VALIDATION-CHECKLIST.md#batch-058 | Endpoint `_api/project/recover` (OAuth + ZIP via `reverseExport` já existente, incluído com `SDD_NO_AUTORUN`), controlador servidor `recuperacoes/recuperacao-dados-recursos.php`, descompilador cliente autônomo `agents/arquitetura/recuperacao-dados-recursos.php` (reverso exato do compilador: extrai `file:<ext>` em layout PLANO sem BOM, decodifica `json`, saneia versao/checksum/user_modified/project/PK/idioma/status='A'/módulo-dono; metadados externos/inline), `recover-project.sh` + 2 VS Code tasks. Ver DEC-066. Validação: `php -l` (4/4) + `composer test` 54/54 (186 assertions, 4 skipped) incluindo novo `RecuperacaoDadosRecursosTest` 6/6. Deploy/pull runtime pendente com o operador. Plano em [BATCH-058.md](BATCH-058.md). Fechado em 2026-06-25. |
| BATCH-059 | complete | Refinamentos, Overrides de Projeto e Sincronização Inteligente de Contents (Pull System) (req-059) | VALIDATION-CHECKLIST.md#batch-059 | Renomeação de CLI server para recuperacao-banco-de-dados.php; overrides de scope/modulo no tables_config.json; pull inteligente de contents via MD5 e timestamps, com touch timestamps e relatório de conflitos. Validação: `php -l` 5/5, `RecuperacaoDadosRecursosTest` 11/11, `composer test` 59/59 (4 skipped). Plano em [BATCH-059.md](BATCH-059.md). Fechado em 2026-06-25. |
| BATCH-060 | ready-for-intake | Pipeline de Metadados de Projeto e Desacoplamento de Configuração (req-060) | VALIDATION-CHECKLIST.md#batch-060 | Compilador gera project-schema-metadata.json a partir de tables_config.json na raiz do gestor; deploy-project-v2.sh empacota o arquivo no deploy; API e CLI banco do servidor lêem project-schema-metadata.json no dump. Documentação CONN2FLOW-SISTEMA-RECURSOS.md atualizada. Plano em [BATCH-060.md](BATCH-060.md). |


## Regra operacional

Não abra um novo batch funcional sem atualizar este índice. Se o escopo mudar de forma normativa, registre primeiro a mudança em `sdd/change-requests/`.
