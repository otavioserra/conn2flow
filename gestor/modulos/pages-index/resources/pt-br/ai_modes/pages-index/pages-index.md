Gerar uma página HTML apenas a parte interna do <body> contendo um ÍNDICE DE PUBLICAÇÕES (Publisher Index). Essa página será usada como um TEMPLATE no qual o motor renderiza dinamicamente uma LISTA PAGINADA de itens (publicações) vindos de um publicador associado (notícias, artigos, lives, notas, etc.), com busca textual, ordenação e botão "Carregar mais" em tempo de execução.

CONTÊINER RAIZ OBRIGATÓRIO:
Todo o índice DEVE ser envolvido por um elemento raiz com a classe `conn2flow-pages-index` e os atributos de dados abaixo (preenchidos automaticamente em runtime pelas variáveis globais). O script público do widget usa essa classe e esses atributos para a interação dinâmica:

```
<section class="conn2flow-pages-index" data-grupo-slug="@[[grupo_slug]]@" data-publisher-id="@[[publisher_id]]@" data-items-per-page="@[[items_per_page]]@" data-ordenacao="@[[ordenacao]]@" data-page="1">
   ...controles + lista de itens...
</section>
```

REGRA OBRIGATÓRIA DE REPETIÇÃO DE ITENS:
Todo o trecho do HTML que representa UM item (ex: um card, uma linha de lista) DEVE estar envelopado entre os marcadores de loop abaixo, dentro de um contêiner com a classe `pages-index-items`:

```
<div class="pages-index-items">
   <!-- item < -->
   ...HTML de UM item, contendo os placeholders @[[item#nome_da_variavel]]@...
   <!-- item > -->
   <!-- no-item < -->
   ...HTML exibido quando não houver publicações (estado vazio)...
   <!-- no-item > -->
</div>
```

Em runtime, o motor repete o conteúdo entre `<!-- item < -->` e `<!-- item > -->` uma vez para cada publicação da página atual, substituindo cada `@[[item#nome_da_variavel]]@` pelo valor real do campo. O bloco `<!-- no-item < --> ... <!-- no-item > -->` é exibido quando não há publicações (incluindo buscas sem resultado).

BLOCOS CONDICIONAIS DE CONTROLE (opcionais, recomendados):
Os blocos abaixo são exibidos ou removidos conforme as configurações do painel (barra de busca, ordenação e paginação). Inclua-os para um índice completo:

- Barra de busca — o input DEVE ter a classe `pages-index-search`:
```
<!-- search-input < -->
<input type="search" class="pages-index-search" placeholder="Buscar publicações...">
<!-- search-input > -->
```

- Ordenação — o select DEVE ter a classe `pages-index-sort` e as opções com os valores `date_desc`, `date_asc`, `title_asc`, `title_desc`:
```
<!-- sort-select < -->
<select class="pages-index-sort">
   <option value="date_desc">Mais recentes</option>
   <option value="date_asc">Mais antigas</option>
   <option value="title_asc">Título (A-Z)</option>
   <option value="title_desc">Título (Z-A)</option>
</select>
<!-- sort-select > -->
```

- Botão "Carregar mais" — DEVE ter a classe `pages-index-load-more`:
```
<!-- load-more < -->
<button type="button" class="pages-index-load-more">Carregar mais</button>
<!-- load-more > -->
```

REGRA DE FORMATAÇÃO DE PLACEHOLDERS:
- Use sempre o formato `[[item#nome_da_variavel]]` (item é literal/estático; nome_da_variavel é dinâmico).
- Use a versão sem `@` para o retorno gerado por você — o pipeline interno converte para `@[[item#nome_da_variavel]]@` automaticamente ao salvar.
- NÃO insira tipos de dado no nome da variável. O tipo vem do publicador associado e o mapeamento é resolvido em runtime.

EXEMPLOS DE VARIÁVEIS COMUNS:
- `[[item#titulo]]` — título da publicação
- `[[item#resumo]]` — subtítulo / resumo
- `[[item#imagem]]` — URL da imagem de capa
- `[[item#url]]` — URL da página final (use no `href` do link principal do item)
- `[[item#data]]` — data formatada
- `[[item#categoria]]`, `[[item#autor]]` — campos opcionais conforme o publicador

O usuário também pode solicitar e fornecer variáveis adicionais personalizadas. Nesses casos, gere-as seguindo o mesmo padrão `[[item#nome_da_variavel_adicional]]`.

Você pode criar textos e imagens fictícios além desses marcadores (estilo "Lorem ipsum") conforme orientado pelo usuário, mas se ele pedir algo mais direto, mantenha somente os placeholders. Devolver o código HTML usando markdown ```html ``` e caso precise de CSS extra, devolva o mesmo com markdown ```css ```.

Essa página irá usar o framework CSS `{{framework_css}}`. Usar a tag `<section></section>` como contêiner raiz do índice (a `<section>` raiz é a que recebe a classe `conn2flow-pages-index`).

Não precisa explicar como fazer a página uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados e processado por rotina técnica transparente ao usuário final.

Todas as sessões geradas que não existam antes devem ter um marcador incremental <NUMBER> da sessão atual. Caso seja uma modificação, manter o valor da incrementação e modificar o conteúdo. Crie um título simples <TITLE> para cada sessão e modifique a mesma na sessão conforme contexto de cada sessão. Colocar esse título no atributo `data-title` (mantendo também a classe `conn2flow-pages-index` e os data-attributes do contêiner raiz):
Exemplo de criação de uma sessão:
<section class="conn2flow-pages-index" data-id="<NUMBER>" data-title="<TITLE>" data-grupo-slug="@[[grupo_slug]]@" data-publisher-id="@[[publisher_id]]@" data-items-per-page="@[[items_per_page]]@" data-ordenacao="@[[ordenacao]]@" data-page="1">
HTML gerado por você (controles condicionais + bloco <!-- item < --> ... <!-- item > --> de repetição)
</section>
HTML gerado numa interação anterior: 
```html
{{html}}
```
CSS gerado numa interação anterior: 
```css
{{css}}
```
Variáveis disponíveis para incluir no template formatação (cada linha é um campo do item disponível no publicador associado):
[variables] // Lista de variáveis abaixo uma por linha (1-n)
nome_da_variavel

Variáveis disponíveis para inclusão:
```variables
{{variables}}
```
As variáveis enviadas acima devem ser usadas dentro do bloco de repetição `<!-- item < --> ... <!-- item > -->` no template, na ordem em que aparecem. A primeira variável deve aparecer no elemento de maior destaque visual do item (em geral o título); a segunda em destaque secundário (subtítulo/resumo); imagens devem usar o elemento `<img>`; URLs devem ser usadas no `href` do link principal do item. Caso receba algum HTML já existente nessa interação, mantenha a ordem das variáveis conforme explicado, ou siga uma nova ordem se o usuário pedir explicitamente. Caso no HTML já existam variáveis definidas fora da lista acima, apenas ignore as mesmas e não duplique nenhuma variável. Procure criar um template organizado e visualmente agradável conforme o tipo de índice pedido (lista, grade de cards, etc.) e o HTML enviado. Por outro lado, caso o usuário peça algo contraditório a essas instruções, priorize sempre o pedido do usuário.

A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele:
