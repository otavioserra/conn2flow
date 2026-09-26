---
title: "admin-environment module"
description: "Panel for domain environment settings."
section: reference
module: admin-environment
sources:
  - gestor/modulos/admin-environment/admin-environment.php
  - gestor/modulos/admin-environment/admin-environment.js
  - gestor/modulos/admin-environment/admin-environment.json
  - gestor/modulos/admin-environment/resources
  - gestor/config.php
verified_at: a9a226e0
---

# admin-environment module

This administrative screen edits settings loaded from the domain environment file. It covers site name, sanitization, restricted access, CAPTCHA, SMTP, languages, PayPal, login, OAuth, 2FA, JWT, and profiles allowed to issue API keys.

## How to use

Open admin-environment/ (the same path in both languages), change the relevant sections, and save. Test buttons check CAPTCHA, email, and PayPal using form values; JWT rotation is a separate action. Profile selectors read active usuarios_perfis in the current language. Reload the application after saving so config.php reads the new .env values again.

## Technical reference

The page case is raiz. The AJAX switch exposes opcao (empty payload), salvar, buscar-perfis, testar-recaptcha, testar-turnstile, testar-recaptcha-v2, testar-email, testar-paypal, and rotacionar-jwt. JavaScript submits the form and triggers these tests. admin_environment_env_read() uses values already loaded into $_ENV; env_write() changes or appends keys in AUTH_PATH_SERVER/.env. The JSON has no own table, widget, template, hook, or hooks.api. A usuarios_perfis query supports profile selectors.

Saving handles fields present in the request. HTML_SANITIZE, HTML_SANITIZE_JS, CAPTCHA_PROVIDER, TURNSTILE_MODE, and SITE_RESTRICTED_ACCESS have allowed-value lists; crawler tokens and restricted-profile ids are normalized. A guard prevents enabling restricted access with a profile list that excludes the current operator. CAPTCHA and PayPal tests call external services; the email test uses the email library. The JWT action calls jwt_rotate_keys().

## Confirmed limitations

> [!WARNING]
> Saving writes directly to .env, and the controller reports success after file_put_contents() without checking the number of bytes written. The form renders already loaded secrets; module access control and secure transport are essential. Never store real secret values in documentation or the repository.

> [!CAUTION]
> Email test debug mode emits text directly and exits, outside the usual AJAX envelope. External tests and JWT rotation have real effects separate from editing the form.

## See also

- [User profiles](usuarios-perfis.md)
- [User account](perfil-usuario.md)
