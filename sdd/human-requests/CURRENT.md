# Current Human Request

- **Intake ativo**: [req-030.md](req-030.md) (Autenticação Multi-Método, 2FA App/E-mail, Login Social, Rotação JWT e Segurança)

- **Status**: BATCH-030 com os 6 slices implementados e validados estaticamente (`in-progress` aguardando deploy/runtime). Slices 1–2 (banco + libs `2fa`/`jwt`/`oauth`, PHPUnit + RFC), 3 (admin-environment: login/2FA/OAuth/JWT no `.env` + rotação), 4 (perfil-usuario: rota de Segurança 2FA/social), 5 (login: render dinâmico + interceptador 2FA fail-safe + login social Google/Meta), 6 (endurecimento: Session Hijacking ativo + infra CSRF). Suíte PHPUnit 32/84 OK. Decisões em DEC-043/DEC-044.

- **Pendências**: deploy com o operador (aplicar migrações + registrar páginas/variáveis novas via `Update => Core` + validação runtime); rollout estrito de CSRF (incremental); logs de eventos de segurança via `log.php`; integração JWT nos endpoints `_api/`.

