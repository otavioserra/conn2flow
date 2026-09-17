# BATCH-168 — CSRF em XMLHttpRequest e Acesso Restrito ao Site (req-163)

- **Status**: implemented-pending-homologation
- **Intake**: [req-163.md](../human-requests/req-163.md)
- **Data**: 2026-09-15
- **Classificação**: implementação de batch (segurança / governança do núcleo)
- **Modo de autonomia**: supervisionado (sem commit, push ou deploy)

## Objetivo

1. Uploads com progresso via `XMLHttpRequest` cru deixam de voltar 403 com o usuário logado.
2. Instalações de desenvolvimento, homologação ou privadas podem ser trancadas por `.env`/tela,
   com restrição por perfil e `noindex, nofollow` obrigatório.

## Pilar 1 — `XMLHttpRequest` em `gestor/assets/global/global.js`

- Envelope de `XMLHttpRequest.prototype.open` (memoriza método/URL e zera o registro de cabeçalhos),
  `setRequestHeader` (registra o nome em minúsculas) e `send` (injeta `X-CSRF-Token` em
  `POST/PUT/PATCH/DELETE` de mesma origem quando ninguém o definiu). Guarda `__c2fCsrf`.
- **`$.ajax` também passa por este envelope** (o jqXHR delega ao XHR nativo). O prefilter do jQuery
  chama `setRequestHeader` e o registro impede duplicar o token — o caso "token manual" do teste cobre
  exatamente esse caminho.
- 401 com `X-Gestor-Auth-Redirect` no evento `load` reaproveita `redirecionarParaLogin()`, que já
  tem trava contra redirecionamento duplo (o `ajaxError` do jQuery dispara no mesmo XHR).
- Falha interna do envelope nunca impede o envio (mesma política do envelope de formulário).
- `global.min.js` regenerado por `assets:minify` (24,9 KB → 10,3 KB); a guarda `__c2fCsrf` e os
  envelopes do prototype estão presentes no derivado.

## Pilar 2 — Acesso Restrito ao Site

### Núcleo

- `config.php`: `site-restricted-access` (bool) e `site-restricted-profiles` a partir do `.env`.
- `bibliotecas/gestor.php` — quatro funções, três delas PURAS:
  `gestor_site_acesso_restrito_ativo()` (`$_CONFIG` → `$_ENV`/`getenv`),
  `gestor_site_acesso_restrito_perfis()` (só inteiros positivos, sem repetição),
  `gestor_site_acesso_restrito_rota_isenta()` e `gestor_site_acesso_restrito_perfil_autorizado()`.
- `gestor.php`:
  - `gestor_roteador_acesso_restrito()` chamado em `gestor_roteador()` **antes da consulta da
    página**: `sem_permissao` e `.ajax.public` não abrem brecha, e o visitante sem login não distingue
    página existente de inexistente.
  - Sem sessão → `redirecionar-local` na sessão + 401 → `signin/` (AJAX: JSON `AUTH_REQUIRED` +
    `X-Gestor-Auth-Redirect`, o mesmo contrato que o `global.js` já consome).
  - Perfil não autorizado → `gestor_roteador_acesso_restrito_negado()`: 403 em tela própria com
    botão `signout/` (AJAX: JSON 403). Textos em variáveis globais `site-restricted-denied-*` (pt-br/en).
  - `X-Robots-Tag: noindex, nofollow` emitido pelo porteiro em TODA resposta do site restrito
    (inclusive redirecionamento e recusa) e `<meta name="robots">` forçada em
    `gestor_pagina_extra_head_e_javascript()` — uma única tag quando a rota também é de sistema.

### Tela `admin-environment`

- Seção "Acesso Restrito ao Site" na aba "Configurações do Site", **antes** de "HTML entregue ao
  navegador" (pt-br). No `en` ela entra antes de "Bots and Crawlers", que é a segunda seção daquele
  template — o `en` não tem a seção de limpeza de HTML (drift pré-existente do req-132, não tratado aqui).
- Toggle, busca com debounce de 250 ms (`ajaxOpcao: 'buscar-perfis'`, perfis ativos do idioma, máx.
  10, busca por nome, slug ou id), badges com "x" e input oculto como lista autoritativa.
  Respostas fora de ordem são descartadas por número de sequência.
- Persistência de `SITE_RESTRICTED_ACCESS` (só `true`/`false`) e `SITE_RESTRICTED_PROFILES`
  (normalizado pela mesma função do núcleo) no `.env` via `admin_environment_env_write()`.
- Módulo `1.0.1` → `1.0.2`; páginas `admin-environment` en `1.24` → `1.25`, pt-br `1.26` → `1.27`.

## Decisões tomadas na implementação

1. **O perfil vem de `gestor_usuario()`, não de `gestor_usuario_perfil()`.** O intake citava a
   segunda, mas ela devolve o cookie `authprofile`, que não é assinado: o próprio visitante poderia
   escolher o perfil com que entra. `gestor_usuario()` resolve o registro do banco a partir do JWT já
   validado por `gestor_permissao_token()`. Um teste de contrato guarda a ausência da chamada insegura.
2. **Perfil recusado recebe 403, não redirecionamento.** Mandar um usuário já logado ao `signin/`
   faria o login devolvê-lo ao painel, que o recusaria de novo — laço. A saída oferecida é `signout/`.
3. **Trava contra autobloqueio no salvamento.** Ligar a restrição com lista que não inclui o perfil
   de quem salva tranca o operador para fora da própria tela, e a única saída passaria a ser editar o
   `.env` no servidor. O salvamento é recusado com `site-restricted-lockout`; lista vazia continua
   permitida (qualquer logado entra).
4. **Rotas isentas pelos caminhos reais.** O intake listava nomes em português
   (`/esqueci-minha-senha/`, `/redefinir-senha/`…); as rotas do `perfil-usuario` são `forgot-password/`,
   `redefine-password/`, `signin-2fa/`, `oauth-callback/`, `email-confirmation/` etc. As duas grafias
   estão na lista. Rotas de sistema (`cookies-is-mandatory/`, páginas de erro) entram via
   `gestor_pagina_rota_sistema()`.
5. **`_api/`, `_gateways/` e estáticos já saem em `gestor_config()`, antes do roteador.** A lista de
   isenção os repete como defesa em profundidade, não como mecanismo principal.

## Pilar 3 — Governança

- Skill `c2f-javascript-ajax` ganhou a tabela de cobertura automática de CSRF/401 do `global.js`,
  incluindo `XMLHttpRequest` e a orientação para uploads com progresso. Replicada nos 5 espelhos do
  repositório (`.codex`, `.claude`, `.cursor`, `.github`, `.gemini`), MD5 idêntico.
- **Pendente**: os espelhos em `conn2flow-ai-workspace` (raiz e kits de `templates/`) são outro
  repositório e não foram tocados.

## Validação executada

| Verificação | Resultado |
| --- | --- |
| `php -l` (gestor.php, bibliotecas/gestor.php, config.php, admin-environment.php) | 4/4 sem erro |
| `node --check` (global.js, admin-environment.js) | OK |
| `git diff --check` nos arquivos de autoria | OK |
| Vitest focado `global-csrf.test.js` | **17/17** (6 novos: POST, GET, cross-origin, token manual, guarda, 401) |
| PHPUnit focado `SiteAcessoRestritoReq163Test` | **16/16** (62 asserções) |
| `php cli/c2f.php assets:minify` | 2 gerados, 63 coerentes, 0 falhas |
| `php cli/c2f.php resources:sync --force` | 2.856 recursos, 237/237 Tailwind, "Nenhum problema detectado" |
| Vitest completo | **423/423** |
| PHPUnit completo (`--order-by=default`) | **1.174/1.174** (7.792 asserções, 4 skipped pré-existentes) |

### Ressalvas de ambiente

- **PHP do host (8.5.8 via WinGet) sem `pdo_sqlite` habilitado e sem `OPENSSL_CONF`**: a primeira
  rodada deu 16 erros (15 `could not find driver` + 1 geração de chave RSA). Sem alterar o `php.ini`
  do operador, a suíte foi rodada com `PHP_INI_SCAN_DIR` apontando para um ini temporário
  (`extension=pdo_sqlite`, `extension=sqlite3`) e `OPENSSL_CONF=<php>\extras\ssl\openssl.cnf`.
- **`resources:sync`**: a etapa final de publicação em `dist/` não roda sem `PUBLIC_PATH`; os recursos
  foram sincronizados e as URLs seguem resolvendo pelo `arquivo-estatico`.
- **`./c2f assets:minify` pelo wrapper Git Bash falha** (o `exec` do `npx` não resolve nesse
  ambiente); pelo PHP nativo (`php cli/c2f.php assets:minify`) funciona.

### Achado fora de escopo — acoplamento entre testes pré-existentes

Com a ordem do `phpunit.xml` (`executionOrder="depends,defects"`, que usa o cache de resultados e
durações), a suíte **alterna** entre verde e 2 falhas em `ForcarAtualizacaoTest`. Causa medida pelo
log de eventos: quando `ProjectIdentityPassthroughTest` roda antes, o `static $meta` de
`schemaMetadata()` (`atualizacoes-banco-de-dados.php:277`) fica congelado com o diretório temporário
dele. **Reproduzido idêntico sem o teste deste lote** (`--exclude-filter`: verde/falha/verde), portanto
não é regressão do req-163. No CI o cache nasce vazio e a ordem padrão passa. Correção sugerida para
intake próprio: dar a `schemaMetadata()` um meio de reset para testes, ou isolar as duas classes em
processo separado.

## Pendências de homologação (operador)

- Deploy `Update => Core` (página, variáveis e JS vêm do banco/derivados).
- Com `SITE_RESTRICTED_ACCESS=true`: página pública sem login → `/signin/`; `/signin/` sem laço;
  CSS/imagens 200; `_api/` e `_gateways/` sem cookie; perfil fora da lista → 403; `false` volta ao
  acesso público; `noindex, nofollow` na meta e no cabeçalho.
- Upload real com progresso via XHR em módulo logado sem 403.
- Não houve validação em navegador real nesta rodada.
