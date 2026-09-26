---
title: "Biblioteca usuario.php"
label: "Usuário e sessões"
description: "O cookie de login, as sessões ativas do usuário, a autorização provisória para ações sensíveis, os tokens pessoais de API (c2f_pat_) e os códigos de recuperação do 2FA."
section: reference
order: 55
sources:
  - gestor/bibliotecas/usuario.php
  - gestor/gestor.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
verified_at: 3dcf5df7
---

# Biblioteca `usuario.php`

Tudo o que acontece **depois** de a senha ser aceita: emitir o cookie de login, listar e encerrar sessões, pedir a senha de novo antes de ações sensíveis, emitir tokens pessoais para a API e guardar os códigos de recuperação do [2FA](2fa.md). Quem a usa é o `perfil-usuario` e a API.

## O cookie de login

`usuario_gerar_token_autorizacao(['id_usuarios' => …, 'sessao' => true])`:

1. sorteia um `pubID` (64 hex) e grava em `usuarios_tokens` com o HMAC `USUARIO_HASH_PASSWORD`, a validade, o IP e o User-Agent;
2. monta o token no formato RSA do Gestor (`usuario_gerar_jwt()`, veja [autenticacao.php](autenticacao.md)) e o põe no cookie `COOKIE_AUTHNAME`: `HttpOnly`, `SameSite=Lax`, `Secure` fora do ambiente de desenvolvimento em HTTP;
3. grava o cookie `COOKIE_AUTHPROFILE` com o SHA-256 do id do perfil (usado só para apresentação; não é assinado).

Com `sessao`, o cookie morre ao fechar o navegador; sem, dura `COOKIE_LIFETIME` (15 dias). A validação a cada requisição é do roteador (`gestor_permissao_token()`, [ciclo de uma requisição](../../concepts/request-lifecycle.md)).

> [!NOTE]
> Aqui a "assinatura" é cifrada com a chave **pública** e conferida com a privada (o inverso dos tokens da API). O que impede um token forjado não é a assinatura, e sim o `pubID` aleatório, que precisa existir em `usuarios_tokens` com o HMAC certo.

`usuario_app_gerar_token_autorizacao()` (devolve o token em vez de gravar o cookie) e `usuario_openssl_gerar_chaves()` não têm chamadores.

## Sessões ativas

| Função | |
|---|---|
| `usuario_sessoes_listar($id_usuario, $pubIdAtual = null)` | As sessões (linhas de `usuarios_tokens`) já formatadas por `usuario_sessao_formatar()`: navegador e sistema (`usuario_user_agent_analisar()`, vazios quando não reconhecidos), IP, datas e qual é a sessão atual |
| `usuario_sessao_revogar($pubID, $id_usuario)` | Encerra uma sessão; o `id_usuarios` entra no WHERE para um id adivinhado não derrubar a sessão de outra conta |
| `usuario_sessoes_revogar_outras($pubIdAtual, $id_usuario)` | Encerra todas, menos a atual (exige o `pubID` atual) |

## Autorização provisória

Antes de ações sensíveis (trocar e-mail, senha, 2FA), o `perfil-usuario` pede a senha de novo. `usuario_autorizacao_provisoria()` controla essa janela, guardada na sessão com validade de `USUARIO_AUTORIZACAO_LIFETIME` (300 s):
- `['validar' => true]` abre a janela; `['invalidar' => true]` fecha; `['verificar' => true]` diz se está aberta;
- `['verificarModal' => ['cancelarUrl', 'confirmarUrl', 'autorizadoUrl', 'autorizadoUrlQuerystring']]` mostra o modal de confirmação quando ela não está aberta.

## Tokens pessoais de API

Tokens `c2f_pat_<64 hex>` (constante `USUARIO_API_TOKEN_PREFIXO`), aceitos no mesmo `Authorization: Bearer` dos tokens [OAuth2](oauth2.md):
- `usuario_api_token_gerar($id_usuario, $nome, $escopos = [], $dias_expiracao = null)` devolve o token em texto **uma única vez**; o banco (`usuarios_api_tokens`) guarda só o SHA-256 (`usuario_api_token_hash()`) e um prefixo exibível (`usuario_api_token_prefixo()`);
- `usuario_api_token_validar($token)` confere hash, status e validade (`usuario_api_token_situacao()` distingue revogado de expirado), registra o uso e devolve o mesmo formato de `oauth2_validar_token()`;
- `usuario_api_token_formato()` só diz se a string tem o formato, para a API escolher o validador;
- `usuario_api_token_revogar($id_token, $id_usuario)`, `usuario_api_tokens_listar($id_usuario)`;
- `usuario_api_tokens_disponivel()`: a tabela existe (instalação migrada).

> [!NOTE]
> Os `escopos` são gravados, mas, como no OAuth2, nenhum endpoint os confere.

## Códigos de recuperação do 2FA

`usuario_recovery_codes_gerar($quantidade = 10)` sorteia os códigos (sem `0`, `O`, `1`, `I` e `L`, para serem copiados à mão); o banco guarda só os hashes (`usuario_recovery_code_hash()` sobre a forma normalizada por `usuario_recovery_code_normalizar()`, que ignora hífen, espaço e caixa). `usuario_recovery_code_consumir($codigo, $hashes)` devolve se o código vale e a lista **sem ele** (uso único). `usuario_recovery_codes_disponivel()` informa se a coluna existe.

## Legado

`usuario_host_dados()` devolve dados do usuário de host (modo multi-host, veja [host.php](host.md)) ou de um anônimo. Não tem chamadores.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/usuario.php` por `c2f docs:extract` — 25 funções. Não edite dentro deste bloco.

- `usuario_openssl_gerar_chaves(array|false $params = false): array|false` — [linha 37](../../../../../gestor/bibliotecas/usuario.php#L37)
  Gera par de chaves pública/privada OpenSSL.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['tipo']`: Tipo de chave: 'RSA' (obrigatório).
  - `$params['senha']`: Senha para proteger chave privada (opcional).
  Retorno: Array com 'publica' e 'privada' ou false.
- `usuario_gerar_jwt(array|false $params = false): string|false` — [linha 94](../../../../../gestor/bibliotecas/usuario.php#L94)
  Gera token JWT assinado com RSA.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['host']`: Host emissor do token (obrigatório).
  - `$params['expiration']`: Unix timestamp de expiração (obrigatório).
  - `$params['pubID']`: ID público do token (obrigatório).
  - `$params['chavePublica']`: Chave pública RSA para assinar (obrigatório).
  Retorno: Token JWT ou false se inválido.
- `usuario_gerar_token_autorizacao(array|false $params = false): bool` — [linha 166](../../../../../gestor/bibliotecas/usuario.php#L166)
  Gera token de autorização para autenticação de usuários web.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['id_usuarios']`: ID do usuário (obrigatório).
  - `$params['sessao']`: Se true, cria cookie de sessão que expira ao fechar navegador (opcional).
  Retorno: True se token criado com sucesso, false caso contrário.
- `usuario_app_gerar_token_autorizacao(array|false $params = false): array|false` — [linha 284](../../../../../gestor/bibliotecas/usuario.php#L284)
  Gera token de autorização para aplicativos mobile.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['id_usuarios']`: ID do usuário (obrigatório).
  Retorno: Array com 'token' e 'expiration', ou false se inválido.
- `usuario_autorizacao_provisoria(array|false $params = false): bool|void` — [linha 373](../../../../../gestor/bibliotecas/usuario.php#L373)
  Gerencia autorizações provisórias de usuários via sessão.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['verificar']`: Retorna true/false se autorização existe e é válida (opcional).
  - `$params['validar']`: Cria nova autorização provisória (opcional).
  - `$params['invalidar']`: Remove autorização provisória (opcional).
  - `$params['verificarModal']`: ['autorizadoUrlQuerystring'] Query string adicional (opcional).
  Retorno: Retorna bool se 'verificar' está definido, void caso contrário.
- `usuario_user_agent_analisar(string $userAgent): array{navegador:string,sistema:string,dispositivo:string}` — [linha 499](../../../../../gestor/bibliotecas/usuario.php#L499)
  Identifica navegador, sistema operacional e tipo de dispositivo a partir do User-Agent.
  Parâmetros:
  - `$userAgent`: Cabeçalho `User-Agent` bruto (pode vir vazio).
  Retorno: Nomes crus, sem tradução.
- `usuario_sessao_formatar(array $registro, string|null $tokenAtual = null): array` — [linha 583](../../../../../gestor/bibliotecas/usuario.php#L583)
  Normaliza uma linha de `usuarios_tokens` para exibição no painel de sessões.
  Parâmetros:
  - `$registro`: Linha de `usuarios_tokens` (`pubID`, `ip`, `user_agent`, `expiration`,
  - `$tokenAtual`: `pubID` do token da requisição corrente.
  Retorno: Sessão normalizada, com `atual`, `navegador`, `sistema`, `dispositivo` e `sessao`.
- `usuario_sessoes_listar(int $id_usuario, string|null $token_atual_pubID = null): array` — [linha 619](../../../../../gestor/bibliotecas/usuario.php#L619)
  Lista as sessões ativas de um usuário, já normalizadas para exibição.
  Parâmetros:
  - `$id_usuario`: ID do usuário dono das sessões.
  - `$token_atual_pubID`: `pubID` do token da requisição corrente (opcional).
  Retorno: Lista de sessões (mais recentes primeiro); vazia quando não há usuário válido.
- `usuario_sessao_revogar(string $pubID, int $id_usuario): bool` — [linha 660](../../../../../gestor/bibliotecas/usuario.php#L660)
  Revoga uma sessão específica do usuário informado.
  Parâmetros:
  - `$pubID`: Identificador público do token a remover.
  - `$id_usuario`: ID do usuário dono do token.
  Retorno: True quando o comando foi emitido; false quando os parâmetros são inválidos.
- `usuario_sessoes_revogar_outras(string $token_atual_pubID, int $id_usuario): bool` — [linha 686](../../../../../gestor/bibliotecas/usuario.php#L686)
  Encerra todas as sessões do usuário, exceto a da requisição corrente.
  Parâmetros:
  - `$token_atual_pubID`: `pubID` do token que deve ser preservado.
  - `$id_usuario`: ID do usuário dono dos tokens.
  Retorno: True quando o comando foi emitido; false quando os parâmetros são inválidos.
- `usuario_api_tokens_disponivel(): bool` — [linha 715](../../../../../gestor/bibliotecas/usuario.php#L715)
  Diz se o schema já suporta Personal Access Tokens (req-119 / BATCH-122).
- `usuario_recovery_codes_disponivel(): bool` — [linha 731](../../../../../gestor/bibliotecas/usuario.php#L731)
  Diz se o schema já suporta códigos de recuperação de 2FA (req-119 / BATCH-122).
- `usuario_api_token_formato(string $token): bool` — [linha 750](../../../../../gestor/bibliotecas/usuario.php#L750)
  Diz se uma string TEM O FORMATO de um Personal Access Token.
  Parâmetros:
  - `$token`: Valor bruto recebido no cabeçalho.
- `usuario_api_token_hash(string $token): string` — [linha 770](../../../../../gestor/bibliotecas/usuario.php#L770)
  Calcula o hash de armazenamento de um Personal Access Token.
  Parâmetros:
  - `$token`: Token em texto puro.
  Retorno: Hash hexadecimal de 64 caracteres.
- `usuario_api_token_prefixo(string $token): string` — [linha 784](../../../../../gestor/bibliotecas/usuario.php#L784)
  Extrai o prefixo exibível de um token.
  Parâmetros:
  - `$token`: Token em texto puro.
  Retorno: Prefixo do sistema mais os 8 primeiros caracteres da parte aleatória.
- `usuario_api_token_situacao(array $registro, int|null $agora = null): string` — [linha 804](../../../../../gestor/bibliotecas/usuario.php#L804)
  Decide se um token registrado está utilizável AGORA.
  Parâmetros:
  - `$registro`: Linha de `usuarios_api_tokens` (`status`, `expiracao`).
  - `$agora`: Timestamp de referência; omitido usa `time()`.
  Retorno: `ativo`, `revogado` ou `expirado`.
- `usuario_api_token_gerar(int $id_usuario, string $nome, array $escopos = Array(), int|null $dias_expiracao = null): array|false` — [linha 838](../../../../../gestor/bibliotecas/usuario.php#L838)
  Gera um Personal Access Token para o usuário.
  Parâmetros:
  - `$id_usuario`: Dono do token.
  - `$nome`: Identificador amigável.
  - `$escopos`: Permissões autorizadas.
  - `$dias_expiracao`: Validade em dias; `null` para token sem expiração.
  Retorno: `['token' => …, 'prefixo' => …, 'expiracao' => …]` ou false se inválido.
- `usuario_api_token_validar(string $token_puro): array|false` — [linha 892](../../../../../gestor/bibliotecas/usuario.php#L892)
  Valida um Personal Access Token e registra o uso.
  Parâmetros:
  - `$token_puro`: Token recebido no cabeçalho.
  Retorno: Dados do usuário autenticado ou false.
- `usuario_api_token_revogar(int $id_token, int $id_usuario): bool` — [linha 956](../../../../../gestor/bibliotecas/usuario.php#L956)
  Revoga um Personal Access Token do usuário informado.
  Parâmetros:
  - `$id_token`: Identificador do token.
  - `$id_usuario`: Dono do token.
  Retorno: True quando o comando foi emitido.
- `usuario_api_tokens_listar(int $id_usuario): array` — [linha 982](../../../../../gestor/bibliotecas/usuario.php#L982)
  Lista os tokens de um usuário, já anotados com a situação corrente.
  Parâmetros:
  - `$id_usuario`: Dono dos tokens.
  Retorno: Lista de tokens (mais recentes primeiro).
- `usuario_recovery_codes_gerar(int $quantidade = 10): array` — [linha 1034](../../../../../gestor/bibliotecas/usuario.php#L1034)
  Gera códigos de recuperação em texto puro.
  Parâmetros:
  - `$quantidade`: Quantos códigos gerar.
  Retorno: Lista de códigos no formato `XXXX-XXXX`.
- `usuario_recovery_code_normalizar(string $codigo): string` — [linha 1063](../../../../../gestor/bibliotecas/usuario.php#L1063)
  Normaliza um código de recuperação para comparação.
  Parâmetros:
  - `$codigo`: Código digitado.
  Retorno: Código só com os caracteres significativos, em caixa alta.
- `usuario_recovery_code_hash(string $codigo): string` — [linha 1077](../../../../../gestor/bibliotecas/usuario.php#L1077)
  Calcula o hash de armazenamento de um código de recuperação.
  Parâmetros:
  - `$codigo`: Código em texto puro.
  Retorno: Hash hexadecimal.
- `usuario_recovery_code_consumir(string $codigo, array $hashes): array{valido:bool,restantes:array}` — [linha 1092](../../../../../gestor/bibliotecas/usuario.php#L1092)
  Consome um código de recuperação de uma lista de hashes.
  Parâmetros:
  - `$codigo`: Código digitado pelo usuário.
  - `$hashes`: Lista de hashes ainda válidos.
- `usuario_host_dados(array|false $params = false): mixed|array` — [linha 1131](../../../../../gestor/bibliotecas/usuario.php#L1131)
  Obtém dados do usuário do host atual.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['campo']`: Nome do campo específico a retornar (opcional, retorna todos se omitido).
  - `$params['id_hosts_usuarios']`: ID do usuário a buscar (opcional, usa usuário atual se omitido).
  Retorno: Valor do campo ou array com todos os dados do usuário.

<!-- c2f:extract:end -->
