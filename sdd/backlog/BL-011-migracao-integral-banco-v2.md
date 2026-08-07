# BL-011 — Migração integral do Gestor e do Gestor Instalador para banco v2

- **Tipo**: Architecture / Security / Maintainability
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: ALTA
- **Origem**: solicitação de análise técnica em 2026-08-07
- **Escopo**: `gestor/`, `gestor-instalador/`, seus testes e workflows de release
- **Dependências históricas**: BL-007 e BL-010, promovidos para `req-107`
- **Continuação arquitetural**: BL-012 define a decomposição do banco v2 em pacotes/classes menores, com autoload e fachada temporária

## Regra de governança

Este documento é um plano de backlog. Ele não autoriza implementação. A migração somente deve começar após promoção explícita para uma requisição humana e criação dos batches correspondentes.

## Objetivo

Migrar todo acesso de dados de runtime do Gestor e todo acesso de dados do Gestor Instalador para uma implementação canônica de banco v2 baseada em PDO, parâmetros preparados, identificadores validados, transações explícitas e erros observáveis. Ao final, `banco.php`/mysqli deve deixar de ser o padrão e ser removido somente depois da janela de compatibilidade com módulos e projetos externos.

“Usar banco v2” não pode significar apenas trocar o nome da função e continuar passando SQL concatenado aos métodos de compatibilidade. O critério técnico é: **valores externos nunca fazem parte da string SQL; todos são enviados separadamente como parâmetros tipados**.

## Resultado da análise

### Inventário do Gestor

Levantamento estático dos PHP de `gestor/`, excluindo `vendor/`, `banco.php` e `banco-v2.php`:

- `banco.php` expõe **47 funções globais**.
- Há **2.462 chamadas** a essas funções em **64 arquivos consumidores**.
- As maiores famílias de chamadas são:

| API legada | Chamadas | Observação de migração |
| --- | ---: | --- |
| `banco_escape_field` | 766 | Deve desaparecer; bind substitui escape manual. |
| `banco_campos_virgulas` | 280 | Separar montagem de identificadores de montagem de valores. |
| `banco_select_campos_antes` | 267 | Estado global implícito; substituir por registro/resultado explícito. |
| `banco_select` | 206 | Converter para consulta parametrizada. |
| `banco_insert_name_campo` | 204 | Builder stateful; converter para array de dados tipado. |
| `banco_select_name` | 187 | O argumento `extra` contém SQL cru; requer reescrita do call-site. |
| `banco_update_campo` | 136 | Builder stateful; converter para `update($dados)` parametrizado. |
| `banco_insert_name` | 90 | Migrar para `insert($dados)`. |
| `banco_identificador` | 65 | Preservar contrato e concorrência, usando transação/índice único. |
| `banco_update_executar` | 51 | Remover dependência de acumuladores globais. |
| `banco_delete` | 45 | Requer `WHERE` parametrizado e proteção contra delete sem filtro. |
| `banco_select_editar` | 44 | Migrar preservando o formato de retorno esperado pela interface. |
| `banco_update` | 41 | Reescrever campos e filtros como estruturas separadas. |
| `banco_insert_name_campos` | 31 | Remover leitura de estado global intermediário. |
| `banco_select_campos_antes_iniciar` | 25 | Substituir snapshot global por objeto/array explícito. |
| Demais APIs | 24 | `query`, `last_id`, fetch, campo existe, insert/update e conexão. |

Arquivos com maior superfície, úteis para organizar as ondas:

| Componente | Chamadas legadas aproximadas |
| --- | ---: |
| `bibliotecas/plugins-installer.php` | 161 |
| `modulos/perfil-usuario/perfil-usuario.php` | 146 |
| `modulos/publisher-pages/publisher-pages.php` | 127 |
| `modulos/admin-paginas/admin-paginas.php` | 109 |
| `bibliotecas/interface.php` | 108 |
| `modulos/dashboard/dashboard.php` | 100 |
| `modulos/admin-templates/admin-templates.php` | 74 |
| `modulos/usuarios-perfis/usuarios-perfis.php` | 74 |
| `gestor.php` | 72 |
| `bibliotecas/formulario.php` | 71 |
| `modulos/usuarios/usuarios.php` | 68 |
| `bibliotecas/autenticacao.php` | 64 |
| `bibliotecas/configuracao.php` | 58 |
| `modulos/modulos/modulos.php` | 57 |
| `modulos/publisher/publisher.php` | 56 |
| `publisher-index` e `publisher-highlights` | 54 cada |

Há ainda PDO/SQL direto fora das duas bibliotecas, principalmente em:

- `controladores/atualizacoes/atualizacoes-banco-de-dados.php`;
- `controladores/plugins/atualizacao-plugin-banco-de-dados.php`;
- `controladores/api/api-module-distributed.php`;
- `bibliotecas/modulo-distribuido.php`;
- `controladores/atualizacoes/atualizacoes-sistema.php`.

Os atualizadores executam DDL e manipulam nomes dinâmicos de tabelas/colunas. Eles precisam de uma API segura de identificadores e de um adaptador de schema; prepared statements protegem valores, mas não parametrizam identificadores SQL.

### Estado atual do banco v2

O arquivo `gestor/bibliotecas/banco-v2.php` ainda não pode ser adotado como padrão:

1. Declara PHP 8.5+ e usa `clone ... with`, `array_first()`, `array_last()` e `#[NoDiscard]`.
2. A suíte atual roda em PHP 8.4 e o job que empacota o Gestor roda em PHP 8.2. O lint do banco v2 falha já no `clone ... with` sob PHP 8.4.8.
3. O instalador publicado declara PHP 8.1+ e é distribuído sem Composer/vendor próprios.
4. Não há testes unitários de contrato dedicados a `BancoV2`, `ConsultaBanco` ou `ConfigBanco`.
5. A v2 possui o caminho correto (`where($sql, $params)`, `query($sql, $params)`, `insert(array)`, `update(array)` e prepares nativos), mas também mantém caminhos inseguros ou ambíguos: `extra()`, `orderBy(string)`, `updateRaw()`, `setCampo()`, `updateVarios()`, `escape()`, `raw()` e os métodos `*Legado`.
6. `quoteIdentifier()` aceita expressões especiais e precisa ser fechado para que identificadores não confiáveis não contornem a validação.
7. Algumas falhas PDO são convertidas em resultado vazio/zero. Isso pode esconder defeitos e fazer o fluxo prosseguir como se a operação tivesse sido válida.
8. A promessa MySQL/PostgreSQL não corresponde ao restante do sistema: foram encontrados, entre outros, 159 usos de `NOW()`, comandos `SHOW`, `ON DUPLICATE KEY`, `TIMESTAMPADD` e centenas de identificadores com crase. A migração deve assumir MySQL/MariaDB primeiro ou financiar uma trilha separada de portabilidade.

### Estado atual do Gestor Instalador

O instalador concentra o acesso em `Installer::getPdo()`, o que reduz bastante sua superfície: apenas `src/Installer.php` usa PDO diretamente. Pontos a tratar:

- habilitar prepares nativos e desabilitar stringify de fetches;
- substituir SELECT fixo, INSERT e UPDATE pelo contrato v2;
- validar/cotar nomes retornados por `SHOW TABLES` antes de `DROP TABLE`;
- garantir a restauração de `FOREIGN_KEY_CHECKS` em bloco `finally`;
- manter `ON DUPLICATE KEY UPDATE` como operação MySQL explicitamente testada;
- migrar o auto-login pós-instalação, que hoje inclui `banco.php`, `gestor.php`, `ip.php` e `usuario.php` do Gestor instalado;
- adicionar testes de instalação limpa, banco já populado, falha intermediária e repetição idempotente.

Existe um problema de bootstrap: antes de baixar/extrair o Gestor, o instalador não tem acesso a `gestor/bibliotecas/banco-v2.php`. Portanto, ambos só usarão a mesma implementação se o núcleo v2 for empacotado também no artefato do instalador.

### Banco distribuído

O modo distribuído intercepta `banco_query()` e transmite uma **string SQL pronta** no JSON assinado. No destino, o SQL é executado com `PDO::query()`/`PDO::exec()`. A assinatura autentica o transporte, mas não transforma a consulta em parametrizada.

A migração precisa criar protocolo v2 com, no mínimo:

- template SQL separado dos parâmetros e tipos;
- versão de protocolo e negociação de capacidade;
- validação de operação, módulo e tabelas permitidas no destino;
- resposta tipada com linhas, colunas, `affected_rows` e `insert_id`;
- compatibilidade temporária com protocolo v1 somente durante rollout controlado;
- testes contra replay, múltiplas instruções, parâmetros malformados e tentativa de acessar tabela fora do escopo.

## Decisões que a requisição deverá fechar

### D1 — Versão mínima de PHP

Recomendação atualizada pelo BL-031: avaliar **PHP 8.3 ou 8.4** como mínimo da v3 e usar PHP 8.5 como referência superior. PHP 8.2 encerra suporte de segurança em 2026-12-31 e não é um baseline sustentável para uma nova linha. A decisão final depende de PoC de backport e da data do RC.

Alternativa: elevar tudo para PHP 8.5. Essa escolha reduz o trabalho de backport, mas rompe hospedagens atuais e não pode acontecer silenciosamente numa atualização.

### D2 — Arquitetura compartilhada

Recomendação: extrair as classes de conexão/consulta/resultado para um núcleo v2 namespaced e sem dependência do bootstrap do Gestor. O mesmo fonte deve ser:

- carregado pelo Gestor;
- incluído no ZIP autônomo do instalador;
- coberto pela mesma suíte de contrato;
- adaptado às funções globais apenas numa camada temporária de compatibilidade do Gestor.

Não manter duas cópias editadas manualmente. O workflow deve gerar/copiar o artefato compartilhado e verificar checksum ou diff na build.

### D3 — Banco suportado

Decisão atualizada: MySQL e PostgreSQL são alvos oficiais pretendidos da v3. PostgreSQL exige a trilha própria do BL-032 — abstração de dialeto, migrations, instalador, Docker e CI — e não pode ser anunciado como suportado antes dos gates ponta a ponta. MariaDB permanece decisão separada a fechar.

### D4 — Semântica de erro

Recomendação: exceções tipadas no núcleo v2, com tradução apenas nas fronteiras HTTP/UI. Não retornar `[]`, `0` ou `false` indistinguíveis de um resultado válido quando houve erro SQL.

### D5 — Compatibilidade externa

Recomendação: manter `banco.php` como fachada de compatibilidade por uma janela definida, com telemetria/deprecation, sem aceitar novos consumidores. Isso inclui helpers usados por projetos externos, como `banco_smartstripslashes()`. A remoção exige inventário dos plugins e projetos fora deste repositório.

## Arquitetura alvo

1. **Núcleo de conexão**: configuração imutável, DSN seguro, PDO com `ERRMODE_EXCEPTION`, `ATTR_EMULATE_PREPARES=false`, `ATTR_STRINGIFY_FETCHES=false`, charset `utf8mb4`, timeout e ciclo de reconexão definidos.
2. **Executor parametrizado**: `query`, `execute`, `fetchOne`, `fetchAll`, `insert`, `update` e `delete` recebem SQL/estrutura e parâmetros separados, com bind explícito de `null`, `bool`, `int`, `string` e LOB.
3. **Validador de identificadores**: tabela, coluna, alias, direção de ordenação e nomes de índice passam por allow-list/grammar estrita; nunca por escape de valor.
4. **Expressões controladas**: operações como `NOW()`, incremento atômico, `IN`, `IS NULL`, `LIKE` e `ON DUPLICATE KEY` usam objetos/estruturas fechadas. `raw()` genérico fica interno e sujeito a revisão, não disponível a módulos.
5. **Transações**: callback transacional, rollback em qualquer `Throwable`, suporte documentado a nesting/savepoint e teste de deadlock/retry quando aplicável.
6. **Repositórios/adaptadores de domínio**: consultas sensíveis e repetidas (usuário, autenticação, sessão, OAuth, API, configuração) deixam de espalhar SQL pelos módulos.
7. **Schema/migrations**: DDL permanece separado do CRUD de runtime, com adaptador próprio para metadados e identificadores validados.
8. **Compatibilidade**: fachada global temporária encaminha apenas operações já migradas; chamadas que ainda recebem fragmentos SQL crus continuam marcadas como legadas e bloqueadas para código novo.
9. **Observabilidade**: erro registra código/correlação e operação, sem senha, token, cookie, SQL com valores sensíveis ou dump integral de parâmetros.

## Mapeamento das APIs legadas

| Grupo v1 | Destino v2 | Trabalho necessário |
| --- | --- | --- |
| conexão, ping, fechar | `ConnectionProvider`/`BancoV2` | Eliminar global mutável e definir reset/reconexão. |
| `escape_field` | nenhum | Remover dos call-sites; usar bind. |
| `query`, `sql`, fetch/result helpers | executor/`ResultadoBanco` | Padronizar retorno e propagar erro. |
| `select*` | query builder parametrizado | Decompor `extra` em where/order/limit/group seguros. |
| `insert*` | `insert(array)`/bulk insert | Tipar valores, expressões controladas e tamanho de lote. |
| `update*` | `update(array)->where(...)` | Remover acumuladores globais e `sem_aspas`. |
| `delete*` | `delete()->where(...)` | Proibir execução sem condição, salvo API administrativa explícita. |
| `last_id`, affected rows | resultado da operação | Não depender de estado global da última consulta. |
| metadados (`campo_existe`, tabelas) | schema adapter | Validar identificadores e tratar dialeto. |
| `identificador*` | serviço de identificador | Garantir unicidade com constraint e retry transacional. |
| `insert_update` | upsert de dialeto | Cobrir concorrência e semântica MySQL/MariaDB. |
| `smartstripslashes` e utilitários | compatibilidade/utilidade fora do banco | Desacoplar de persistência antes de retirar a fachada. |

## Plano de execução proposto

### Fase 0 — Baseline e contrato

- congelar novas chamadas v1 por regra de lint/CI;
- gerar inventário automatizado por arquivo, função e tipo de SQL;
- inventariar plugins, módulos distribuídos e projetos externos;
- registrar ADRs das decisões D1–D5;
- definir contrato de tipos, erros, transações, paginação e resultados vazios;
- medir antes da mudança: consultas por rota, tempo total, p50/p95, conexões e memória.

### Fase 1 — Tornar o próprio banco v2 adotável

- adequar a sintaxe ao PHP mínimo decidido ou elevar formalmente o requisito;
- separar núcleo namespaced das fachadas globais;
- fechar validação de identificadores;
- remover/depreciar os atalhos de concatenação (`escape`, `extra`, `updateRaw`, `setCampo`, `raw` público e métodos `*Legado`);
- parametrizar bulk insert, update em lote, upsert, `IN` e limites;
- corrigir propagação de exceções, `rowCount` de SELECT e ciclo do singleton;
- documentar MySQL/MariaDB como dialeto inicial;
- criar testes unitários e integração do núcleo antes de migrar módulos.

### Fase 2 — Fachada, instrumentação e migração mecânica segura

- criar fachada v1 com aviso de depreciação agregável por call-site;
- impedir log de valores sensíveis;
- criar helpers codemod/lint para localizar SQL interpolado e uso de escape;
- converter primeiro funções de baixo risco e utilitários sem SQL;
- não fazer dual-write em produção; para SELECTs determinísticos, shadow read opcional pode comparar v1/v2 sem alterar resposta.

### Fase 3 — Bootstrap e segurança do Gestor

Migrar primeiro, com testes de integração:

- `gestor.php` e carga/configuração do banco;
- `bibliotecas/gestor.php`, `usuario.php`, `ip.php`, `autenticacao.php`;
- sessão, login, recuperação de senha, 2FA, OAuth/OAuth2 e JWT;
- `controladores/api/api-auth.php` e rate limit;
- usuários, perfis, permissões e logs de auditoria.

Essa fase desbloqueia o auto-login do instalador e reduz primeiro o risco nos dados mais sensíveis.

### Fase 4 — Bibliotecas transversais

- `interface.php` e `interface-v2.php` — a segunda já chama v2, mas ainda usa métodos de compatibilidade/escape e deve ser reescrita para bind real;
- `formulario.php`, `configuracao.php`, `log.php`, `hooks.php`;
- `plugins-installer.php`, plugins, IA, comunicação e pagamentos;
- substituir snapshots globais de “campos antes” por dados explícitos por operação.

### Fase 5 — Módulos administrativos e de conteúdo

Dividir em batches pequenos e reversíveis, cada um com testes de rota/CRUD:

1. perfil, usuários, perfis e permissões;
2. dashboard e configurações;
3. páginas, páginas v2, templates, layouts, componentes e categorias;
4. publisher, publisher-pages, publisher-index e highlights;
5. menus, galleries, forms e forms-search;
6. módulos, grupos, operações, arquivos, prompts/modos/IA;
7. módulos remanescentes encontrados pelo lint até contador zero.

Um batch só é concluído quando o arquivo não chama API v1 nem usa `escape()`/SQL interpolado para valores.

### Fase 6 — Atualizações, plugins e schema

- substituir conexões PDO duplicadas por provider v2;
- criar `SchemaInspector`/`SchemaExecutor` MySQL para `SHOW`, DDL e metadados;
- validar nomes de tabela/coluna contra manifestos e metadados conhecidos;
- preservar transações onde DDL permitir e tornar retomada idempotente;
- testar atualização completa, incremental, reexecução e rollback/falha parcial;
- manter Phinx como mecanismo de migrations, usando a mesma configuração de conexão sempre que possível.

### Fase 7 — Banco distribuído v2

- criar payload `{version, operation, statement, params, param_types, module, nonce, timestamp}`;
- aplicar prepare/bind no executor remoto;
- restringir recursos e tabelas por manifesto do módulo;
- versionar cliente/servidor e planejar rollout em duas pontas;
- remover protocolo que transporta SQL já interpolado somente após todas as instalações compatíveis;
- adaptar resultado remoto ao contrato v2, sem emular `mysqli_result` no núcleo novo.

### Fase 8 — Gestor Instalador

- empacotar o núcleo compartilhado no `instalador.zip` sem depender do Gestor já baixado;
- trocar `getPdo()` por provider v2 ou fazer esse método apenas construir a configuração do núcleo;
- migrar limpeza, página de sucesso e criação/atualização do administrador;
- migrar a cadeia `usuario.php` necessária ao auto-login antes de trocar os includes;
- garantir que instalação abortada restaure `FOREIGN_KEY_CHECKS` e não deixe estado silenciosamente parcial;
- alinhar requisito PHP/extensões do instalador com o Gestor;
- adicionar job de teste do instalador e validação do ZIP no workflow.

### Fase 9 — Ecossistema externo e troca do padrão

- publicar guia de migração com equivalências e exemplos preparados;
- varrer plugins oficiais e projetos conhecidos fora do repo;
- disponibilizar uma versão com deprecation e relatório de chamadas v1;
- mudar `$_GESTOR['bibliotecas']` para carregar v2 como padrão somente quando o core e os módulos oficiais estiverem zerados;
- manter fachada v1 por pelo menos uma janela de release decidida;
- remover mysqli, `banco.php` e métodos `*Legado` apenas após telemetria/inventário confirmarem zero consumidores.

## Estratégia de testes

### Núcleo v2

- configuração/DSN, conexão, reconexão e fechamento;
- bind de `null`, booleano, inteiro, string, Unicode, JSON, binário e strings com aspas/barras;
- payloads de injeção, comentários SQL, multibyte e tentativa via identificadores/order by;
- SELECT vazio, uma/múltiplas linhas, aliases e tipos de retorno;
- INSERT, bulk insert, UPDATE, DELETE, upsert, `affected_rows` e `lastInsertId`;
- transação, rollback em `Throwable`, savepoints e concorrência;
- erro de conexão, timeout, violação de constraint e SQL inválido sem falso sucesso.

### Compatibilidade e módulos

- testes diferenciais v1 versus v2 sobre fixtures equivalentes durante a migração;
- testes de autenticação/sessão/OAuth/API e permissões;
- CRUD e paginação dos módulos por onda;
- verificação automatizada de ausência de `banco_escape_field`, APIs v1 e concatenação de valores no escopo migrado;
- teste de queries e contagem para detectar regressão de performance/N+1.

### Banco e CI

- matriz na versão mínima de PHP e na versão estável usada em produção;
- MySQL 8 e versão MariaDB oficialmente suportada;
- `pdo_mysql` obrigatório; `mysqli` só durante compatibilidade;
- lint de todos os arquivos empacotados na versão mínima;
- teste do ZIP do Gestor e do instalador em ambiente limpo;
- Phinx migrate/rollback e atualização de uma base real anonimizada ou fixture representativa.

### Instalador e distribuído

- instalação nova, banco não vazio, reinstalação idempotente e credencial inválida;
- falha em cada etapa com retomada/limpeza previsível;
- protocolo distribuído v1/v2 durante transição, assinatura, nonce, timeout e permissões;
- comparação de resultados locais e remotos com os mesmos parâmetros.

## Rollout, desempenho e reversão

- feature flag por componente/onda, não uma chave global que troca tudo de uma vez;
- métricas antes/depois por rota: duração total de SQL, número de consultas, conexões, erros e p95;
- reutilizar uma conexão por request/contexto, sem conexão a cada chamada;
- cachear metadados de schema apenas com invalidação definida;
- usar bulk operations parametrizadas com chunk limitado por `max_allowed_packet`;
- plano de rollback por onda enquanto a fachada v1 existir;
- nunca executar simultaneamente escrita v1 e v2 como “segurança”, pois isso duplica efeitos;
- falha de banco deve interromper a unidade de trabalho e ser visível, não cair silenciosamente para resultado vazio.

## Riscos principais

| Risco | Mitigação |
| --- | --- |
| Quebra por versão de PHP | Decidir baseline antes do código; lint e CI na mínima. |
| Falsa sensação de segurança ao usar métodos legados da v2 | Critério de aceite exige bind e bloqueia raw/escape. |
| Mudança de formato/tipo de retorno entre mysqli e PDO | Testes diferenciais e objetos de resultado explícitos. |
| Estado global dos builders atuais | Migrar para objetos/dados por operação, sem acumuladores. |
| Regressão de performance | Baseline, métricas por rota, conexão por request e teste N+1. |
| SQL MySQL tratado como portátil | Declarar dialeto e separar adaptador de schema/upsert. |
| Ruptura de plugins/projetos externos | Fachada, deprecation, inventário e janela de releases. |
| Canal distribuído continuar enviando SQL interpolado | Protocolo v2 com statement + parâmetros e allow-list remota. |
| Instalador depender do Gestor antes de baixá-lo | Núcleo v2 compartilhado e empacotado no instalador. |

## Entregáveis sugeridos para a futura requisição

Como a superfície é extensa, a requisição deve ser um **Epic** dividido em batches, e não um único lote:

1. ADRs, baseline e inventário automatizado;
2. hardening/compatibilidade/testes do núcleo banco v2;
3. fachada de depreciação e regras de CI;
4. bootstrap, usuário, autenticação e segurança;
5. bibliotecas transversais;
6. módulos administrativos por ondas;
7. publisher/forms/conteúdo por ondas;
8. atualizador, plugins, Phinx e schema;
9. protocolo de banco distribuído v2;
10. instalador autônomo com núcleo v2;
11. migração externa, troca do padrão e retirada do legado.

Cada batch deve listar arquivos exatos após atualizar o inventário, ter rollback próprio e terminar com o contador de chamadas legadas daquele escopo igual a zero.

## Critérios de aceite globais

- Banco v2 carrega e passa lint/testes na versão mínima oficial de PHP.
- Gestor e instalador usam o mesmo núcleo v2, sem cópias divergentes.
- Zero concatenação de valores externos em SQL nos dois produtos.
- Zero uso oficial de `banco_escape_field()` e das APIs v1.
- Identificadores dinâmicos são validados por allow-list/grammar fechada.
- Atualizadores/Phinx, banco distribuído e instalador estão cobertos, não apenas CRUD de módulos.
- Fluxos críticos e todos os módulos oficiais passam testes de integração.
- Performance não apresenta regressão fora do orçamento definido na requisição.
- Guia e janela de compatibilidade externa foram executados.
- `banco.php`/mysqli só é removido depois de zero consumidores internos e externos conhecidos.

## Condições para mover este item a READY

- fechar D1–D5;
- confirmar a matriz real de PHP, MySQL, PostgreSQL e eventual MariaDB suportados conforme BL-031/BL-032;
- aceitar a estratégia de núcleo compartilhado com o instalador;
- definir a janela de compatibilidade de plugins/projetos externos;
- aprovar a divisão do Epic em requisição e batches.

> Item de backlog — não autorizado para implementação até promoção explícita para `sdd/human-requests/`.
