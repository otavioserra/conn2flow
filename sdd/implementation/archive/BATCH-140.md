# BATCH-140 — Seleção em lote no picker, overlay fixo nos modos compactos e tamanhos no galleries

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-137.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / usabilidade de módulos administrativos (`admin-arquivos`, `galleries`)

## Objetivo

Fechar três lacunas de ergonomia que aparecem juntas no mesmo fluxo — montar uma galeria com muitas
fotos: despachar vários arquivos de uma vez do picker, eliminar o tremor do grid ao passar o mouse
nos modos compactos e permitir encolher as miniaturas da lista curada.

## Slice aprovado

1. Botão `#c2f-pick-selected` na barra de seleção do `admin-arquivos`, visível apenas no modo picker
   (iframe) e apenas com ao menos um arquivo marcado, despachando o lote ao módulo consumidor.
2. `.c2f-actions` dos modos `medium` e `small` convertida em overlay absoluto sobre a miniatura, com
   as medidas do card idênticas com e sem o mouse sobreposto.
3. Seletor `#c2f-gallery-views` com três tamanhos de miniatura no `galleries`, preferência persistida
   em `localStorage` e restaurada na carga.

## Fora do escopo

- Alterar o contrato do canal `postMessage` consumido por `galleries`, `html-editor`,
  `html-editor-interface`, `interface-v2` e `dashboard.toolbar`.
- Alterar a listagem em modo `large` ou `list`, o upload, o CRUD de pastas ou a galeria modal.
- Alterar a estrutura da linha curada do `galleries` (handle, campos, painel de link) ou o Sortable.
- Fazer commit, push ou deploy remoto.

## Decisões de implementação

- **Uma mensagem por arquivo, não um array.** Os seis consumidores do canal leem um objeto único por
  evento (`JSON.parse(data.data)` seguido de `dados.tipo`). Despachar um array quebraria todos de uma
  vez; o laço mantém o contrato intacto e nenhum consumidor precisou mudar.
- **`estado.selecionados` passou a guardar o payload completo do item.** Antes guardava só
  `{caminho, tipo}`; o despacho precisa dos mesmos campos do envio individual e o item pode já não
  estar no DOM quando o clique acontece (busca, filtro ou nova página re-renderizam a lista sem
  limpar a seleção). `admin_arquivos_ajax_excluir()` lê apenas `caminho` e `tipo`, então os campos
  extras não afetam a exclusão em lote.
- **`pointer-events` fica em `none` no overlay e só é liberado nos botões.** Cobrir a miniatura com
  uma camada clicável mataria dois comportamentos existentes: clicar na miniatura para abrir a
  galeria/entrar na pasta e clicar no checkbox. Com o contêiner transparente a eventos, a área vazia
  continua entregando o clique para baixo.
- **`.c2f-check` subiu de `z-index: 3` para `5`.** O overlay é `z-index: 4` e cobre justamente o canto
  onde o checkbox mora — sem isso, marcar um item ficaria impossível exatamente enquanto o mouse
  está sobre o card, que é o único momento em que o overlay existe.
- **A marcação dos checkboxes é reaplicada a cada `renderLista()`.** Trocar de modo, filtrar ou
  paginar reconstrói o HTML e os checkboxes nasciam desmarcados enquanto a barra seguia dizendo
  "2 selecionados" — o despacho enviaria itens que o usuário já não via marcados. Descoberto na
  evidência visual do próprio lote.
- **Classe de modo no contêiner `#gallery-items`, não nas linhas.** `renderItems()` esvazia e
  reconstrói a lista a cada mudança; no contêiner o modo sobrevive sem nenhuma condicional por modo
  no `buildItemRow()`, e o Sortable.js segue operando sobre a mesma estrutura nos três tamanhos.
- **`galleriesNormalizarView()` erra para `large`.** A preferência vem do `localStorage`, que pode
  trazer valor de versão anterior, de outra aba ou editado à mão; uma classe inválida no contêiner
  deixaria a lista sem medida alguma.
- **Barra de ferramentas em flex, não `right floated`.** O `.ui.segment` não garante clearfix e o
  float sairia da caixa do segmento.

## Contrato de validação

- Lint: `node --check` nos dois JS, `php -l` nos dois PHP e parse de todos os JSON tocados.
- Testes focados novos cobrindo o filtro de pastas, a regra de visibilidade do botão, o payload do
  despacho e a normalização dos modos.
- Suítes PHPUnit e Vitest sem regressão.
- Validação visual autônoma no ambiente local (Chrome headless + cookies de sessão), provando sob
  interação real: geometria do card estável no hover, botão oculto fora do iframe, despacho de uma
  mensagem por arquivo com pastas de fora, e os três tamanhos com persistência.

## Evidências

- Lint: `node --check` **2/2**, `php -l` **2/2**, JSON **4/4** válidos.
- Testes focados novos: `admin-arquivos.batch-pick.test.js` **8/8** e `galleries.view-modes.test.js`
  **4/4** — total **12/12**.
- Suítes: Vitest **357/357** (24 arquivos) e PHPUnit **776/776** (3.326 asserções), sem regressão.
- `c2f resources:sync`: 2.660 recursos, **0 erros**; 6 variáveis novas (`pick-selected` ×2 idiomas e
  `view-{large,medium,small}-title` ×2 idiomas) compiladas em `VariaveisData.json`.
- `c2f manager:update-all`: pipeline completo aprovado; banco local com `paginas +2 ~254` e
  `variaveis +300 ~8`, **0 órfãos**.
- Validação visual autônoma (script Playwright efêmero + `c2f auth:cookie`, executado localmente): **19/19**
  checagens aprovadas, console **sem erros** em todas as telas. Destaques medidos:
  - modo `medium`: card `208x174` idêntico com e sem hover, mesmos `x`/`y` em todos os cards;
  - modo `small`: card `120x98` idêntico com e sem hover;
  - overlay `position: absolute`, `opacity` 1 ↔ 0, `pointer-events: none` fora do hover, `z-index` 4;
  - `.c2f-check` em `z-index: 5`, acima do overlay;
  - fora do iframe, com 1 item marcado e a barra visível, o botão permanece `display: none`;
  - no picker, com "Selecionar Todos" (1 pasta + 1 arquivo), **1** mensagem despachada — a pasta
    ficou de fora — seleção zerada e botão recolhido;
  - payload: `{id, caminho, imgSrc, nome, data, tipo}` com o MIME em `tipo`, igual ao envio individual;
  - marcação preservada na troca de modo (2 marcados antes e depois, contador `2`);
  - miniaturas `140px` / `85px` / `50px`, tooltips vindas do sistema de variáveis, `type="button"`;
  - preferência sobrevive ao reload e valor corrompido cai em `view-large`.
- Screenshots (recapturados após a troca de rótulo): `temp/req-137-picker-selecao.png`, `temp/req-137-overlay-medium.png`,
  `temp/req-137-admin-arquivos-picker.png`, `temp/req-137-galleries-views.png`.
- Ambiente restaurado ao final: `DEVELOPMENT_ENV=false` confirmado por `c2f env:status`.
- `git diff --check`: limpo.
- Review findings-first: **1 finding próprio, corrigido no lote** — a restauração da marcação lia
  `estado.selecionados[caminho]` por truthiness, e um arquivo chamado `constructor` ou `toString`
  acharia a propriedade herdada de `Object.prototype` e nasceria marcado sem nunca ter sido
  selecionado. Trocado por `Object.prototype.hasOwnProperty.call()` e coberto por teste.
- Artefatos regenerados no escopo: `VariaveisData.json` (+54 linhas, apenas as 6 variáveis novas) e
  `schema-metadata.json` (somente o `generated_at`). Nada alheio ao lote entrou.
- Memória de execução: **4.147 bytes / 52 linhas**; podada de 4 para 3 tarefas para caber no teto.
- Nível 1 respeitado: nenhum commit, push ou deploy remoto.

## Ajuste pedido pela chefia durante a implementação

- Rótulo `pick-selected` alterado de `"Selecionar selecionados"` (texto literal do intake) para
  **`"Incluir Selecionados"`** / **`"Add Selected"`**. Motivo dado: o botão inclui os arquivos na
  lista de imagens do módulo consumidor, e "incluir" descreve o efeito melhor do que "selecionar" —
  que já é o nome da etapa anterior ("Selecionar Todos"). De quebra pareia com o vizinho
  `"Excluir Selecionados"`: mesma caixa e o par incluir/excluir fica evidente na barra.
- Revalidado após a troca: `resources:sync` 0 erros, `variaveis ~2` no banco local e **19/19**
  checagens visuais, com o rótulo lido do DOM como `"Incluir Selecionados"`.

## Retorno de homologação — item 3 refeito (a grade que faltava)

A primeira entrega do item 3 **encolhia apenas a miniatura**, mantendo uma foto por linha: a caixa
`.gallery-item` continuava ocupando a largura inteira da área disponível, então trocar de modo quase
não mudava a densidade da tela. A chefia apontou o erro de interpretação, e o correto é a **CAIXA**
encolher para as fotos se acomodarem lado a lado, em linhas e colunas.

- `#gallery-items` nos modos compactos passa a `flex-direction: row` + `flex-wrap: wrap`; a
  `.gallery-item` vira card vertical com largura fracionária (`25%` no médio, `12.5%` no pequeno,
  reduzindo para 3 e 5 colunas em telas médias e 2 e 3 em telas estreitas).
- **Ordem de leitura**: a ordem visual da grade é a própria ordem do DOM — esquerda para a direita,
  descendo ao fim da linha. Como o `onEnd` do Sortable já relê a ordem física do DOM, a reordenação
  continua correta **sem nenhuma condicional por modo**. Verificado por arraste real, inclusive
  atravessando a quebra de linha.
- **Controles em overlay**, como no `admin-arquivos`: handle e remover saem do fluxo
  (`position: absolute`) sobre a miniatura e aparecem no hover, então a grade não estremece.
  `.sortable-chosen` mantém os controles à vista durante o arraste, quando o cursor deixa o card.
- **Modo pequeno esconde legenda e painel de link**: não cabem numa caixa de 147px. Nada se perde —
  a serialização lê do array `items` em memória, não do DOM; basta voltar ao modo grande para editar.
- Medido no runtime: caixa **1222x158** (grande, 1 por linha) → **300x190** (médio, **4 por linha**)
  → **147x97** (pequeno, **8 por linha**).

### Evidências da segunda rodada

- Roteiro de grade (Playwright, executado localmente): **12/12** checagens, console limpo. Cobre a densidade por modo, o
  encolhimento da caixa, a ordem de leitura, a estabilidade da grade no hover e o **arraste real**
  (`f1,f2,f3,f4,…` → `f2,f3,f4,f1,…`).
- Roteiro de ordem serializada (Playwright, executado localmente): **2/2**. Prova o que o DOM sozinho não prova — interceptando o
  payload enviado ao servidor, a ordem **serializada** (`f8,f1,f2,f3,f4,f5,f6,f7`) é idêntica à
  ordem exibida depois de arrastar o último item para o começo, atravessando a quebra de linha.
- Guardas de regressão no `galleries.view-modes.test.js` (**8/8**): exigem `flex-wrap` nos modos
  compactos, proíbem `flex-wrap` no modo grande, e travam o overlay em `position: absolute`.
- Suítes após a rodada: Vitest **367/367**, PHPUnit **784/784**.
- Screenshots: `temp/req-137-galleries-grid-medium.png`, `temp/req-137-galleries-grid-small.png`.

## Achados fora do escopo — promovidos para `req-138` / BATCH-141 (já corrigidos lá)

- **`admin-arquivos` não devolve MIME real, e sim `<tipo>/<extensão>` concatenado**
  ([admin-arquivos.php:183](../../../gestor/modulos/admin-arquivos/admin-arquivos.php#L183)):
  `'mime' => $tipo . '/' . strtolower(pathinfo($nome, PATHINFO_EXTENSION))`, com `$tipo` limitado a
  `image|video|audio|file`. Produz `image/jpg` (o real é `image/jpeg`), `file/json` (`application/json`)
  e `file/pdf` (`application/pdf`); `image/png` e `video/mp4` acertam por coincidência.
  - **Por que não quebra hoje**: todos os consumidores testam apenas o PREFIXO (`/^image\//`), que
    está sempre correto para imagens. O sufixo nunca é comparado.
  - **Onde já incomoda**: o valor é exibido cru ao usuário em `.widgetImage-tipo`
    ([interface-v2.js:480](../../../gestor/assets/interface-v2/interface-v2.js#L480)) e em
    `._html-editor-imagepick-tipo` ([html-editor-interface.js:1214](../../../gestor/assets/interface/html-editor-interface.js#L1214)).
  - **Risco futuro**: qualquer consumidor que compare MIME exato, ou que persista esse valor como
    `content-type` de um asset, falha em silêncio.
  - Anterior a este lote: o envio individual (`.c2f-select`) já usava exatamente o mesmo campo. O
    despacho em lote só replicou o contrato vigente.
- **Comparação sempre falsa em `interface-v2.js`**
  ([linha 471](../../../gestor/assets/interface-v2/interface-v2.js#L471)):
  `if (dados.tipo?.match(/image\//) === 'image/')`. `String.prototype.match` sem flag `g` devolve um
  **array** (`['image/']`), nunca uma string, então `=== 'image/'` é sempre falso e o ramo de sucesso
  nunca executa — o `interface-v2` cai no `else` e alerta "Not an image" mesmo com imagem válida.
  Defeito pré-existente, em arquivo fora do escopo do req-137.
