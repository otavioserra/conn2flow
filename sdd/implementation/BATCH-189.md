# BATCH-189: Ajustes de Navegação e Referência nas Docs Online

Execução da [req-185](../human-requests/req-185.md). Publicação no Lab pelo agente da [req-184](../human-requests/req-184.md) ([BATCH-188](BATCH-188.md)).

**Status**: `complete` (2026-09-26).

## O que mudou

- O menu lateral dos templates `docs-article` em pt-br e en abre o grupo atual e centraliza imediatamente o link com `aria-current="page"` dentro de `[data-docs-scroll]`, sem animação nem rolagem da página.
- Os cartões anterior/próximo usam `menuLabel()`: nome do módulo por idioma, `label:` do frontmatter ou título.
- `docs:extract` preserva o resumo do docblock, a descrição de cada `@param` (inclusive `$params['chave']`) e a descrição de `@return`. As chaves não entram na inferência de tipo da assinatura. Funções sem docblock mantêm só assinatura e link.
- O rodapé mantém selo de verificação e fontes, sem link de edição. A chave `edit` saiu de `docs.config.json` nos dois idiomas.
- Os 76 arquivos de referência de bibliotecas alterados foram regenerados pelo comando oficial. Comparação com `HEAD` após remover os blocos gerados: zero diferenças fora desses blocos.

## Commits

- Core `main`: `8653c4cd` — builder, extrator, testes e blocos gerados; push concluído.
- Site `feat/req-055`: `e7e5db2` — templates bilíngues e configuração; push concluído.
- Este relatório integra um commit separado de registro do lote.

## Validação

- `php vendor/bin/phpunit tests/Unit/PHP/DocsBuildReq178Test.php tests/Unit/PHP/DocsToolingReq177Test.php tests/Unit/PHP/DocsSddReq184Test.php`: **23 testes, 370 asserções, exit 0**. Cobertura nova para rótulo dos cartões, ausência do link de edição, resumo, parâmetros, chaves de array, retorno e função sem docblock.
- `php cli/c2f.php docs:extract --all --check`: exit 0.
- `php cli/c2f.php docs:audit --json`: exit 0, **0 erros, 4 avisos**.
- `php cli/c2f.php docs:build --project=conn2flow-site-local --dry-run`: exit 0; 318 páginas, 303 publicações, 5 landings no plano; **nenhum arquivo escrito**.
- `php -l` nos três arquivos PHP alterados: sem erros; `git diff --check` nos dois repositórios: exit 0.
- Conferência do JS: nos dois templates, o cálculo usa os retângulos do link atual e de `[data-docs-scroll]` depois da expansão dos grupos e escreve somente `scroll.scrollTop`. O dry run leu os templates do projeto. A inspeção visual final no Lab acompanha a publicação da req-184, conforme §3 da requisição.

## Publicação no Lab (2026-09-26, pelo agente da req-179, que assumiu o pipeline a pedido do Humano)

- `docs:build --project=conn2flow-site-local`: 319 páginas, 304 publicações, 5 landings; `project:update-all`: sem órfãos.
- Conferido por HTTP e `page:inspect` (0 erros de console): `/docs/sdd/` 200; menu centralizado no item atual (`/docs/reference/modules/usuarios/`); cartões anterior/próximo com o rótulo do menu e na ordem do menu (correção `fix(docs)` no DocsBuilder); descrições das funções no bloco; sem link de edição; callouts separados.
- SDD publicado: 776 substituições do filtro; varredura do JSON e do `llms-full-pt-br.txt` sem IP real, e-mail, host de laboratório ou caminho de servidor.
- Site: commit `84e4cca` (`feat/req-055`).
