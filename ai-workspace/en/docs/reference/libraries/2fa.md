---
title: "2fa.php library"
label: "Two-factor (2FA)"
description: "Second authentication factor: TOTP compatible with authenticator apps and a 6-digit code by e-mail."
section: reference
order: 150
sources:
  - gestor/bibliotecas/2fa.php
  - gestor/modulos/perfil-usuario/perfil-usuario.php
verified_at: fce0b830
---

# `2fa.php` library

It implements the two second factors of the login: **TOTP** (RFC 6238, the 30-second code of Google Authenticator, Authy and similar apps) and **e-mail code**. The screens and the flow live in the `perfil-usuario` module (`signin-2fa/` and its OAuth equivalent); the library only computes, validates and sends.

The data lives in the `usuarios` table: `two_factor_enabled`, `two_factor_type` (`app` or `email`), `two_factor_secret`, `two_factor_email_code` and `two_factor_email_expire`. The offered methods come from `AUTH_2FA_METHOD_APP` and `AUTH_2FA_METHOD_EMAIL` (default `true`) in the `.env`.

## TOTP

```php
gestor_incluir_biblioteca('2fa');

$secret = two_factor_generate_secret();              // 16 Base32 characters = 80 bits
$uri    = two_factor_get_qr_code($email, $secret);   // otpauth://totp/Conn2Flow:<email>?secret=…
// render $uri as a QR Code; store $secret in usuarios.two_factor_secret

$ok = two_factor_validate_code($secret, $typedCode);
```

- SHA-1, 6 digits, 30 s period, with a tolerance of one period back and one forward (a code is valid for up to 90 s).
- `two_factor_generate_secret($n)` accepts at least 16 characters.
- The issuer shown in the app is always `Conn2Flow`, not the site name.
- The secret is stored in plain text in the database.

## E-mail code

- `two_factor_email_send_code($id_usuarios, $email)` draws 6 digits, stores them valid for **5 minutes** (replacing any previous code) and sends them with `comunicacao_email()`. The e-mail subject and text are hard-coded in Portuguese.
- `two_factor_email_validate($id_usuarios, $code)` checks value and deadline and **clears the code** when it accepts it, preventing reuse.

> [!WARNING]
> Neither the library nor `perfil-usuario` limits verification **attempts**, and an accepted TOTP is not marked as used (it can be repeated within the window). Resending the e-mail code has no limit either. See req-181 (item A5) for the proposed fix.

`perfil-usuario` adds **recovery codes** (req-119), tried only when the second factor fails and valid once each.

**Internal helpers** (used by the functions above; rarely called directly): `two_factor_base32_encode()`, `two_factor_base32_decode()`, `two_factor_hotp()`.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/2fa.php` by `c2f docs:extract` — 8 functions. Do not edit inside this block.

- `two_factor_base32_encode(string $data): string` — [line 33](../../../../../gestor/bibliotecas/2fa.php#L33)
- `two_factor_base32_decode(string $b32): string` — [line 57](../../../../../gestor/bibliotecas/2fa.php#L57)
- `two_factor_generate_secret(int $length = 16): string` — [line 87](../../../../../gestor/bibliotecas/2fa.php#L87)
- `two_factor_get_qr_code(string $email, string $secret): string` — [line 109](../../../../../gestor/bibliotecas/2fa.php#L109)
- `two_factor_hotp(string $secret, int $counter): string` — [line 126](../../../../../gestor/bibliotecas/2fa.php#L126)
- `two_factor_validate_code(string $secret, string $code): bool` — [line 155](../../../../../gestor/bibliotecas/2fa.php#L155)
- `two_factor_email_send_code(int $usuario_id, string $email): bool` — [line 178](../../../../../gestor/bibliotecas/2fa.php#L178)
- `two_factor_email_validate(int $usuario_id, string $code): bool` — [line 223](../../../../../gestor/bibliotecas/2fa.php#L223)

<!-- c2f:extract:end -->
