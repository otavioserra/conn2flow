# BATCH-208 — Recuperação de arquivos do servidor (req-200)

Execução da [req-200](../human-requests/req-200.md) na worktree `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req200`, branch `feat/req-200`.

**Status:** `complete` (implementado pelo agente executor; revisado, mesclado no `main` e homologado no tenant pelo agente da req-198/199 em 2026-09-30; homologação humana pendente).

## Escopo

- [x] Inventário de divergências por camada, com filtros e ZIP restrito a caminhos explícitos.
- [x] CLI `project:recover-files` com simulação padrão, aplicação no projeto, download do core e plugins apenas para análise, JSON e relatório por execução.
- [x] Choques locais passam por `instalacao_choque_acoes()` e `instalacao_choque_resolver()`; a cópia baixada é a nova versão, sem gravar regras de deploy localmente.
- [x] Testes automatizados para estados, precedência, ZIP, caminhos sensíveis e três decisões.
- [x] Documentação bilíngue da API, CLI, conceito e biblioteca.

## Validação

- PHPUnit focado `RecoverFiles|UpdateConflicts|UpdateRollback|AtualizacoesManifesto|InstalacaoManifesto|AtualizacoesSistemaTrava|DeployLock|MigrationChecker`: 54 testes, 264 asserções, verde (dois avisos de depreciação do PHPUnit).
- `php -l` nos arquivos PHP alterados: sem erro após correção de sintaxe inicial.
- `docs:audit`: 0 erros, 0 avisos nos arquivos tocados; seis avisos prévios em outras docs. Blocos da biblioteca regenerados com `docs:extract`.
- `project:recover-files project-test --simular --camada=projeto --json`: HTTP 404, pois o tenant ainda não tem a rota nova. Nenhum arquivo do tenant foi alterado.

## Revisão e homologação (agente da req-198/199, 2026-09-30)

**Revisão (findings-first):** nenhum achado bloqueante. Pontos fortes: caminhos validados (sem `..`, ocultos, `:`, pastas protegidas), contenção por `realpath` e recusa de link simbólico, hash conferido antes de zipar e na extração, ZIP só de caminhos explícitos e divergentes, extensão do motor (`copia_externa`, `gravarRegras`) sem quebrar os chamadores.
**Observações (não bloqueantes, ficam para depois):**
- o inventário calcula o sha256 de todos os arquivos da instalação a cada chamada (duas por execução do CLI: lista e ZIP); em instalação grande, considerar cache por mtime/tamanho;
- sem `--camada`, o CLI também baixa os `fora-do-manifesto` (arquivos que nenhuma camada entregou); útil para análise, mas pode ser volumoso.

**Mesclagem:** `origin/feat/req-200` no `feat/req-197` (conflito só textual em `concepts/system-updates.md`, os dois parágrafos mantidos); PHPUnit dos lotes da req-197 a 201 com a req-200: **66 testes, 308 asserções, verdes**.

**Tenant isolado** (código mesclado levado pela atualização do sistema):

| Verificação | Resultado |
|---|---|
| `project:recover-files project-test --camada=projeto` | 1 divergência: `bibliotecas/fpdf184/install.txt` editado no servidor; local também diferente do manifesto → `choque_pendente`; relatório em `temp/recover-files/…/relatorio.json` (1,0 s) |
| Edição direta no servidor em `bibliotecas/SimpleImage/README.md` + `--camada=core --json` | `editado`, `analise`; arquivo baixado em `servidor/…` com o hotfix; repositório local intocado |
| `--caminho=bibliotecas/fpdf184/install.txt --aplicar --acao=sobrescrever` | `aplicado`: a versão do servidor foi para o repositório local do projeto (a própria worktree, então o arquivo foi restaurado em seguida com `git checkout`) |

## Limite da homologação (registro do agente executor)

O tenant `project-test` é compartilhado e a req-200 proíbe atualização do sistema e deploy nele. A rota nova só estará disponível ali após a integração da branch por quem controla o tenant. A homologação HTTP/CLI contra ele deve ocorrer nesse momento, preservando a restrição de não alterar o tenant durante este lote.
