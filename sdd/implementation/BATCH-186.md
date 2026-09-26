# BATCH-186: Documentação do Core — Guias e Referência de CLI e API (onda 4)

Execução da [req-182](../human-requests/req-182.md), por um agente em outra infraestrutura. Ondas irmãs: [BATCH-187](BATCH-187.md) (onda 5) e [BATCH-188](BATCH-188.md) (onda 6).

**Status**: `complete` (documentação versionada; publicação do site pertence à req-184).

## Progresso

| Doc (pt-br + en) | Legado removido | Achados do código |
|---|---|---|
| `guides/development-environment.md` | `CONN2FLOW-AMBIENTE-DESENVOLVIMENTO`, `CONN2FLOW-AMBIENTE-DOCKER`, `CONN2FLOW-CLOUDFLARE-TUNNEL`, en `CONN2FLOW-DEVELOPMENT-ENVIRONMENT`, `CONN2FLOW-DOCKER-ENVIRONMENT`, `PHP85-INSTALL-GUIDE` | O Compose padrão publica HTTP 80, MySQL 3306 e phpMyAdmin 8081; túnel é perfil opcional. Variantes PHP 8.5 são pilhas alternativas na mesma porta. Comentários dessas variantes citam `banco-v2.php`, removida do Core. |
| `guides/dev-tools-vscode.md` | `GUIA-PAINEL-DEV-TOOLS-VSCODE` (sem par en) | O painel é a extensão `conn2flow-tools` do repositório `conn2flow-ai-workspace`, não apenas `.vscode/tasks.json`. Árvore e comandos conferidos em `vscode-extension/src/providers/conn2flowTreeProvider.ts` e `package.json` do commit `a537050` desse repositório. |
| `guides/ci-cd.md` | `CONN2FLOW-GITHUB-ACTIONS` (pt-br/en) | Dois workflows: tags `gestor-v*` e `instalador-v*`. Ambos usam `make_latest: true`, logo `releases/latest` não identifica a série do instalador. Release do gestor roda PHP 8.4, Node 22, PHPUnit, Vitest e Playwright. |
| `guides/create-a-module.md` | — | `module:create` normaliza id, recusa pasta existente e cria controller/JSON/JS e recursos pt-br/en; o scaffold requer revisão de permissões e CRUD. |
| `guides/create-a-plugin.md` | `CONN2FLOW-PLUGIN-INSTALADOR-FLUXO` (pt-br/en) | O instalador procura `manifest.json` (não `plugin.json`), exige `id`, `name`, `version` e valida versão `x.y.z`; busca manifesto no pacote extraído e usa staging. |
| `guides/deploy-a-project.md` | `CONN2FLOW-AUTOMACAO-EXPORTACAO`, `CONN2FLOW-HISTORICO-EXPORTACAO` (pt-br/en) | `project:update-all` tem 8 etapas; CSS, minificação e assets podem avisar e continuar. O deploy não agenda cron no servidor nem regenera `sitemap.xml`. E-mail exige SMTPS/TLS implícito na porta 465; `EMAIL_SECURE=false` não desliga a criptografia. |
| `reference/cli/index.md` + 18 famílias | — | 51 comandos realmente registrados em `Application::registerBuiltInCommands()`; `BaseProcessCommand` é base, não comando. Cada página traz ajuda executável de `--help` e fontes dos comandos. `resources:sync --force` não é repassado ao compilador. |
| `reference/api/index.md`, `auth.md`, `oauth.md` | — | O envelope usa `status: success/error`, não `success: bool`. O rate limit padrão é 100/3600 s por rota/sujeito; falha de infraestrutura retorna 503. `me` e `modules` não impõem método HTTP. `scope` OAuth2 não é aplicado por rota. |
| `reference/api/project.md`, `system.md`, `modules.md` | — | `project/update` aceita ZIP de até 100 MB e sincroniza hooks após banco; `recover` retorna ZIP de `*Data.json` e aceita `recover_contents`. `system/update` usa POST até para `status`. O despacho de módulo exige bearer e delega método ao hook. |
| `reference/api/distributed.md`, `gateways.md` | — | Canal distribuído usa HMAC; roteador encaminha `ativar` ao central, mas o switch retorna 404. Gateway PagBank/PagSeguro ainda retorna 404; Stripe/PayPal têm fluxos legados e modulares distintos. Detalhes sensíveis ficam na req-181. |

## Commits

- `32702bb7` — guias bilíngues e remoção dos 15 legados.
- `40f59dd3` — índice e 18 famílias da CLI, 51 comandos.
- `7eea42f3` — índice e sete famílias da API HTTP.
- `61fc06ce` — ressalva de SMTPS/porta 465 no guia de deploy.

Todos enviados a `origin/main` após `git fetch` e `git rev-list --count HEAD..origin/main` = 0 antes de cada push.

## Validação

- Worktree limpo do HEAD publicado `61fc06ce`: `php cli/c2f.php docs:audit --json` retornou **0 erros**, 3 avisos de cobertura externos ao lote (`formulario`, `html-editor`, `usuario`) e score **0 nas 70 páginas** de `guides/`, `reference/cli/` e `reference/api/`. `php cli/c2f.php docs:extract --all --check` retornou **exit 0**.
- Na árvore de trabalho compartilhada, o `formulario.md` pt-br não versionado por outra frente faz a auditoria local mostrar 1 erro e `docs:extract --all --check` sair com 1. O comando sem `--check` foi evitado para preservar esse trabalho concorrente. A validação de aceite acima é do HEAD versionado e enviado.
- Pipeline `docs:build`, `project:update-all`, `css:rebuild` e `resources:sync` não executados, conforme exclusividade da req-184.
