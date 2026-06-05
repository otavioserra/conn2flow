Gerar uma página HTML apenas a parte interna do <body> contendo uma GALERIA DE IMAGENS do site (carrossel, slider ou grade). Essa página será usada como um TEMPLATE no qual um motor renderiza dinamicamente uma LISTA de imagens curadas pelo usuário.

REGRA OBRIGATÓRIA DE REPETIÇÃO DE IMAGENS:
Todo o trecho do HTML que representa UMA imagem da galeria (ex: uma `<figure>`, um slide, um card) DEVE estar envelopado entre os marcadores de loop abaixo:

```
<!-- item < -->
...HTML de UMA imagem, contendo os placeholders @[[item#nome_da_variavel]]@...
<!-- item > -->
```

Em runtime, o motor irá repetir o conteúdo entre `<!-- item < -->` e `<!-- item > -->` uma vez para cada imagem curada, substituindo cada placeholder pelo valor real. Variáveis fora desse bloco NÃO são repetidas (servem para o contêiner da galeria, setas, paginação, etc).

Caso o usuário deseje exibir uma mensagem quando a galeria não tiver imagens, envolva esse trecho no bloco opcional:

```
<!-- no-item < -->
...HTML exibido quando a galeria estiver vazia...
<!-- no-item > -->
```

CONTROLES OPCIONAIS (CARROSSEL/SLIDER):
O contêiner principal da galeria deve ter a classe `conn2flow-gallery` e expor os atributos de comportamento usando as variáveis globais (substituídas por `true`/`false`/número em runtime):

```
<section class="conn2flow-gallery" data-autoplay="[[autoplay]]" data-speed="[[autoplay_speed]]" data-loop="[[loop]]">
```

Os slides devem ficar dentro de um contêiner com a classe `gallery-slides-wrapper`, e cada slide com a classe `gallery-slide`.

Setas de navegação (exibidas apenas quando o usuário ativar `show_arrows`): envolva-as no bloco condicional, usando as classes `gallery-prev` e `gallery-next`:

```
<!-- controls-arrows < -->
<button type="button" class="gallery-prev">‹</button>
<button type="button" class="gallery-next">›</button>
<!-- controls-arrows > -->
```

Pontinhos de paginação (exibidos apenas quando o usuário ativar `show_dots`): envolva o contêiner `gallery-dots` no bloco condicional e, DENTRO dele, use o bloco de repetição `dot-item` (um pontinho por imagem). Cada pontinho deve ter a classe `gallery-dot`, o atributo `data-index="[[dot#index]]"` e a classe ativa `[[dot#active-class]]` (preenchida automaticamente no primeiro pontinho):

```
<!-- controls-dots < -->
<div class="gallery-dots">
    <!-- dot-item < -->
    <button type="button" class="gallery-dot [[dot#active-class]]" data-index="[[dot#index]]"></button>
    <!-- dot-item > -->
</div>
<!-- controls-dots > -->
```

REGRA DE FORMATAÇÃO DE PLACEHOLDERS:
- Use sempre o formato `[[item#nome_da_variavel]]` (item é literal/estático; nome_da_variavel é dinâmico).
- Use a versão sem `@` para o retorno gerado por você — o pipeline interno converte para `@[[item#nome_da_variavel]]@` automaticamente ao salvar.

VARIÁVEIS DISPONÍVEIS PARA CADA IMAGEM:
- `[[item#img-src]]` — endereço público da imagem (use no atributo `src` da tag `<img>`)
- `[[item#caminho]]` — caminho relativo do arquivo original
- `[[item#nome]]` — nome do arquivo (use em `alt`/`title`)
- `[[item#legenda]]` — legenda personalizada da imagem

VARIÁVEIS GLOBAIS (CONTROLES, fora do bloco `item`):
- `[[show_arrows]]` — exibir setas (`true`/`false`)
- `[[show_dots]]` — exibir pontinhos (`true`/`false`)
- `[[autoplay]]` — slide automático (`true`/`false`)
- `[[autoplay_speed]]` — tempo de transição em milissegundos (número)
- `[[loop]]` — loop infinito (`true`/`false`)

Você pode criar textos fictícios além desses marcadores conforme orientado pelo usuário, mas se ele pedir algo mais direto, mantenha somente os placeholders. Devolver o código HTML usando markdown ```html ``` e caso precise de CSS extra, devolva o mesmo com markdown ```css ```.

Essa página irá usar o framework CSS `{{framework_css}}`. Usar a tag `<section class="conn2flow-gallery"></section>` para o contêiner principal da galeria conforme o contexto do pedido.

Não precisa explicar como fazer a página uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados e processado por rotina técnica transparente ao usuário final.

Todas as sessões geradas que não existam antes devem ter um marcador incremental <NUMBER> da sessão atual. Caso seja uma modificação, manter o valor da incrementação e modificar o conteúdo. Crie um título simples <TITLE> para cada sessão e modifique a mesma na sessão conforme contexto de cada sessão. Colocar esse título no atributo `data-title`:
Exemplo de criação de uma sessão:
<section class="conn2flow-gallery" data-id="<NUMBER>" data-title="<TITLE>">
HTML gerado por você (incluindo o bloco <!-- item < --> ... <!-- item > --> de repetição)
</section>
HTML gerado numa interação anterior: 
```html
{{html}}
```
CSS gerado numa interação anterior: 
```css
{{css}}
```
Variáveis disponíveis para incluir no template formatação (cada linha é um campo do item disponível):
[variables] // Lista de variáveis abaixo uma por linha (1-n)
nome_da_variavel

Variáveis disponíveis para inclusão:
```variables
{{variables}}
```
As variáveis enviadas acima devem ser usadas dentro do bloco de repetição `<!-- item < --> ... <!-- item > -->` (variáveis de imagem) ou no contêiner/controles (variáveis globais). O `[[item#img-src]]` deve ir no atributo `src` da imagem; a `[[item#legenda]]` no texto descritivo. Caso receba algum HTML já existente nessa interação, mantenha a ordem das variáveis conforme explicado, ou siga uma nova ordem se o usuário pedir explicitamente. Procure criar um template organizado e visualmente agradável conforme o tipo de galeria pedido (grade, carrossel, slider, mosaico, etc.) e o HTML enviado. Por outro lado, caso o usuário peça algo contraditório a essas instruções, priorize sempre o pedido do usuário.

A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele:
