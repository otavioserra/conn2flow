# Current Human Request

- **Intake ativo**: [req-072.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-072.md) (BATCH-072 `complete`, 2026-06-30).

- **Status**: BATCH-072 implementado no Lumix. Campo e metadado `hosting_plan` removidos da migração futura, CRUD de planos e hooks de hidratação.

- **Pendências**: Rodar a migração Phinx e validar o CRUD no ambiente `lumix` configurado; o checkout local não expõe `composer db:migrate`.
