# BL-032 — Suporte integral a PostgreSQL no Contao Flow v3

- **Tipo:** Epic/Architecture/Data Migration/Compatibility
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** banco v2, Gestor, Gestor Instalador, migrations, atualizador, Docker, core e overlays privados
- **Relacionados:** BL-011, BL-012, BL-021, BL-024, BL-029, BL-030, BL-031

## Decisão

PostgreSQL deixa de ser apenas experimento futuro e passa a banco oficialmente pretendido para a v3. A referência nova de desenvolvimento será PostgreSQL 18.4, com versão mínima definida pela matriz do BL-031.

Isso não significa declarar o produto compatível agora. O suporte só pode ser anunciado quando instalação, migrations, atualizações, módulos e overlays passarem ponta a ponta. Ter `pdo_pgsql` e montar um DSN cobre apenas a conexão.

## Diagnóstico atual

- `banco-v2.php` já reconhece `pgsql`, mas mistura decisões de dialeto no arquivo monolítico;
- metadados e quoting PostgreSQL são incompletos;
- o upsert moderno e seguro precisa de `ON CONFLICT`;
- `phinx.php` está fixo em adapter MySQL;
- o instalador constrói DSN MySQL e limpa tabelas com `SHOW TABLES`/`FOREIGN_KEY_CHECKS`;
- migrations usam `MysqlAdapter::INT_TINY`, limites `TEXT_*`, opção `after` e default `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`;
- controladores possuem `SHOW COLUMNS`, `SHOW TABLES`, crases e `ON DUPLICATE KEY`;
- muitos módulos ainda consomem `banco.php`/mysqli;
- o Compose PostgreSQL existente usa versão 17, nomes/portas compartilhados e não executa instalação/testes reais.

## Arquitetura de dialetos

Criar componentes pequenos sob namespace/prefixo C2F:

```text
C2F\Database\
  Contract/
    Connection.php
    Dialect.php
    SchemaInspector.php
    MigrationCapability.php
  Driver/
    MySql/
    PostgreSql/
  Query/
  Schema/
  Exception/
```

O contrato PostgreSQL deve cobrir:

- DSN, porta, `sslmode`, `application_name` e timeout;
- charset/client encoding, timezone e `search_path`/schema;
- quoting seguro de identificadores simples e qualificados;
- prepared statements e tipos PHP→PostgreSQL, incluindo boolean, JSON/JSONB, UUID, datas e binários;
- paginação, ordenação, filtros, `RETURNING` e identity/sequence;
- upsert atômico por `ON CONFLICT` em chave única/primária;
- transações, savepoints, níveis de isolamento e retry de deadlock/serialization quando aplicável;
- inspeção de tabelas, colunas, chaves, constraints, índices, sequences e tipos;
- mapeamento de códigos SQLSTATE para exceções tipadas;
- capabilities por versão, sem espalhar comparações de driver pelos módulos.

MySQL e PostgreSQL devem implementar a mesma suíte de contrato. Métodos legados raw/escape não fazem parte da API portátil final.

## Portabilidade de queries

Inventariar e substituir por operação de alto nível ou estratégia de dialeto:

| MySQL atual | Estratégia PostgreSQL/portátil |
| --- | --- |
| `ON DUPLICATE KEY UPDATE` | upsert do dialeto / `ON CONFLICT` |
| `SHOW TABLES/COLUMNS` | `SchemaInspector` sobre catálogos |
| crases | quoting do dialeto |
| `IFNULL` | `COALESCE` quando semântica equivalente |
| `GROUP_CONCAT` | agregação abstrata / `string_agg` |
| `DATE_FORMAT`, `TIMESTAMPADD` | serviço de data/dialeto |
| `FIND_IN_SET` | remodelagem/array/relacionamento, não tradução textual cega |
| `INSERT ... VALUES('0',...)` | lista de colunas + identity/sequence |
| `TINYINT` booleano | boolean lógico com mapper |

Não criar um conversor regex genérico de SQL. Queries raw complexas recebem implementação de repositório por dialeto ou são reescritas em operações portáveis.

## Migrations e schema

- parametrizar Phinx para `mysql`/`pgsql`, porta, schema e credenciais;
- remover constantes `MysqlAdapter` das migrations compartilhadas;
- criar helpers C2F para boolean, texto grande, identity, timestamps e JSON;
- substituir `ON UPDATE CURRENT_TIMESTAMP` por trigger/serviço ou atualização explícita portátil;
- ignorar/condicionar `after`, que é apenas ordenação física MySQL;
- validar nomes de índices/constraints nos limites de ambos os bancos;
- manter ordem e IDs de migrations coerentes entre dialetos;
- proibir branches de migration que gerem schemas logicamente diferentes sem registro no SchemaMap;
- executar `migrate`, idempotência, rollback aplicável e instalação vazia em ambos.

## Gestor Instalador e atualizador

- seletor MySQL/PostgreSQL e campos condicionais de porta/schema/SSL;
- preflight de `pdo_mysql` ou `pdo_pgsql` antes do download/deploy;
- DSN/configuração compartilhados com o core, não duplicados manualmente;
- limpeza segura por inspector, sem `SHOW TABLES` ou concatenação de identificadores;
- configuração `.env` com `DB_CONNECTION`, `DB_PORT`, `DB_SCHEMA` e `DB_SSLMODE`;
- Phinx recebe adapter correto em instalação e produção;
- instalação limpa PostgreSQL 18 e versão mínima;
- atualização de banco existente PostgreSQL;
- falha/rollback/finalize e sessão expirada testados nos dois bancos.

## Docker e CI

- perfil isolado `V3-PG` com PHP mais novo aprovado e PostgreSQL 18.4 fixado;
- volume PostgreSQL 18 montado em `/var/lib/postgresql`, respeitando o novo `PGDATA` versionado da imagem oficial;
- portas e diretórios de sites separados de MySQL/legado;
- health check com `pg_isready` e query real da aplicação;
- pgAdmin versionado e opt-in;
- serviço descartável que executa contratos banco v2;
- jobs adicionais para PostgreSQL mínimo e intermediário;
- nenhum volume PostgreSQL é reutilizado diretamente entre majors.

## Migração de dados MySQL → PostgreSQL

Suportar PostgreSQL em instalação nova é diferente de converter uma instalação MySQL. Criar trilha própria:

1. preflight e inventário de schema/tipos/collations;
2. snapshot e checksum de origem;
3. exportação por modelo lógico, não dump SQL MySQL importado diretamente;
4. criação do schema PostgreSQL pelas mesmas migrations v3;
5. transformação de identity, boolean, JSON, datas, zero dates e encoding;
6. importação em ordem de dependência;
7. ajuste de sequences após IDs explícitos;
8. comparação de contagens e invariantes;
9. validação funcional e janela de cutover;
10. origem preservada para rollback até aprovação.

## Ondas de implementação futuras

1. ADR, versão mínima e suíte de contrato;
2. interfaces/classes de dialeto e conexão;
3. query builder, tipos, upsert, transações e metadados;
4. Docker PostgreSQL 18.4 isolado e testes de contrato;
5. Phinx/helpers e conversão de todas as migrations;
6. instalador/atualizador PostgreSQL;
7. serviços compartilhados exigidos pelo piloto;
8. `admin-paginas-v2` ponta a ponta nos dois bancos;
9. ondas de módulos core;
10. overlays privados e migração cross-engine;
11. documentação bilíngue, performance, segurança e gate de release.

Cada onda exige requisição própria. Nenhum scaffold parcial deve ser tratado como suporte de produção.

## Matriz de aceite

- instalação vazia e upgrade passam em MySQL mínimo/novo e PostgreSQL mínimo/18.4;
- todas as migrations criam o mesmo modelo lógico;
- todos os métodos do contrato passam nos dois dialetos;
- nenhuma rota suportada executa SQL específico do outro banco;
- PostgreSQL cobre login, sessão, recursos, módulos, arquivos, DataGrid, atualizador e instalador;
- core + overlays privados passam na mesma revisão;
- conversão MySQL→PostgreSQL tem reconciliação e rollback testados;
- documentação informa diferenças operacionais inevitáveis sem expor abstrações falsas.

## Estado do trabalho parcial de 2026-08-07

Foi iniciado, antes da confirmação de que esta etapa era somente planejamento, um scaffold de classes de dialeto, upsert PostgreSQL, configuração e Compose PostgreSQL 18.4. Esse material deve permanecer isolado na linha v3 como **WIP não homologado**. Houve teste unitário da camada de dialeto, mas o build/integração foi interrompido e não autoriza marcar qualquer item deste Epic como concluído.

## Próxima ação

Na branch `3.0.x`, promover primeiro apenas o ADR/contrato e a PoC da matriz mínima. Retomar ou descartar o scaffold WIP somente dentro da requisição aprovada correspondente.
