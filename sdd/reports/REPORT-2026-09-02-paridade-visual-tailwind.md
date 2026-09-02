# Relatório de sessão — Paridade visual entre o editor e a página publicada

- **Data**: 2026-09-02
- **Intakes**: [req-158](../human-requests/req-158.md), [req-159](../human-requests/req-159.md), [req-160](../human-requests/req-160.md)
- **Lotes**: [BATCH-158](../implementation/BATCH-158.md), [BATCH-160](../implementation/BATCH-160.md), [BATCH-161](../implementation/BATCH-161.md)
- **Estado**: os três `complete`, homologados pelo operador
- **Validação final**: PHPUnit **1.096/1.096** (7.547 asserções) · Vitest **408/408**

---

## 1. O essencial

O operador relatou que a mesma página renderizava diferente em três lugares: a rota pública, a aba de
pré-visualização e o Editor HTML Visual. A investigação encontrou **seis defeitos independentes**,
não um. Todos foram corrigidos e homologados.

A causa mais cara de achar não estava no CSS de nenhuma página: **o editor filtrava a captura de CSS
contra um conjunto de folhas que o runtime público nunca recebe**. Isso apagava, em silêncio,
exatamente as regras que vinham dos templates inseridos — o mecanismo que o operador usa para montar
páginas.

| Indicador | Antes | Depois |
| --- | ---: | ---: |
| Defeitos distintos | — | **6 corrigidos** |
| Classes sem regra na página publicada | 99 | **5** |
| Bytes de CSS capturados no preview | 0 | **38.990** |
| Tags de CDN no runtime | 24 | **0** |

As 5 classes remanescentes não são defeito: duas são falso positivo do próprio script de medição
(`className` de SVG não é string) e duas são hooks de JavaScript.

---

## 2. Os três lotes

| Lote | Escopo | Estado |
| --- | --- | --- |
| `BATCH-158` | Isolamento do iframe do editor visual + eliminação de CDN em runtime | complete |
| `BATCH-160` | Animação `animate-fade-in-up` que nunca existiu nos templates | complete |
| `BATCH-161` | O CSS que não chegava ao runtime — quatro causas encadeadas | complete |

---

## 3. BATCH-158 — Duas contaminações, dois mecanismos

O BATCH-156 havia removido a folha do Fomantic do *pré-visualizador* e deixado a mesma tag, escrita à
mão, nos dois ramos do *editor visual*. A assimetria produziu exatamente o "ambiente intermediário"
descrito no intake.

Medido em Chromium sobre a página real, são **duas contaminações distintas**, e cada uma exige um
mecanismo próprio:

| Contaminação | Efeito medido (editor × preview) | Correção |
| --- | --- | --- |
| Folha sem camada vence utilities em `@layer` | título 72 → **24px**; peso 900 → 700; texto do CTA branco → `rgb(65,131,196)` | `@import … layer(c2f-editor-chrome)` |
| `html{font-size:14px}` redefine a unidade `rem` | tudo × **14/16 = 0,875**: 128 → 112px, 48 → 42px, 16 → 14px | restaurar a raiz, fora de camada |

A segunda quase passou. **Nenhuma cascade layer a corrige**: o Tailwind v4 dimensiona espaçamento,
tipografia e raio em `rem`, e não existe regra do Tailwind disputando `html { font-size }` — a do
Fomantic vence por ausência de concorrente. Aplicando só a camada, a paridade parava em 63px.

> **Por que a folha não podia simplesmente sair**
> O `#html-editor-modal` é um modal Fomantic, e `html-editor.js` chama `.modal()` sobre ele.
> Removê-la, como no preview, deixaria o modal de edição de texto, imagem e código sem estilo. Ela
> precisava ficar — e parar de reger o conteúdo.

### 3.1 Eliminação de CDN — pedido do operador durante a execução

O BATCH-146 tirou o gestor do CDN, mas varreu as tags montadas no **PHP**. As que o **cliente** monta
escaparam do inventário: iframes por `srcdoc`, Editbar e previews de widget traziam host e versão
escritos à mão, paralelos ao registro.

| Arquivo | Tags removidas |
| --- | --- |
| `html-editor-interface.js` | 18 — Fomantic CSS/JS, jQuery, **14 do CodeMirror**, Quill, Tailwind Browser |
| `dashboard.toolbar.js` (Editbar) | 3 — jQuery, CodeMirror, Tailwind Browser |
| 5 módulos de preview de widget | 1 cada — Tailwind Browser |

Dois achados no caminho:

- `addon/edit/closetag.js` e `closebrackets.js` eram usados **apenas** pelo iframe do editor e não
  estavam no registro. Migrar sem incluí-los os deixaria caindo no CDN em silêncio — que é como
  `assets_externos_url()` degrada quando o arquivo local não existe.
- **`@tailwindcss/browser` nunca esteve no registro**: era o último ponto preso a `unpkg.com`, com a
  versão repetida em sete lugares.

---

## 4. BATCH-160 — Uma utility que nunca existiu

Quatro templates de sessão aplicavam `animate-fade-in-up` sem que o token existisse em contrato
nenhum. No Tailwind v4, `animate-<nome>` só é gerada quando há `--animate-<nome>` no `@theme`; sem
ele, a utility é descartada em silêncio na compilação.

Medido em Chromium, sobre o HTML e o sidecar reais, antes da correção:

```
sessao-contato-mapa  .animate-fade-in-up   animation-name: none     0s    <- morto
sessao-com-abas      .animate-fade-in      animation-name: fadeIn   0.5s  <- funciona
sessao-destaque      .animate-bounce       animation-name: bounce   1s    <- funciona
sessao-destaque      .animate-pulse        animation-name: pulse    2s    <- funciona
```

O contraste deu a causa mais rápido que ler o compilador: `sessao-com-abas` anima porque **traz o
`@keyframes` num `<style>` embutido no próprio HTML**. Os quatro afetados não traziam nada.

Alcance: `sessao-contato-mapa`, `sessao-contato-mapa-alternativo`, `sessao-galeria-masonry` e
`sessao-newsletter-minimalista` — 7 usos por idioma, **14 no total**.

> **Severidade baixa — e o motivo importa**
> Nenhum elemento afetado tinha `opacity-0` junto, então todos apareciam e apenas entravam estáticos.
> Fosse o contrário, o conteúdo ficaria **invisível para sempre** — a animação que o traria de volta
> não existe. É esse modo de falha que justificou corrigir a utility em vez de apenas remover a
> classe.

Corrigido no contrato central (`system-input.css`) por decisão do operador, e não em `<style>` por
template: `tailwind_recursos_browser_contract()` **deriva** o `browser-contract.css` desse arquivo,
então a definição alcança o build offline, o editor visual e a Editbar de uma vez.

**Resultado: 14/14 animações vivas**, contra 0/14, sem regressão nas nativas.

### 4.1 Falsos positivos descartados na auditoria

Registrado para não ser reaberto:

- `tab-btn`, `tab-content`, `sidebar-item`, `c-header-nav-btn` e afins aparecem sem regra compilada
  porque **não são utilities**: são hooks de JavaScript ou estão em `<style>` local.
- 20 templates sem miniatura: layouts e componentes **não declaram** o campo `thumbnail` no
  manifesto — só as sessões declaram, porque só elas aparecem no seletor visual de inserção.
- 36 Tailwind com sidecar / 36 Fomantic sem: correto, Fomantic não usa o pipeline do Tailwind.

---

## 5. BATCH-161 — Quatro causas para o mesmo sintoma

Depois do BATCH-158 os dois editores ficaram idênticos, mas a página publicada continuava divergindo.
Aqui o operador foi decisivo ao insistir: *"a página é só a evidência, a semente está no
repositório"*. Estava.

### Causa 1 — o filtro da captura usava um baseline que o runtime não recebe

- **Sintoma**: 8 utilities aplicadas sem regra na publicada — `border-b-2`, `bg-gradient-to-t`,
  `list-none`, `ml-auto`, `-z-10`, `rotate-3`, `translate-x-1/2`, `via-transparent`.
- **Causa**: o BATCH-156 passou a somar o sidecar do template ao baseline, para o editor renderizar
  certo. Com isso o sidecar entrou no conjunto contra o qual `HtmlEditorCssCapture.extract()`
  **filtra**, e as regras do template deixaram de ser gravadas em `css_compiled`.
- **Correção**: baseline emitido em duas folhas — `data-c2f-tailwind-role="baseline"` (o que o runtime
  entrega, alvo do filtro) e `data-c2f-css-role="session-overlay"` (o sidecar da sessão, fora dele).

A cadeia foi medida atravessando os dois repositórios de semente:

| Camada | `.border-b-2` |
| --- | --- |
| Sidecar do template — **core** `gestor/resources/pt-br/templates/sessao-com-abas-alternativo/` | **existe** |
| Sidecar do layout — **projeto** `conn2flow-site/…/layout-conn2flow-site/` | não existe |
| `paginas.css_compiled` (banco) | **não existe** |

A regra existia na origem e sumia no runtime.

### Causa 2 — a captura de CSS só existia no editor visual

- **Sintoma**: página criada do zero pelo `adicionar/` salvava `css_compiled` **vazio**. O CRUD
  parecia perfeito; a publicada saía sem CSS.
- **Causa**: `HtmlEditorCssCapture` vive em `html-editor.js`, injetado só no iframe do editor visual.
  No preview a captura falhava sempre — `motivo: 'sem-motor'` — e, pela regra de preservação
  (req-117), mantinha o valor anterior: vazio numa página nova.
- **Correção**: motor injetado no preview com `__c2fHtmlEditorNoAutoInit` (BATCH-075), o mesmo
  mecanismo da Editbar — dá a API de captura sem ativar a UI de edição sobre o body.

```
medido numa página criada do zero
classes aplicadas   360
bytes gravados      0   →   38.990
```

### Causa 3 — nada garantia que o CSS fosse gerado antes de salvar

Formulação do operador: se houve modificação no HTML e o CSS não foi gerado, o sistema não pode
deixar salvar. A implementação seguiu a segunda opção que ele propôs — **gerar em vez de bloquear**:
o editor troca para a aba de visualização, aguarda a captura *avisar* que terminou (`aoConcluir`, não
um tempo fixo) e reenvia o formulário.

```
HTML alterado com .rotate-45, sem revisualizar
antes de salvar   38.990  não cobre
depois            39.020  cobre     <- gerado antes do envio
```

Três cuidados: só age quando o HTML mudou de fato; não trava página sem classe Tailwind, onde CSS
vazio é legítimo; e se a captura falhar mesmo assim, o save prossegue — travar a tela seria pior que
preservar o valor anterior. A interceptação usa *capture phase* com `stopImmediatePropagation`,
porque o handler de submit do `formulario.js` é registrado por jQuery na fase de bubble.

### Causa 4 — o CSS autoral do layout nunca chegava ao editor

- **Sintoma**: fundo preto na publicada, claro nos editores. Era o sintoma relatado desde a primeira
  mensagem da sessão.
- **Causa**: `html_editor_css_precompiled_baseline()` lia do layout apenas `css_precompiled`. A
  coluna `css` — autoria do layout, com `body { background: #000; }` — nunca era lida.
- **Correção**: emitida como `data-c2f-css-role="layout-authored"`, **fora** do baseline (não é
  derivado; incluí-lo ali faria a captura descartar regras) e na posição que o runtime usa.

O layout pode ser **trocado no select do CRUD a qualquer momento**, e o baseline entregue na abertura
deixa de valer nesse instante. A rota `html-editor-layout-css` devolve as duas camadas do layout
escolhido; o observador do select recarrega o baseline preservando o que a sessão já acumulou
(sidecars de template inseridos) e remonta a visualização.

---

## 6. Uma correção que o próprio acervo barrou

A primeira tentativa para a Causa 1 foi persistir o baseline em `paginas.css_precompiled`, com campo
próprio no formulário e alteração em cinco módulos. `CssProcedenciaGravacaoTest` reprovou:

> `css_precompiled` é DERIVADO: só o compilador e a herança do template o escrevem. Um módulo
> gravando o valor cru do POST devolveria ao operador a capacidade de descolar CSS de HTML.

É o contrato do CR-002 / BATCH-144. Tudo foi revertido — os cinco módulos, o componente nos dois
idiomas, a biblioteca — e a correção refeita sem tocar nele. **O teste que barrou a abordagem errada
continua verde**, e a solução final ficou menor: uma mudança, num arquivo, anterior a todos os
módulos.

---

## 7. Validação

### Ciclo ponta a ponta no Lab

| Documento | Classes | Sem regra | Galeria sem regra | Fundo do body |
| --- | ---: | ---: | ---: | --- |
| Página publicada | 379 | 5 | **0** | `rgb(0, 0, 0)` |
| Preview (editor) | 362 | 5 | **0** | `rgb(0, 0, 0)` |

Mesmo número de classes sem regra nos dois e o mesmo fundo — antes eram **99 contra 5**.

A galeria masonry, reportada como "zoada", ficou com **0 classes sem regra**: `columns-1`,
`md:columns-2`, `lg:columns-3` e `break-inside-avoid` estavam entre as ausentes, e sem elas o masonry
desmonta. O HTML da galeria já era idêntico nos dois — a divergência era só CSS.

### Suítes e pipeline

- **PHPUnit 1.096/1.096** — 7.547 asserções, 4 skips esperados
- **Vitest 408/408** — 28 arquivos
- `resources:sync`: 2.844 recursos, 0 problemas · `assets:minify --verificar`: 0 desatualizados
- Guardas novas **validadas por mutação**: reintroduzindo cada defeito, o teste correspondente falha
  nomeando o artefato e a classe; restaurado, volta a passar

> **Sobre a validação por mutação**
> Um teste que nunca falhou não prova nada. Para cada guarda criada, o defeito foi reintroduzido
> deliberadamente e confirmou-se que a suíte o detecta. Isso vale para o overlay de sessão, para a
> regra de animação e para o isolamento do chrome do editor.

---

## 8. Pendências e ressalvas

### Contrato de projeto substitui o do core, não estende

`tailwind_recursos_input_central()` usa o `contents/tailwindcss/input.css` do projeto quando ele
existe, **substituindo** o do core. Um projeto com contrato próprio não herda tokens novos do core
enquanto não os declarar.

Medido em compilação real: herdar por `@import` explícito funciona hoje e **não tira soberania do
projeto** — o `@theme` do Tailwind v4 é aditivo com precedência do último. A recomendação registrada
foi manter a substituição como padrão e usar herança opt-in, evitando que o arquivo do projeto deixe
de dizer a verdade sobre o tema. Tornar isso automático exigiria change request.

Cuidado medido ao herdar: escrever o próprio `@import "tailwindcss"` **além** do import do core
duplica o framework — 224 KB e dois preflights, contra 5 KB e um. Quem importa o core não escreve o
seu.

### Cópias manuais no Lab

Durante a validação, `html-editor-interface.js`, seu `.min.js` e `html-editor.php` foram copiados por
`scp` para o tenant de teste, com autorização do operador. Cópia manual não deixa rastro no
manifesto — convém confirmar que o `project:deploy` posterior as sobrescreveu.

### Colisão de numeração entre agentes

Durante a sessão, um agente concorrente sobrescreveu `req-156.md` com escopo alheio (CI/CD,
BATCH-159). O intake original foi recuperado do histórico do git (`git show <commit>:<caminho>`) e
recadastrado como **req-158**, sem alterar o arquivo nem o lote do outro agente. O batch de
implementação seguiu sendo o BATCH-158.

### Estado do repositório

Nenhum commit, push ou deploy foi executado. A árvore tem ~20 arquivos de autoria alterados, mais
derivados (`.min.js`, `Data.json`, sidecars), prontos para revisão.

---

## 9. O que a sessão ensinou

Registrado em [MEMORIA-ENGENHARIA-EXECUCAO.md](../MEMORIA-ENGENHARIA-EXECUCAO.md):

- **Uma folha sem camada vence qualquer coisa em `@layer`**, independentemente da ordem. Quando dois
  ambientes divergem, compare o `<head>` que cada um monta antes de procurar no conteúdo.
- **Fator uniforme de 0,875 num A/B significa que alguém mexeu na raiz do `rem`** — e nenhuma camada
  corrige isso, porque não há regra do Tailwind disputando `html { font-size }`.
- **Defeito de transição não aparece medindo estado.** O BATCH-158 mediu 15/15 e 18/18 com o conteúdo
  já estabilizado e passou por cima do BATCH-161: ele nasce no ato de inserir o template.
- **Quando um teste antigo reprova sua abordagem, leia a decisão que ele protege** antes de pensar em
  contorná-lo.
- **Corrigir um lado da cascata pode quebrar o outro.** O BATCH-156 acertou o editor e, sem saber,
  apagou CSS do runtime.
- **O banco do container pode ser espelho desatualizado do que a VM serve** — 3 seções locais contra
  9 na VM. Medir o ambiente real antes de concluir sobre produção.
- **`className` de SVG não é string** (`SVGAnimatedString`): varredura de classes pelo DOM gera falso
  positivo se não tratar isso.
- **A publicada é `página + layout`; os editores, só a página.** Confirmar a origem de cada classe
  antes de concluir de onde vem a falta.
