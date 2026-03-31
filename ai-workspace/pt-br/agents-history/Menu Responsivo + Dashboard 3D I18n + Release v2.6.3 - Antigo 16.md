# Menu Responsivo + Dashboard 3D I18n + Release v2.6.3 - Antigo 16

## Contexto e Objetivos

Esta sessão de desenvolvimento cobriu três frentes de evolução do core do Conn2Flow no repositório `conn2flow` (`branch: main`), culminando no release **gestor-v2.6.3**. O trabalho foi executado ao longo de uma conversa única abrangendo UX do menu administrativo, otimização do dashboard para tablets e internacionalização do Dashboard 3D.

**Arquivos envolvidos:**
- `gestor/assets/global/global.js` — reescrita completa da lógica do menu
- `gestor/resources/pt-br/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.css` — novas classes CSS e media queries
- `gestor/resources/en/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.css` — idem en
- `gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards/dashboard-cards.css` — media queries tablet
- `gestor/modulos/dashboard/resources/en/components/dashboard-cards/dashboard-cards.css` — idem en
- `gestor/modulos/dashboard/resources/pt-br/components/dashboard-3d/dashboard-3d.html` — i18n data attributes
- `gestor/modulos/dashboard/resources/en/components/dashboard-3d/dashboard-3d.html` — i18n data attributes
- `gestor/assets/dashboard/dashboard-3d-ui.js` — leitura de i18n via dataset
- `CHANGELOG.md` — tradução para inglês + entry v2.6.3
- `CHANGELOG-PT-BR.md` — criado como versão pt-br do changelog
- `README.md` / `README-PT-BR.md` — atualizados com v2.6.3
- `ai-workspace/pt-br/docs/CONN2FLOW-CHANGELOG-HISTORY.md` — entry v2.6.3
- `ai-workspace/en/docs/CONN2FLOW-CHANGELOG-HISTORY.md` — entry v2.6.3
- `.github/workflows/release-gestor.yml` — body atualizado com features v2.6.3

### Objetivos Resumidos

1. Redesenhar o menu administrativo com comportamento unificado mobile/tablet (breakpoint 1024px)
2. Adicionar redimensionamento do menu via drag com persistência localStorage
3. Implementar atalho de teclado (Ctrl/Cmd+B) e duplo-clique para reset
4. Corrigir flash de animação na inicialização do menu
5. Otimizar o dashboard de módulos para tablets (2 colunas em 768px–1024px)
6. Externalizar textos i18n do Dashboard 3D do JS para HTML específico por idioma
7. Preparar release completo: CHANGELOG, READMEs, CHANGELOG-HISTORY, workflow

---

## Fase 1 — Menu Administrativo Responsivo

### Problema Inicial: Breakpoint Insuficiente e Flash de Animação

**Contexto**: O menu do gestor usava breakpoint de `770px` para distinguir mobile/desktop. Tablets em portrait ou landscape (768px–1024px) caíam no comportamento de desktop, exibindo menu lateral fixo com largura fixa de 250px. Isso deixava o conteúdo principal muito estreito em tablets — especialmente em portrait.

**Problema Secundário**: ao carregar a página, qualquer estado salvo (menu fechado) gerava um flash visível: o menu aparecia aberto (estado CSS padrão) e animava para fechado via `transition: 0.3s`. Isso era perceptível mesmo em conexões rápidas.

### Investigação e Decisões

**Análise do `global.js` original**: O código existente era simples — listener no botão de fechar/abrir, scroll persistente via sessionStorage. Sem estado de largura, sem suporte a resize, sem breakpoint configurável.

**Análise do CSS original**: Havia dois conjuntos de regras separados:
- `@media (max-width: 770px)` — comportamento mobile com menu oculto
- Regras base sem media query — comportamento desktop com menu fixo

**Decisão arquitetural**: Reescrever `global.js` com objeto de configuração central (`menuConfig`) contendo todos os parâmetros, e refatorar o CSS para 3 zonas: mobile/tablet (≤1024px), apenas mobile (≤770px) e desktop (≥1025px).

### Implementação do `global.js`

**Objeto de configuração**:
```javascript
var menuConfig = {
    defaultWidth: 250,
    minWidth: 200,
    maxWidth: 450,
    mobileBreakpoint: 1024,
    storageKeys: { width: '...', closed: '...', scroll: '...', scrollMobile: '...' }
};
```

**Funções de estado**: `isMobile()`, `getMenuState()`, `saveMenuState()`, `setMenuWidth()`.

**`openMenu()` / `closeMenu()`**: comportamento bifurcado — mobile usa classes CSS (`menu-mobile-open`) enquanto desktop usa `margin-left` inline + classes (`menu-closed`).

**Solução anti-flash em 3 etapas**:
1. Adicionar `menu-no-transition` ao carregar
2. Aplicar estado inicial (margin, classes) sem animação
3. Remover `menu-no-transition` após 2 `requestAnimationFrame` (browser completa layout)

**Redimensionamento via drag** (apenas desktop):
- `mousedown` no `#menu-resize-handle`: captura `startX`, `startWidth`, flag `isResizing = true`
- `mousemove` no `document`: calcula diff, chama `setMenuWidth()`, salva em localStorage em tempo real
- `mouseup` no `document`: finaliza, reabilita transições CSS
- Suporte a touch: mesma lógica com `touches[0].clientX`
- Double-click no handle: reset para `defaultWidth` (250px)

**Atalho de teclado**:
```javascript
$(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        toggleMenu();
    }
});
```

**Listener de resize da janela** com debounce de 100ms: transição suave ao rotacionar dispositivo, restaurando estado correto para o novo mode (mobile vs desktop).

### Atualização do CSS

**Novas classes de estado no `<body>`**:

| Classe | Efeito |
|--------|--------|
| `menu-closed` | `translateX(-100%)` no menu, `margin-left: 0` no conteúdo (desktop) |
| `menu-mobile-open` | `translateX(0)` no menu, overlay escuro visível (mobile/tablet) |
| `menu-no-transition` | `transition: none !important` em todos os elementos do menu |
| `menu-resizing` | `cursor: ew-resize` no `body`, `user-select: none` |

**Handle de resize** (`#menu-resize-handle`): elemento absoluto de 12px de largura no canto direito do menu, com ícone que só aparece no hover (opacity 0 → 1) e background azul translúcido no hover.

**Media query reorganizada**:
- `@media (max-width: 1024px)`: menu usa `transform: translateX(-100%)` por padrão; `menu-mobile-open` usa `translateX(0)`; overlay `.menu-mobile-overlay` controlado por CSS; `#menu-toggle-btn` sempre visível (exceto quando menu está aberto); `#menu-resize-handle` oculto
- `@media (min-width: 1025px)`: `.mobilecode` oculto; overlay forçado a `display: none !important`; input de filtro da tabela com `width: 250px`

### Ajuste do Breakpoint: 770px → 1024px

**Problema encontrado durante testes**: após implementar, o comportamento de tablet estava funcionando mas o breakpoint inicial era 770px (o antigo). A conversa identificou que era necessário incluir tablets explicitamente.

**Ação**: alterar `mobileBreakpoint: 770` para `mobileBreakpoint: 1024` na configuração do menu e ajustar a media query CSS de `@media (max-width: 770px)` para `@media (max-width: 1024px)`.

**Breaking change**: Documentado no CHANGELOG.md e CONN2FLOW-CHANGELOG-HISTORY.md — projetos com CSS customizado que dependiam do comportamento de desktop em tablets precisam verificar.

---

## Fase 2 — Dashboard Otimizado para Tablets

### Problema

O dashboard de módulos (componente `dashboard-cards`) exibia 4 cards por linha em qualquer resolução acima de 768px (comportamento padrão do Fomantic UI). Em tablets (768px–1024px), isso resultava em cards muito pequenos — especialmente após o menu usar sidebar overlay e o conteúdo ocupar 100% da largura.

### Solução

Adicionadas media queries customizadas no arquivo CSS do componente:

**`dashboard-cards.css` (pt-br e en)**:
```css
/* Tablets (768px - 1024px): 2 colunas ao invés de 4 */
@media screen and (min-width: 768px) and (max-width: 1024px) {
    .dashboard-sortable-cards .ui.card {
        width: calc(50% - 1em) !important;
        margin-left: 0.5em !important;
        margin-right: 0.5em !important;
    }
    .dashboard-sortable-cards .ui.card:nth-child(odd) { margin-left: 0 !important; }
    .dashboard-sortable-cards .ui.card:nth-child(even) { margin-right: 0 !important; }
}

/* Mobile portrait (≤767px): 1 coluna */
@media screen and (max-width: 767px) {
    .dashboard-sortable-cards .ui.card {
        width: 100% !important; margin-left: 0 !important; margin-right: 0 !important;
    }
}
```

O `!important` é necessário para sobrescrever a especificidade alta das classes de grid do Fomantic UI.

---

## Fase 3 — Internacionalização do Dashboard 3D

### Problema Original

O arquivo `gestor/assets/dashboard/dashboard-3d-ui.js` é um asset **global** — usado tanto pela versão pt-br quanto en do Dashboard 3D. No método `showActionModal`, os botões "Documentação" e "Manual" eram criados com textos hardcoded em português:

```javascript
// ❌ Textos hardcoded em pt-br num arquivo JS global
docBtn.innerHTML = `...<span>Documentação</span>`;
alert('Documentação não disponível para este módulo.');
```

Isso causava os textos em português mesmo quando o usuário usava o gestor em inglês.

### Análise das Alternativas

| Abordagem | Decisão |
|-----------|---------|
| Arquivo JSON separado | Descartado — requer request extra ou include PHP |
| Variável JS global por idioma | Descartado — polui escopo global |
| `data-*` no `<body>` | Considerado — simples mas menos modular |
| **`div#i18n-texts` com `data-*`** | ✅ Escolhido — simples, HTML nativo, consistente com padrão do sistema (`#dashboard-3d-data`) |

### Implementação

**Passo 1**: Adicionar `<div id="i18n-texts">` ao final dos arquivos HTML de cada idioma, logo antes do `<script id="dashboard-3d-data">`:

```html
<!-- pt-br -->
<div id="i18n-texts"
     data-doc-text="Documentação"
     data-manual-text="Manual"
     data-doc-unavailable="Documentação não disponível para este módulo."
     data-manual-unavailable="Manual não disponível para este módulo."
     style="display:none;"></div>

<!-- en -->
<div id="i18n-texts"
     data-doc-text="Documentation"
     data-manual-text="Manual"
     data-doc-unavailable="Documentation not available for this module."
     data-manual-unavailable="Manual not available for this module."
     style="display:none;"></div>
```

**Passo 2**: No início de `showActionModal`, ler o dataset:
```javascript
var i18n = document.getElementById('i18n-texts').dataset;
```

**Passo 3**: Substituir textos hardcoded por referências ao dataset:
```javascript
`<span>${i18n.docText}</span>`
`<span>${i18n.manualText}</span>`
alert(i18n.docUnavailable);
alert(i18n.manualUnavailable);
```

**Verificação de sintaxe**: executado `node -c dashboard-3d-ui.js` — sem erros.

---

## Fase 4 — Preparação do Release v2.6.3

### Arquivos de Documentação Atualizados

**`CHANGELOG.md`**:
- Traduzido completamente para inglês (arquivo estava em pt-br)
- Adicionada entrada v2.6.3 com todas as melhorias listadas

**`CHANGELOG-PT-BR.md`**:
- Criado como versão em português do CHANGELOG.md
- Cópia do arquivo original com entry v2.6.3

**`README.md`** e **`README-PT-BR.md`**:
- Versão atual atualizada para v2.6.3
- Adicionados bullets das novas features (menu responsivo, resize, atalho, tablet, i18n)
- Links de download atualizados

**`ai-workspace/pt-br/docs/CONN2FLOW-CHANGELOG-HISTORY.md`** e versão en:
- Adicionadas entradas v2.6.3 com tema, melhorias principais e breaking changes

**`.github/workflows/release-gestor.yml`**:
- Body do release atualizado com features da v2.6.3
- Bullets formatados em markdown para exibição no GitHub Releases

### Mensagens de Commit e Tag Preparadas

**Commit**: `feat(v2.6.3): Menu administrativo responsivo + Dashboard otimizado para tablets`

**Tag**: `gestor-v2.6.3`

**Descrição da tag** (para GitHub Releases):
- Lista completa de 9 melhorias
- Seção de Breaking Changes com notas de migração
- Referência ao CHANGELOG.md

---

## Decisões Técnicas e Justificativas

### Por Que `localStorage` e Não `sessionStorage` para Largura do Menu?

`sessionStorage` é limpo ao fechar a aba. O usuário que ajusta o menu para 300px espera que essa configuração persista para sempre, não apenas durante a sessão atual. `localStorage` é a escolha correta para preferências de UI do usuário.

### Por Que `requestAnimationFrame` Duplo?

Um único `requestAnimationFrame` agenda execução antes do próximo paint, mas o browser pode ainda não ter processado o reflow causado pela adição da classe `menu-no-transition`. O segundo frame garante que o browser completou o ciclo de layout+paint antes de remover a classe, eliminando o flash completamente.

### Por Que Sidebar Overlay e Não Push Content em Mobile?

Em push content, o conteúdo é deslocado para a direita quando o menu abre, reduzindo o espaço útil pela largura do menu. Em mobile/tablet, isso tornaria o conteúdo muito estreito. O padrão overlay (menu sobre o conteúdo com backdrop escuro) é a escolha de UX adotada pelos principais frameworks (Material UI, Bootstrap 5 Offcanvas, Fomantic UI Sidebar com overlay).

### Por Que `!important` no CSS do Dashboard Tablet?

O Fomantic UI usa `.four.cards > .card { width: ... }` — seletores com alta especificidade. Sobrescrever isso sem `!important` exigiria duplicar a estrutura de seletores (`.dashboard-sortable-cards.four.cards > .card`), o que criaria acoplamento frágil com a versão do Fomantic UI. O `!important` é justificável aqui porque a regra é intencional e claramente documentada no CSS.

---

## Bugs Encontrados e Resolvidos

| # | Bug | Causa | Solução |
|---|-----|-------|---------|
| 1 | Flash visual ao carregar página com menu fechado | Transições CSS ativas durante aplicação do estado inicial | Classe `menu-no-transition` + duplo `requestAnimationFrame` |
| 2 | Conteúdo deslocado incorretamente em tablet ao redimensionar janela | Listener de resize não limpa `margin-left` inline corretamente ao mudar para mobile | `$('.paginaCont').css('margin-left', '0')` e remoção de `menu-closed` no listener de resize |
| 3 | Botões "Documentação"/"Manual" em pt-br na versão en do dashboard 3D | Textos hardcoded em JS global | Externalização via `div#i18n-texts` com `data-*` attributes |
| 4 | Handle de resize visível em mobile/tablet | Não havia `display: none` no handle para a media query mobile | Adicionado `#menu-resize-handle { display: none; }` na media query `max-width: 1024px` |

---

## Histórico de Operações do Agente

### Sequência de Ações Executadas

1. **Análise do código existente** — leitura de `global.js`, `layout-administrativo-do-gestor.css` e componentes do dashboard para entender o estado anterior.

2. **Reescrita de `global.js`** — implementação do objeto `menuConfig`, funções auxiliares, sistema de inicialização anti-flash, event listeners de resize com suporte a touch, atalho de teclado e listener de resize de janela com debounce.

3. **Refatoração do CSS pt-br e en** — reorganização das media queries, adição das novas classes de estado, estilos do handle de resize e overlay mobile.

4. **Ajuste do breakpoint 770px → 1024px** — após identificar que tablets estavam sendo excluídos do comportamento mobile, alterado `mobileBreakpoint` no JS e `max-width` na media query CSS.

5. **Dashboard tablet** — adição das media queries customizadas em `dashboard-cards.css` (pt-br e en) para layout 2 colunas em 768px–1024px.

6. **i18n do Dashboard 3D** — identificação do problema de textos hardcoded, decisão pela abordagem `div#i18n-texts`, adição nos HTMLs pt-br e en, e atualização do `dashboard-3d-ui.js` para ler via `dataset`.

7. **Preparação do release** — atualização de `CHANGELOG.md` (tradução para inglês + entry v2.6.3), criação de `CHANGELOG-PT-BR.md`, atualização de `README.md` e `README-PT-BR.md`, atualização de ambos os arquivos `CONN2FLOW-CHANGELOG-HISTORY.md` (pt-br/en), atualização do `release-gestor.yml`.

8. **Criação de mensagens de commit e tag** — preparação das mensagens completas para execução do script de release.

9. **Documentação desta sessão** — criação dos arquivos de documentação técnica e histórico de agente.

### Verificações Realizadas

- `node -c dashboard-3d-ui.js` — sem erros de sintaxe JavaScript
- Sincronização `synchronize-manager.sh checksum` — executada com exit code 0
- Sincronização `atualizacoes-banco-de-dados.php --debug --log-diff` — executada com exit code 0

---

## Referências

| Recurso | Localização |
|---------|-------------|
| Documentação técnica (pt-br) | `ai-workspace/pt-br/docs/CONN2FLOW-MENU-RESPONSIVO-DASHBOARD-I18N-v2.6.3.md` |
| Documentação técnica (en) | `ai-workspace/en/docs/CONN2FLOW-RESPONSIVE-MENU-DASHBOARD-I18N-v2.6.3.md` |
| CHANGELOG | `CHANGELOG.md`, `CHANGELOG-PT-BR.md` |
| Histórico de versões | `ai-workspace/pt-br/docs/CONN2FLOW-CHANGELOG-HISTORY.md` |
| Workflow de release | `.github/workflows/release-gestor.yml` |
