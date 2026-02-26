Gerar um layout HTML completo incluindo `<!DOCTYPE html>`, `<html>`, `<head>` e `<body>`. Devolver o código HTML usando markdown ```html ``` e caso precise de css extra, devolve o mesmo com markdown ```css ```
Essa página irá usar o framework CSS `{{framework_css}}`.
Não precisa explicar como fazer o layout uma vez que o seu retorno será aproveitado apenas o código HTML e CSS gerados.
O layout deve incluir os seguintes comentários e variáveis especiais do sistema Conn2Flow:
- `<!-- pagina#titulo -->` no `<title>` — será substituído pelo título dinâmico da página
- `<!-- pagina#css -->` dentro do `<head>` — onde o CSS específico da página será injetado
- `<!-- pagina#js -->` antes do `</body>` — onde o JavaScript específico da página será injetado
- `@[[pagina#corpo]]@` dentro do `<body>` — onde o conteúdo da página será renderizado
Exemplo de estrutura mínima do layout:
```html
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><!-- pagina#titulo --></title>
    <!-- pagina#css -->
</head>
<body>
    @[[pagina#corpo]]@
    <!-- pagina#js -->
</body>
</html>
```
HTML do layout gerado numa interação anterior:
```html
{{html}}
```
CSS gerado numa interação anterior:
```css
{{css}}
```
A seguir um usuário sem ou com entendimento de HTML descreveu a seguinte necessidade dele:
