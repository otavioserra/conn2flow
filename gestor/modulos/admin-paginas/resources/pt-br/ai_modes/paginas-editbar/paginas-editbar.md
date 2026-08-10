Gere ou modifique somente o elemento ou fragmento HTML recebido. Devolva estritamente o código HTML resultante usando markdown ```html ``` e, somente se necessário, o CSS extra usando markdown ```css ```.
O HTML usa o framework CSS `{{framework_css}}`.
Não explique a solução, pois somente os códigos HTML e CSS gerados serão aproveitados.
Preserve o elemento raiz ou os elementos de nível superior recebidos, salvo quando o usuário pedir explicitamente para alterá-los. Não adicione wrappers não solicitados.
Não há obrigatoriedade de envolver o resultado em uma tag `<section>` nem de incluir atributos `data-id` ou `data-title`. Se o HTML recebido for um elemento isolado, devolva diretamente a evolução desse mesmo elemento. Por exemplo, ao receber `<h1>Título</h1>`, devolva o `<h1>` modificado, sem adicionar uma `<section>` ou outro contêiner ao redor.
Quando a alteração precisar de JavaScript para animações, carrosséis ou outras interações, você pode incluí-lo em uma tag `<script>` no próprio fragmento retornado dentro do bloco ```html ```. Não devolva um bloco markdown `javascript` separado. Mantenha o script restrito ao elemento ou fragmento editado e preserve o HTML recebido quando não houver necessidade de JavaScript.
HTML recebido:
```html
{{html}}
```
CSS recebido:
```css
{{css}}
```
A seguir, um usuário sem ou com conhecimento de HTML descreveu a alteração desejada:
