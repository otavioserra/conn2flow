---
title: "Biblioteca autenticacao.php"
label: "Autenticação"
description: "O token de login assinado com a chave RSA do site, a cifragem RSA de segredos, o controle de tentativas por IP (login, cadastro, recuperação de senha) e a validação de credenciais do canal distribuído."
section: reference
order: 50
sources:
  - gestor/bibliotecas/autenticacao.php
  - gestor/gestor.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
  - gestor/config.php
verified_at: 558004f2
---

# Biblioteca `autenticacao.php`

Quatro assuntos: o **token JWT com RSA** que o Gestor usa no cookie de login e na API, **cifrar e decifrar** valores com as chaves RSA do site, o **controle de tentativas por IP** e as **credenciais do canal distribuído**. As chaves ficam em `autenticacoes/<dominio>/chaves/gestor/` (`privada.key`, protegida por `OPENSSL_PASSWORD`, e `publica.key`), veja [variáveis globais](../../concepts/global-variables.md).

## O token RSA

Não é um JWT padrão (RS256): o cabeçalho diz `"alg": "RSA"`, e a "assinatura" é o próprio `cabeçalho.payload` **cifrado com a chave privada** (`openssl_private_encrypt`, em blocos de 245 bytes, cada bloco em base64 e o conjunto de novo em base64). Validar é decifrar com a chave pública e comparar com o `cabeçalho.payload` recebido.

- `autenticacao_gerar_jwt_chave_privada(['host', 'expiration', 'pubID', 'chavePrivada', 'chavePrivadaSenha', 'payload' => opcional])` gera o token. Sem `payload`, usa `{iss: host, exp: expiration, sub: pubID}`. Emite os tokens da [API](oauth2.md); o cookie de login usa o mesmo formato, gerado por `usuario_gerar_token_autorizacao()` (usuario.php) e validado por `gestor_permissao_validar_jwt()`.
- `autenticacao_validar_jwt_chave_publica(['token', 'chavePublica', 'retornarPayloadCompleto'])` confere a assinatura, exige `exp` e `sub` e recusa token vencido. Devolve o `sub` (o `pubID`), o payload inteiro ou `false`.

Validar o token é só o primeiro passo: `gestor_permissao_token()` ainda confere o `pubID` em `usuarios_tokens` com o HMAC de `USUARIO_HASH_PASSWORD` ([ciclo de uma requisição](../../concepts/request-lifecycle.md)).

As variantes invertidas `autenticacao_gerar_jwt_chave_publica()` e `autenticacao_validar_jwt_chave_privada()`, e `autenticacao_cliente_gerar_jwt()` / `autenticacao_cliente_gerar_token_validacao()` (do modo multi-host), **não têm chamadores**.

## Cifrar segredos

| Função | Cifra com | Decifra com |
|---|---|---|
| `autenticacao_encriptar_chave_privada()` / `autenticacao_decriptar_chave_publica()` | privada | pública |
| `autenticacao_encriptar_chave_publica()` / `autenticacao_decriptar_chave_privada()` | pública | privada |

O primeiro par é o usado para guardar chaves de API (servidores de [IA](ia.md)): esconde o valor de quem só vê o banco, mas quem tem o banco e a chave **pública** o recupera. Para sigilo de verdade, cifre com a pública e decifre com a privada.

`autenticacao_openssl_gerar_chaves()` gera um par RSA de 2048 bits; `autenticacao_gerar_senha()` e `autenticacao_crypto_rand_secure()` geram senhas e números aleatórios. As três não têm chamadores no core (o instalador tem o próprio gerador de chaves).

## Controle de tentativas por IP

A tabela `acessos` guarda, por **IP** (de [ip_get()](ip.md)) e **tipo** (`login`, `signup`, `forgot-password`), a contagem, o status e o bloqueio.

| Função | Quando chamar |
|---|---|
| `autenticacao_acesso_verificar(['tipo'])` | Antes de mostrar/processar o formulário: `permitido`, `status` (`livre`, `antispam`, `bloqueado`) e `tempo_bloqueio` |
| `autenticacao_acesso_falha(['tipo'])` | Senha errada |
| `autenticacao_acesso_confirmar(['tipo'])` | Login certo: zera a contagem |
| `autenticacao_acesso_cadastrar(['tipo', 'antispam'])` | Cada cadastro ou pedido de recuperação |

Limites do `.env`: no login, até `ACESSOS_MAXIMO_LOGINS_SIMPLES` (3) falhas o status é `livre`, até `ACESSOS_MAXIMO_FALHAS_LOGINS` (10) é `antispam`, e depois `bloqueado`. Nos cadastros, `ACESSOS_MAXIMO_CADASTROS_*` e `ACESSOS_MAXIMO_CADASTROS_SIMPLES_*` por tipo. Cada bloqueio dura `bloqueios × ACESSOS_TEMPO_BLOQUEIO_IP` segundos (cresce a cada reincidência), e o registro some depois de `ACESSOS_TEMPO_DESBLOQUEIO_IP` sem atividade. `autenticacao_acessos_limpeza()` roda a cada verificação e aplica essas duas regras.

No `perfil-usuario`, o status decide o CAPTCHA: com reCAPTCHA, ele só é exigido a partir de `antispam`; com Turnstile, sempre.

> [!NOTE]
> O controle é por IP, não por conta: tentativas distribuídas em muitos IPs contra o mesmo usuário não são freadas, e atrás de uma CDN todos os visitantes podem compartilhar o mesmo IP (veja [ip.php](ip.md)). O segundo fator não passa por aqui (req-181, item A5).

## Canal distribuído

Usadas pelo central da [arquitetura distribuída](modulo-distribuido.md):
- `autenticacao_distribuido_validar_credenciais($usuario, $senha)`: `password_verify` contra `usuarios`, exige status ativo;
- `autenticacao_distribuido_gerar_tokens($id_usuarios)`: tokens [OAuth2](oauth2.md) com escopo `distributed`;
- `autenticacao_distribuido_verificar_permissao_modulo()`: o perfil do usuário tem o módulo alvo;
- `autenticacao_distribuido_token_ativo()`: o access token do canal é válido.

A validação de credenciais do canal não passa pelo controle de tentativas.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/autenticacao.php` por `c2f docs:extract` — 22 funções. Não edite dentro deste bloco.

- `autenticacao_crypto_rand_secure(int $min, int $max): int` — [linha 33](../../../../../gestor/bibliotecas/autenticacao.php#L33)
  Gera um número aleatório criptograficamente seguro.
  Parâmetros:
  - `$min`: Valor mínimo (inclusivo).
  - `$max`: Valor máximo (inclusivo).
  Retorno: Número aleatório seguro entre $min e $max.
- `autenticacao_cliente_gerar_jwt(array|false $params = false): string|false` — [linha 69](../../../../../gestor/bibliotecas/autenticacao.php#L69)
  Gera um token JWT assinado com chave RSA para autenticação de cliente.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['host']`: Host de acesso do JWT (obrigatório).
  - `$params['expiration']`: Timestamp de expiração do JWT (obrigatório).
  - `$params['pubID']`: ID público do token para referência (obrigatório).
  - `$params['chavePublica']`: Chave pública RSA para assinar (obrigatório).
  Retorno: Token JWT completo ou false em caso de erro.
- `autenticacao_openssl_gerar_chaves(array|false $params = false): array|false` — [linha 145](../../../../../gestor/bibliotecas/autenticacao.php#L145)
  Gera par de chaves pública e privada OpenSSL com algoritmo RSA.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['tipo']`: Tipo da chave (RSA obrigatório).
  - `$params['senha']`: Senha para encriptar a chave privada (opcional).
  Retorno: Array com 'publica' e 'privada', ou false em erro.
- `autenticacao_gerar_senha(int $length = 32): string` — [linha 298](../../../../../gestor/bibliotecas/autenticacao.php#L298)
  Gera uma senha aleatória segura com caracteres variados.
  Parâmetros:
  - `$length`: Comprimento da senha (padrão: 32 caracteres).
  Retorno: Senha aleatória gerada.
- `autenticacao_gerar_jwt_chave_publica(array|false $params = false): string|false` — [linha 343](../../../../../gestor/bibliotecas/autenticacao.php#L343)
  Gera JWT assinado com chave pública RSA.
  Parâmetros:
  - `$params`: Parâmetros (host, expiration, pubID, chavePublica obrigatórios).
  Retorno: Token JWT ou false em erro.
- `autenticacao_gerar_jwt_chave_privada(array|false $params = false): string|false` — [linha 408](../../../../../gestor/bibliotecas/autenticacao.php#L408)
  Gera JWT assinado com chave privada RSA.
  Parâmetros:
  - `$params`: Parâmetros (host, expiration, pubID, chavePrivada, chavePrivadaSenha obrigatórios; payload opcional).
  Retorno: Token JWT ou false em erro.
- `autenticacao_validar_jwt_chave_publica(array|false $params = false): array|string|false` — [linha 480](../../../../../gestor/bibliotecas/autenticacao.php#L480)
  Valida JWT usando chave pública RSA.
  Parâmetros:
  - `$params`: Parâmetros (token, chavePublica obrigatórios; retornarPayloadCompleto opcional).
  - `$params['retornarPayloadCompleto']`: Se true, retorna o payload completo em vez de apenas o pubID.
  Retorno: Payload decodificado, pubID ou false se inválido.
- `autenticacao_validar_jwt_chave_privada(array|false $params = false): array|false` — [linha 569](../../../../../gestor/bibliotecas/autenticacao.php#L569)
  Valida JWT usando chave privada RSA.
  Parâmetros:
  - `$params`: Parâmetros (token, chavePrivada, chavePrivadaSenha obrigatórios).
  Retorno: Payload decodificado ou false se inválido.
- `autenticacao_cliente_gerar_token_validacao(array|false $params = false): array` — [linha 657](../../../../../gestor/bibliotecas/autenticacao.php#L657)
  Gera token JWT de validação para cliente.
  Parâmetros:
  - `$params`: Parâmetros (id_hosts obrigatório, pubID opcional).
  Retorno: Token gerado ou array vazio em erro.
- `autenticacao_acesso_verificar(array|false $params = false): array` — [linha 725](../../../../../gestor/bibliotecas/autenticacao.php#L725)
  Verifica estado de acesso do usuário com proteção anti-spam.
  Parâmetros:
  - `$params`: Parâmetros (tipo obrigatório).
  Retorno: Estado do acesso com 'permitido', 'status' e 'mensagem' opcional.
- `autenticacao_acesso_cadastrar(array|false $params = false): void` — [linha 795](../../../../../gestor/bibliotecas/autenticacao.php#L795)
  Cadastra tentativa de acesso do usuário para controle anti-spam.
  Parâmetros:
  - `$params`: Parâmetros (tipo obrigatório, antispam opcional).
- `autenticacao_acesso_confirmar(array|false $params = false): void` — [linha 914](../../../../../gestor/bibliotecas/autenticacao.php#L914)
  Confirma acesso bem-sucedido do usuário.
  Parâmetros:
  - `$params`: Parâmetros (tipo obrigatório).
- `autenticacao_acesso_falha(array|false $params = false): void` — [linha 972](../../../../../gestor/bibliotecas/autenticacao.php#L972)
  Registra falha de acesso do usuário.
  Parâmetros:
  - `$params`: Parâmetros (tipo obrigatório).
- `autenticacao_acessos_limpeza(array|false $params = false): void` — [linha 1086](../../../../../gestor/bibliotecas/autenticacao.php#L1086)
  Limpa registros antigos da tabela de acessos.
  Parâmetros:
  - `$params`: Parâmetros da função.
- `autenticacao_encriptar_chave_publica(array|false $params = false): string|false` — [linha 1116](../../../../../gestor/bibliotecas/autenticacao.php#L1116)
  Encripta valor usando chave pública RSA.
  Parâmetros:
  - `$params`: Parâmetros (valor, chavePublica obrigatórios).
  Retorno: Valor encriptado em base64 ou false em erro.
- `autenticacao_encriptar_chave_privada(array|false $params = false): string|false` — [linha 1164](../../../../../gestor/bibliotecas/autenticacao.php#L1164)
  Encripta valor usando chave privada RSA.
  Parâmetros:
  - `$params`: Parâmetros (valor, chavePrivada, chavePrivadaSenha obrigatórios).
  Retorno: Valor encriptado em base64 ou false em erro.
- `autenticacao_decriptar_chave_publica(array|false $params = false): string|false` — [linha 1212](../../../../../gestor/bibliotecas/autenticacao.php#L1212)
  Decripta valor usando chave pública RSA.
  Parâmetros:
  - `$params`: Parâmetros (criptografia, chavePublica obrigatórios).
  Retorno: Valor decriptado ou false em erro.
- `autenticacao_decriptar_chave_privada(array|false $params = false): string|false` — [linha 1266](../../../../../gestor/bibliotecas/autenticacao.php#L1266)
  Decripta valor usando chave privada RSA.
  Parâmetros:
  - `$params`: Parâmetros (criptografia, chavePrivada, chavePrivadaSenha obrigatórios).
  Retorno: Valor decriptado ou false em erro.
- `autenticacao_distribuido_validar_credenciais(string $usuario, string $senha): array` — [linha 1329](../../../../../gestor/bibliotecas/autenticacao.php#L1329)
  Valida as credenciais de um usuário para ativação/login do canal distribuído.
  Parâmetros:
  - `$usuario`: Login do usuário.
  - `$senha`: Senha em texto plano.
  Retorno: ['valido' => bool, 'id_usuarios' => int|null, 'mensagem' => string|null]
- `autenticacao_distribuido_gerar_tokens(int $id_usuarios): array|false` — [linha 1367](../../../../../gestor/bibliotecas/autenticacao.php#L1367)
  Gera os tokens de acesso e renovação (OAuth2) para o canal distribuído.
  Parâmetros:
  - `$id_usuarios`: ID do usuário já validado.
  Retorno: Tokens (access_token, refresh_token, expires_in, ...) ou false.
- `autenticacao_distribuido_verificar_permissao_modulo(int $id_usuarios, string $modulo): bool` — [linha 1397](../../../../../gestor/bibliotecas/autenticacao.php#L1397)
  Verifica se o usuário tem permissão de acesso ao módulo alvo (controle por perfil).
  Parâmetros:
  - `$id_usuarios`: ID do usuário já autenticado.
  - `$modulo`: Slug do módulo alvo (ex.: 'modulos-grupos-distribuido').
  Retorno: true se o usuário pode acessar o módulo.
- `autenticacao_distribuido_token_ativo(string $token): bool` — [linha 1505](../../../../../gestor/bibliotecas/autenticacao.php#L1505)
  Verifica se um access token do canal distribuído está ativo e íntegro.
  Parâmetros:
  - `$token`: Access token a validar.
  Retorno: true se o token é válido e não expirou.

<!-- c2f:extract:end -->
