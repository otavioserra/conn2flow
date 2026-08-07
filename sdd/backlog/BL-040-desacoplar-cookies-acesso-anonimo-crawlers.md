# BL-040 — Desacoplar verificação de cookies, autenticação anônima e crawlers

- **Tipo:** Corrective Maintenance/Architecture/SEO/Security/UX
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** fluxo de cookies/autenticação em `gestor/gestor.php`, linha 2.9.x e arquitetura v3
- **Relacionados:** BL-033, BL-034, BL-036, BL-037, BL-041, BL-042, BL-043

## Conclusão executiva

A verificação de capacidade de cookies está sendo executada cedo demais e possui efeito colateral de redirect. Qualquer cliente anônimo que alcance uma rota protegida pode ser enviado para `_gestor-cookie-verify` e, se não persistir o cookie, para `cookies-is-mandatory/`. Isso inclui crawlers, monitores, clientes HTTP e pessoas que apenas abriram uma URL sem tentar autenticar.

A correção não deve reconhecer “robôs” por `User-Agent` nem liberar a eles páginas privadas. User-Agent é falsificável e servir conteúdo protegido somente ao crawler criaria vazamento/cloaking. O comportamento correto é:

- página pública: renderizar normalmente sem exigir ou testar cookie;
- página protegida acessada anonimamente: responder como recurso autenticado, sem revelar conteúdo;
- tentativa interativa de login sem cookies: somente então explicar que cookies são obrigatórios para manter a sessão;
- API/AJAX: resposta `401 AUTH_REQUIRED` estável, nunca uma página HTML/desafio de cookies.

## Evidências no código atual

### 1. Verificação antes de saber se há autenticação

`gestor_permissao_token_processar()` chama `gestor_cookie_verificacao()` antes de verificar se existe `cookie-authname`. Para um anônimo sem cookies, o fluxo não retorna simplesmente `false`: cria um cookie de teste e encerra a requisição com redirect.

### 2. Leitura de perfil também redireciona

`gestor_usuario_perfil()` chama a mesma função antes de apenas ler `cookie-authprofile`. Uma operação que deveria ser consulta sem efeito colateral pode mudar a resposta HTTP. Isso pode afetar recursos opcionais de páginas públicas, como a decisão de exibir a toolbar.

### 3. Desafio global

`gestor_cookie_verificacao()`:

1. gera um identificador CSPRNG;
2. envia `Set-Cookie`;
3. redireciona a URL original para `/_gestor-cookie-verify/{id}/`;
4. o endpoint manda clientes que não devolveram o cookie para uma única página `cookies-is-mandatory/`.

O resultado colapsa URLs protegidas diferentes numa página genérica, cria redirects desnecessários e impede que o roteador devolva a semântica normal de anônimo, login, `401` ou `404`.

### 4. Modelo de visibilidade

O roteador considera `sem_permissao` como indicador de acesso público. Quando esse valor não existe, chama `gestor_permissao()`. O nome negativo é ambíguo e segurança/visibilidade estão misturadas aos dados localizados da página.

## Impactos

- crawlers recebem redirect para conteúdo genérico em vez de status coerente;
- URLs diferentes podem parecer duplicadas ou erros suaves para mecanismos de busca;
- crawl budget e relatórios de indexação/monitoramento ficam poluídos;
- páginas públicas que apenas consultem estado opcional podem emitir cookie/redirect sem necessidade;
- humanos sem cookies veem a mensagem antes mesmo de uma tentativa de login;
- o acoplamento torna perigoso alterar autenticação, toolbar ou sessão;
- bypass por suposto bot vazaria conteúdo e não é uma alternativa aceitável.

## Correção proposta para 2.9.x

### S29-COOKIE-A — Tornar leitura de autenticação livre de redirects

1. `gestor_permissao_token_processar()` verifica primeiro a existência do cookie de autenticação; ausente significa usuário anônimo e retorno `false`.
2. `gestor_usuario_perfil()` apenas retorna o perfil existente ou `false`, sem testar cookies.
3. nenhuma página pública recebe `Set-Cookie` somente para descobrir se o navegador aceita cookies.
4. manter a memoização atual para não validar/renovar autenticação mais de uma vez por request.

### S29-COOKIE-B — Mover o teste para o lifecycle de login

1. a página de login pode emitir o cookie de probe sem redirecionar a navegação inicial;
2. o submit/login ou um passo explícito de retorno confirma se o probe voltou;
3. se o navegador não aceitar cookie, devolver a mensagem de cookies obrigatórios dentro do fluxo de login;
4. após autenticação, confirmar que o cookie de sessão foi persistido antes de entrar numa rota protegida;
5. impedir loops e limitar qualquer marcador/return target a caminho local validado e uso único.

O endpoint `_gestor-cookie-verify` pode ser mantido temporariamente como adapter do login, mas deixa de ser acionado por toda consulta anônima e deve ser removido quando o novo fluxo estiver comprovado.

### S29-COOKIE-C — Semântica HTTP e SEO

- páginas protegidas nunca entregam conteúdo privado a crawler anônimo;
- request HTML anônimo recebe `401` com UI de login no próprio fluxo ou redirect temporário controlado para `signin`; a decisão final deve ser caracterizada contra a UX atual;
- AJAX/API recebe `401` JSON e código `AUTH_REQUIRED` sem redirect HTML;
- login, cookies obrigatórios e respostas de acesso protegido recebem `X-Robots-Tag: noindex, nofollow, noarchive` quando aplicável;
- sitemap inclui somente URLs públicas indexáveis;
- `robots.txt` pode reduzir crawling administrativo, mas não substitui autenticação;
- `GET` e `HEAD` preservam semântica e não criam sessão auxiliar apenas para renderizar página pública;
- páginas inexistentes continuam `404`, sem serem transformadas em login/cookies.

### S29-COOKIE-D — Corrigir classificação, não criar exceção para bot

Se uma página destinada a marketing/SEO estiver marcada como protegida, corrigir sua classificação ou criar uma apresentação pública sanitizada. Não conceder acesso por `User-Agent`, IP presumido de bot ou ausência de cookie.

## Arquitetura definitiva da v3

O manifesto de rotas do BL-034 deve usar uma classificação positiva e explícita:

```text
public          conteúdo acessível sem sessão
guest-only      login/cadastro; não exige usuário autenticado
authenticated   exige identidade válida
capability      exige identidade + capacidade/policy de recurso
```

- middleware de sessão tenta autenticar sem efeitos colaterais e produz `AnonymousPrincipal` quando não houver credencial;
- `CookieCapabilityGuard` pertence somente ao caso de uso de login/sessão, não ao roteador público;
- controllers escolhem resposta HTML/JSON a partir do contrato da rota, não pelo User-Agent;
- cookie de idioma/consentimento e cookie de autenticação têm lifecycles distintos;
- páginas públicas devem ser cacheáveis quando o restante do contrato permitir;
- observabilidade separa `anonymous`, `auth_required`, `cookie_disabled`, `invalid_session` e `forbidden`.

## Matriz mínima de testes

| Cenário | Resultado esperado |
| --- | --- |
| navegador novo em página pública | `200`, mesma URL, sem desafio de cookies |
| crawler sem cookies em página pública | mesmo conteúdo/status essencial do navegador anônimo |
| anônimo em página protegida | `401` ou redirect de login aprovado; nunca conteúdo privado/cookie probe global |
| crawler em página protegida | nenhuma exceção; conteúdo privado não é entregue |
| pessoa com cookies desativados abre login | login renderiza; mensagem aparece ao tentar estabelecer sessão |
| pessoa com cookies desativados tenta login | erro claro, sem loop |
| sessão válida | fluxo atual preservado, inclusive renovação controlada |
| cookie inválido/expirado | `401 AUTH_REQUIRED`, não “cookies desativados” |
| AJAX sem sessão | JSON `401`, sem HTML/redirect em loop |
| URL inexistente | `404` real |
| `HEAD` público/protegido | mesmos status de acesso do `GET`, sem mutação |

Os testes devem incluir Googlebot apenas como cliente sem cookie; alterar o User-Agent para `Googlebot` não pode mudar autorização nem revelar conteúdo.

## Critérios de aceite

- nenhum acesso público depende de aceitar cookies de autenticação;
- ausência de cookie representa anonimato, não erro de navegador;
- aviso de cookies ocorre somente quando a pessoa tenta criar/usar sessão web;
- rotas protegidas mantêm confidencialidade para qualquer cliente;
- não há detecção/bypass por User-Agent;
- redirects de autenticação não formam ciclos e preservam somente retorno local seguro;
- respostas protegidas/login não são indexadas e sitemaps não anunciam URLs privadas;
- regressões cobrem HTML, AJAX, toolbar, login, sessão expirada e idiomas;
- correção 2.9.x é incorporada depois à v3, onde o comportamento passa ao pipeline/manifesto.

## Próxima ação

Promover uma requisição corretiva própria para a 2.9.x. O primeiro batch deve apenas caracterizar a matriz HTTP e tornar as leituras de token/perfil livres de redirect; o segundo move o probe para o login e fecha SEO/headers. A arquitetura v3 reutiliza os testes, mas não bloqueia a correção imediata.
