---
title: "Biblioteca 2fa.php"
label: "Dois fatores (2FA)"
description: "Segundo fator de autenticação: TOTP compatível com aplicativos autenticadores e código de 6 dígitos por e-mail."
section: reference
order: 150
sources:
  - gestor/bibliotecas/2fa.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
verified_at: fce0b830
---

# Biblioteca `2fa.php`

Implementa os dois segundos fatores do login: **TOTP** (RFC 6238, o código de 30 segundos do Google Authenticator, Authy e similares) e **código por e-mail**. As telas e o fluxo ficam no módulo `perfil-usuario` (`signin-2fa/` e o equivalente no OAuth); a biblioteca só calcula, valida e envia.

Os dados ficam na tabela `usuarios`: `two_factor_enabled`, `two_factor_type` (`app` ou `email`), `two_factor_secret`, `two_factor_email_code` e `two_factor_email_expire`. Os métodos oferecidos saem de `AUTH_2FA_METHOD_APP` e `AUTH_2FA_METHOD_EMAIL` (padrão `true`) no `.env`.

## TOTP

```php
gestor_incluir_biblioteca('2fa');

$secret = two_factor_generate_secret();              // 16 caracteres Base32 = 80 bits
$uri    = two_factor_get_qr_code($email, $secret);   // otpauth://totp/Conn2Flow:<email>?secret=…
// renderize $uri como QR Code; grave $secret em usuarios.two_factor_secret

$ok = two_factor_validate_code($secret, $codigoDigitado);
```

- SHA-1, 6 dígitos, período de 30 s, com tolerância de um período para trás e um para frente (o código vale por até 90 s).
- `two_factor_generate_secret($n)` aceita no mínimo 16 caracteres.
- O emissor mostrado no aplicativo é sempre `Conn2Flow`, não o nome do site.
- O segredo fica em texto claro no banco.

## Código por e-mail

- `two_factor_email_send_code($id_usuarios, $email)` sorteia 6 dígitos, grava com validade de **5 minutos** (substituindo qualquer código anterior) e envia com `comunicacao_email()`. O assunto e o texto do e-mail são fixos em português.
- `two_factor_email_validate($id_usuarios, $codigo)` confere valor e prazo e **apaga o código** ao aceitar, impedindo o reuso.

> [!WARNING]
> Nem a biblioteca nem o `perfil-usuario` limitam as **tentativas** de verificação, e o TOTP aceito não é marcado como usado (pode ser repetido dentro da janela). O reenvio do código por e-mail também não tem limite. Veja a req-181 (item A5) para a correção proposta.

O `perfil-usuario` acrescenta os **códigos de recuperação** (req-119), tentados só quando o segundo fator falha e válidos uma vez cada.

**Auxiliares internos** (usados pelas funções acima; raramente chamados direto): `two_factor_base32_encode()`, `two_factor_base32_decode()`, `two_factor_hotp()`.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/2fa.php` por `c2f docs:extract` — 8 funções. Não edite dentro deste bloco.

- `two_factor_base32_encode(string $data): string` — [linha 33](../../../../../gestor/bibliotecas/2fa.php#L33)
- `two_factor_base32_decode(string $b32): string` — [linha 57](../../../../../gestor/bibliotecas/2fa.php#L57)
- `two_factor_generate_secret(int $length = 16): string` — [linha 87](../../../../../gestor/bibliotecas/2fa.php#L87)
- `two_factor_get_qr_code(string $email, string $secret): string` — [linha 109](../../../../../gestor/bibliotecas/2fa.php#L109)
- `two_factor_hotp(string $secret, int $counter): string` — [linha 126](../../../../../gestor/bibliotecas/2fa.php#L126)
- `two_factor_validate_code(string $secret, string $code): bool` — [linha 155](../../../../../gestor/bibliotecas/2fa.php#L155)
- `two_factor_email_send_code(int $usuario_id, string $email): bool` — [linha 178](../../../../../gestor/bibliotecas/2fa.php#L178)
- `two_factor_email_validate(int $usuario_id, string $code): bool` — [linha 223](../../../../../gestor/bibliotecas/2fa.php#L223)

<!-- c2f:extract:end -->
