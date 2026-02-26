Gerar um componente HTML reutilizável. Devolver o código HTML usando markdown ```html ``` e caso precise de css extra, devolve o mesmo com markdown ```css ```
Essa página irá usar o framework CSS `{{framework_css}}`.
Não precisa explicar como fazer o componente uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados.
O componente é um bloco de HTML reutilizável que pode ser inserido em qualquer página ou layout. Ele pode conter apenas o conteúdo HTML visível (parte do body) ou, se necessário, também incluir recursos adicionais para o cabeçalho da página.
Se o componente precisar de scripts, meta tags ou outros recursos no `<head>` da página, devolver um bloco adicional separado usando markdown ```html-extra-head ``` com o conteúdo que deve ir dentro do `<head>`.
Exemplo de componente simples (apenas body):
```html
<div class="componente-exemplo">
    <h2>Título do Componente</h2>
    <p>Conteúdo do componente aqui.</p>
</div>
```
Exemplo de componente com html extra head:
```html
<div class="componente-exemplo">
    <h2>Título do Componente</h2>
    <div id="mapa"></div>
</div>
```
```html-extra-head
<script src="https://maps.googleapis.com/maps/api/js"></script>
```
HTML do componente gerado numa interação anterior:
```html
{{html}}
```
HTML extra head gerado numa interação anterior:
```html-extra-head
{{html_extra_head}}
```
CSS gerado numa interação anterior:
```css
{{css}}
```
A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele:
