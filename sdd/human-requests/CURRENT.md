# Current Human Request

- **Intake ativo**: [req-074.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-074.md) (BATCH-074 `complete`, 2026-06-30).

- **Status**: BATCH-074 concluído. Dinamização completa de planos e saneamento de hardcodes no módulo `subscriptions` (lumix): helpers de nome de plano e de form_ids dinâmicos, checkout resolvendo o plano no banco e renderizando o formulário via `forms_render` (campo `forms.html`), landing/callback enxutos. Em conn2flow (§6): redação de campos `password` em `forms_submissions` e no e-mail. Ver DEC-077.

- **Pendências**: Deploy (`Update => Core`) e validação runtime com o operador (checkout dos 3 tipos de plano, IPN/webhook, redação de senha no banco). Nenhum `git commit`/`git push` executado.
