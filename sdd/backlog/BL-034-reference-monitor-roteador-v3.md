# BL-034 — Reference monitor e roteador de segurança da v3

- **Tipo:** Architecture/Security/Maintainability
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** evolução do `gestor.php` como único ponto de entrada mediado

## Regra de governança

Este backlog define uma direção arquitetural, não uma implementação. Classes, namespaces, biblioteca de roteamento e formato do manifesto exigem ADR e requisito próprios antes de alteração do core.

## Decisão proposta

A ideia existente deve ser preservada: toda requisição web passa por um front controller, e nenhum módulo é incluído antes de autenticação e autorização.

A mudança recomendada é centralizar o **contrato e a decisão**, não acumular toda a implementação em um arquivo procedural. O `gestor.php` v3 deve ser um composition root/orquestrador curto que monta e executa uma cadeia de componentes C2F pequenos e testáveis.

Isso transforma o roteador em um *reference monitor* com três propriedades verificáveis:

1. **mediação completa:** nenhum caminho alternativo chega a código privilegiado;
2. **fail closed:** rota, método, identidade ou política ausente resultam em negação;
3. **simplicidade testável:** o núcleo de decisão é pequeno o bastante para auditoria e testes exaustivos.

## Pontos sólidos do desenho atual

- o módulo é resolvido a partir da página registrada, não diretamente de um caminho PHP fornecido pelo cliente;
- `gestor_permissao()` roda antes do `require_once` do módulo em páginas protegidas;
- autenticação e autorização de módulo são decisões distintas;
- o menu também deriva das permissões, melhorando UX sem substituir o controle backend;
- rotas API e gateways entram pelo mesmo bootstrap público;
- o instalador foi desenhado para deixar apenas `index.php`/`.htaccess` no diretório público e o Gestor em outro caminho físico;
- a req-107 adicionou headers, CSRF e contenção de caminhos no bootstrap.

## Limitações arquiteturais atuais

### Um arquivo central não garante mediação completa

API, gateways, hooks, cron, scripts de migração, instalador, plugins e módulos distribuídos usam mecanismos de confiança diferentes. Forçar todos a uma única autenticação de usuário seria incorreto; o que deve ser único é o registro de rotas e a obrigação de cada rota declarar sua política.

### Política está misturada com conteúdo localizado

`paginas.sem_permissao`, módulo e opção estão replicados por idioma. Hoje não foram encontradas divergências de público/protegido entre idiomas no seed, mas o modelo permite que tradução e segurança se desencontrem. A rota deve possuir identidade e política únicas; idioma seleciona apenas apresentação/recurso.

### Inclusões dinâmicas precisam de contenção

Módulos e hooks montam caminhos a partir de metadados JSON/banco. Mesmo sendo código confiável, a v3 deve resolver `realpath`, conferir contenção na raiz autorizada e aceitar somente arquivos declarados em manifesto validado.

### Plugin não é sandbox

Um PHP incluído roda no mesmo processo, com as mesmas credenciais de banco e acesso ao filesystem do core. O gate do roteador protege o acesso ao plugin, mas não limita o que um plugin autorizado consegue fazer. Plugins instaláveis devem ser tratados como código privilegiado; isolamento real de código não confiável exigiria processo/container separado.

## Pipeline proposto

```text
FrontController
  -> TrustedProxy/CanonicalHost/HTTPS
  -> RequestFactory + CorrelationId
  -> RouteRegistry (método + caminho + versão)
  -> InputEnvelope (limites, content type, schema)
  -> Authenticator selecionado pela rota
  -> Session/CSRF guard quando aplicável
  -> AuthorizationPolicy (ação + recurso + contexto)
  -> RateLimit/StepUp guard quando aplicável
  -> HandlerResolver contido em raiz autorizada
  -> Handler/UseCase
  -> ResponseFactory + headers
  -> SecurityAudit
```

O pipeline deve retornar antes do handler em qualquer falha. Hooks de negócio executados pelo handler não podem alterar retroativamente identidade, rota ou decisão de autorização.

## Componentes C2F sugeridos

Nomes finais dependem do padrão técnico em inglês do BL-023.

- `C2F\Http\RequestContext`: método, rota, origem, IP confiável, correlation ID e tipo de cliente;
- `C2F\Routing\RouteRegistry`: tabela imutável de rotas compilada/cacheada;
- `C2F\Security\Authenticator`: contrato para cookie, OAuth, HMAC/webhook e CLI;
- `C2F\Security\Principal`: identidade normalizada sem depender de globais;
- `C2F\Security\CsrfGuard`: aplicado apenas ao mecanismo/rota corretos;
- `C2F\Security\PolicyEngine`: decide `allow/deny` com motivo seguro;
- `C2F\Security\SecurityAudit`: eventos estruturados de autenticação/autorização;
- `C2F\Routing\HandlerResolver`: allowlist e contenção física;
- `C2F\Http\ResponseFactory`: status, JSON/HTML e headers consistentes.

`gestor.php` deve apenas construir o container mínimo, executar o pipeline e traduzir exceções conhecidas em respostas seguras.

## Manifesto declarativo de rota

Cada rota deve declarar, no mínimo:

| Campo | Exemplo | Regra |
| --- | --- | --- |
| `id` | `admin.pages.delete` | estável, não localizado |
| `methods` | `DELETE`, `POST` | lista fechada |
| `path` | `/admin-pages/{id}` | parâmetros tipados |
| `handler` | serviço/ação registrado | sem caminho livre vindo da request |
| `auth` | `session`, `oauth`, `hmac`, `public`, `cli` | exatamente um contrato |
| `capability` | `pages.delete` | obrigatória quando não pública |
| `resource` | `page:{id}` | resolver antes da autorização fina |
| `csrf` | `required`, `not-applicable` | não inferir de forma ambígua |
| `content_types` | `application/json` | allowlist |
| `rate_limit` | política nomeada | inclusive login/export/deploy |
| `step_up` | `admin-recent-auth` | ações críticas |
| `audit` | evento e severidade | sem segredos |

Rotas públicas exigem justificativa explícita. A ausência de manifesto ou campo obrigatório deve negar a rota durante compilação/boot, não apenas em runtime.

## Fronteiras de entrada

### Web administrativo

Cookie de sessão, CSRF, RBAC/capability e autorização do recurso.

### API OAuth

Bearer, issuer/audience/client/scope, autorização por endpoint e recurso; sem cookie/CSRF.

### Webhooks/gateways

Assinatura do provedor, timestamp/anti-replay, método e content type estritos. Não são “públicos sem segurança”; usam outro autenticador.

### Módulos distribuídos

HMAC/mTLS ou credencial de workload, nonce/timestamp e capacidade mínima. A assinatura autentica o par, mas a ação ainda precisa ser autorizada.

### Cron/CLI/migrations

Bloqueio explícito quando `PHP_SAPI` não for CLI, usuário de SO dedicado, lock, credencial de banco específica e allowlist de comandos. Não deve existir URL equivalente por acidente.

## Estratégia de migração

1. gerar inventário das rotas atuais sem mudar comportamento;
2. caracterizar cada rota com método, auth, módulo, opção e efeitos;
3. introduzir registro compatível que delega ao roteador atual;
4. bloquear primeiro rotas inexistentes/métodos inesperados;
5. migrar um módulo piloto e uma rota de cada tipo (HTML, AJAX, API, webhook, CLI);
6. tornar o manifesto obrigatório para novo código;
7. migrar por ondas e reduzir `gestor.php` progressivamente;
8. remover o dispatcher procedural somente após paridade e testes de bypass.

## Critérios de aceite

- todo endpoint executável aparece no inventário e declara autenticador/política;
- nenhuma rota localizada carrega sua própria decisão de segurança;
- PHP privado fica fora do DocumentRoot e acesso direto recebe `403/404`;
- resolução de handlers/hooks permanece dentro da raiz allowlisted;
- rota ou método desconhecido falha fechado;
- `gestor.php` não contém regras de negócio de módulos;
- API, webhook, sessão e CLI compartilham o pipeline, mas não fingem usar a mesma credencial;
- testes negativos provam que o módulo não é incluído quando qualquer gate falha.

## Próxima ação

Promover um spike de inventário/ameaças e uma ADR do manifesto/pipeline antes de escolher ou construir a biblioteca de roteamento.
