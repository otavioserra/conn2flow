# BL-041 — Domínio central de páginas e publicação na v3

- **Tipo:** Epic/Architecture/Content Management/API
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** todos os fluxos que criam, alteram, clonam, publicam, despublicam, agendam, movem ou removem páginas
- **Relacionados:** BL-011, BL-013, BL-014, BL-021, BL-023, BL-024, BL-034, BL-035, BL-037, BL-038, BL-039, BL-040, BL-042, BL-043, BL-044

## Problema observado

`admin-paginas` e `publisher-pages` implementam separadamente grande parte do mesmo ciclo de vida sobre `paginas`: validação de caminho, insert/update, clone, status, janela de publicação, histórico e criação de redirecionamento 301. Há ainda mutações laterais:

- `interface.php` e `interface-v2.php` executam status e exclusão lógica genéricos;
- `plugins-installer.php` faz upsert direto de páginas de módulos/plugins;
- os sincronizadores de dados do core e plugins atualizam `PaginasData.json`/`paginas` por pipelines próprios;
- `publisher-pages` precisa manter `paginas` e `publisher_pages` coerentes;
- hooks podem reagir depois da gravação, mas não constituem uma API de domínio transacional.

Adicionar geração de sitemap diretamente a cada módulo repetiria regras e deixaria rotas laterais sem notificação. Também permitiria publicar no sitemap uma página que ainda estivesse protegida, agendada, excluída ou parcialmente gravada.

## Evidências no código atual

- `admin-paginas.php`: inserts de adicionar/clonar, update de editar e 301 próprio nas regiões das linhas 119, 435, 478 e 861;
- `publisher-pages.php`: fluxo paralelo nas regiões das linhas 194, 774, 784 e 1511, além da manutenção de `publisher_pages`;
- `interface.php`/`interface-v2.php`: exclusão lógica e mudança de status genéricas escrevem a tabela configurada pelo módulo;
- `plugins-installer.php::plugin_upsert_page()`: faz upsert direto em `paginas`;
- `atualizacoes-banco-de-dados.php` e `atualizacao-plugin-banco-de-dados.php`: sincronizam `paginas` em lote;
- `gestor.php`: o roteador filtra `status`/janela e interpreta `sem_permissao` como acesso público, mostrando que entrega e publicação ainda dependem de campos legados compartilhados.

## Decisão proposta

Criar um bounded context de conteúdo/páginas no core, com namespace final sob `C2F\Content` ou `C2F\Pages` definido por ADR. O serviço pertence ao domínio do Contao Flow, não à tela `admin-paginas`.

`admin-paginas`, `publisher-pages`, instalador de plugins, sincronizadores e futuros módulos serão adapters/clientes da mesma camada de aplicação. Estar no mesmo processo PHP não exige uma chamada HTTP: o contrato primário deve ser uma API interna OO. Uma API HTTP versionada só será exposta quando houver consumidor remoto real.

## Modelo de domínio proposto

Separar conceitos atualmente comprimidos em `status`, `sem_permissao`, `tipo` e datas:

- **estado editorial:** draft, review, scheduled, published, unpublished, archived;
- **visibilidade/acesso:** public, guest-only, authenticated, capability;
- **indexabilidade:** index/follow, noindex, exclusão de sitemap e política por crawler;
- **identidade:** ID estável independente de slug/caminho;
- **URL canônica:** host/site, idioma, caminho normalizado e histórico de redirects;
- **janela:** publicação inicial/final com timezone explícito;
- **datas:** criação, modificação técnica, modificação significativa do conteúdo e publicação;
- **origem:** página editorial, página de publisher, página de sistema, plugin ou recurso gerenciado;
- **metadados:** título/descrição SEO, imagem social, canonical override controlado e dados estruturados.

Os nomes físicos finais dependem do BL-024; na primeira etapa o `SchemaMap` pode traduzir o schema legado.

## Casos de uso canônicos

- `CreatePage`, `UpdatePage`, `ClonePage` e `ArchivePage`;
- `PublishPage`, `UnpublishPage`, `SchedulePage` e `ExpirePage`;
- `ChangePagePath`, sempre produzindo redirect validado quando aplicável;
- `ChangePageVisibility` e `ChangeIndexingPolicy`;
- `UpsertManagedPage`, para recursos vindos de módulos/plugins sem apagar customizações do usuário;
- `AttachPublisherContent`, preservando atomicidade entre `paginas` e `publisher_pages`;
- queries `GetPage`, `ListPages` e `ListDiscoverablePages`.

Cada comando deve possuir DTO tipado, autorização/capability, validação, transação, optimistic locking/idempotência quando necessária, histórico funcional, auditoria e eventos após commit.

## Contrato de publicação

Uma página só é publicável quando:

- caminho e URL canônica são válidos e únicos no site/idioma;
- layout/template e dependências obrigatórias existem;
- o estado e a janela temporal permitem entrega;
- a política de acesso está explícita;
- o conteúdo exigido pelo tipo de página está completo;
- não há conflito de versão concorrente;
- a policy autoriza o ator a publicar, não apenas a editar.

Salvar rascunho e publicar são operações distintas. Um CRUD genérico pode apoiar a edição, mas publicação é caso de uso Nível 3 do BL-039.

## Eventos após commit

Eventos tipados mínimos:

- `PageCreated`, `PageContentChanged`, `PagePathChanged`;
- `PagePublished`, `PageUnpublished`, `PageScheduled`, `PageExpired`;
- `PageVisibilityChanged`, `PageIndexingPolicyChanged`, `PageArchived`;
- `ManagedPageSynchronized`.

Consumidores como sitemap, cache, feed, IndexNow e observabilidade não executam dentro da transação principal. Usar outbox transacional e processamento idempotente conforme BL-044 evita página salva com side effects parcialmente executados.

## API interna e HTTP opcional

### API interna obrigatória

- interfaces e DTOs namespaced, sem `$_REQUEST` ou globals;
- chamada direta pelos controllers/adapters locais;
- retorno tipado com ID, versão, estado e eventos produzidos;
- mesma policy para interface administrativa, CLI, atualização e jobs.

### API HTTP somente sob demanda

Se overlays, agentes ou serviços externos precisarem publicar remotamente:

- endpoints versionados sobre os mesmos casos de uso, sem SQL/CRUD genérico exposto;
- autenticação própria do canal e scopes como `pages:write`, `pages:publish` e `pages:read`;
- idempotency key, rate limit, limites de payload e auditoria;
- publicação separada de edição para aplicar aprovação humana/policy;
- respostas `202` para workflows assíncronos quando houver processamento posterior;
- documentação OpenAPI gerada do contrato, sem transformar `llms.txt` em API de escrita.

## Migração dos consumidores

1. inventariar e caracterizar todas as escritas em `paginas` e tabelas relacionadas;
2. criar testes de contrato do comportamento atual, incluindo 301 e janelas;
3. aprovar ADR do agregado, estados e boundaries transacionais;
4. implementar o serviço somente quando houver requisição humana;
5. adaptar `admin-paginas-v2` e `publisher-pages`;
6. adaptar status/exclusão da plataforma CRUD para delegar ao caso de uso;
7. adaptar plugins e sincronizadores com `UpsertManagedPage` em lote;
8. bloquear novas escritas diretas e reduzir as existentes até zero;
9. retirar hooks textuais equivalentes somente após todos os consumidores usarem eventos tipados.

Operações de migração em massa devem poder suprimir notificações externas por registro e emitir um evento consolidado/rebuild ao final, sem ignorar validação ou auditoria.

## Testes mínimos

- paridade create/edit/clone/status/delete entre `admin-paginas` e `publisher-pages`;
- transação entre `paginas`, `publisher_pages`, histórico, redirect e outbox;
- colisão de caminho por site/idioma e normalização equivalente;
- draft, agendamento, expiração e relógio/timezone controlados;
- página pública, protegida, noindex e arquivada;
- IDOR/capability distinta para editar e publicar;
- idempotência de plugin/update e preservação de conteúdo customizado;
- falha de consumidor externo não desfaz página já confirmada nem perde evento;
- MySQL e PostgreSQL com o mesmo contrato funcional.

## Critérios de aceite da Epic

- nenhuma regra de publicação é duplicada entre `admin-paginas` e `publisher-pages`;
- nenhuma escrita normal em `paginas` contorna o serviço de aplicação aprovado;
- publicação e acesso/indexabilidade são conceitos positivos e distintos;
- side effects acontecem após commit por contrato idempotente;
- o serviço funciona sem depender de HTML, Tailwind, superglobais ou módulo administrativo;
- sitemap e descoberta de IA recebem somente eventos/queries do domínio, sem consultar regras particulares de cada módulo;
- adapters legados possuem contador e data de remoção.

## Próxima decisão

Promover primeiro um batch de inventário e ADR, junto da caracterização do BL-014. Não implementar sitemap dentro do módulo legado antes de fechar o contrato de publicação.
