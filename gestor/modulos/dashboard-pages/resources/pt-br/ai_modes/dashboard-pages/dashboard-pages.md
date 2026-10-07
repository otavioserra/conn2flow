Gerar o HTML de uma PÁGINA DE LOUSA: apenas a parte interna do <body>, que serve de moldura para uma lousa do Dashboard (um conjunto de widgets e objetos montado pelo usuário). O sistema coloca a lousa pronta dentro dessa moldura; você NÃO gera os widgets nem o conteúdo da lousa.

LUGAR DA LOUSA (OBRIGATÓRIO, UMA VEZ SÓ):
Escreva o marcador abaixo exatamente uma vez, sozinho, no ponto em que a lousa deve aparecer. Ao salvar, o sistema troca esse marcador pela lousa escolhida.

```
[[lousa#widget]]
```

- Não coloque o marcador dentro de um atributo, de um link, de um parágrafo ou de um título.
- Dê à lousa um contêiner com largura: ela ocupa toda a largura do elemento em que estiver. Para conteúdo centrado use algo como `max-width: 1280px; margin: 0 auto`.
- Não defina altura fixa nem `overflow: hidden` no contêiner da lousa: a altura vem dos itens.

TÍTULO DA PÁGINA (OPCIONAL, RECOMENDADO):
Use o marcador `[[lousa#titulo]]` onde o título deve aparecer. O texto vem do painel (título próprio ou o nome da página). Envolva TODO o trecho que só faz sentido com o título entre os marcadores de bloco abaixo; o painel tem um controle que desliga o título e remove esse trecho inteiro.

```
<!-- lousa-titulo < -->
<h1 class="minha-pagina-titulo">[[lousa#titulo]]</h1>
<!-- lousa-titulo > -->
```

O QUE VOCÊ PODE ACRESCENTAR EM VOLTA DA LOUSA:
Faixa de abertura com rótulo e frase de apoio, navegação simples (links de âncora ou para páginas do site), chamada com botão, texto de rodapé, faixas de cor. Tudo isso é HTML comum, editável depois no editor.

REGRAS DE FORMATAÇÃO:
- Use os marcadores sem `@` (`[[lousa#widget]]`, `[[lousa#titulo]]`). Variáveis do sistema também vão sem `@`, por exemplo `[[pagina#url-raiz]]` no início de um link interno; o sistema converte ao salvar.
- Não use `<html>`, `<head>`, `<body>`, `<script>` nem `<iframe>`.
- Não invente outros marcadores `[[lousa#...]]`: só existem `widget` e `titulo`.
- A página é montada dentro do layout do site (cabeçalho e rodapé do site já existem): não repita o cabeçalho nem o rodapé do site.

ESTILO:
- Prefira classes próprias com um prefixo único (por exemplo `minha-pagina-`) e o CSS correspondente no campo de CSS. Não estilize as classes que começam com `c2f-lousa`, que pertencem à lousa.
- A página deve funcionar de 360 px até telas largas, sem rolagem lateral. Abaixo de 640 px a lousa vira uma coluna.
- Texto com contraste suficiente sobre o fundo escolhido; o layout do site pode ser claro ou escuro, então defina cor de fundo e de texto juntos quando usar uma faixa colorida.

EXEMPLO MÍNIMO:

```
<section class="minha-pagina">
<!-- lousa-titulo < -->
<h1 class="minha-pagina-titulo">[[lousa#titulo]]</h1>
<!-- lousa-titulo > -->
[[lousa#widget]]
</section>
```
