Gerar apenas a parte interna do <body> de uma APRESENTAÇÃO EM SLIDES. O HTML é um modelo: um motor conta os slides e monta a navegação; um script público cuida de teclado, toque, tela cheia, escala e impressão.

REGRA OBRIGATÓRIA DOS SLIDES:
Cada slide é uma `<section data-slide>` filha do palco `[data-c2f-deck-stage]`. O atributo `data-slide` é o que define o slide: não use outra marcação para isso. Um slide ocupa a tela inteira; mantenha o conteúdo dentro de um contêiner central com largura máxima.

```
<section data-slide class="flex-col items-center justify-center p-6 md:p-12">
    <div class="w-full max-w-5xl mx-auto">
        ...conteúdo de UM slide...
    </div>
</section>
```

Não escreva `display` no slide (nem a classe `flex`, nem `hidden`): o CSS do modelo mostra só o slide ativo, já como flex. Use `flex-col`, `items-center` e `justify-center` para orientar o conteúdo.

ESTRUTURA DO CONTÊINER:
Preserve o contêiner externo com `data-c2f-deck` e todos os atributos `data-*`, o palco `data-c2f-deck-stage` e a barra de navegação. Eles já vêm no modelo; altere só as seções.

```
<div class="c2f-deck" data-c2f-deck data-mode="[[mode]]" data-transition="[[transition]]" data-loop="[[loop]]" data-keyboard="[[keyboard]]" data-touch="[[touch]]" data-hash="[[hash]]" data-autoplay="[[autoplay]]" data-speed="[[autoplay_speed]]" data-preview="[[preview]]" style="--c2f-deck-height: [[height]]px;">
    <div class="c2f-deck-stage" data-c2f-deck-stage>
        ...seções...
    </div>
    ...controles...
</div>
```

CONTROLES (blocos condicionais; o motor mantém ou remove conforme as opções):

```
<!-- controls-progress < --><div class="c2f-deck-progress"><span data-c2f-deck-progress></span></div><!-- controls-progress > -->
<!-- controls-arrows < --><button type="button" data-c2f-deck-prev>‹</button><!-- controls-arrows > -->
<!-- controls-dots < -->
<div class="c2f-deck-dots">
    <!-- dot-item < --><button type="button" class="c2f-deck-dot" data-c2f-deck-dot="[[dot#index]]" aria-label="Ir para o slide [[dot#number]]"></button><!-- dot-item > -->
</div>
<!-- controls-dots > -->
<!-- controls-counter < --><span><span data-c2f-deck-current>1</span> / [[total]]</span><!-- controls-counter > -->
<!-- controls-arrows < --><button type="button" data-c2f-deck-next>›</button><!-- controls-arrows > -->
<!-- controls-fullscreen < --><button type="button" data-c2f-deck-fullscreen>⛶</button><!-- controls-fullscreen > -->
```

O bloco `dot-item` é repetido uma vez por slide. Não escreva os pontos à mão nem numere os slides: a contagem é automática.

SALTO ENTRE SLIDES:
Para um botão ou link dentro de um slide levar a outro, use `data-c2f-deck-goto="N"`, com N começando em zero. Não use `onclick` nem funções globais.

REGRA DE FORMATAÇÃO DE PLACEHOLDERS:
Use sempre `[[nome]]`, sem `@`. O pipeline converte para `@[[nome]]@` ao salvar.

VARIÁVEIS GLOBAIS:
- `[[total]]` — quantidade de slides
- `[[mode]]` — `fullscreen` ou `embedded`
- `[[height]]` — altura em pixels no modo embutido
- `[[transition]]`, `[[loop]]`, `[[keyboard]]`, `[[touch]]`, `[[hash]]`, `[[autoplay]]`, `[[autoplay_speed]]`, `[[preview]]` — opções lidas pelo script

VARIÁVEIS DO PONTO (só dentro de `dot-item`):
- `[[dot#index]]` — posição começando em zero
- `[[dot#number]]` — posição começando em um

ESTILO:
Escreva o conteúdo dos slides com classes do framework CSS da página. Texto grande e pouco texto por slide; contraste alto; uma ideia por slide. Não inclua `<script>`.
