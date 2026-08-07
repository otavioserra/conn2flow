# BL-021 — Roadmap de implementação e estratégia da branch 3.0.x

- **Tipo:** Architecture/Program Plan/Release Engineering
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** sequenciamento dos BL-011 a BL-051 e coordenação com projetos privados
- **Objetivo de branches:** `main` mantém 2.9.x; `3.0.x` concentra a evolução v3 até promoção futura

## Regra de governança

Este roadmap organiza dependências, mas permanece no backlog. Não autoriza criar branch, alterar código, gerar releases ou iniciar batches. Para execução, cada fase deve ser promovida explicitamente para `sdd/human-requests/` e dividida em batches verificáveis.

## Estado e estratégia de branches

O branch estável atual do repositório chama-se `main` — não `master`.

- `main`: linha estável 2.9.x, correções e releases de produção;
- `3.0.x`: branch longa de integração da v3;
- `feature/v3-*`: branches curtas por requisito/batch, abertas a partir de `3.0.x`;
- correções feitas em `main` devem ser incorporadas regularmente em `3.0.x` por merge controlado;
- código exclusivo da v3 não retorna para `main`;
- conflitos entre uma correção 2.9.x e a nova arquitetura devem ser resolvidos na v3 e registrados;
- `main` só recebe a v3 quando o release candidate cumprir todos os gates.

Para trabalho simultâneo, preferir `git worktree`/diretórios separados para 2.9.x e 3.0.x. Não alternar branches sobre o mesmo ambiente instalado durante migrações.

## Regra de ambientes e banco

Um ambiente atualizado para schema v3 não deve ser reutilizado cegamente pela 2.9.x. Cada linha precisa de site/banco isolado ou snapshot restaurável. Migrações devem declarar se são backward-compatible; na ausência dessa declaração, considerar o avanço para v3 unidirecional para aquela cópia do banco.

## Encadeamento geral

```text
Fase 0: canal/preflight ainda na 2.9.x ------------------+
        + hotfixes de segurança pós-req-107 -------------+
                                                         |
Fase 1: branch 3.0.x + Docker v3 isolado + CI -----------+
        + matriz mínima + padrão inglês + security core  |
        + arquitetura OO, autoload e guardrails          |
                                                         |
Fase 2: banco v2 seguro + SchemaMap +
                |                  |
Fase 3: Interface v2 + C2FI18n -----+---- primitives Tailwind
        + plataforma CRUD C2F      |
                |                  |              |
                +---------- C2FDataGrid/DataTables 3
                +---------- C2FUpload/Uppy 5
                                  |
Fase 4: piloto admin-paginas-v2
                                  |
Fase 5: ondas core + overlays privados + conteúdos
                                  |
Fase 6: remoção legado + alpha/beta/RC + promoção

Trilhas transversais: qualidade, configuração, observabilidade, performance,
governança de dados, recuperação e documentação atualizadas por batch/marco
```

A Fase 0 é obrigatória antes de publicar qualquer release v3, mas não precisa impedir o trabalho local e isolado da Fase 1. A branch e o Docker v3 podem ser preparados em paralelo ao preflight compatível da 2.9.x, desde que nenhuma tag v3 seja oferecida aos ambientes antigos.

## Trilha transversal — Documentação e conhecimento

- BL-027 cria catálogo, identidade, versionamento, ownership e mapa de conhecimento dos módulos;
- BL-028 exige atualização bilíngue junto de cada mudança funcional e uma auditoria final antes do release;
- documentação histórica da 2.9.x é preservada e marcada, não reescrita como v3;
- todo batch declara `Documentation impact` e os `doc_id` afetados;
- código, testes e documentação da mesma mudança devem apontar para a mesma revisão.

## Trilhas transversais — Qualidade e operação

Estas atividades começam nas fundações e acompanham os batches; não são uma etapa de limpeza deixada para o final:

- BL-045 torna CI de push/PR e o contrato de testes por batch obrigatórios desde o bootstrap da branch;
- BL-046 fecha configuração tipada, segredos e identidade da instalação antes de banco, sessão e integrações dependerem de novos contratos;
- BL-047 define correlação, logs estruturados e health/readiness, começando por atualizador e jobs;
- BL-049 mede baselines 2.9.x/v3 e aplica orçamentos progressivos às jornadas alteradas;
- BL-050 inventaria dados sensíveis e retenção antes de renames/migrations físicas;
- BL-051 prova backup/restore antes da primeira migration destrutiva e novamente no RC;
- BL-048 fornece jobs/runner comuns antes de outbox, sitemap, integrações e tarefas demoradas.

O agente de cada batch implementa e executa os testes automatizados correspondentes. Smokes humanos curtos ocorrem nos marcos; a homologação manual completa permanece no beta/RC, quando todas as composições estiverem disponíveis.

## Fase 0 — Preparar a linha 2.9.x para coexistir com a v3

Esta fase é a primeira porque apenas código compatível com a 2.9.x pode impedir que um servidor PHP antigo tente instalar arquivos PHP 8.5.

### Entregas

1. definir manifesto de release, requisitos e canais 2.x/3.x do BL-020;
2. ensinar atualizador e instalador atuais a ler requisitos antes de baixar/deployar;
3. manter 2.9.x como canal estável/LTS e mostrar aviso de v3 incompatível;
4. separar tags/releases estáveis de pre-releases v3;
5. impedir que uma tag alpha v3 seja marcada como `latest` para usuários 2.x;
6. adicionar teste negativo: PHP 8.4/atualizador 2.x recusa v3 antes de tocar em arquivos/banco;
7. levar essas mudanças compatíveis de `main` para a futura `3.0.x`.
8. caracterizar e congelar o Docker atual como baseline legado `L29`, sem mover dados nem anexar seu volume ao MySQL v3.
9. promover os hotfixes prioritários do BL-033: eliminar mutações por GET e aplicar scopes/capabilities nos endpoints OAuth de alto impacto.
10. promover o BL-040 na 2.9.x para separar anonimato, autenticação e suporte a cookies antes de levar o contrato à v3.

### Gate

Nenhuma release v3 é publicada enquanto uma instalação 2.9.x suportada não puder recusá-la com segurança e o baseline legado não puder ser reproduzido.

## Fase 1 — Criar a linha de desenvolvimento v3

### Entregas

1. criar `3.0.x` a partir de uma tag/commit 2.9.x conhecido;
2. criar o Docker v3 isolado do BL-029 com PHP 8.5 e MySQL 8.4 LTS, preservando o perfil legado;
3. executar a PoC do BL-031 e fechar versões mínimas de PHP/MySQL/PostgreSQL;
4. criar também o perfil PostgreSQL 18.4 isolado exigido pelo BL-032;
5. implementar wrapper operacional e eliminar dependência de nomes físicos de containers;
6. executar os gates iniciais `L29`, `C29-V3`, `V3` e `V3-PG` do BL-030;
7. configurar a estratégia do BL-045 em push/PR na versão PHP mínima e no PHP 8.5, Composer, Node, Vitest, PHPUnit e Playwright sobre o mesmo contrato de ambiente;
8. definir tags `gestor-v3.0.0-alpha.N`, depois beta/RC, como pre-release;
9. implementar autoload/namespaces e a modularização do BL-012;
10. aprovar o padrão técnico em inglês, glossário, aliases e baseline do BL-023 antes de nomear as novas APIs;
11. criar catálogo/baseline documental do BL-027, sem reescrever a árvore inteira;
12. adicionar regras que bloqueiem novas chamadas às APIs v1, novos identificadores técnicos em português e novos assets Fomantic/DataTables 1;
13. criar baselines funcionais, de segurança, i18n, documentação e performance do BL-049 antes das reescritas.
14. inventariar rotas/fronteiras e aprovar o reference monitor/manifesto do BL-034.
15. produzir a matriz inicial de capacidades/recursos do BL-035 e a ADR de implementação/migração da sessão opaca do BL-036.
16. gerar os inventários e testes negativos iniciais do BL-037.
17. aprovar o ADR de arquitetura OO, composition root, fachadas procedurais e guardrails do BL-038.
18. gerar o baseline de funções globais, superglobais, includes, hooks textuais e dependências exigido pelo BL-038.
19. inventariar fontes de configuração/segredos e aprovar precedência, identidade de instalação e redaction do BL-046.
20. aprovar o schema inicial de logs/correlação e os health checks do BL-047.
21. iniciar o catálogo de dados sensíveis, owners e retenção do BL-050 junto do dicionário de dados.

### Gate

Branch v3 reproduzível e testável no ambiente `V3`, sem compartilhar dados com `L29`, ainda funcional sem exigir que módulos sejam migrados todos de uma vez e com catálogo documental capaz de distinguir conteúdo atual, legado e histórico.

O gate também exige autoload/namespace aprovados, baseline arquitetural reproduzível, CI por push/PR, configuração inválida falhando de modo seguro e bloqueio de novas APIs procedurais no código v3, sem remover as fachadas ainda necessárias aos módulos não migrados.

## Fase 2 — Tornar banco v2 a fundação segura

Antes da primeira migration destrutiva, o BL-051 deve provar backup e restore consistentes em ambiente descartável. O catálogo do BL-050 deve acompanhar todo rename ou mudança de retenção.

### Ordem

1. contratos de conexão, comando preparado, transação e erros;
2. builder com valores parametrizados e identificadores por allowlist;
3. dicionário de dados e `SchemaMap` do BL-024, ainda sem renomeação física massiva;
4. adapters legados instrumentados;
5. migração dos serviços compartilhados exigidos pelo piloto;
6. testes de contrato, concorrência, transação e dialeto;
7. migração correspondente no instalador;
8. atualizar documentos de banco/instalador e criar o guia incremental 2.9.x → 3.x correspondente.

### Backlogs

- BL-011 — migração integral para banco v2;
- BL-012 — decomposição em pacotes/classes;
- BL-024 — nomes lógicos ingleses e migração gradual do schema;
- BL-029 — MySQL 8.4 LTS isolado e configuração efetivamente carregada;
- BL-030 — dump/restore 8.0→8.4, strict modes e testes de integridade;
- BL-031 — extremos da matriz de PHP e bancos;
- BL-032 — dialeto, migrations e execução PostgreSQL;
- BL-034 — pipeline de mediação completa como fundação dos novos serviços;
- BL-035 — autorização dos comandos e escopo de recurso no acesso a dados;
- BL-037 — testes negativos de isolamento, SQL, tenant e bypass;
- backlogs BL-001 dos projetos privados, iniciados apenas após o contrato estabilizar.

### Gate

O piloto não pode usar `escape`, SQL textual derivado de request ou métodos de compatibilidade que apenas mudem o nome da API; suas queries e migrations precisam passar no MySQL 8.4 LTS do ambiente `V3`.

## Fase 3 — Plataforma de interface e Tailwind

Três trilhas podem avançar em paralelo depois dos contratos básicos do banco:

### Trilha A — Interface

- BL-013: request/response, autorização, CSRF, validação, renderer e protocolo AJAX;
- BL-034/BL-035/BL-036: consumir o pipeline, principal e policy canônicos; a interface não cria um segundo sistema de segurança;
- fachadas temporárias para coexistência de módulos v1/v2.

### Trilha A2 — Idiomas e recursos

- BL-025: `C2FI18n`, loaders de banco/JSON, namespaces, fallback, placeholders, escaping e catálogo frontend;
- BL-026: começar pelas mensagens transversais de sessão, autorização, CSRF e erros AJAX;
- manter a infraestrutura declarativa atual como fonte, sem criar um catálogo paralelo;
- novas telas/componentes não entram com textos hardcoded.

### Trilha B — Design system

- BL-016: tokens, shell e componentes server-rendered;
- BL-017: dialog/confirm, toast/alert, busy state, campos e lifecycle AJAX;
- começar somente pelas primitives necessárias ao piloto.

### Trilha C — Listagens

- BL-018: contrato `C2FDataGrid`, backend próprio e adapter DataTables 3;
- tema/controles Tailwind próprios, sem jQuery/Fomantic;
- PoC de listagem simples, autorizada e volumosa.

### Trilha D — Uploads

- decisão arquitetural aprovada em 2026-08-07: Uppy 5 como motor frontend interno;
- BL-022: contrato `C2FUpload`, policy/backend seguro e adapter Uppy 5;
- UI Tailwind própria com XHR multipart no primeiro lote;
- piloto no `admin-arquivos` e coordenação obrigatória com o módulo privado `conn2flow-site/arquivos`;
- tus/chunks somente em requisito posterior, se volumes/rede justificarem.

### Trilha E — Plataforma CRUD

- BL-039: caracterizar as repetições reais e aprovar os limites entre CRUD declarativo, composto e casos de uso explícitos;
- construir sobre banco v2, reference monitor, policies, HTTP e eventos tipados, nunca como atalho que contorne essas camadas;
- validar primeiro um cadastro simples e depois `admin-paginas`, evitando abstração moldada por um único módulo complexo;
- integrar por adapters com C2FDataGrid, C2FUpload, C2FI18n e os presenters Tailwind;
- manter `AbstractCrudModule`, se existir, pequeno e opcional; composição e interfaces são o padrão.

### Gate

Uma tela de referência demonstra banco seguro, nomes técnicos ingleses, arquitetura OO, interface v2, plataforma CRUD, `C2FI18n`, Tailwind, componentes AJAX e `C2FDataGrid` sem dependências v1 nem texto de UI hardcoded.

## Fase 4 — Piloto admin-paginas-v2

Executar BL-014 somente após as fundações anteriores.

### Ordem

1. matriz de paridade com `admin-paginas` atual;
2. inventário de toda mutação em `paginas`/`publisher_pages`, incluindo interface genérica, plugins e sincronizadores;
3. ADR do domínio central, estados e casos de uso de publicação do BL-041;
4. testes de caracterização de registros, rotas, recursos, permissões, janelas, redirects e customizações;
5. classificar cada fluxo como CRUD comum ou caso de uso específico e confrontá-lo com o piloto simples do BL-039;
6. provar o runner SQL/CLI do BL-048 e a outbox/eventos do BL-044 com consumidor local idempotente;
7. reimplementação OO sobre banco/interface/plataforma CRUD/C2FI18n/C2FDataGrid e domínio de páginas, usando nomes lógicos ingleses;
8. adaptar `publisher-pages` ao mesmo serviço de publicação, ainda que sua migração visual completa pertença à onda correspondente;
9. migração idempotente dos metadados experimentais;
10. cutover por flag na branch v3;
11. produzir a projeção `sitemap.xml`/SEO do BL-042 e somente depois executar o PoC `llms.txt` do BL-043;
12. atualizar referência técnica e manual `pt-br`/`en`, usando o piloto do BL-028;
13. testes de atualização, rollback, segurança, acessibilidade, descoberta pública, documentação e performance, seguidos do smoke humano do marco.

### Gate

Paridade aprovada e uma única identidade canônica, sem perda de dados, sem chamadas v1 e com documentação técnica/manual verificada contra o mesmo commit. Toda publicação passa pelo domínio central; sitemap deriva de URLs públicas/indexáveis e nenhum side effect externo participa da transação do CRUD.

## Fase 5 — Migração por ondas

### Core

- seguir as ondas do BL-015;
- converter funções/globais em controllers, casos de uso, policies e repositories conforme BL-038, mantendo adapter procedural apenas enquanto houver consumidor;
- adotar o nível adequado da plataforma CRUD do BL-039 sem forçar workflows complexos ao executor genérico;
- converter componentes Fomantic conforme BL-016/BL-017;
- migrar listagens para `C2FDataGrid`;
- migrar uploads compartilhados para `C2FUpload` conforme BL-022;
- renomear símbolos/comentários junto de cada módulo conforme BL-023;
- migrar o schema físico por bounded context conforme BL-024, somente após seus consumidores usarem banco v2/SchemaMap;
- remover textos hardcoded e validar recursos conforme BL-025/BL-026;
- converter layouts, páginas, templates e recursos pelo BL-019.
- atualizar a documentação técnica e o manual de cada módulo no próprio batch conforme BL-027/BL-028.

### Projetos privados

- `conn2flow-site`: banco/schema, interface, UI/listagens, inglês técnico, i18n e validação do overlay;
- `lumix`: banco/schema, interface, UI/listagens, inglês técnico, i18n e validação de fluxos financeiros/externos;
- `transformamp`: recursos/conteúdo, IDs técnicos, i18n e validação da composição;
- cada overlay deve ser testado unido à mesma tag/commit do core v3.

### Regra de ondas

Uma onda só começa quando a anterior não criou novos usos v1 e possui rollback/fixtures suficientes. Correções urgentes da 2.9.x continuam em `main` e são mescladas na v3.

## Fase 6 — Remoção e estabilização

### Critérios para remover legado

- zero consumidores de banco/interface v1 no core e overlays suportados;
- zero consumidores das fachadas procedurais marcadas para remoção e nenhuma regra de negócio duplicada nelas;
- zero acesso a superglobais/container global fora dos adapters aprovados no código v3;
- zero aliases técnicos em português fora da camada de compatibilidade aprovada e, ao final, zero aliases pendentes;
- schema físico final em inglês, sem views/triggers de compatibilidade permanentes;
- zero texto de UI hardcoded e catálogos obrigatórios sem chaves/placeholders divergentes;
- zero DataTables 1.10.23, Responsive antigo e tema Semantic;
- zero runtime Fomantic/CDN administrativo;
- conteúdo customizado possui conversor, compatibilidade isolada ou decisão explícita;
- instalador/atualizador reconhecem canais e requisitos;
- métricas não ultrapassam orçamento aprovado;
- documentação de extensão privada está publicada;
- catálogo documental não possui item `current` apontando para API/schema removido;
- documentos e manuais obrigatórios têm paridade semântica `pt-br`/`en`, links válidos e commit verificado.

### Escada de release

1. `3.0.0-alpha.N`: desenvolvimento integrado e ambientes descartáveis;
2. `3.0.0-beta.N`: feature complete, migrações congeladas sob controle de versão;
3. `3.0.0-rc.N`: somente correções, upgrade/rollback e homologação dos overlays;
4. `3.0.0`: release estável após aprovação humana;
5. promoção futura da v3 para `main`, preservando tag/branch de manutenção 2.9.x conforme política LTS.

## Ordem recomendada dos primeiros requisitos/batches

1. **Hotfixes de segurança e acesso anônimo na 2.9.x** — BL-033/BL-040, começando por mutações GET, autorização OAuth e remoção do desafio global de cookies; incorporar depois à v3.
2. **Compatibilidade/canais na 2.9.x** — parte compatível do BL-020; pode avançar em paralelo ao item 1.
3. **Caracterização/congelamento do Docker legado** — Lote A do BL-029 + baseline `L29` do BL-030.
4. **Bootstrap da branch 3.0.x + Docker v3 isolado** — BL-029, antes das reescritas de biblioteca.
5. **PoC da compatibilidade mínima** — BL-031, fechando PHP/MySQL/PostgreSQL antes das novas APIs.
6. **Matriz inicial, PostgreSQL 18.4 e CI da v3** — BL-029/BL-030/BL-032 + ferramentas do BL-020/BL-012.

Até o item 6 também devem ser promovidas as primeiras fatias transversais do BL-045/BL-046/BL-047/BL-049/BL-050: CI por push/PR, inventário de configuração/segredos, schema de observabilidade, baseline de performance e catálogo de dados. O ensaio inicial do BL-051 ocorre antes de qualquer migration destrutiva; a PoC do BL-048 ocorre antes do item 24.

7. **Inventário de rotas e threat model** — baseline não funcional do BL-034/BL-037.
8. **ADR do reference monitor e manifesto de rotas** — BL-034.
9. **Matriz RBAC/capabilities/recursos** — primeira fatia do BL-035.
10. **ADR da sessão opaca e credenciais por canal** — primeira fatia do BL-036; a escolha web está fechada, faltam contrato, coexistência e rotação.
11. **ADR/glossário inglês + baseline técnico** — primeira fatia do BL-023.
12. **Catálogo documental + mapa de conhecimento** — primeira fatia do BL-027, sem reescrita massiva.
13. **ADR OO + inventário/guardrails** — primeira fatia do BL-038, antes de criar novas APIs e sem reescrever módulos.
14. **Core seguro do banco v2 + SchemaMap do piloto** — BL-011 + primeira fatia do BL-024, consumindo policy/contexto e rodando nos extremos aprovados.
15. **Contrato C2FI18n + validador de recursos** — primeira fatia do BL-025, antes das novas telas.
16. **Contrato HTTP/interface v2** — primeira fatia do BL-013, sobre o pipeline de segurança aprovado.
17. **Mensagens globais de sessão/autorização/CSRF** — primeira fatia do BL-026.
18. **Tokens + dialog/toast/loading Tailwind** — BL-016/BL-017, consumindo C2FI18n.
19. **Contrato e PoC C2FDataGrid/DataTables 3** — BL-018.
20. **Contrato/backend e PoC C2FUpload/Uppy 5** — BL-022; pode avançar paralelamente ao item 19.
21. **Inventário/ADR da plataforma CRUD + piloto simples** — primeira fatia do BL-039, após contratos de dados/segurança/HTTP.
22. **Inventário/ADR do domínio de páginas e publicação** — primeira fatia do BL-041 junto da caracterização do BL-014, incluindo todas as escritas laterais.
23. **Caracterização e paridade admin-paginas/publisher-pages** — BL-014, com matriz de autorização, estados editoriais e classificação dos fluxos CRUD.
24. **Core de publicação + outbox local** — primeira fatia do BL-041/BL-044, sem integração externa.
25. **Reimplementação/cutover OO do piloto complexo e adapters de publisher** — BL-014/BL-039/BL-041, incluindo nomes lógicos ingleses e recursos.
26. **Sitemap, canonical, idiomas e SEO técnico** — primeira fatia do BL-042 sobre a projeção de publicação.
27. **PoC curado de `llms.txt` e política de crawlers de IA** — BL-043, somente após sitemap/indexabilidade estáveis.
28. **Piloto documental bilíngue do admin-paginas-v2** — primeira fatia do BL-028, dentro do mesmo cutover.
29. **Migração física do schema do contexto piloto** — BL-024, após contador zero de consumidores legados.
30. **Migração do admin-arquivos e overlay privado arquivos** — após estabilização de C2FUpload.
31. **Primeira onda de módulos simples** — BL-015 + BL-023/BL-026/BL-028/BL-038/BL-039.
32. **Primeiro overlay privado representativo** — após estabilização dos contratos.

## Critérios de aceite do roadmap

- dependências e gates são refletidos nos requisitos promovidos;
- nenhum batch mistura infraestrutura, dezenas de módulos e migração de dados sem checkpoint;
- `main` continua liberável durante todo o desenvolvimento da v3;
- 2.9.x e 3.0.x usam ambientes/bancos isolados;
- os perfis `L29` e `V3` não compartilham volume, diretório de site, `.env`, rede ou nome físico de container;
- a versão PHP mínima e o PHP 8.5, além dos extremos MySQL/PostgreSQL, são verificados localmente e no CI pelo mesmo contrato;
- fixes 2.9.x chegam regularmente à v3;
- releases v3 não são oferecidas como atualização compatível para PHP antigo;
- novos contratos nascem em inglês e textos de interface nascem em recursos;
- renames físicos do banco só ocorrem depois da migração lógica dos consumidores;
- cada batch declara impacto documental e a auditoria do RC confirma paridade `pt-br`/`en`;
- cada batch deixa CI verde, adiciona testes proporcionais ao risco e registra a evidência exigida pelo BL-045;
- migrations destrutivas não avançam sem restore comprovado, e beta/RC incluem smoke humano das composições suportadas;
- configuração, logs, jobs, caches e backups respeitam isolamento de instalação, redaction e ciclo de vida dos dados;
- nenhuma mutação usa GET, nenhuma operação desconhecida faz fallback permissivo e todo endpoint API de alto impacto exige scope/capability explícitos;
- inventário de rotas prova mediação completa e acesso direto a PHP privado recebe `403/404`;
- sessão, OAuth, webhook, módulo distribuído e CLI usam autenticadores próprios sob o mesmo pipeline, sem isenções implícitas;
- APIs novas são OO/namespaced, dependências são explícitas e nenhuma superclasse CRUD concentra o sistema;
- métricas de fachadas procedurais, funções globais e acesso a superglobais decrescem por batch até os gates de remoção;
- promoção para `main` é decisão humana posterior, não consequência automática de merge/tag.

## Próxima ação

Quando houver autorização para iniciar, promover a Fase 0 como requisição própria e, em paralelo seguro, promover a caracterização não destrutiva do Docker legado. Em seguida, criar a branch `3.0.x` e fazer do ambiente Docker v3 isolado o primeiro requisito técnico dessa branch, antes de banco/interface.
