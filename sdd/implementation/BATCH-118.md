# BATCH-118 — Findings restantes do review de 2026-08-15 (F2, F3, F4, F7–F10)

Origem: [REVIEW-2026-08-15-batches-111-112-115.md](../reviews/REVIEW-2026-08-15-batches-111-112-115.md)
Validação: [VALIDATION-CHECKLIST.md#batch-118](../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-17)

Continuação direta do BATCH-117, que já havia fechado **F1** e **F4c**. Este lote fecha os findings
restantes. Autorizado pelo Chefe em 2026-08-17.

---

## F2 — Descarte silencioso de sidecar em modo bundle · alta

Em modo bundle, `gestor_css_precompiled_ordenar()` devolve `page-precompiled` + `other` e descarta o
resto. Layout e dependências são descartados **por desenho** (o bundle já os compilou junto), mas
`resource-precompiled` é o bucket de quem **não declarou papel** — e é por aí que entra um recurso
escolhido em RUNTIME.

O caso medido: a lista de templates de uma rota é **dado, não código**
(`SELECT id, nome FROM templates WHERE target='clinical-search' AND status='A'`). Um template novo
com esse `target` fica selecionável pelo operador, nasce fora do bundle declarado em build-time e
renderiza sem estilo — sem erro em lugar nenhum.

**Implementado**: o descarte de `resource-precompiled` em modo bundle passa a ser registrado em
`log_disco(..., 'tailwind')`, com a rota e a contagem, uma vez por requisição. É a sugestão 1 do
review — a mais barata das três — e ataca o que o próprio review identificou como o problema
central: *"o descarte silencioso é o que impede o diagnóstico"*.

Não implementado (registrado como dívida): dependência declarada por `target` de template, em vez de
por `id`. Exige mudar o resolvedor de dependências do compilador e o contrato do metadado; com o log
no lugar, o defeito deixa de ser mudo, que era a urgência.

## F3 — Bundle é opt-in e o esquecimento é silencioso · média

Rota nova sob layout que usa `hidden`/`lg:flex` volta à concatenação de sidecars, a ordem global das
utilities se perde e a inversão desktop/mobile reaparece — sidebar sumindo no desktop, hambúrguer
aparecendo nele. O build não avisava, e o sintoma leva a suspeitar do markup, que está correto.

**Implementado**: `tailwind_recursos_layout_display_sensivel()` (pura) detecta, no CSS já compilado
de um layout, a combinação que produz o conflito — `display` sob `@media` **e** um `display`
incondicional concorrente (`.hidden`, `.flex`, `.block`, `.grid`). No fim do build, toda página sem
`tailwind_bundle` servida por um layout assim recebe aviso.

Calibração medida: os layouts do **core** não disparam (nenhum tem a combinação); os do lumix
`photon-admin` e `photon-public` disparam — e `photon-admin` é exatamente o layout onde o defeito foi
observado na Busca Clínica.

## F4 — `dependency-precompiled` sem emissor no core · baixa

**Correção ao texto do review**: ele descreve os dois pontos de `bibliotecas/gestor.php` como
"includes de template". Não são — estão dentro de `gestor_layout()`. O papel correto, portanto, não é
`dependency-precompiled` e sim **`layout-precompiled`**.

Isso torna o finding mais grave do que registrado: sem papel, o pré-compilado caía no default
`resource-precompiled` e ia para o **fim** da ordem, depois das dependências e da página. Como CSS de
layout carrega theme, base e Preflight, a cascata ficava invertida.

**Implementado**: os dois pontos declaram `css_precompiled_role => 'layout-precompiled'`.

## F7 — Heurística de página de confirmação não casa com o nome real · baixa

A regra cobria `…-confirmation` e `…/success`, mas as páginas do projeto se chamam
`contacts-success` — com hífen. Medido no sitemap gerado: `contacts-success/`,
`en/contacts-success/`, `subscription-checkout/error/` e `subscription-checkout/payment/` estavam
indexadas.

**Implementado**: `sitemap_caminho_nao_indexavel()` passa a tratar `confirmation`, `success`,
`error`, `failure` e `cancel` como sufixo (`-`, `_` ou `/`), e `payment`/`checkout`/`processing` como
segmento final **de caminho composto** — `payment/` sozinho na raiz pode ser conteúdo legítimo
("formas de pagamento") e continua indexável. A regra é de sufixo, então `success-stories/` também
continua.

Medido no `snapphoton-local`: o sitemap caiu de 36 para **31 URLs**, e as quatro telas acima saíram.

## F8 — `sitemap.xml` antigo continua na raiz · baixa

O req-112 moveu o arquivo para `assets/` porque a entrega pela raiz depende da regra `!-f` do
`.htaccess`, que varia por instalação — mas não removeu o antigo. Numa instalação onde o `!-f`
resolva primeiro, o arquivo velho passaria a ser servido para sempre, com 9 URLs e sem sinal nenhum.

**Implementado**: `sitemap_legado_remover()` roda a cada gravação, com uma trava — só apaga o que
`sitemap_conteudo_proprio()` reconhece como gerado por esta biblioteca. Um `sitemap.xml` posto à mão
pelo operador, ou um índice de sitemaps, é preservado e registrado no log.

Medido: o arquivo de 1.161 bytes da raiz do `snapphoton-local` foi removido na primeira geração.

## F9 — Não existe `robots.txt` · baixa

O `noindex` que o BATCH-111 emite só é visto **depois** do rastreio; o `robots.txt` é o único ponto
em que dá para barrar antes e declarar o sitemap.

**Implementado**: `sitemap_robots_montar()` (pura) e `sitemap_robots_gravar()`, acionadas junto da
geração completa. O arquivo vive em `assets/`, pela mesma decisão do sitemap (req-112) — verificado
por HTTP que `/robots.txt` resolve para `assets/robots.txt` com `text/plain`, pelo `default:` do
`arquivo-estatico.php`.

Os prefixos barrados saem das mesmas rotas que o sitemap exclui, preservando o acerto de desenho
registrado no review: rota nova entra ou sai dos dois de uma vez.

## F10 — Dedup do 301 sem escopo · baixa

`WHERE caminho='…'` sem página nem idioma. **Duas** consequências, não uma:

1. **Caminho reciclado aponta para a página errada.** `/promo/` liberado por X e depois assumido por
   Y: ao renomear Y, o `if(!$ja_existe)` pulava a gravação e `/promo/` continuava redirecionando para
   X — silenciosamente, porque o único `log_disco` do bloco cobria outro ramo.
2. **Só o primeiro idioma ganhava 301** (achado nesta rodada, não estava no review). `paginas_301`
   não tem coluna `language` e o `caminho` gravado é agnóstico, então a linha do pt-br bloqueava a do
   en para a MESMA página.

**Implementado**, nas duas pontas:

- `gestor_pagina_301_registrar()` na biblioteca `gestor` — dedup pelo par (`caminho`, `id_paginas`).
  Substitui o bloco que estava duplicado em `admin-paginas` e `publisher-pages`.
- `gestor_roteador_301()` deixou de usar `[0]`: varre os candidatos do mais recente para o mais antigo
  e para no primeiro que resolva para uma página ativa no idioma corrente — que é, por construção, a
  dona atual do caminho.

---

## Correções de homologação do BATCH-117 aplicadas nesta rodada

Reportadas pelo Chefe durante a implementação deste lote.

1. **Painel de Código não fechava ao clicar fora.** Só o clique na Editbar o fechava
   (`c2f-toolbar:ui-dismiss`). O painel passou ao padrão do `c2f-ai-panel` — overlay de tela cheia
   com backdrop —, e o clique no backdrop fecha. Um listener no documento permanece como rede de
   segurança.
2. **Painel nascia deslocado para a borda direita.** Estava ancorado às coordenadas do botão, que
   fica no canto direito da Editbar. Agora é **centralizado** (`margin: 7vh auto`), como o painel de
   IA. As coordenadas continuam na mensagem, por consistência com os demais painéis, mas são
   ignoradas.
3. **O salvo renderizava diferente do que se via na edição.** Regressão introduzida pelo próprio
   BATCH-117 e detalhada abaixo.

### A regressão do `@layer theme` — F1 batendo na própria captura

O BATCH-117 descartava `@layer theme` inteiro quando o baseline já tinha um. Só que **o theme do
layout contém apenas os tokens que o LAYOUT usa** — é o finding F1: o Tailwind v4 só emite a variável
que ele vê usada. Uma utility nova da página (`.text-3xl`, `.text-slate-300`) referencia
`var(--text-3xl)` e `var(--color-slate-300)`, que não estão lá; `var()` indefinida invalida a
declaração e a propriedade cai para o valor inicial.

Medido com Chromium real, comparando estilos computados entre "durante a edição" e "depois de
salvar": **13 de 21 elementos** divergiam — `font-size` 48px → 16px, `font-weight` 800 → 400,
`color` → preto.

**Correção**: dentro de `@layer theme` o corte passou a ser por **declaração**, não por camada
(`filterThemeRule`). Grava o token que falta, não regrava o que o build já entrega — resolvendo os
dois lados de uma vez. `@layer base` (o Preflight) continua decidido por camada: não depende do
conteúdo da página.

Nova medição após a correção: **0 de 21 elementos divergentes**; `css_compiled` de 3.695 bytes contra
8.774 do output completo (58% de economia).

---

## Fora de escopo, com motivo

- **Dependência de template por `target`** (sugestão 2 do F2): exige mudar o resolvedor de
  dependências e o contrato do metadado. Com o log do F2 no lugar, o defeito deixa de ser mudo.
- **`html-editor-publisher-simulation` sem `framework_css`** (achado da guarda F4c no BATCH-117):
  declarar o framework **não basta**. O componente é montado na página administrativa, cujo layout é
  `fomantic-ui` e não carrega `@layer theme`; recursos isolados compilam só utilities
  (`@reference` + `@import "tailwindcss/utilities.css"`), então as classes gerariam regras que
  referenciam tokens inexistentes na página — o mesmo F1 de novo. Resolver exige decidir como o
  painel administrativo recebe o tema do Tailwind, o que é uma mudança de arquitetura própria.
- **`sessao-com-2-colunas-fomantic-ui`** (o outro aviso da guarda): `font-light text-primary mb-8` —
  limítrofe, provável falso positivo. `mb-4`/`px-2` existem em Bootstrap e Tailwind e não são
  separáveis por regex; é por isso que o limiar é de duas utilities distintas.
