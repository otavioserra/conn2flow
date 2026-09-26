# 🤖 AI Workspace — Conn2Flow (pt-br)

Área de apoio do Core para documentação técnica e utilitários de desenvolvimento. A governança de trabalho vive em [`sdd/`](../../sdd/README.md) e as skills dos agentes, nos kits `.claude/`, `.github/`, `.cursor/`, `.gemini/` e `.codex/`.

## 📁 Estrutura

```
ai-workspace/pt-br/
├── docs/       # Documentação técnica (fonte da documentação pública em /docs/)
├── scripts/    # Utilitários usados pelo CLI, tasks do VS Code e pipelines (código vivo)
├── snippets/   # Trechos de referência
└── utils/      # Anotações operacionais de apoio
```

## 📚 Documentação

A documentação nova segue o contrato descrito em [docs/guides/documentation.md](docs/guides/documentation.md):

- **Árvore:** `guides/`, `concepts/`, `reference/{libraries,modules,api,cli,hooks}/` e `whats-new/`, espelhada em `../en/docs/` com o mesmo caminho relativo.
- **Frontmatter** com `title`, `description`, `section`, `sources` e `verified_at`.
- **Comandos:**
  - `php cli/c2f.php docs:audit` gera o ranking de defasagem;
  - `php cli/c2f.php docs:extract` gera a referência das bibliotecas a partir do código;
  - `php cli/c2f.php docs:build --project=<id>` publica no site como recursos do sistema.

As páginas de conceitos e novidades já estão na árvore nova. Arquivos legados de bibliotecas ainda aparecem em `legacy` no `docs:audit` até a conclusão da onda correspondente.

## 🗄️ Histórico

As pastas `agents-history/`, `prompts/` e `templates/` foram retiradas da `main` em 2026-09-25 (req-176). O conteúdo integral continua na branch `legacy/ai-workspace-pre-docs`.
