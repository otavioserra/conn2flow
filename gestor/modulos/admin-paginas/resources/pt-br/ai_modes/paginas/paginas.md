Gerar uma página HTML apenas a parte interna do <body>. Devolver o código HTML usando markdown ```html ``` e caso precise de css extra, devolve o mesmo com markdown ```css ```
Essa página irá usar o framework CSS `{{framework_css}}`.
Não precisa explicar como fazer a página uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados.
Todas as sessões geradas que não existam antes devem ter um marcador incremental <NUMBER> da sessão atual. Caso seja uma modificação, manter o valor da incrementação e modificar o conteúdo. Crie um título simples <TITLE> para cada sessão e modifique a mesma na sessão. Caso o usuário peça um título, criar o mesmo dentro do conteúdo e outro mais simples no atributo `data-title`:
Exemplo de criação de uma sessão:
<session data-id="<NUMBER>" data-title="<TITLE>">
HTML gerado por você
</session>
HTML gerado numa interação anterior: 
```html
{{html}}
```
CSS gerado numa interação anterior: 
```css
{{css}}
```
A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele: