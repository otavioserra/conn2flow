# BATCH-161 — O CSS dos templates inseridos não chegava ao runtime público

- **Status**: complete
- **Intake**: `sdd/human-requests/req-160.md`
- **Data de abertura**: 2026-09-02
- **Modo**: supervisionado

## Escopo aprovado

1. Fazer as regras dos templates inseridos chegarem à página publicada.
2. Cobrir todos os módulos que usam a biblioteca do editor HTML.
3. Guarda automatizada sobre o CICLO (inserir → salvar → publicar), não sobre o estado.

## Diagnóstico medido

O BATCH-158 igualou os três ambientes com o conteúdo já estabilizado (15/15 e 18/18). Este defeito
nasce na **transição de inserir um template** — e por isso não apareceu lá.

Cadeia medida, atravessando os dois repositórios de semente:

| Camada | `.border-b-2` (usada por `sessao-com-abas-alternativo`) |
| --- | --- |
| Sidecar do template — CORE `gestor/resources/pt-br/templates/…` | **existe** |
| Sidecar do layout — PROJETO `conn2flow-site/…/layout-conn2flow-site/` | não existe |
| `paginas.css_compiled` (banco) | **não existe** |

A regra existe na origem e some no runtime. Cobertura na página publicada: 424 classes aplicadas,
**13 sem regra**; no iframe de preview, 405 e 5. As **8 de diferença** são exatamente as fornecidas
por sidecar de template: `-z-10`, `bg-gradient-to-t`, `border-b-2`, `list-none`, `ml-auto`,
`rotate-3`, `translate-x-1/2`, `via-transparent`.

**Causa**: o editor filtrava a captura contra um baseline que o runtime nunca recebe.

O BATCH-156 passou a somar o sidecar do template ao baseline para o editor renderizar certo. Com
isso o sidecar entrou no conjunto contra o qual `HtmlEditorCssCapture.extract()` filtra — e as regras
do template deixaram de ser gravadas em `css_compiled`. Como o runtime monta
`layout.css_precompiled + css_compiled`, e o sidecar não está em nenhum dos dois, elas sumiam.

## Caminho descartado, e por quê

A primeira tentativa foi persistir o baseline acumulado em `paginas.css_precompiled`, com campo
próprio no formulário. **`CssProcedenciaGravacaoTest` reprovou**, e a guarda está certa:

> `css_precompiled` é DERIVADO: só o compilador e a herança do template o escrevem. Um módulo
> gravando o valor cru do POST devolveria ao operador a capacidade de descolar CSS de HTML.

É o contrato do CR-002 / BATCH-144. A correção foi refeita sem tocar nele — o teste que barrou a
abordagem errada é de um lote anterior e continua verde.

## Implementação

`tailwindPreviewIncludes()` passou a emitir o baseline em **duas folhas**:

- `data-c2f-tailwind-role="baseline"` — só o que o runtime entrega (layout + `css_precompiled` do
  recurso, ambos do banco). É contra esta folha que a captura filtra.
- `data-c2f-css-role="session-overlay"` — o sidecar acumulado na sessão.

`baselineStyles()` seleciona `[data-c2f-tailwind-role="baseline"]` e `[data-tailwind-role]`; o
overlay não tem nenhuma das duas marcas, então sai do filtro. O iframe mantém a mesma cascata (as
duas folhas estão no documento, na mesma ordem), e a captura volta a gravar em `css_compiled` o que
o runtime não tem. `css_precompiled` segue derivado e escrito só pelo compilador.

Uma mudança, num arquivo — nenhum módulo alterado. O levantamento dos 12 consumidores de
`html_editor_componente()` ficou registrado na req-160 e não foi necessário: a correção é anterior a
todos eles, no ponto onde o baseline é montado.

## Três defeitos a mais, achados na homologação

A separação de folhas acima resolveu o CSS dos templates, mas o operador reportou que a página
publicada continuava divergindo. A investigação sobre os documentos que ele capturou (publicada,
preview e editor visual lado a lado) revelou mais três causas independentes.

### 1. A captura só existia no editor visual (`motivo: 'sem-motor'`)

`HtmlEditorCssCapture` vive em `html-editor.js`, injetado apenas no iframe do editor visual.
`updateCSSCompiled()` é chamado também pelo preview, onde falhava **sempre** — e, pela regra de
preservação (req-117), mantinha o valor anterior: vazio numa página nova.

Quem criava a página pelo `adicionar/`, montava com os modelos, conferia no preview e salvava
gravava `css_compiled` **VAZIO**. Medido numa página criada do zero: **360 classes aplicadas, 0 byte
gravado** — e o CRUD parecia perfeito, porque lá o Tailwind Browser compila em runtime.

Corrigido injetando o motor no preview com `__c2fHtmlEditorNoAutoInit` (BATCH-075), o mesmo
mecanismo da Editbar: a API de captura passa a existir sem ativar a UI de edição sobre o body.
Medido: **0 → 38.990 bytes**, e o aviso `sem-motor` desapareceu.

### 2. Nada garantia que o CSS fosse gerado antes de salvar

Proposta do operador. Em vez de recusar o save, o editor **gera e então envia**: detecta que o HTML
mudou depois da captura, troca para a aba de visualização, aguarda a captura **avisar** que terminou
(`aoConcluir`, não um tempo fixo) e reenvia o formulário.

Três cuidados: só age quando o HTML realmente mudou (comparação com `htmlEditorCssCompiledOrigem`);
não trava página sem classe Tailwind, onde CSS vazio é legítimo; e se a captura falhar mesmo assim,
o save prossegue — preservar o valor anterior já é o comportamento seguro, e travar a tela seria
pior. A interceptação usa capture phase com `stopImmediatePropagation`, porque o handler de submit
do `formulario.js` é registrado por jQuery na fase de bubble.

Validado no CRUD real: HTML alterado com `rotate-45` → CSS passou de 38.990 (sem cobrir) para
**39.020 cobrindo a classe nova**, antes do envio.

### 3. O CSS AUTORAL do layout não chegava ao editor

`html_editor_css_precompiled_baseline()` lê do layout apenas `css_precompiled`. A coluna `css` — a
autoria do layout — nunca era lida: o editor montava a cascata do layout pela metade.

O `layout-conn2flow-site` trazia `body { background: #000; }`. A página publicada saía com fundo
preto e o editor com fundo claro — toda seção de fundo transparente divergia. Foi o sintoma que o
operador relatou desde o início ("o fundo está transparente na publicada e branco nos editores").

`html_editor_layout_css_autoral()` passa a ler essa coluna, e a folha é emitida como
`data-c2f-css-role="layout-authored"` — **fora** do baseline (não é derivado, e incluí-lo ali faria a
captura descartar regras) e na posição que o runtime usa (depois do pré-compilado).

**O layout pode ser trocado no select do CRUD a qualquer momento**, e o baseline entregue na abertura
deixa de valer nesse instante. A rota `html-editor-layout-css` devolve as duas camadas do layout
escolhido; o observador do select recarrega o baseline preservando o que a sessão já acumulou
(sidecars de template inseridos) e remonta a visualização.

## Evidências

- Guarda nova (3 testes) **validada por mutação**: devolvendo a marca de baseline ao overlay, o teste
  falha com `expected 'data-c2f-tailwind-role="baseline">…' not to contain '.border-b-2'`.
- `CssProcedenciaGravacaoTest` 6/6 — o contrato de autoria x derivado permanece intacto.
- PHPUnit completo: **1.096/1.096**, 7.547 asserções, 4 skips esperados.
- Vitest completo: **397/397** (394 antes; +3).
- `resources:sync`: 2.844 recursos, 0 problemas. `assets:minify --verificar`: 0 desatualizados.

### Ciclo ponta a ponta no Lab (após os três defeitos acima)

| documento | classes | sem regra | galeria sem regra | fundo do body |
| --- | --- | --- | --- | --- |
| publicada | 379 | 5 | **0** | `rgb(0, 0, 0)` |
| preview (editor) | 362 | 5 | **0** | `rgb(0, 0, 0)` |

Mesmo número de classes sem regra nos dois, e o mesmo fundo — antes eram 99 na publicada contra 5 no
preview. A galeria masonry, que o operador reportou "zoada", ficou com **0 classes sem regra**:
`columns-1`, `md:columns-2`, `lg:columns-3` e `break-inside-avoid` estavam entre as ausentes, e sem
elas o masonry desmonta. O HTML da galeria já era idêntico nos dois — a divergência era só CSS.

As 5 restantes não são defeito: duas são falso positivo do próprio script de medição (`className` de
SVG não é string), `tab-btn-alt` e `tab-content-alt` são hooks de JavaScript, e `animate-fade-in-up`
é o BATCH-160, cujo `system-input.css` ainda não tinha chegado ao Lab naquela medição.

- Vitest após as guardas dos três defeitos: **408/408** (23 no arquivo de paridade).
- Homologado pelo operador em página nova criada do zero, com o sistema atualizado.

## Gates residuais

- **Homologação visual concluída**: o operador testou e homologou o comportamento visual no CRUD autenticado e na renderização dos templates.
- A página `teste-de-pagina` salva e renderizada com as novas correções exibiu paridade visual completa.

## Restrições

- Nenhum commit, push, release ou deploy sem autorização do Humano-no-Loop.
