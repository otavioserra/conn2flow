# BL-039 — Plataforma CRUD C2F para módulos v3

- **Tipo:** Epic/Architecture/Developer Experience/Security
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** padrão reutilizável para CRUDs do core e overlays privados
- **Relacionados:** BL-011, BL-013, BL-014, BL-015, BL-018, BL-025, BL-034, BL-035, BL-038

## Problema observado

Os módulos administrativos repetem a mesma estrutura, mesmo quando usam `interface.php` ou a experiência `interface-v2.php`:

- funções `*_start`, `*_adicionar`, `*_editar`, `*_clonar` e configuração de listagem;
- binding manual de request para campos, validação de obrigatórios e normalização;
- geração de identificador, status, versão, timestamps, idioma e usuário;
- listagem, filtro, ordenação, paginação, botões e redirects/AJAX;
- histórico, backup, hooks e mensagens;
- autorização muitas vezes implícita na visibilidade do módulo/ação.

No inventário preliminar do core há cerca de 34 entradas `*_start`, 24 rotinas de adição e 26 de edição. `admin-paginas-v2` reduz parte da configuração por meio da fachada `InterfaceV2`, mas ainda mantém fluxo procedural, SQL/where textual, globals e regras do módulo misturadas à entrega HTTP.

## Decisão proposta

Criar a plataforma namespaced `C2F\Crud` (nome final por ADR), integrada à interface v3. Ela deve reduzir boilerplate por composição e metadados tipados, sem forçar todo módulo a herdar de uma superclasse gigante.

Contrato recomendado:

- `CrudModule`/`CrudResourceDefinition`: identidade, entidade lógica, capacidades e operações suportadas;
- `FieldDefinition`, `ColumnDefinition`, `ActionDefinition`, `FilterDefinition` e regras tipadas;
- comandos/DTOs para `Create`, `Update`, `Delete`, `Clone` e `ChangeStatus`;
- queries para `List` e `Get`;
- handlers/casos de uso pequenos;
- repository por agregado/contexto apoiado no banco v2 e em prepared statements;
- policy de autorização por operação e recurso;
- serviços de validação, transação, histórico, auditoria e eventos;
- presenters/adapters para formulário, `C2FDataGrid`, resposta HTTP/AJAX e CLI/testes.

Uma classe opcional `AbstractCrudModule` pode fornecer apenas lifecycle estável e conveniências. Ela não deve possuir banco, sessão, renderer e dezenas de hooks protegidos nem se tornar requisito para casos complexos.

## Três níveis de adoção

### Nível 1 — CRUD declarativo

Para tabelas de referência e cadastros simples. O módulo declara campos, colunas, capacidades e validações; a plataforma executa os casos de uso comuns.

### Nível 2 — CRUD composto

Para cadastros com policies, validações, relacionamentos ou eventos próprios. O módulo reaproveita comandos e pipeline, mas injeta handlers/serviços específicos.

### Nível 3 — Caso de uso explícito

Para pagamentos, atualização do sistema, plugins, usuários/identidade, publicação e demais workflows complexos. Não devem ser deformados para caber num CRUD genérico; podem reutilizar formulário, grid, transação, auditoria e response sem usar o executor CRUD padrão.

## Fluxo canônico de mutação

```text
rota + request
  -> DTO de entrada/normalização
  -> autenticação e policy(capability, recurso)
  -> validação e regras de domínio
  -> transação
       -> repository preparado
       -> histórico/versionamento quando aplicável
       -> auditoria/outbox/eventos
  -> presenter + resposta HTTP/AJAX tipada
```

Falha em qualquer passo transacional deve reverter registro e histórico relacionados. Auditoria de segurança imutável e histórico funcional/restaurável são conceitos separados, ainda que compartilhem contexto.

## Metadados e extensibilidade

- preferir objetos PHP tipados/imutáveis para definições executáveis;
- manter JSON do módulo para manifesto, instalação e recursos/i18n, não para esconder policies, SQL ou closures;
- attributes podem marcar metadados estáticos pequenos após PoC, mas não são requisito da primeira versão;
- eventos (`BeforeCreate`, `AfterCreate`, etc.) são tipados e handlers são registrados explicitamente;
- adapter legado traduz hooks atuais para eventos durante a janela de migração;
- extensões privadas usam pontos de extensão públicos/versionados, sem alcançar propriedades internas da plataforma.

## Segurança obrigatória

- allowlist de campos graváveis para impedir mass assignment;
- allowlist de filtros, ordenação, relações e identificadores;
- nenhuma mutação por GET e CSRF aplicado no pipeline de rota;
- capability por operação e policy por registro para impedir IDOR;
- prepared statements e mapeamento lógico de schema; tabela/coluna nunca vêm diretamente da request;
- optimistic locking/campo de versão nos recursos concorrentes;
- limites para paginação, busca e payload;
- escaping contextual no presenter e tipo explícito para HTML confiável;
- logs e envelopes de erro não expõem SQL, segredos ou dados pessoais.

## Integrações da plataforma

- **BL-011/BL-032:** repositories e transações portáveis entre MySQL e PostgreSQL;
- **BL-018:** listagens usam `C2FDataGrid`; o CRUD não conhece DataTables diretamente;
- **BL-022:** campos de arquivo usam `C2FUpload`; upload não é implementado pelo CRUD;
- **BL-025/BL-026:** labels, erros e confirmações usam chaves `C2FI18n`;
- **BL-034/BL-035:** rotas e capabilities são declaradas no reference monitor;
- **BL-016/BL-017:** presenters usam componentes Tailwind, sem classes Fomantic no domínio;
- **BL-038:** módulos e casos de uso seguem contratos OO e fachadas procedurais temporárias.

## Pilotos e sequência

1. caracterizar automaticamente os padrões repetidos e as variações reais dos módulos atuais;
2. aprovar ADR do contrato CRUD, limites dos três níveis e lifecycle transacional;
3. implementar, quando autorizado, um cadastro simples de referência como piloto de Nível 1;
4. medir linhas/configuração, queries, tempo, segurança e capacidade de extensão;
5. usar `admin-paginas-v2` como piloto de Nível 2/3, depois da matriz de paridade do BL-014;
6. revisar a abstração com os dois extremos antes de migrar ondas inteiras;
7. oferecer gerador de esqueleto apenas após estabilizar o contrato.

O primeiro piloto não deve ser somente `admin-paginas`: sua complexidade poderia induzir abstrações específicas. O par “cadastro simples + admin-paginas” prova tanto o caminho feliz quanto extensibilidade.

## Gerador de módulo futuro

Após o contrato estabilizar, uma ferramenta pode criar manifesto, resource definition, controller/handler, policy, resources i18n e testes básicos. O gerador deve produzir pouco código e nunca sobrescrever customizações silenciosamente. A fonte de verdade continua sendo a definição tipada e os casos de uso, não milhares de linhas geradas.

## Testes de contrato

- matriz create/read/update/delete/clone/status com sucesso e falha;
- autenticação, capabilities, IDOR, CSRF e mass assignment;
- validação, unicidade, concorrência/optimistic lock e rollback;
- atomicidade entre registro, histórico e eventos;
- paginação/filtro/ordenação e prevenção de N+1;
- paridade de repository em MySQL e PostgreSQL;
- sessão expirada e envelopes HTTP/AJAX;
- i18n, escaping e acessibilidade do presenter;
- compatibilidade de composição com pelo menos um overlay privado.

## Orçamento de desempenho

- definitions compiladas/cacheadas por módulo e request;
- proibir consulta de permissão por linha e carregamento N+1;
- paginação server-side e projeção apenas das colunas necessárias;
- medir quantidade de queries, p95, memória e tamanho de resposta contra o módulo legado;
- regressões só são aceitas com justificativa explícita e orçamento aprovado.

## Critérios de aceite da Epic

- um cadastro simples e `admin-paginas` demonstram os níveis distintos sem duplicar o pipeline;
- criar um CRUD padrão exige principalmente definição, policy, recursos e testes, não copiar funções inteiras;
- regras específicas permanecem em casos de uso explícitos;
- histórico/auditoria são transacionais e distinguíveis;
- nenhuma superclasse concentra dependências globais ou internals de UI/banco;
- contratos de módulo privado são documentados e testados na composição;
- métricas comprovam redução de duplicação sem regressão indevida de segurança ou performance.

## Próxima decisão

Promover um batch somente de inventário e ADR. A implementação da plataforma começa depois dos contratos de banco, segurança e HTTP e antes das ondas de migração de módulos.
