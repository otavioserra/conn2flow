Gerar uma página HTML apenas a parte interna do <body>. Essa página será usada como um template com variáveis enviadas logo abaixo que serão populadas em outra rotina. As variáveis devem ser formatadas da seguinte forma: `[[publisher#tipo_de_dado#nome_da_variavel]]` (publisher é texto estático, tipo_de_dado e nome_da_variavel são dinâmicos) e incluídas no template. Você pode criar além desses marcadores em suas devidas sessões, textos e imagens fictícias conforme orientado pelo usuário. Caso usuário peça para ser mais direto ou algo similar, sempre coloque as variáveis de cada tipo de dado, mas não esses textos ou imagens fictícios. Devolver o código HTML usando markdown ```html ``` e caso precise de css extra, devolve o mesmo com markdown ```css ```
Essa página irá usar o framework CSS `{{framework_css}}`. Usar a tag `<section></section>` para cada sessão criada conforme o contexto do pedido.
Não precisa explicar como fazer a página uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados e processado por rotina técnica transparente ao usuário final.
Todas as sessões geradas que não existam antes devem ter um marcador incremental <NUMBER> da sessão atual. Caso seja uma modificação, manter o valor da incrementação e modificar o conteúdo. Crie um título simples <TITLE> para cada sessão e modifique a mesma na sessão conforme contexto de cada sessão. Colocar esse título no atributo `data-title`:
Exemplo de criação de uma sessão:
<section data-id="<NUMBER>" data-title="<TITLE>">
HTML gerado por você
</section>
HTML gerado numa interação anterior: 
```html
{{html}}
```
CSS gerado numa interação anterior: 
```css
{{css}}
```
Variáveis disponíveis para incluir no template (nome_da_variavel | tipo_de_dado).
Explicação breve de cada tipo de dado (tipo_de_dado | descrição):
```variables
{{variables}}
```
As variáveis enviadas acima devem ser usadas no template conforme o tipo de dado. Assim, analise o tipo de dado e escolha elementos HTML condizentes com o tipo. Bem como a ordem é importante para organização do template. As que aparecem primeiro na lista, devem ser usadas primeiro no template e subsequentemente as demais variáveis. Caso receba algum HTML já existente nessa interação, deve manter a ordem das variáveis conforme explicado ou se o usuário abaixo pedir outra ordem, seguir a ordem definida pelo usuário. Caso no HTML já existam variáveis definidas fora da lista acima, apenas ignore as mesmas e não duplique nenhuma variável. Procure criar um template organizado e visualmente agradável conforme o tipo de dado e o HTML enviado. Por outro lado, caso o usuário peça algo contraditório a essas instruções, priorize sempre o pedido do usuário.

A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele: