# Decision Log

## DEC-001 - 2026-05-25 - accepted

Adotar SDD repo-wide no `conn2flow` como camada de controle para mudanÃ§as novas, sem tentar substituir a arquitetura vigente do repositÃ³rio.

## DEC-002 - 2026-05-25 - accepted

Suportar os dois executores de IA no mesmo repositÃ³rio:

- Claude Code via `CLAUDE.md` e `.claude/`
- GitHub Copilot via `.github/`

Os dois devem convergir para os mesmos artefatos em `sdd/`.

## DEC-003 - 2026-05-25 - accepted

Tratar `sdd/00-baseline-architecture.md` como referÃªncia primÃ¡ria do estado atual aprovado do legado. MudanÃ§as futuras devem declarar o delta em relaÃ§Ã£o a essa base, em vez de assumir que o legado pode ser descartado.

## DEC-004 - 2026-05-25 - accepted

Tratar `sdd/human-requests/` como intake humano nÃ£o normativo, com resoluÃ§Ã£o padrÃ£o por `CURRENT.md`, depois `README.md`, depois o arquivo `.md` mais recente.

## DEC-005 - 2026-05-25 - accepted

Definir como prÃ³ximo intake funcional esperado o `Plano 1`, focado em tarefas e scripts de sincronizaÃ§Ã£o de projetos.

## DEC-006 - 2026-05-25 - accepted
# Decision Log

## DEC-001 - 2026-05-25 - accepted

Adotar SDD repo-wide no `conn2flow` como camada de controle para mudanças novas, sem tentar substituir a arquitetura vigente do repositório.

## DEC-002 - 2026-05-25 - accepted

Suportar os dois executores de IA no mesmo repositório:

- Claude Code via `CLAUDE.md` e `.claude/`
- GitHub Copilot via `.github/`

Os dois devem convergir para os mesmos artefatos em `sdd/`.

## DEC-003 - 2026-05-25 - accepted

Tratar `sdd/00-baseline-architecture.md` como referência primária do estado atual aprovado do legado. Mudanças futuras devem declarar o delta em relação a essa base, em vez de assumir que o legado pode ser descartado.

## DEC-004 - 2026-05-25 - accepted

Tratar `sdd/human-requests/` como intake humano não normativo, com resolução padrão por `CURRENT.md`, depois `README.md`, depois o arquivo `.md` mais recente.

## DEC-005 - 2026-05-25 - accepted

Definir como próximo intake funcional esperado o `Plano 1`, focado em tarefas e scripts de sincronização de projetos.

## DEC-006 - 2026-05-25 - accepted

Adotar fallbacks estruturados e mapeamento dinâmico na sincronização e atualização de projetos específicos:
1. Usar `path_tests` como fallback automático para `target` no `synchronize-project.sh`.
2. Derivar o `dockerPath` substituindo o prefixo local por `/var/www/sites/` se ausente no `updates-manager-database.sh`.
3. Isolar os dados dinâmicos do projeto de destino no `sync-core-to-project.sh` por meio de exclusões no rsync.

## DEC-007 - 2026-05-25 - accepted

Adotar arquitetura baseada em contrato de esquema (schema contract) por meio de um arquivo central compilado `db/data/schema-metadata.json`, gerado a partir do nó "tabela" dos descritores de módulos (ex: `admin-paginas.json`) e do descritor global dedicado `tables_config.json`. O script de deploy consumirá apenas esse contrato, eliminando códigos hardcoded.

## DEC-008 - 2026-05-25 - accepted

Padronizar a coluna identificadora de idioma de banco de dados e arquivos de recursos de desenvolvimento para o nome unificado `language` em todas as tabelas (substituindo o termo legado `linguagem_codigo`). Esta padronização exige também a atualização/migração de todos os módulos ativos do sistema que atualmente consomem a coluna antiga, garantindo a compatibilidade de runtime do Conn2Flow.

## DEC-009 - 2026-05-25 - accepted

Implementar uma estratégia de loteamento dinâmico com threshold de segurança de 30% em relação ao limite `max_allowed_packet` obtido em tempo de execução, dividindo payloads volumosos de HTML/CSS em chunks seguros para evitar estouro de buffer de rede.

## DEC-010 - 2026-05-25 - accepted

Utilizar a nomenclatura dedicada `data-hooks.php` para a execução sequencial em pipeline de ganchos locais nos módulos ou globais, garantindo isolamento em relação aos hooks de execução do sistema (`hooks.php`).

## DEC-011 - 2026-05-25 - accepted

Substituir deleções automáticas de registros órfãos por uma estratégia imperativa de deleção controlada via chave dedicada "deletar" declarada nos arquivos descritores de metadados de recursos de desenvolvimento, evitando exclusão acidental de dados de diferentes projetos de banco de dados.

## DEC-012 - 2026-05-25 - accepted

Padronizar todo o registro de mensagens de logs dos scripts de sincronização de banco e geração de dados para utilizar exclusivamente a biblioteca oficial do sistema `log.php` e sua função nativa `log_disco()`, eliminando implementações duplicadas ou logs soltos.
