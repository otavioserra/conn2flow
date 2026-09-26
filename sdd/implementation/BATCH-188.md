# BATCH-188: Publicação do SDD do Core no Site e Pipeline das Docs (onda 6)

Execução da [req-184](../human-requests/req-184.md), por um agente em outra infraestrutura. Ondas irmãs: [BATCH-186](BATCH-186.md) (onda 4) e [BATCH-187](BATCH-187.md) (onda 5).

**Status**: `in-progress`. O pipeline no Lab só pode começar depois do [BATCH-184](BATCH-184.md) `complete`.

## Desenho

- `SddSource` lê apenas `conn2flow/sdd/` (Core), inclui a raiz e arquivos correntes de `process`, `decisions`, `implementation`, `human-requests` e `validation`. Exclui `archive/`, `backlog/`, memórias e outras pastas. Deriva título do primeiro H1, descrição do primeiro parágrafo e ordem do número no nome.
- O filtro roda antes de renderizar HTML ou `llms.txt`: remove caminhos de usuário/servidor, IPs, e-mails, hosts e nomes de projetos privados. Arquivos com credenciais ou chaves não entram no plano; o comando lista cada exclusão/substituição como aviso. A fonte SDD só entra em pt-br.
- `DocsBuilder` reutiliza o template, a mescla por id, menu, landing e `llms.txt`. O site declara `docs-sdd`, `docs-sdd-index` e o HTML do índice em recursos autorais. `/docs/sdd/` é a landing; os artigos ficam em `/docs/sdd/<caminho>/`. O índice principal `llms.txt` (idioma padrão en) contém um link para a landing pt-br.
- Links Markdown entre arquivos incluídos são reescritos para as páginas. Links a arquivos excluídos apontam para `#`, sem publicar seu conteúdo.

## Commits

- Core `4a634bfd`: fonte SDD, filtro, testes e guia bilíngue (push em `main`).
- Site `6a2c3ed`: configuração do publisher e índice SDD (push em `feat/req-055`).
- Recursos gerados e validação do Lab pendentes da liberação do BATCH-184.

## Validação

- `DocsSddReq184Test`: 5 testes, 245 asserções, exit 0; inclui o corpus real do SDD após o filtro e o link no `llms.txt` principal.
- `DocsBuildReq178Test`: 7 testes, 64 asserções, exit 0.
- `DocsToolingReq177Test`: 9 testes, 36 asserções, exit 0.
- `docs:build --project=conn2flow-site-local --dry-run`: exit 0; 311 páginas, 296 publicações, 5 landings; lista substituições sensíveis. Nenhum arquivo gravado.
- `git diff --check`: exit 0 no Core e no site.
- `devProjects.conn2flow-site-local.local`: `true`.
- Publicação, inspeção de console e conferência do HTML gerado aguardam BATCH-184 `complete` no `BATCH-INDEX.md`, conforme req-184.

### Testes manuais para o Humano

- Abrir `/docs/sdd/` no Lab, navegar para um documento de cada pasta e conferir o menu SDD.
- Conferir o índice `llms-pt-br.txt` e o conteúdo `llms-full-pt-br.txt` para as páginas do SDD.
- Revisar os avisos de redaction/exclusão do build e buscar dados sensíveis no HTML gerado antes da publicação de produção.
