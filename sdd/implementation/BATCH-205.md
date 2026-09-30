# BATCH-205: Atualização segura, fase 3a — motor de choques: decisão por arquivo (req-199)

Execução da [req-199](../human-requests/req-199.md), proposta G do [BL-028](../backlog/BL-028-atualizacao-segura-choques-backup-rollback.md), primeira parte: o motor comum e três das quatro portas (painel, API, CLI). A extensão do VS Code fica no BATCH-206; a exclusão declarativa de dados (proposta C) no BATCH-207.

**Status**: `complete` (implementado e validado no tenant isolado; homologação humana pendente).
**Worktree**: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-bl028`, branch `feat/req-197`.
**Diretriz do Humano**: um motor só, para subir e descer, projeto e core ("vai ter choques de arquivo sempre"); a recuperação (req-200) usa o mesmo motor na descida.

## Desenho

| Peça | O quê |
|---|---|
| Motor (`instalacao-manifesto.php`, puro) | `instalacao_choque_acoes` (decisões por motivo), `instalacao_choque_versoes` (no ar × nova), `instalacao_choque_resolver` (aplica no disco), regras em `installation/regras.json` |
| Regras | `manter` com o disco igual ao da decisão: a próxima entrega nasce resolvida (`manter-regra`). Sobrescrever e mesclar apagam a regra |
| Planejador | a camada que entrega o mesmo conteúdo de antes não gera choque (antes, choque repetido a cada entrega e de volta depois de uma mescla) |
| Registro (`atualizacoes-choques.php`, nova) | listar, obter, detalhe com as duas versões, resolver (resolve junto as pendentes do mesmo arquivo e camada; recusa choque já resolvido) |
| Migração `20260930220000` | `resolvido_em`, `resolvido_por` |
| API | `/_api/project/conflicts` (lista ou detalhe, binário em base64) e `/_api/project/resolve` (sob a trava de deploy; `api:<e-mail>`) |
| Painel | detalhe do choque com os botões das decisões válidas e o editor de mescla (resultado × versão nova); AJAX `choque-resolver`; lista mostra a resolução |
| CLI | `c2f update:conflicts` (lista; baixa `no-ar`, `nova`, `mesclado` em `temp/conflicts/<projeto>/<id>/`; `--abrir` com `code --diff`), `c2f update:resolve` (`--acao`, `--arquivo`, `--local` grava a mescla no repositório do projeto); `ProjectApiClient` comum, com `api_resolve_ip` para o Lab |

## Validação

### Automatizada
- `InstalacaoManifestoTest` (23: decisões por motivo, versões, sobrescrever, mesclar, manter com regra e regra que cai quando o disco muda, decisões inválidas, retirada aceita, mesma versão sem choque, filtro com resolução), `UpdateConflictsCommandTest` (5) e os demais do lote: **51 testes, 220 asserções, verdes**.
- `docs:audit`: 0 erros; os 6 avisos são anteriores.

### Tenant isolado (`project-test`, `url` `https://c2f-teste.local:8443/`, `api_resolve_ip` `127.0.0.1`)

| Verificação | Resultado |
|---|---|
| Migração `20260930220000` | aplicada pela atualização do sistema |
| `c2f update:conflicts project-test` | tabela com os 6 choques pendentes e as decisões de cada um |
| `c2f update:conflicts project-test 6` | `no-ar.txt`, `nova.txt`, `mesclado.txt` baixados; sugestão de `code --diff` |
| `update:resolve 6 --acao=mesclar` | mescla gravada no servidor; "3 linha(s) resolvida(s)" (as versões anteriores do mesmo choque) |
| `update:resolve 5 --acao=manter` | regra em `installation/regras.json`; 3 linhas resolvidas |
| Resolver de novo | 422 "choque já resolvido" |
| Atualização v4 (install.txt novo, README editado no servidor e mudado, license.txt igual) | install.txt: `manter-regra` automático; README: choque pendente; license.txt (mesmo conteúdo): sem choque |
| Painel (Playwright), choque 7 | três botões e a ajuda do motivo; editor de mescla lado a lado; "Sobrescrever" → "Sobrescrito — … (painel:admin@c2f-teste.local)"; arquivo com a versão nova; sem erro de JS |

### Achados corrigidos
- Choque repetido para a mesma versão da camada (ver Planejador).
- O `.min.js` do módulo foi gerado como o `assets:minify` faz (terser sobre o conteúdo em LF); rodar o minificador geral na worktree acusaria 58 derivados "desatualizados" só por CRLF.

### Pendências
- Linha `manter-regra` sem `resolvido_em`/`resolvido_por` (é a entrega que resolve).
- Extensão do VS Code (BATCH-206), exclusão declarativa de dados (BATCH-207), descida/recuperação (req-200).
- Homologação humana.
