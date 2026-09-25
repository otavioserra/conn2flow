# BATCH-183: Parser `c2f docs:build` — Markdown → Recursos do Sistema com Tailwind

Execução da [req-178](../human-requests/req-178.md). FEAT-014, fase 1.

## Atividades

- [x] **Parsedown 1.7.4** embutido em `cli/lib/parsedown/` (MIT, sem Composer, só em tempo de build). Tem um patch local de assinatura (`?array $Block`) contra *deprecation* no PHP 8.4+, documentado em `cli/lib/parsedown/README.md`.
- [x] **`MarkdownRenderer`** (estende `Parsedown::element()`):
  - classes Tailwind explícitas do `DocsTheme`;
  - âncoras h2/h3 com sufixo para ids repetidos, e o sumário;
  - callouts `> [!NOTE|TIP|IMPORTANT|WARNING|CAUTION]`;
  - moldura de código com botão de copiar;
  - tabela com rolagem;
  - *safe mode*.
- [x] **Neutralização dos marcadores do Gestor:** `@[[` vira `&#64;[[`, para que os exemplos das docs não sejam interpretados pelo runtime. O link interno usa um token que só vira `@[[pagina#url-raiz]]@` depois do escape.
- [x] **`DocsBuilder`** (plano puro, testável):
  - `pages` (publicações com `publisher_id`; `index.md` como página comum);
  - `publisher_pages` (8 campos);
  - página de entrada por publisher, com o widget `publisher-index`, só quando há publicações;
  - menu lateral em árvore (seção → subpasta → doc);
  - anterior/próximo;
  - selo com commit e fontes no GitHub;
  - `assets/docs/llms*.txt`.
  - A mescla por id gerencia só `docs`/`docs-*`, preserva `version`/`checksum` e remove as docs apagadas.
- [x] **`c2f docs:build --project=<id> [--dry-run] [--source]`**, que aborta sem gravar se houver link quebrado ou frontmatter inválido. Registrado em `Application.php`.
- [x] PHPUnit: `tests/Unit/PHP/DocsBuildReq178Test.php`, com 4 testes.

## Notas

1. **Variantes arbitrárias com `&` (`[&_p]:my-2`) não servem:** o atributo sai como `[&amp;_p]` e o compilador do Tailwind, que lê o texto bruto, não gera a classe. O tema evita esse tipo de variante.
2. **`hidden` perde para `inline-flex`** na ordem do CSS do Tailwind v4. Para esconder um elemento com classe de display, remova o elemento (ou use `style.display`), em vez de adicionar `hidden`.
3. **Links de menu `href="#"` resolvem para a URL atual.** O destaque de "página atual" precisa ignorá-los.
4. **Qualquer URL com extensão é servida de `<gestor>/assets/`** pelo `arquivo-estatico`, o que permite `/docs/llms.txt` sem rota nova.
5. **A busca do `publisher-index` usa `JSON_SEARCH` sobre todos os `fields_values`.** Como o HTML da doc é um campo, a busca do índice é de texto completo.

## Validação

- `vendor/bin/phpunit --filter "DocsBuildReq178Test|DocsToolingReq177Test"`: 12/12, 84 asserções.
- `docs:build --project=conn2flow-site-local` duas vezes seguidas: a segunda reporta `0 file(s) to change` (idempotente).
- `project:update-all conn2flow-site-local` (Lab): `paginas` +12, `publisher` +6, `publisher_pages` +6, `publisher_index` +6, `menus` +2, `templates` +4, 0 órfãos.
- HTTP 200 em `/docs/`, `/docs/guides/`, `/docs/reference/`, nas 3 docs e em `/en/docs/...`. `/docs/llms.txt` também em 200. Sem marcador cru no HTML, e `&#64;[[item#url]]&#64;` exibido no exemplo.
- `page:inspect` (Playwright): 0 erros de console. Callouts, sumário, destaque da página atual e índice com cards ("Exibindo 2 de 2") conferidos por screenshot em pt-br e en.
