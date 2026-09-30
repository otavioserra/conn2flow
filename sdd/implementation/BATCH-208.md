# BATCH-208 — Recuperação de arquivos do servidor (req-200)

Execução da [req-200](../human-requests/req-200.md) na worktree `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req200`, branch `feat/req-200`.

**Status:** implementado; homologação HTTP no tenant isolado pendente de integração da rota.

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

## Limite da homologação

O tenant `project-test` é compartilhado e a req-200 proíbe atualização do sistema e deploy nele. A rota nova só estará disponível ali após a integração da branch por quem controla o tenant. A homologação HTTP/CLI contra ele deve ocorrer nesse momento, preservando a restrição de não alterar o tenant durante este lote.
