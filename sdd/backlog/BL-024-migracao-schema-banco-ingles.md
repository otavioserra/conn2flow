# BL-024 — Migração gradual do schema de banco para inglês

- **Tipo:** Architecture/Data Migration/Maintainability
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** tabelas, colunas, índices, constraints, seeds, manifestos, SQL, migrations e integrações do Gestor/Instalador
- **Dependências:** BL-011, BL-012, BL-020, BL-021 e BL-023

## Decisão recomendada

Não renomear todo o banco antes do banco v2. Primeiro deve existir uma camada segura de repositórios e um mapa de schema; depois cada contexto migra seus consumidores e, somente então, suas tabelas/colunas físicas. Essa ordem reduz SQL espalhado, permite testar equivalência e evita uma operação única impossível de reverter com segurança.

## Diagnóstico

O contrato consolidado `gestor/db/data/schema-metadata.json` contém hoje 20 tabelas. Entre elas convivem nomes portugueses (`categorias`, `componentes`, `layouts`, `modulos`, `paginas`, `usuarios`, `variaveis`) e ingleses (`forms`, `pages_index`, `widgets`). Os manifestos ampliam a mistura, por exemplo `arquivos`, `atualizacoes_execucoes`, `servidores_ia`, `forms_submissions`, `publisher_highlights` e `plugins`.

O problema não está só no nome da tabela. Campos como `nome`, `caminho`, `modulo`, `perfil`, `operacao`, `valor`, `data_criacao` e `id_numerico` aparecem em:

- SQL procedural do runtime;
- banco v2 e interface v2;
- JSONs de módulos e `tables_config.json`;
- gerador/recuperador de recursos;
- `*Data.json` e `schema-metadata.json` gerados;
- atualizador, recuperação, plugins e banco distribuído;
- overlays privados que são mesclados fisicamente ao core;
- integrações e relatórios potencialmente externos.

Uma simples busca/substituição não preserva chaves naturais, foreign keys, conteúdo customizado, `user_modified`, retries do atualizador nem compatibilidade entre releases.

## Convenções a decidir antes da primeira migration

1. tabelas em inglês e `snake_case`, preferencialmente no plural para acompanhar o legado inglês atual;
2. foreign keys e índices com padrão determinístico;
3. timestamps (`created_at`, `updated_at`, `deleted_at`) e estados;
4. tratamento uniforme de idioma (`language`/`locale`) e projeto;
5. distinção entre chave numérica, chave natural, `slug`, `code` e identificador público;
6. nomes de constraints e índices dentro dos limites do MySQL/MariaDB;
7. política para tabelas de junção e siglas como AI/OAuth;
8. charset, collation e sensibilidade a caixa/acentos.

Não definir automaticamente que o atual `id` textual vira PK numérica. O sistema usa combinações como `id` + `id_numerico` e chaves naturais por idioma/módulo. Cada família precisa de decisão explícita no dicionário de dados.

## Arquitetura de transição

### `SchemaMap`

Criar um catálogo canônico consumido pelo banco v2 e pelo tooling, contendo:

- entidade/campo lógico em inglês;
- tabela/coluna física vigente;
- versão em que o nome físico muda;
- chaves, tipos, nullable, defaults e relações;
- aliases legados e responsáveis;
- overlays/integrações que consomem o item.

Enquanto a tabela ainda for legada, o repositório usa nomes lógicos ingleses e resolve o nome físico pelo mapa. O mapa não deve aceitar identificadores arbitrários vindos do request; ele também funciona como allowlist para o banco v2.

### Estratégia de migration

1. inventariar todos os consumidores da entidade na árvore composta;
2. migrar o acesso para repositório/banco v2 com nomes lógicos ingleses;
3. testar leitura/escrita sobre o schema antigo;
4. criar migration Phinx idempotente para a mudança física;
5. atualizar manifestos, seeds, recursos, recovery, índices e constraints no mesmo release;
6. executar testes de upgrade com cópia realista e conteúdo customizado;
7. ativar o novo nome e manter compatibilidade apenas quando necessária e limitada;
8. remover alias após todos os produtos suportados avançarem.

Views de compatibilidade podem ajudar temporariamente em leitura, mas não devem virar solução permanente para escrita, triggers ou dual-write. Dual-write aumenta risco de divergência e não é recomendado como padrão.

## Ondas propostas

### Onda 0 — Catálogo e ferramentas

- ADR de naming e dicionário de dados;
- gerador de inventário de SQL/manifests/migrations;
- `SchemaMap`, validação e detector de identificadores legados novos;
- testes de upgrade/rollback e backup obrigatório;
- mapeamento de dependências entre core e overlays.

### Onda 1 — Identidade, acesso e configuração

- usuários, perfis, permissões, módulos, operações, variáveis e configurações;
- exige coordenação com autenticação, sessão, instalador e atualização;
- apesar de central, deve ocorrer somente depois dos repositórios v2 correspondentes.

### Onda 2 — Conteúdo administrativo

- páginas, layouts, templates, componentes, categorias e arquivos;
- coordenar com `admin-paginas-v2`, publisher, Tailwind, `C2FDataGrid` e `C2FUpload`;
- preservar conteúdo customizado e chaves naturais multilíngues.

### Onda 3 — Conteúdo e serviços auxiliares

- galleries, menus, forms, publisher, plugins, IA e tabelas restantes;
- agrupar por bounded context e não apenas por semelhança do nome.

### Onda 4 — Overlays privados

- renomear tabela compartilhada somente uma vez, no proprietário definido no catálogo;
- adaptar `conn2flow-site`, Lumix e Transformamp à mesma versão do mapa;
- validar artefato final core + overlay antes de concluir a onda.

### Onda 5 — Remoção

- contador zero de nomes físicos legados em runtime, tooling e overlays;
- remover aliases/views/adapters temporários;
- regenerar todos os dados/contratos;
- validar instalação limpa, upgrade 2.9.x → 3.x, recuperação e rollback ensaiado.

## Atualização e rollback

- backup/snapshot deve ser confirmado antes da primeira migration destrutiva;
- renames devem preservar dados e reconstruir foreign keys/índices de forma determinística;
- migrations de grande volume precisam de estimativa de lock e plano online/cópia quando necessário;
- o atualizador deve gravar checkpoint antes/depois de cada contexto;
- downgrade automático só é prometido quando a migration reversa for segura; caso contrário, rollback restaura snapshot e arquivos da mesma release;
- bancos usados pela 2.9.x e 3.x permanecem isolados.

## Complexidade

- catálogo, mapa e tooling: alta, porém fundacional;
- renomear uma tabela isolada sem consumidores externos: média;
- famílias centrais como páginas/usuários/variáveis: muito alta;
- programa completo: vários batches e vários meses, incorporado às ondas banco/interface — não uma tarefa única.

## Critérios de aceite

- nenhum identificador físico é renomeado sem entrada no dicionário e inventário de consumidores;
- módulos novos conhecem apenas nomes lógicos ingleses;
- valores continuam parametrizados; o mapa não reintroduz SQL concatenado;
- migrations preservam dados, chaves, recursos multilíngues e customizações;
- instalação, upgrade, recuperação e composição privada passam com o mesmo schema versionado;
- nomes legados têm owner e data/condição de remoção;
- o estado final não depende de views, triggers ou dual-write de compatibilidade.

## Próxima ação

Promover um spike para produzir o dicionário de dados e o `SchemaMap` somente para o contexto do piloto `admin-paginas-v2`, sem ainda renomear fisicamente tabelas.
