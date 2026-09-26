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
- `usuario_gerar_jwt(array|false $params = false): string|false` — [linha 94](../../../../../gestor/bibliotecas/usuario.php#L94)
- `usuario_gerar_token_autorizacao(array|false $params = false): bool` — [linha 166](../../../../../gestor/bibliotecas/usuario.php#L166)
- `usuario_app_gerar_token_autorizacao(array|false $params = false): array|false` — [linha 284](../../../../../gestor/bibliotecas/usuario.php#L284)
- `usuario_autorizacao_provisoria(array|false $params = false): bool|void` — [linha 373](../../../../../gestor/bibliotecas/usuario.php#L373)
- `usuario_user_agent_analisar(string $userAgent): array{navegador:string,sistema:string,dispositivo:string}` — [linha 499](../../../../../gestor/bibliotecas/usuario.php#L499)
- `usuario_sessao_formatar(array $registro, string|null $tokenAtual = null): array` — [linha 583](../../../../../gestor/bibliotecas/usuario.php#L583)
- `usuario_sessoes_listar(int $id_usuario, string|null $token_atual_pubID = null): array` — [linha 619](../../../../../gestor/bibliotecas/usuario.php#L619)
- `usuario_sessao_revogar(string $pubID, int $id_usuario): bool` — [linha 660](../../../../../gestor/bibliotecas/usuario.php#L660)
- `usuario_sessoes_revogar_outras(string $token_atual_pubID, int $id_usuario): bool` — [linha 686](../../../../../gestor/bibliotecas/usuario.php#L686)
- `usuario_api_tokens_disponivel(): bool` — [linha 715](../../../../../gestor/bibliotecas/usuario.php#L715)
- `usuario_recovery_codes_disponivel(): bool` — [linha 731](../../../../../gestor/bibliotecas/usuario.php#L731)
- `usuario_api_token_formato(string $token): bool` — [linha 750](../../../../../gestor/bibliotecas/usuario.php#L750)
- `usuario_api_token_hash(string $token): string` — [linha 770](../../../../../gestor/bibliotecas/usuario.php#L770)
- `usuario_api_token_prefixo(string $token): string` — [linha 784](../../../../../gestor/bibliotecas/usuario.php#L784)
- `usuario_api_token_situacao(array $registro, int|null $agora = null): string` — [linha 804](../../../../../gestor/bibliotecas/usuario.php#L804)
- `usuario_api_token_gerar(int $id_usuario, string $nome, array $escopos = Array(), int|null $dias_expiracao = null): array|false` — [linha 838](../../../../../gestor/bibliotecas/usuario.php#L838)
- `usuario_api_token_validar(string $token_puro): array|false` — [linha 892](../../../../../gestor/bibliotecas/usuario.php#L892)
- `usuario_api_token_revogar(int $id_token, int $id_usuario): bool` — [linha 956](../../../../../gestor/bibliotecas/usuario.php#L956)
- `usuario_api_tokens_listar(int $id_usuario): array` — [linha 982](../../../../../gestor/bibliotecas/usuario.php#L982)
- `usuario_recovery_codes_gerar(int $quantidade = 10): array` — [linha 1034](../../../../../gestor/bibliotecas/usuario.php#L1034)
- `usuario_recovery_code_normalizar(string $codigo): string` — [linha 1063](../../../../../gestor/bibliotecas/usuario.php#L1063)
- `usuario_recovery_code_hash(string $codigo): string` — [linha 1077](../../../../../gestor/bibliotecas/usuario.php#L1077)
- `usuario_recovery_code_consumir(string $codigo, array $hashes): array{valido:bool,restantes:array}` — [linha 1092](../../../../../gestor/bibliotecas/usuario.php#L1092)
- `usuario_host_dados(array|false $params = false): mixed|array` — [linha 1131](../../../../../gestor/bibliotecas/usuario.php#L1131)

<!-- c2f:extract:end -->
