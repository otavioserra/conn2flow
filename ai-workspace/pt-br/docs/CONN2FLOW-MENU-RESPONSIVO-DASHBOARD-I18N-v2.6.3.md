# Menu Administrativo Responsivo + Dashboard Otimizado + I18n do Dashboard 3D — v2.6.3

## Visão Geral

Esta documentação cobre três melhorias implementadas no gestor do Conn2Flow durante a versão **v2.6.3**:

1. **Menu administrativo responsivo** com comportamento unificado para mobile e tablet (sidebar overlay até 1024px), largura redimensionável e persistência em localStorage.
2. **Dashboard otimizado para tablets** com layout de cards em 2 colunas na faixa de 768px a 1024px.
3. **Internacionalização (i18n) do Dashboard 3D** — textos dos botões "Documentação" e "Manual" externalizados do JavaScript para os arquivos HTML específicos por idioma (pt-br e en).

---

## 1. Menu Administrativo Responsivo

### 1.1 Contexto e Motivação

Antes da v2.6.3, o menu administrativo do gestor usava dois sistemas separados:
- **Desktop** (>770px): menu fixo lateral esquerdo com largura estática.
- **Mobile** (<770px): menu colapsável sem controle de largura.

O breakpoint de **770px** excluía tablets (768px–1024px) do comportamento mobile, fazendo com que tablets exibissem o menu de desktop com pouco espaço útil para o conteúdo. Além disso, não havia como redimensionar o menu nem persistência do estado entre páginas.

### 1.2 Decisões de Design

| Decisão | Justificativa |
|---------|---------------|
| Breakpoint unificado em **1024px** | Inclui todos os tablets (iPad, Android) no comportamento de sidebar overlay |
| Sidebar overlay (não push) em mobile/tablet | Conteúdo principal não é deslocado — mais espaço útil em telas menores |
| Handle de resize apenas em desktop (>1024px) | Em mobile/tablet, o resize seria semanticamente inútil e visualmente poluente |
| Persistência via **localStorage** (não sessionStorage) | Estado persiste entre sessões — o usuário não precisa reajustar o menu toda vez que abre o gestor |
| Transições desabilitadas na inicialização | Evita o "flash" visual onde o menu anima da posição errada para a correta ao carregar a página |
| `requestAnimationFrame` duplo para reabilitar transições | Garante que o browser completou o layout inicial antes de ligar as transições CSS |

### 1.3 Arquivos Modificados

| Arquivo | Natureza da Modificação |
|---------|------------------------|
| `gestor/assets/global/global.js` | Lógica completa do menu: configuração, funções auxiliares, inicialização, event listeners, resize, touch, atalho de teclado |
| `gestor/resources/pt-br/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.css` | Classes CSS novas, media queries reorganizadas, estilos do handle de resize, overlay mobile |
| `gestor/resources/en/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.css` | Mesmo conteúdo CSS do pt-br (layout CSS não tem texto a traduzir) |

### 1.4 Estrutura JavaScript — `global.js`

#### Objeto de Configuração

```javascript
var menuConfig = {
    defaultWidth: 250,      // px — largura padrão do menu
    minWidth: 200,          // px — limite mínimo de resize
    maxWidth: 450,          // px — limite máximo de resize
    mobileBreakpoint: 1024, // px — dispositivos ≤ 1024px usam sidebar overlay

    storageKeys: {
        width: 'gestor-menu-width',         // localStorage — largura salva
        closed: 'gestor-menu-closed',       // localStorage — estado fechado
        scroll: 'menuComputerContScroll',   // sessionStorage — scroll do menu desktop
        scrollMobile: 'menuMobileContScroll' // sessionStorage — scroll do menu mobile
    }
};
```

#### Funções Auxiliares

**`isMobile()`** — Detecta se a largura da janela é ≤ `mobileBreakpoint`:
```javascript
function isMobile() {
    return window.innerWidth <= menuConfig.mobileBreakpoint;
}
```

**`getMenuState()`** — Lê estado persistido do localStorage:
```javascript
function getMenuState() {
    return {
        width: parseInt(localStorage.getItem(menuConfig.storageKeys.width)) || menuConfig.defaultWidth,
        closed: localStorage.getItem(menuConfig.storageKeys.closed) === 'true'
    };
}
```

**`saveMenuState(state)`** — Persiste estado parcial no localStorage. Aceita objeto com `width` e/ou `closed`.

**`setMenuWidth(width)`** — Aplica largura com clamp (min/max), atualiza `.menuComputerCont` e `.paginaCont` (apenas em desktop):
```javascript
function setMenuWidth(width) {
    width = Math.max(menuConfig.minWidth, Math.min(menuConfig.maxWidth, width));
    $('.menuComputerCont').css('width', width + 'px');
    if (!isMobile()) {
        $('.paginaCont').css('margin-left', width + 'px');
    }
    return width;
}
```

#### Comportamento por Modo

| Função | Mobile/Tablet (≤1024px) | Desktop (>1024px) |
|--------|------------------------|-------------------|
| `openMenu()` | Adiciona `menu-mobile-open` ao `body` | Remove `menu-closed`, aplica `margin-left` com largura salva |
| `closeMenu()` | Remove `menu-mobile-open` | Adiciona `menu-closed`, zera `margin-left`, salva estado |
| `toggleMenu()` | Verifica presença de `menu-mobile-open` | Verifica presença de `menu-closed` |

#### Inicialização sem Animação (Anti-Flash)

O problema: quando a página carrega, o CSS vai aplicar `transition: all 0.3s ease` ao menu. Se o menu precisa começar fechado (estado salvo), ele vai "animar de aberto para fechado" visivelmente durante o carregamento.

A solução em 3 passos:
```javascript
// 1. Desabilitar transições IMEDIATAMENTE
$('body').addClass('menu-no-transition');

// 2. Aplicar estado inicial de forma direta (sem animação)
if (isMobile()) {
    $('.paginaCont').css('margin-left', '0');
} else {
    if (menuState.closed) {
        $('body').addClass('menu-closed');
        $('.paginaCont').css('margin-left', '0');
    } else {
        $('.paginaCont').css('margin-left', menuState.width + 'px');
    }
}

// 3. Reabilitar transições após 2 frames de animação (browser layout completo)
requestAnimationFrame(function () {
    requestAnimationFrame(function () {
        $('body').removeClass('menu-no-transition');
    });
});
```

#### Redimensionamento via Drag

O handle de resize (`#menu-resize-handle`) é um elemento `<div>` posicionado no canto direito do menu com `cursor: ew-resize`. O resize funciona via três eventos:

1. **`mousedown`**: captura `clientX` inicial e largura atual; inicia flag `isResizing = true`; desabilita transições CSS para fluidez máxima.
2. **`mousemove`** no `document`: calcula diferença `e.clientX - startX`, chama `setMenuWidth(newWidth)` e salva em localStorage em tempo real.
3. **`mouseup`** no `document`: finaliza resize, reabilita transições, salva largura final.

Suporte a **touch** implementado com os eventos `touchstart`, `touchmove`, `touchend`, usando `e.originalEvent.touches[0].clientX`.

**Double-click para reset**:
```javascript
$('#menu-resize-handle').on('dblclick', function () {
    setMenuWidth(menuConfig.defaultWidth);
    saveMenuState({ width: menuConfig.defaultWidth });
});
```

#### Atalho de Teclado

```javascript
$(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        toggleMenu();
    }
});
```

`metaKey` cobre o `Cmd` no macOS, tornando o atalho cross-platform.

#### Listener de Resize da Janela

Com debounce de 100ms para evitar execuções excessivas:
```javascript
var resizeTimeout;
$(window).on('resize', function () {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function () {
        if (isMobile()) {
            $('body').removeClass('menu-closed');
            $('.paginaCont').css('margin-left', '0');
        } else {
            $('body').removeClass('menu-mobile-open');
            if (state.closed) {
                $('body').addClass('menu-closed');
                $('.paginaCont').css('margin-left', '0');
            } else {
                $('.paginaCont').css('margin-left', state.width + 'px');
            }
        }
    }, 100);
});
```

Isso garante transições suaves quando o usuário redimensiona a janela ou rotaciona o dispositivo, preservando o estado correto para o novo modo (mobile vs desktop).

### 1.5 Estrutura CSS — Classes e Media Queries

#### Classes de Estado no `<body>`

| Classe | Quando Aplicada | Efeito |
|--------|----------------|--------|
| `menu-closed` | Desktop, menu fechado | `.menuComputerCont` usa `translateX(-100%)`, `.paginaCont` fica com `margin-left: 0` |
| `menu-mobile-open` | Mobile/tablet, menu aberto | `.menuComputerCont` usa `translateX(0)`, overlay escuro fica visível |
| `menu-no-transition` | Durante inicialização e resize | Remove todas as transições CSS dos elementos do menu |
| `menu-resizing` | Durante drag do handle | Cursor `ew-resize` em todo o `body`, `user-select: none` |

#### Media Queries

```css
/* Mobile e Tablet (≤1024px): sidebar overlay */
@media screen and (max-width: 1024px) {
    .menuComputerCont {
        display: block;
        transform: translateX(-100%); /* Sempre começa fora da tela */
        z-index: 1001;
    }
    body.menu-mobile-open .menuComputerCont {
        transform: translateX(0); /* Desliza para dentro */
    }
    .menu-mobile-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }
    body.menu-mobile-open .menu-mobile-overlay {
        display: block; /* Overlay escuro aparece */
    }
    .paginaCont {
        margin-left: 0px !important; /* Conteúdo ocupa tela cheia */
    }
    #menu-resize-handle { display: none; }
    #menu-toggle-btn { display: block !important; z-index: 999; }
    body.menu-mobile-open #menu-toggle-btn { display: none !important; }
}

/* Desktop (>1025px): menu fixo */
@media screen and (min-width: 1025px) {
    .mobilecode { display: none; }
    .menuComputerCont { display: block; }
    .menu-mobile-overlay { display: none !important; }
}
```

#### Handle de Resize

```css
#menu-resize-handle {
    position: absolute;
    top: 0;
    right: 0px;
    width: 12px;
    height: 100%;
    cursor: ew-resize;
    background: transparent;
    z-index: 10;
    transition: background 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}
#menu-resize-handle:hover {
    background: rgba(33, 133, 208, 0.1); /* Azul translúcido no hover */
}
#menu-resize-handle:hover i {
    opacity: 1;
    color: #2185d0; /* Ícone azul visível apenas no hover */
}
```

O `<i>` dentro do handle tem `opacity: 0` por padrão e aparece apenas no hover, mantendo a interface limpa.

---

## 2. Dashboard Otimizado para Tablets

### 2.1 Contexto

O dashboard de módulos usa o grid de cards do Fomantic UI, que por padrão exibe 4 cards por linha em resoluções ≥ 768px. Em tablets (768px–1024px), 4 cards resultavam em cards muito pequenos e textos ilegíveis.

### 2.2 Solução

Adicionadas media queries customizadas no CSS do componente `dashboard-cards`:

```css
/* Tablets (768px - 1024px): 2 colunas ao invés de 4 */
@media screen and (min-width: 768px) and (max-width: 1024px) {
    .dashboard-sortable-cards .ui.card {
        width: calc(50% - 1em) !important;
        margin-left: 0.5em !important;
        margin-right: 0.5em !important;
    }
    .dashboard-sortable-cards .ui.card:nth-child(odd) {
        margin-left: 0 !important;
    }
    .dashboard-sortable-cards .ui.card:nth-child(even) {
        margin-right: 0 !important;
    }
}

/* Mobile portrait (≤767px): 1 coluna */
@media screen and (max-width: 767px) {
    .dashboard-sortable-cards .ui.card {
        width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
}
```

O uso de `!important` é necessário porque o Fomantic UI aplica larguras via classes de grid com especificidade alta.

### 2.3 Arquivos Modificados

| Arquivo |
|---------|
| `gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards/dashboard-cards.css` |
| `gestor/modulos/dashboard/resources/en/components/dashboard-cards/dashboard-cards.css` |

---

## 3. Internacionalização do Dashboard 3D (i18n)

### 3.1 Contexto e Problema

O modal de ações do Dashboard 3D (acessado ao clicar num card de módulo) exibe dois botões dinâmicos criados por JavaScript:
- Botão **"Documentação"** — abre link de documentação do módulo em nova aba.
- Botão **"Manual"** — abre link do manual do módulo em nova aba.

Os textos desses botões e as mensagens de alerta (quando doc/manual não disponível) estavam **hardcoded em português** diretamente no arquivo `dashboard-3d-ui.js`:

```javascript
// ❌ Antes — textos hardcoded em pt-br no JS
docBtn.innerHTML = `...<span>Documentação</span>`;
manualBtn.innerHTML = `...<span>Manual</span>`;
alert('Documentação não disponível para este módulo.');
alert('Manual não disponível para este módulo.');
```

Como o `dashboard-3d-ui.js` é um **asset global** compartilhado entre os idiomas pt-br e en, os textos precisavam ser externalizados para os arquivos HTML específicos de cada idioma.

### 3.2 Solução — Data Attributes HTML

A abordagem escolhida foi adicionar uma `<div>` oculta (`display: none`) com `id="i18n-texts"` nos arquivos HTML do componente dashboard-3d de cada idioma. Os textos ficam como atributos de dados (`data-*`):

**`dashboard-3d.html` (pt-br):**
```html
<!-- Textos de internacionalização -->
<div id="i18n-texts"
     data-doc-text="Documentação"
     data-manual-text="Manual"
     data-doc-unavailable="Documentação não disponível para este módulo."
     data-manual-unavailable="Manual não disponível para este módulo."
     style="display:none;">
</div>
```

**`dashboard-3d.html` (en):**
```html
<!-- Internationalization texts -->
<div id="i18n-texts"
     data-doc-text="Documentation"
     data-manual-text="Manual"
     data-doc-unavailable="Documentation not available for this module."
     data-manual-unavailable="Manual not available for this module."
     style="display:none;">
</div>
```

### 3.3 Leitura no JavaScript

No `dashboard-3d-ui.js`, no início da função `showActionModal`, os textos são lidos via `dataset`:

```javascript
// ✅ Depois — textos lidos do HTML específico do idioma
showActionModal: function (card) {
    var modal = document.getElementById('module-action-modal');
    if (!modal) return;

    var i18n = document.getElementById('i18n-texts').dataset;
    // i18n.docText, i18n.manualText, i18n.docUnavailable, i18n.manualUnavailable
    ...
```

Os textos são então usados na construção dos botões:
```javascript
docBtn.innerHTML = `
    <svg>...</svg>
    <span>${i18n.docText}</span>
`;

manualBtn.innerHTML = `
    <svg>...</svg>
    <span>${i18n.manualText}</span>
`;
```

E nas mensagens de alerta:
```javascript
docBtn.addEventListener('click', function () {
    var docLink = card.getAttribute('data-module-doc');
    if (docLink) {
        window.open(docLink, '_blank');
    } else {
        alert(i18n.docUnavailable); // Texto vem do HTML
    }
});
```

### 3.4 Por Que Data Attributes?

| Alternativa | Prós | Contras |
|-------------|------|---------|
| **Data attributes em div oculta** ✅ | Simples, sem dependências, HTML nativo, funciona com qualquer framework | Requer que o elemento exista no DOM |
| Arquivo JSON separado por idioma | Organizado, escalável | Requer requisição HTTP extra ou inclusão PHP |
| Variável JS global por idioma | Rápido de acessar | Polui escopo global, requer arquivo JS por idioma |
| `data-*` direto no `<body>` | Muito simples | Menos modular, pode colidir com outros sistemas |

A abordagem por `div#i18n-texts` é consistente com o padrão já usado no sistema (`#dashboard-3d-data` usa `<script type="application/json">` para dados PHP→JS).

### 3.5 Arquivos Modificados

| Arquivo | Modificação |
|---------|-------------|
| `gestor/modulos/dashboard/resources/pt-br/components/dashboard-3d/dashboard-3d.html` | Adicionada `<div id="i18n-texts">` com textos em pt-br após os atalhos de teclado |
| `gestor/modulos/dashboard/resources/en/components/dashboard-3d/dashboard-3d.html` | Adicionada `<div id="i18n-texts">` com textos em inglês |
| `gestor/assets/dashboard/dashboard-3d-ui.js` | Adicionada leitura de `i18n` via `dataset`; textos hardcoded substituídos por `${i18n.*}` |

---

## 4. Breaking Changes — v2.6.3

| Mudança | Impacto | Migração |
|---------|---------|----------|
| Breakpoint mobile/tablet de **770px → 1024px** | Tablets que antes usavam layout desktop agora usam sidebar overlay | Nenhuma ação necessária — melhora automática |
| Menu usa **sidebar overlay** em tablets | O conteúdo principal não é mais deslocado lateralmente em tablets | Nenhuma ação necessária |
| Novas classes CSS no `<body>`: `menu-closed`, `menu-mobile-open`, `menu-no-transition`, `menu-resizing` | Customizações CSS que dependiam da ausência dessas classes podem ser afetadas | Verificar CSS customizado de projetos |
| Estado do menu persiste em **localStorage** | Ao atualizar, o menu abre/fecha conforme o estado salvo (pode surpreender se o usuário esperava menu sempre aberto) | Limpar `gestor-menu-closed` e `gestor-menu-width` do localStorage para resetar |

---

## 5. Estrutura de Arquivos Envolvidos

```
conn2flow/
└── gestor/
    ├── assets/
    │   ├── global/
    │   │   └── global.js                              ← Menu: lógica principal
    │   └── dashboard/
    │       └── dashboard-3d-ui.js                     ← Dashboard 3D: modal com i18n
    ├── resources/
    │   ├── pt-br/layouts/layout-administrativo-do-gestor/
    │   │   └── layout-administrativo-do-gestor.css    ← Menu: estilos pt-br
    │   └── en/layouts/layout-administrativo-do-gestor/
    │       └── layout-administrativo-do-gestor.css    ← Menu: estilos en
    └── modulos/
        └── dashboard/
            └── resources/
                ├── pt-br/components/
                │   ├── dashboard-cards/
                │   │   └── dashboard-cards.css        ← Dashboard: tablet 2 colunas
                │   └── dashboard-3d/
                │       └── dashboard-3d.html          ← i18n pt-br: data attributes
                └── en/components/
                    ├── dashboard-cards/
                    │   └── dashboard-cards.css        ← Dashboard: tablet 2 colunas
                    └── dashboard-3d/
                        └── dashboard-3d.html          ← i18n en: data attributes
```

---

## 6. Documentação de Versão

Esta implementação corresponde ao release **`gestor-v2.6.3`** do repositório `conn2flow`.

**Referências:**
- `CHANGELOG.md` — entry v2.6.3 com lista de melhorias
- `CHANGELOG-PT-BR.md` — versão em português do changelog
- `ai-workspace/pt-br/docs/CONN2FLOW-CHANGELOG-HISTORY.md` — histórico detalhado de versões
- `ai-workspace/en/docs/CONN2FLOW-CHANGELOG-HISTORY.md` — histórico em inglês
- `.github/workflows/release-gestor.yml` — GitHub Actions atualizado com notas da v2.6.3
