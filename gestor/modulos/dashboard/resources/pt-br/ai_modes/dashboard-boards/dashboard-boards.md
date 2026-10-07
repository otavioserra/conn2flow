Gerar o ARRANJO DE UMA LOUSA do Dashboard em JSON. O resultado NÃO é HTML: é um único objeto JSON, sem texto antes ou depois, que descreve os itens da lousa. O sistema cria a lousa a partir dele.

FORMATO:

```
{
  "modo": "grade",
  "widgets": [ ...itens... ]
}
```

- `modo`: `"grade"` (malha de 12 colunas, itens em ordem; recomendado, porque se ajusta a qualquer largura) ou `"lousa"` (posição livre em células).
- `widgets`: lista de até 40 itens, na ordem em que aparecem.

ITEM QUE É UM WIDGET:

```
{"id": "menus", "name": "Menus", "registro_id": "", "width": 4, "height_px": 320, "options": {"title": "Navegação"}}
```

- `id`: o tipo do widget, exatamente como no cadastro de widgets (por exemplo `menus`, `pages-index`, `publisher-index`, `publisher-highlights`, `forms`, `forms-search`, `galleries`). Não invente tipos.
- `registro_id`: deixe vazio. Ao criar a lousa, o sistema usa o primeiro registro ativo daquele tipo na instalação; tipo sem registro fica de fora.
- `width`: largura em colunas, de 2 a 12 na grade (até 24 na lousa). `height_px`: altura em pixels, de 120 a 960.

ITEM QUE É UM OBJETO LIVRE (texto, forma, imagem, ícone ou botão):

```
{"id": "objeto", "name": "Objeto", "width": 12, "height_px": 120, "options": {"header": false, "frame": false},
 "object": {"type": "text", "text": "Título", "size": 44, "weight": 700, "align": "center", "color": "#0f172a"}}
```

- `id` é sempre `"objeto"`.
- `object.type`: `text`, `shape`, `image`, `icon` ou `button`.
- Texto e botão: `text` (até 2.000 caracteres), `size` (10 a 160), `weight` (400 ou 700), `align` (`left`, `center`, `right`), `color` (hexadecimal de seis dígitos).
- Botão: `href` (endereço http(s) ou caminho do site começando com `/`), `fill` (cor do botão), `newTab` (true ou false).
- Forma: `shape` (`rect`, `rounded`, `circle`, `line`) e `fill`. Ícone: `icon` (nome de ícone do Lucide) e `color`. Imagem: `src` (caminho de um arquivo do painel, começando com `/`), `fit` (`cover` ou `contain`) e `alt`.

OPÇÕES DE QUALQUER ITEM (`options`, todas opcionais):
`header` (mostrar o cabeçalho), `frame` (mostrar a moldura), `title` (título próprio, até 80 caracteres), `background` (cor de fundo hexadecimal), `padding` (`none`, `small`, `medium`, `large`), `hide` (esconder abaixo de uma largura: `sm`, `md` ou `lg`).

POSIÇÃO NO MODO LOUSA (opcional): `x` (coluna, de 0 a 23) e `y` (linha de 20 px, a partir de 0). Na grade, não use.

REGRAS:
- Somente JSON válido: aspas duplas, sem comentários, sem vírgula sobrando.
- Na grade, pense em linhas que somam 12 colunas (12; 8 + 4; 6 + 6; 4 + 4 + 4).
- Comece com um texto de título e termine, quando fizer sentido, com um botão de chamada.
- Não ponha HTML, script nem endereços de outros sites em imagens.
