Gere somente o HTML interno do `<body>` para um WIDGET DE BUSCA. O componente usa envio GET, possui um campo textual de busca intrínseco e pode repetir campos extras de filtro configurados pelo usuário.

CONTRATO OBRIGATÓRIO:
- Use `<form class="conn2flow-search-form ..." data-form-id="[[form_id]]" action="[[form_action]]" method="get">`.
- Não crie configurações, campos ou textos relacionados a e-mail, submissão AJAX de formulário, reCAPTCHA ou redirects de sucesso/erro.
- O campo principal não pertence ao loop de campos extras. Envolva exatamente um `<input type="search">` com os marcadores:
```html
<!-- input-search < -->
<input type="search" placeholder="Buscar">
<!-- input-search > -->
```
- Não defina outro `name` para esse input; o renderer sempre injeta `name="search"`, `required`, ids e atributos ARIA.
- Inclua uma caixa flutuante vazia, dentro de um ancestral com `position: relative`, usando:
```html
<!-- results-box < -->
<div class="forms-search-results ..."></div>
<!-- results-box > -->
```
- Posicione o botão `type="submit"` fora das duas células. Clique e Enter sem item selecionado navegam para `[[form_action]]`; a ação vazia usa `pages-index-search/`.

CAMPOS EXTRAS:
Todo o HTML de UM filtro extra deve ficar entre `<!-- item < -->` e `<!-- item > -->`. O motor repete esse trecho para cada campo configurado. Use apenas:
- `[[item#label]]`, `[[item#name]]`, `[[item#placeholder]]`, `[[item#type]]`, `[[item#required]]`, `[[item#options]]`.
- Blocos condicionais permitidos: `type-input`, `type-textarea`, `type-select`, `type-radio` e `type-checkbox`, sempre delimitados por `<!-- nome < -->` e `<!-- nome > -->`.
- `[[item#options]]` só pode aparecer em select, radio e checkbox.

O autocomplete é ligado automaticamente pelo módulo: debounce de 300 ms, mínimo de 3 caracteres, lotes de 30, botão "Carregar mais", navegação por setas/Enter, destaque com `<mark>` e cache local. Crie apenas a estrutura visual; não inclua JavaScript próprio nem resultados fictícios dentro de `results-box`.

Use placeholders sem `@`; o pipeline converte para o formato de armazenamento. Não invente variáveis. Use classes compatíveis com `{{framework_css}}` e evite dependências externas.

Retorne apenas blocos markdown `html` e, se indispensável, `css`. Preserve os contratos ao editar conteúdo existente.

HTML anterior:
```html
{{html}}
```
CSS anterior:
```css
{{css}}
```
Variáveis extras disponíveis:
```variables
{{variables}}
```

Necessidade descrita pelo usuário:
