# BL-021 — Roadmap de implementação e estratégia da branch 3.0.x

- **Tipo:** Architecture/Program Plan/Release Engineering
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** sequenciamento dos BL-011 a BL-032 e coordenação com projetos privados
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
                                                         |
Fase 1: branch 3.0.x + Docker v3 isolado + CI -----------+
        + matriz mínima + padrão inglês + arquitetura    |
                                                         |
Fase 2: banco v2 seguro + SchemaMap +
                |                  |
Fase 3: Interface v2 + C2FI18n -----+---- primitives Tailwind
                |                  |              |
                +---------- C2FDataGrid/DataTables 3
                +---------- C2FUpload/Uppy 5
                                  |
Fase 4: piloto admin-paginas-v2
                                  |
Fase 5: ondas core + overlays privados + conteúdos
                                  |
Fase 6: remoção legado + alpha/beta/RC + promoção

Trilha transversal: documentação pt-br/en atualizada por batch e auditada em cada marco
```

A Fase 0 é obrigatória antes de publicar qualquer release v3, mas não precisa impedir o trabalho local e isolado da Fase 1. A branch e o Docker v3 podem ser preparados em paralelo ao preflight compatível da 2.9.x, desde que nenhuma tag v3 seja oferecida aos ambientes antigos.

## Trilha transversal — Documentação e conhecimento

- BL-027 cria catálogo, identidade, versionamento, ownership e mapa de conhecimento dos módulos;
- BL-028 exige atualização bilíngue junto de cada mudança funcional e uma auditoria final antes do release;
- documentação histórica da 2.9.x é preservada e marcada, não reescrita como v3;
- todo batch declara `Documentation impact` e os `doc_id` afetados;
- código, testes e documentação da mesma mudança devem apontar para a mesma revisão.

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
7. configurar CI na versão PHP mínima e no PHP 8.5, Composer, Node, Vitest, PHPUnit e Playwright sobre o mesmo contrato de ambiente;
8. definir tags `gestor-v3.0.0-alpha.N`, depois beta/RC, como pre-release;
9. implementar autoload/namespaces e a modularização do BL-012;
10. aprovar o padrão técnico em inglês, glossário, aliases e baseline do BL-023 antes de nomear as novas APIs;
11. criar catálogo/baseline documental do BL-027, sem reescrever a árvore inteira;
12. adicionar regras que bloqueiem novas chamadas às APIs v1, novos identificadores técnicos em português e novos assets Fomantic/DataTables 1;
13. criar baselines funcionais, de segurança, i18n, documentação e performance antes das reescritas.

### Gate

Branch v3 reproduzível e testável no ambiente `V3`, sem compartilhar dados com `L29`, ainda funcional sem exigir que módulos sejam migrados todos de uma vez e com catálogo documental capaz de distinguir conteúdo atual, legado e histórico.

## Fase 2 — Tornar banco v2 a fundação segura

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
- backlogs BL-001 dos projetos privados, iniciados apenas após o contrato estabilizar.

### Gate

O piloto não pode usar `escape`, SQL textual derivado de request ou métodos de compatibilidade que apenas mudem o nome da API; suas queries e migrations precisam passar no MySQL 8.4 LTS do ambiente `V3`.

## Fase 3 — Plataforma de interface e Tailwind

Três trilhas podem avançar em paralelo depois dos contratos básicos do banco:

### Trilha A — Interface

- BL-013: request/response, autorização, CSRF, validação, renderer e protocolo AJAX;
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

### Gate

Uma tela de referência demonstra banco seguro, nomes técnicos ingleses, interface v2, `C2FI18n`, Tailwind, componentes AJAX e `C2FDataGrid` sem dependências v1 nem texto de UI hardcoded.

## Fase 4 — Piloto admin-paginas-v2

Executar BL-014 somente após as fundações anteriores.

### Ordem

1. matriz de paridade com `admin-paginas` atual;
2. testes de caracterização da tabela `paginas`, rotas, recursos, permissões e customizações;
3. reimplementação sobre banco/interface/C2FI18n/C2FDataGrid, usando nomes lógicos ingleses;
4. migração idempotente dos metadados experimentais;
5. cutover por flag na branch v3;
6. atualizar referência técnica e manual `pt-br`/`en`, usando o piloto do BL-028;
7. testes de atualização, rollback, segurança, acessibilidade, documentação e performance.

### Gate

Paridade aprovada e uma única identidade canônica, sem perda de dados, sem chamadas v1 e com documentação técnica/manual verificada contra o mesmo commit.

## Fase 5 — Migração por ondas

### Core

- seguir as ondas do BL-015;
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

1. **Compatibilidade/canais na 2.9.x** — parte compatível do BL-020.
2. **Caracterização/congelamento do Docker legado** — Lote A do BL-029 + baseline `L29` do BL-030; pode avançar em paralelo ao item 1.
3. **Bootstrap da branch 3.0.x + Docker v3 isolado** — BL-029, antes das reescritas de biblioteca.
4. **PoC da compatibilidade mínima** — BL-031, fechando PHP/MySQL/PostgreSQL antes das novas APIs.
5. **Matriz inicial, PostgreSQL 18.4 e CI da v3** — BL-029/BL-030/BL-032 + ferramentas do BL-020/BL-012.
6. **ADR/glossário inglês + baseline técnico** — primeira fatia do BL-023.
7. **Catálogo documental + mapa de conhecimento** — primeira fatia do BL-027, sem reescrita massiva.
8. **Core seguro do banco v2 + SchemaMap do piloto** — BL-011 + primeira fatia do BL-024, nos extremos MySQL/PostgreSQL aprovados.
9. **Contrato C2FI18n + validador de recursos** — primeira fatia do BL-025, antes das novas telas.
10. **Contrato HTTP/interface v2** — primeira fatia do BL-013, já usando códigos/chaves de erro.
11. **Mensagens globais de sessão/autorização/CSRF** — primeira fatia do BL-026.
12. **Tokens + dialog/toast/loading Tailwind** — BL-016/BL-017, consumindo C2FI18n.
13. **Contrato e PoC C2FDataGrid/DataTables 3** — BL-018.
14. **Contrato/backend e PoC C2FUpload/Uppy 5** — BL-022; pode avançar paralelamente ao item 13.
15. **Caracterização e paridade admin-paginas** — primeira fatia do BL-014.
16. **Reimplementação/cutover do piloto** — segunda fatia do BL-014, incluindo nomes lógicos ingleses e recursos.
17. **Piloto documental bilíngue do admin-paginas-v2** — primeira fatia do BL-028, dentro do mesmo cutover.
18. **Migração física do schema do contexto piloto** — BL-024, após contador zero de consumidores legados.
19. **Migração do admin-arquivos e overlay privado arquivos** — após estabilização de C2FUpload.
20. **Primeira onda de módulos simples** — BL-015 + BL-023/BL-026/BL-028.
21. **Primeiro overlay privado representativo** — após estabilização dos contratos.

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
- promoção para `main` é decisão humana posterior, não consequência automática de merge/tag.

## Próxima ação

Quando houver autorização para iniciar, promover a Fase 0 como requisição própria e, em paralelo seguro, promover a caracterização não destrutiva do Docker legado. Em seguida, criar a branch `3.0.x` e fazer do ambiente Docker v3 isolado o primeiro requisito técnico dessa branch, antes de banco/interface.
