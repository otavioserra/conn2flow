# BATCH-107 — Hardening de Segurança, Mitigação de Vulnerabilidades e Saneamento do Core

## Origem

- Intake: `sdd/human-requests/req-107.md`
- Decisão: DEC-102
- Status: `complete`
- Data de fechamento: 2026-08-06

## Entrega

- **Instalador**: SSL/TLS verificado, checksum SHA-256 obrigatorio, trava de execução (`install.lock`), bloqueio de reinstalação, escrita literal no `.env` e remoção/destruição de resíduos pós-instalação (incluindo `installer.log`).
- **Servidor de Estáticos**: Proteção estrita contra Path Traversal textual (`..`, `\0`) e física via `realpath()` + `str_starts_with()` contra as raízes autorizadas (`assets/`, `contents/`, `modulos/`).
- **Criptografia & Sessões**: Centralização de gerador CSPRNG em `seguranca_token_aleatorio()` usando `random_bytes()` / `random_int` (no mínimo 128 bits de entropia) para IDs de sessão, cookies de verificação e `pubID` do OAuth2.
- **Proteção CSRF**: Ativação obrigatória de `gestor_csrf_validar()` nos formulários e requisições AJAX autenticados por cookie de sessão do painel, com injeção de tokens em HTML/meta e headers jQuery/fetch.
- **Cabeçalhos HTTP**: Emissão de `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN` e `Strict-Transport-Security` em HTTPS, além de suporte a CSP `Report-Only`.
- **API Pública**: Restrição de CORS por allow-list configurável, obrigatoriedade de `Authorization: Bearer` (remoção de `?token=`) e rate limit persistido por usuário/rota na nova tabela `api_rate_limits`.
- **OAuth2**: Validação do HMAC `pubIDValidation` nos access tokens e rotação FIFO (revogação do token mais antigo ao atingir o limite de sessões).
- **Acesso a Dados & Saneamento**: Banco v1 legado marcado como obsoleto, remoção do fallback inseguro `addslashes()` em `banco_escape_field()`, remoção de código morto (`banco_smartstripslashes()`) e parametrização de Argon2id para `password_hash()`.

## Evidência automatizada

- `php -l`: 17 arquivos validados com sucesso.
- `node --check`: 2 scripts validados com sucesso.
- `git diff --check`: sem problemas de espaçamento/formatação.
- PHPUnit: **181 testes, 732 asserções** com 100% de aprovação (incluindo a nova suíte `HardeningReq107Test.php`).
- Vitest: **118 testes** com 100% de aprovação.
