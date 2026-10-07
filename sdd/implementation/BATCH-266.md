# BATCH-266 — Aba Modelos vazia na inclusão e miniaturas cortadas na horizontal (linha 3.0)

- **Requisição:** [REQ-257](../human-requests/req-257.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.0` (branch `feat/req-256`, entregue na `3.0` e na `main`)
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://conn2flow.local/` (projeto `conn2flow-site-local`)

## Live Todo List

- [x] Causa da aba Modelos vazia nas telas de adicionar
- [x] Correção no editor (cliente e servidor), para todos os módulos
- [x] Causa do corte horizontal das miniaturas
- [x] Miniaturas refeitas em 4:3, com margem lateral em tudo
- [x] Testes, publicação no Lab da 3.0 e roteiro de navegador
- [ ] Homologação humana

## 1. Aba Modelos vazia nas telas de adicionar

**Causa.** A aba Modelos do editor pede os modelos filtrando pelo framework CSS do recurso. Nas telas com campo de framework (páginas, layouts, componentes) o valor vem do campo. Nos módulos sem esse campo (formulários, busca, menus, índices, destaques, galerias) o editor só sabe o framework depois que um modelo é carregado pelo seletor do módulo: na edição isso já aconteceu; na inclusão, não. Sem saber, o editor assumia `fomantic-ui`, e o servidor não achava nada, porque os modelos desses módulos são Tailwind.

**Correção** (em `html-editor`, vale para todos os módulos):

- cliente: a aba Modelos usa `frameworkCSSModelos()`, que devolve o framework quando ele é conhecido (campo na tela ou modelo já carregado) e vazio quando não é;
- servidor: `html_editor_ajax_templates_load()` com framework vazio lista os modelos do alvo em qualquer framework; com framework informado, ou sem o parâmetro (chamada antiga), filtra como antes;
- ao escolher um modelo pela aba numa tela sem campo de framework, o editor passa a registrar o framework do modelo, para a prévia usar o CSS certo.

## 2. Miniaturas cortadas na horizontal

**Causa.** O cartão de modelo mostra a imagem numa área 4:3 com recorte para preencher (`aspect-4/3` e `object-cover`). As miniaturas, novas e antigas, eram mais largas que isso (290:197): o painel cortava perto de 5% de cada lado. Só aparecia nos modelos com conteúdo perto da borda: os dois checkouts, o contato básico e as listas de produtos.

**Correção.**

- as miniaturas passam a nascer em 4:3 (foto de 1024 × 768, arquivo de 580 × 435);
- na largura, tudo cabe com 40 px de margem, inclusive as listas longas, que antes ficavam de borda a borda. Cortar embaixo continua aceito;
- as 184 miniaturas foram refeitas (120 do core, 64 do site). A maior tem 25 KB.

As 55 miniaturas antigas (modelos de página, 290 × 197) não foram refeitas e continuam perdendo um pouco das laterais no cartão, como sempre perderam.

## Arquivos

- `gestor/assets/interface/html-editor-interface.js` (e `.min.js`), `gestor/bibliotecas/html-editor.php`
- `gestor/assets/templates/images/{pt-br,en}/*.webp` (120 arquivos)
- `sdd/validation/req256/gerar-miniaturas.cjs`, `montar.py`, `req256-browser.cjs`, `README.md`
- `tests/Unit/PHP/HtmlEditorModelosInclusaoReq257Test.php`, `tests/Unit/PHP/TemplatesMiniaturasReq256Test.php`
- No site: `gestor/assets/templates/images/{pt-br,en}/*.webp` (64 arquivos)

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit do core na `3.0` (suíte completa) | 1.691 testes, sem falha |
| Vitest do core na `3.0` | 614 testes, sem falha |
| `assets:minify` e `project:update-all conn2flow-site-local` | código de saída 0 |
| Navegador, `req256-browser.cjs` no Lab da 3.0 | 24/24 |

O roteiro confere: a aba Modelos lista os modelos, todos com miniatura, em sete telas de adicionar (formulários, menus, índice e destaques do publicador, índice de páginas, galerias e busca); em formulários, escolher um modelo pela aba leva o HTML ao editor e registra `tailwindcss`; as telas com campo de framework continuam mostrando cinco modelos cada; e a miniatura aparece na edição de 12 modelos do cadastro.

Para o corte, montei os cinco casos apontados recortados em 4:3, como o cartão mostra, e conferi a imagem: aparecem inteiros na largura.

**Primeira rodada do roteiro: 23/24.** A falha era do roteiro, que usava `click` num botão que responde a `mouseup`.

## Limites

- Na inclusão, a aba lista modelos de todos os frameworks do alvo. Hoje cada um desses alvos só tem modelos Tailwind; se um alvo ganhar modelos nos dois frameworks, a lista da inclusão mostra os dois.
- Não exercitei a aba Modelos na tela de clonar nem nos módulos do site (loja, planos, apresentações).
- O cartão continua recortando o que for mais largo que 4:3: miniatura enviada à mão em outra proporção perde as laterais.
- Quem já abriu o painel pode ver a miniatura antiga do cache do navegador (mesmo endereço) até atualizar sem cache.

## Critérios de aceite

- [x] As telas de adicionar dos módulos com editor listam os modelos na aba Modelos.
- [x] Escolher um modelo pela aba, na inclusão, aplica HTML e CSS e a prévia usa o framework do modelo. *Conferido em formulários.*
- [x] Telas com campo de framework continuam filtrando pelo framework escolhido.
- [x] Contato básico, os dois checkouts e o índice de produtos aparecem inteiros na largura, no recorte do cartão. *Conferido na imagem recortada em 4:3, não numa captura do cartão no painel.*
- [x] PHPUnit e Vitest verdes; conferência de navegador no Lab da 3.0.
