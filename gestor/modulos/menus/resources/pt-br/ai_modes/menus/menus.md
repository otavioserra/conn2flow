Gerar uma página HTML apenas a parte interna do <body> contendo um MENU DE NAVEGAÇÃO do site. Essa página será usada como um TEMPLATE no qual um motor renderiza dinamicamente uma LISTA de itens de menu (links para páginas do site) curados pelo usuário.

REGRA OBRIGATÓRIA DE REPETIÇÃO DE ITENS:
Todo o trecho do HTML que representa UM item do menu (ex: um `<li>`, um link de navbar, uma coluna de rodapé) DEVE estar envelopado entre os marcadores de loop abaixo:

```
<!-- item < -->
...HTML de UM item de menu, contendo os placeholders @[[item#nome_da_variavel]]@...
<!-- item > -->
```

Em runtime, o motor irá repetir o conteúdo entre `<!-- item < -->` e `<!-- item > -->` uma vez para cada item curado do menu, substituindo cada placeholder `@[[item#nome_da_variavel]]@` pelo valor real do campo correspondente da página. Variáveis fora desse bloco NÃO são repetidas (servem para o contêiner do menu, logotipo, cabeçalho, etc).

Caso o usuário deseje exibir uma mensagem quando o menu não tiver itens, envolva esse trecho no bloco opcional:

```
<!-- no-item < -->
...HTML exibido quando o menu não tiver itens...
<!-- no-item > -->
```

REGRA DE FORMATAÇÃO DE PLACEHOLDERS:
- Use sempre o formato `[[item#nome_da_variavel]]` (item é literal/estático; nome_da_variavel é dinâmico).
- Use a versão sem `@` para o retorno gerado por você — o pipeline interno converte para `@[[item#nome_da_variavel]]@` automaticamente ao salvar.

VARIÁVEIS DISPONÍVEIS PARA ITENS DE MENU:
- `[[item#label]]` — rótulo/nome da página (texto do link)
- `[[item#url]]` — endereço da página (use no atributo `href` do link)
- `[[item#slug]]` — identificador textual da página (opcional)

Você pode criar textos fictícios além desses marcadores conforme orientado pelo usuário, mas se ele pedir algo mais direto, mantenha somente os placeholders. Devolver o código HTML usando markdown ```html ``` e caso precise de CSS extra, devolva o mesmo com markdown ```css ```.

Essa página irá usar o framework CSS `{{framework_css}}`. Usar a tag `<nav></nav>` para o contêiner principal do menu conforme o contexto do pedido.

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
Variáveis disponíveis para incluir no template formatação (cada linha é um campo do item disponível):
[variables] // Lista de variáveis abaixo uma por linha (1-n)
nome_da_variavel

Variáveis disponíveis para inclusão:
```variables
{{variables}}
```
As variáveis enviadas acima devem ser usadas dentro do bloco de repetição `<!-- item < --> ... <!-- item > -->` no template. O `[[item#label]]` deve ser o texto visível do link de cada item; o `[[item#url]]` deve ir no atributo `href` do link principal do item. Caso receba algum HTML já existente nessa interação, mantenha a ordem das variáveis conforme explicado, ou siga uma nova ordem se o usuário pedir explicitamente. Procure criar um template organizado e visualmente agradável conforme o tipo de menu pedido (navbar horizontal, lateral, rodapé, dropdown, breadcrumb, mobile, etc.) e o HTML enviado. Por outro lado, caso o usuário peça algo contraditório a essas instruções, priorize sempre o pedido do usuário.

A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele:
