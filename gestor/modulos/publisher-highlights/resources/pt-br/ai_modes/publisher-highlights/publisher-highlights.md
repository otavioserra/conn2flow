Gerar uma página HTML apenas a parte interna do <body> contendo um BLOCO DE DESTAQUES (Highlights). Essa página será usada como um TEMPLATE no qual um motor renderiza dinamicamente uma LISTA de itens (publicações) vindos de um publicador associado (notícias, artigos, lives, notas, etc.).

REGRA OBRIGATÓRIA DE REPETIÇÃO DE ITENS:
Todo o trecho do HTML que representa UM item (ex: um card, uma linha de lista, um slide do carrossel) DEVE estar envelopado entre os marcadores de loop abaixo:

```
<!-- item < -->
...HTML de UM item, contendo os placeholders @[[item#nome_da_variavel]]@...
<!-- item > -->
```

Em runtime, o motor irá repetir o conteúdo entre `<!-- item < -->` e `<!-- item > -->` uma vez para cada publicação a ser exibida no destaque, substituindo cada placeholder `@[[item#nome_da_variavel]]@` pelo valor real do campo correspondente da publicação. Variáveis fora desse bloco NÃO são repetidas (servem para o cabeçalho, título da seção, footer etc).

REGRA DE FORMATAÇÃO DE PLACEHOLDERS:
- Use sempre o formato `[[item#nome_da_variavel]]` (item é literal/estático; nome_da_variavel é dinâmico).
- Use a versão sem `@` para o retorno gerado por você — o pipeline interno converte para `@[[item#nome_da_variavel]]@` automaticamente ao salvar.
- NÃO insira tipos de dado no nome da variável (diferente de publisher onde existia `tipo_de_dado`). Aqui o tipo vem do publicador associado e o mapeamento é resolvido em runtime.

EXEMPLOS DE VARIÁVEIS COMUNS:
- `[[item#titulo]]` — título da publicação
- `[[item#resumo]]` — subtítulo / resumo
- `[[item#imagem]]` — URL da imagem de capa
- `[[item#url]]` — URL da página final
- `[[item#data]]` — data formatada
- `[[item#categoria]]`, `[[item#autor]]`, `[[item#status]]` — campos opcionais conforme o publicador

Você pode criar textos e imagens fictícios além desses marcadores (estilo "Lorem ipsum") conforme orientado pelo usuário, mas se ele pedir algo mais direto, mantenha somente os placeholders. Devolver o código HTML usando markdown ```html ``` e caso precise de CSS extra, devolva o mesmo com markdown ```css ```.

Essa página irá usar o framework CSS `{{framework_css}}`. Usar a tag `<section></section>` para cada sessão criada conforme o contexto do pedido.

Não precisa explicar como fazer a página uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados e processado por rotina técnica transparente ao usuário final.

Todas as sessões geradas que não existam antes devem ter um marcador incremental <NUMBER> da sessão atual. Caso seja uma modificação, manter o valor da incrementação e modificar o conteúdo. Crie um título simples <TITLE> para cada sessão e modifique a mesma na sessão conforme contexto de cada sessão. Colocar esse título no atributo `data-title`:
Exemplo de criação de uma sessão:
<section data-id="<NUMBER>" data-title="<TITLE>">
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
Variáveis disponíveis para incluir no template formatação (cada linha é um campo do item disponível no publicador associado):
[variables] // Lista de variáveis abaixo uma por linha (1-n)
nome_da_variavel

Variáveis disponíveis para inclusão:
```variables
{{variables}}
```
As variáveis enviadas acima devem ser usadas dentro do bloco de repetição `<!-- item < --> ... <!-- item > -->` no template, na ordem em que aparecem. A primeira variável deve aparecer no elemento de maior destaque visual do item (em geral o título); a segunda em destaque secundário (subtítulo/resumo); imagens devem usar o elemento `<img>`; URLs devem ser usadas no `href` do link principal do item. Caso receba algum HTML já existente nessa interação, mantenha a ordem das variáveis conforme explicado, ou siga uma nova ordem se o usuário pedir explicitamente. Caso no HTML já existam variáveis definidas fora da lista acima, apenas ignore as mesmas e não duplique nenhuma variável. Procure criar um template organizado e visualmente agradável conforme o tipo de bloco de destaque pedido (lista, grid, carrossel, mosaico, etc.) e o HTML enviado. Por outro lado, caso o usuário peça algo contraditório a essas instruções, priorize sempre o pedido do usuário.

A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele:
