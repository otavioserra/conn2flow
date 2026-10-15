# Index — archive

| ID | Title | Executive summary | Relative link | Status |
| --- | --- | --- | --- | --- |
| BL-001 | BL-001 — Instalador: verificação de integridade e TLS no download do gestor | `downloadFile()` baixa o release do gestor com `CURLOPT_SSL_VERIFYPEER = false` (&#91;Installer.php:435&#93;(../../../gestor-... | [source](<BL-001-instalador-integridade-download.md>) | IN-DISCUSSION |
| BL-002 | BL-002 — Geração criptograficamente fraca de IDs de sessão e tokens | Identificadores sensíveis são gerados com `md5(uniqid(rand(), true))` / `md5(uniqid(mt_rand(), true))`: | [source](<BL-002-rng-sessao-tokens.md>) | IN-DISCUSSION |
| BL-003 | BL-003 — Contenção de path traversal no servidor de arquivos estáticos | O caminho físico do arquivo servido é montado por concatenação direta do caminho da URL: | [source](<BL-003-path-traversal-arquivo-estatico.md>) | IN-DISCUSSION |
| BL-004 | BL-004 — Proteção CSRF existe mas não é aplicada (código morto) | A biblioteca `seguranca.php` implementa `gestor_csrf_token()` e `gestor_csrf_validar()` corretamente (token de 32 byt... | [source](<BL-004-csrf-nao-aplicado.md>) | IN-DISCUSSION |
| BL-005 | BL-005 — Hardening da API pública: CORS wildcard e token via query string |  | [source](<BL-005-api-cors-token-querystring.md>) | IN-DISCUSSION |
| BL-006 | BL-006 — Instalador: lock de execução, autenticação e resíduos pós-instalação |  | [source](<BL-006-instalador-lock-e-residuos.md>) | IN-DISCUSSION |
| BL-007 | BL-007 — Acesso a dados por concatenação de SQL e fallback de escape frágil | O caminho principal de acesso a dados monta SQL por **concatenação de string**: `banco_select_name($campos, $tabela, ... | [source](<BL-007-acesso-dados-prepared-statements.md>) | IN-DISCUSSION |
| BL-008 | BL-008 — Hardening do OAuth2: validação de token e limite de sessões |  | [source](<BL-008-oauth2-hardening.md>) | IN-DISCUSSION |
| BL-009 | BL-009 — Ausência de cabeçalhos de segurança HTTP | Busca em todo o `gestor/` por `Content-Security-Policy`, `X-Frame-Options`, `Strict-Transport-Security`, `X-Content-T... | [source](<BL-009-cabecalhos-seguranca-http.md>) | IN-DISCUSSION |
| BL-010 | BL-010 — Débito de migração (bibliotecas paralelas v1/v2) e código morto | Convivem versões paralelas de componentes centrais, sinal de migrações inacabadas: | [source](<BL-010-debito-migracao-e-codigo-morto.md>) | IN-DISCUSSION |

## Directories

- [compacted](<compacted/index.md>)
- [original](<original/index.md>)
