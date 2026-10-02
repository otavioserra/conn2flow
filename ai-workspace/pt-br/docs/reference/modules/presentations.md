---
title: "Módulo presentations"
description: "Apresentações em slides: o autor escreve as seções e o widget monta a navegação."
section: reference
module: presentations
sources:
  - gestor/modulos/presentations/presentations.php
  - gestor/modulos/presentations/presentations.js
  - gestor/modulos/presentations/presentations.widget.php
  - gestor/modulos/presentations/presentations.widget.js
  - gestor/modulos/presentations/presentations.json
  - gestor/modulos/presentations/resources
  - gestor/db/migrations/20261002110000_create_presentations_and_cookie_consent_tables.php
verified_at: ce9ba7ac
---

# Módulo `presentations`

Transforma um HTML com seções em uma apresentação navegável. Cada `<section data-slide>` é um slide. Pontos, setas, contador, barra de progresso, teclado, toque, tela cheia, escala em telas pequenas e impressão saem do widget.

## Como usar

Abra `presentations/adicionar/`, dê um nome, escolha o modelo `presentations-deck` e escreva os slides no editor HTML. Para criar um slide, copie uma seção:

```html
<section data-slide class="flex-col items-center justify-center p-6 md:p-12">
    <div class="w-full max-w-5xl mx-auto">
        <h2 class="text-5xl font-black">Título do slide</h2>
    </div>
</section>
```

Não escreva `display` na seção (nem a classe `flex`): o CSS do modelo mostra só o slide ativo, já como flex.

### Quadro de slides

A seção "Slides" da tela mostra um cartão por slide e reescreve o HTML do deck a cada operação:

| Ação | O que faz |
|---|---|
| Slide em HTML | Inclui uma seção de exemplo depois do último slide |
| Slide de imagem | Abre o gerenciador de arquivos; cada imagem escolhida vira um slide, e o gerenciador fica aberto para escolher várias |
| Editar | Título do cartão (`data-title`), classes da seção e o HTML do slide; no slide de imagem, a imagem, o texto alternativo e o ajuste (inteira ou preenchendo) |
| Duplicar | Copia o slide logo depois dele |
| Mover | Arraste pelo cartão ou use as setas |
| Excluir | Pede dois cliques |

O conteúdo continua sendo o HTML do deck: o que o quadro muda aparece no editor, e o que o editor, a IA ou a troca de modelo mudam aparece no quadro. Slide de imagem é `<section data-slide data-slide-type="image">` com um `<img class="c2f-slide-image" data-fit="contain|cover">`.

Na visualização do editor HTML o modelo aparece cru, com as opções ainda como variáveis: os slides ficam empilhados, um abaixo do outro, para editar. A aba de pré-visualização mostra a apresentação funcionando.

Para um botão levar a outro slide, use `data-c2f-deck-goto="N"`, com N começando em zero.

Insira o widget na página:

```html
<!-- widgets#presentations->render({"grupo_slug":"minha-apresentacao"}) < -->
<div>Mockup</div>
<!-- widgets#presentations->render({"grupo_slug":"minha-apresentacao"}) > -->
```

### Opções

| Opção | Padrão | O que faz |
|---|---|---|
| `mode` | `fullscreen` | `fullscreen` cobre a janela; `embedded` ocupa a altura de `height` dentro da página |
| `height` | `600` | Altura em pixels no modo embutido |
| `transition` | `random` | `random`, `fade`, `zoom`, `slide-up` ou `slide-horizontal` |
| `loop` | `false` | Depois do último slide, volta ao primeiro |
| `keyboard` | `true` | Setas, espaço, PageUp/PageDown, Home, End e F (tela cheia) |
| `touch` | `true` | Deslize para navegar |
| `hash` | `true` | Guarda o slide no endereço (`#slide-3`) e abre nele |
| `show_arrows`, `show_dots`, `show_counter`, `show_progress`, `show_fullscreen` | `true` | Mostram cada controle |
| `autoplay`, `autoplay_speed` | `false`, `8000` | Avanço automático e tempo por slide, em milissegundos |

`?transition=zoom&loop=true` no endereço sobrepõe as duas opções naquela visita.

## Referência técnica

O controlador despacha `listar`, `adicionar`, `editar` e `clonar`. AJAX administrativo: `template-load` e `widget-preview`. A pré-visualização renderiza no servidor e roda o mesmo controlador público que o site.

A tabela `presentations` tem a estrutura de `galleries`: `id_presentations`, `id` até 100, `name`, `fields_schema` JSON, `html`, `css`, `css_compiled`, `html_extra_head`, `plugin`, `project`, `language`, `status`, `versao`, datas e flags de atualização. `(id, language)` é único.

O widget conta as seções com `data-slide`, ignorando comentários, e resolve os blocos do modelo:

| Bloco | Quando fica |
|---|---|
| `controls-arrows` | `show_arrows` e mais de um slide. Pode aparecer mais de uma vez |
| `controls-dots` com `dot-item` | `show_dots` e mais de um slide. `dot-item` é repetido por slide, com `[[dot#index]]` e `[[dot#number]]` |
| `controls-counter` | `show_counter` e mais de um slide |
| `controls-progress` | `show_progress` e mais de um slide |
| `controls-fullscreen` | `show_fullscreen` |

Variáveis globais: `[[total]]`, `[[mode]]`, `[[height]]`, `[[transition]]`, `[[loop]]`, `[[keyboard]]`, `[[touch]]`, `[[hash]]`, `[[autoplay]]`, `[[autoplay_speed]]` e `[[preview]]`.

O controlador público lê as opções dos atributos `data-*` do contêiner `[data-c2f-deck]`. Ele expõe `elemento.c2fDeck` (`next`, `prev`, `goTo`, `current`, `total`, `setTransition`) e dispara `c2f:deck:change` com `{ index, total }`. Em telas pequenas o palco `[data-c2f-deck-stage]` é desenhado maior e reduzido por escala; em tela cheia, a pinça amplia e o arrasto move.

Na impressão, o CSS do modelo mostra um slide por página e esconde os controles.

## Limitações confirmadas

> [!WARNING]
> As classes dos slides só têm CSS se a página que mostra o widget as compilar. Registro editado no painel guarda o CSS em `css_compiled`. Registro declarado como recurso de projeto não passa pelo editor: leve o HTML do deck como mockup dentro do marcador do widget, para o compilador da página enxergar as classes.

> [!CAUTION]
> Com mais de uma apresentação na página, o teclado vai para a de janela inteira ou para a última com que o visitante interagiu. A apresentação embutida só responde ao teclado com o ponteiro sobre ela, com foco dentro dela ou em tela cheia.

## Veja também

- [Galerias](galleries.md)
- [Modelos](admin-templates.md)
