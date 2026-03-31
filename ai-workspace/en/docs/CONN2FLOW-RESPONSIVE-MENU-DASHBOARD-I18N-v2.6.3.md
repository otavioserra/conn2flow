# Responsive Admin Menu + Optimized Dashboard + Dashboard 3D i18n — v2.6.3

## Overview

This documentation covers three improvements implemented in the Conn2Flow manager during **v2.6.3**:

1. **Responsive admin menu** with unified behavior for mobile and tablet (sidebar overlay up to 1024px), resizable width, and localStorage persistence.
2. **Tablet-optimized dashboard** with a 2-column card layout in the 768px–1024px range.
3. **Dashboard 3D internationalization (i18n)** — "Documentation" and "Manual" button texts externalized from JavaScript into language-specific HTML files (pt-br and en).

---

## 1. Responsive Admin Menu

### 1.1 Context and Motivation

Before v2.6.3, the gestor's admin menu used two separate systems:
- **Desktop** (>770px): fixed left sidebar with static width.
- **Mobile** (<770px): collapsible menu without width control.

The **770px** breakpoint excluded tablets (768px–1024px) from mobile behavior, causing tablets to display the desktop menu with very little content space. Additionally, there was no way to resize the menu, nor any state persistence between pages.

### 1.2 Design Decisions

| Decision | Justification |
|----------|--------------|
| Unified breakpoint at **1024px** | Includes all tablets (iPad, Android) in sidebar overlay behavior |
| Sidebar overlay (not push) for mobile/tablet | Main content isn't displaced — more usable space on smaller screens |
| Resize handle only on desktop (>1024px) | On mobile/tablet, resizing would be semantically useless and visually polluting |
| **localStorage** persistence (not sessionStorage) | State persists across sessions — user doesn't need to readjust the menu every time |
| Transitions disabled during initialization | Avoids visual "flash" where the menu animates from the wrong position to the correct one on page load |
| Double `requestAnimationFrame` to re-enable transitions | Ensures the browser has completed the initial layout before CSS transitions are re-enabled |

### 1.3 Modified Files

| File | Nature of Modification |
|------|----------------------|
| `gestor/assets/global/global.js` | Complete menu logic: configuration, helper functions, initialization, event listeners, resize, touch, keyboard shortcut |
| `gestor/resources/pt-br/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.css` | New CSS classes, reorganized media queries, resize handle styles, mobile overlay |
| `gestor/resources/en/layouts/layout-administrativo-do-gestor/layout-administrativo-do-gestor.css` | Same CSS content as pt-br (layout CSS has no translatable text) |

### 1.4 JavaScript Structure — `global.js`

#### Configuration Object

```javascript
var menuConfig = {
    defaultWidth: 250,      // px — default menu width
    minWidth: 200,          // px — minimum resize limit
    maxWidth: 450,          // px — maximum resize limit
    mobileBreakpoint: 1024, // px — devices ≤ 1024px use sidebar overlay

    storageKeys: {
        width: 'gestor-menu-width',          // localStorage — saved width
        closed: 'gestor-menu-closed',        // localStorage — closed state
        scroll: 'menuComputerContScroll',    // sessionStorage — desktop menu scroll
        scrollMobile: 'menuMobileContScroll' // sessionStorage — mobile menu scroll
    }
};
```

#### Helper Functions

**`isMobile()`** — Detects if window width is ≤ `mobileBreakpoint`:
```javascript
function isMobile() {
    return window.innerWidth <= menuConfig.mobileBreakpoint;
}
```

**`getMenuState()`** — Reads persisted state from localStorage:
```javascript
function getMenuState() {
    return {
        width: parseInt(localStorage.getItem(menuConfig.storageKeys.width)) || menuConfig.defaultWidth,
        closed: localStorage.getItem(menuConfig.storageKeys.closed) === 'true'
    };
}
```

**`saveMenuState(state)`** — Persists partial state to localStorage. Accepts an object with `width` and/or `closed`.

**`setMenuWidth(width)`** — Applies width with clamp (min/max), updates `.menuComputerCont` and `.paginaCont` (desktop only):
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

#### Behavior by Mode

| Function | Mobile/Tablet (≤1024px) | Desktop (>1024px) |
|----------|------------------------|-------------------|
| `openMenu()` | Adds `menu-mobile-open` to `body` | Removes `menu-closed`, applies `margin-left` with saved width |
| `closeMenu()` | Removes `menu-mobile-open` | Adds `menu-closed`, zeroes `margin-left`, saves state |
| `toggleMenu()` | Checks for `menu-mobile-open` presence | Checks for `menu-closed` presence |

#### Initialization Without Animation (Anti-Flash)

The problem: when the page loads, CSS applies `transition: all 0.3s ease` to the menu. If the menu needs to start in closed state (saved state), it visibly "animates from open to closed" during page load.

The 3-step solution:
```javascript
// 1. Disable transitions IMMEDIATELY
$('body').addClass('menu-no-transition');

// 2. Apply initial state directly (without animation)
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

// 3. Re-enable transitions after 2 animation frames (browser layout complete)
requestAnimationFrame(function () {
    requestAnimationFrame(function () {
        $('body').removeClass('menu-no-transition');
    });
});
```

#### Drag Resize

The resize handle (`#menu-resize-handle`) is a `<div>` element positioned at the right edge of the menu with `cursor: ew-resize`. Resizing works via three events:

1. **`mousedown`**: captures initial `clientX` and current width; sets `isResizing = true`; disables CSS transitions for maximum smoothness.
2. **`mousemove`** on `document`: calculates difference `e.clientX - startX`, calls `setMenuWidth(newWidth)`, and saves to localStorage in real-time.
3. **`mouseup`** on `document`: finalizes resize, re-enables transitions, saves final width.

**Touch support** implemented with `touchstart`, `touchmove`, `touchend` events, using `e.originalEvent.touches[0].clientX`.

**Double-click to reset**:
```javascript
$('#menu-resize-handle').on('dblclick', function () {
    setMenuWidth(menuConfig.defaultWidth);
    saveMenuState({ width: menuConfig.defaultWidth });
});
```

#### Keyboard Shortcut

```javascript
$(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        toggleMenu();
    }
});
```

`metaKey` covers `Cmd` on macOS, making the shortcut cross-platform.

#### Window Resize Listener

With 100ms debounce to avoid excessive executions:
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

### 1.5 CSS Structure — Classes and Media Queries

#### State Classes on `<body>`

| Class | When Applied | Effect |
|-------|-------------|--------|
| `menu-closed` | Desktop, menu closed | `.menuComputerCont` uses `translateX(-100%)`, `.paginaCont` gets `margin-left: 0` |
| `menu-mobile-open` | Mobile/tablet, menu open | `.menuComputerCont` uses `translateX(0)`, dark overlay becomes visible |
| `menu-no-transition` | During initialization and resize | Removes all CSS transitions from menu elements |
| `menu-resizing` | During handle drag | `ew-resize` cursor on entire `body`, `user-select: none` |

#### Media Queries

```css
/* Mobile and Tablet (≤1024px): sidebar overlay */
@media screen and (max-width: 1024px) {
    .menuComputerCont {
        display: block;
        transform: translateX(-100%); /* Always starts off-screen */
        z-index: 1001;
    }
    body.menu-mobile-open .menuComputerCont {
        transform: translateX(0); /* Slides in */
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
        display: block; /* Dark overlay appears */
    }
    .paginaCont {
        margin-left: 0px !important; /* Content takes full screen */
    }
    #menu-resize-handle { display: none; }
    #menu-toggle-btn { display: block !important; z-index: 999; }
    body.menu-mobile-open #menu-toggle-btn { display: none !important; }
}

/* Desktop (>1025px): fixed menu */
@media screen and (min-width: 1025px) {
    .mobilecode { display: none; }
    .menuComputerCont { display: block; }
    .menu-mobile-overlay { display: none !important; }
}
```

#### Resize Handle

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
    background: rgba(33, 133, 208, 0.1); /* Translucent blue on hover */
}
#menu-resize-handle:hover i {
    opacity: 1;
    color: #2185d0; /* Blue icon visible only on hover */
}
```

The `<i>` inside the handle has `opacity: 0` by default and appears only on hover, keeping the interface clean.

---

## 2. Tablet-Optimized Dashboard

### 2.1 Context

The module dashboard uses the Fomantic UI card grid, which by default displays 4 cards per row at resolutions ≥ 768px. On tablets (768px–1024px), 4 cards resulted in very small cards and illegible text.

### 2.2 Solution

Custom media queries added to the `dashboard-cards` component CSS:

```css
/* Tablets (768px - 1024px): 2 columns instead of 4 */
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

/* Mobile portrait (≤767px): 1 column */
@media screen and (max-width: 767px) {
    .dashboard-sortable-cards .ui.card {
        width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
}
```

The use of `!important` is necessary because Fomantic UI applies widths via grid classes with high specificity.

### 2.3 Modified Files

| File |
|------|
| `gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards/dashboard-cards.css` |
| `gestor/modulos/dashboard/resources/en/components/dashboard-cards/dashboard-cards.css` |

---

## 3. Dashboard 3D Internationalization (i18n)

### 3.1 Context and Problem

The Dashboard 3D action modal (accessed by clicking a module card) displays two dynamically created JavaScript buttons:
- **"Documentation"** button — opens the module documentation link in a new tab.
- **"Manual"** button — opens the module manual link in a new tab.

The text of these buttons and the alert messages (when doc/manual is unavailable) were **hardcoded in Portuguese** directly in `dashboard-3d-ui.js`:

```javascript
// ❌ Before — hardcoded pt-br text in JS
docBtn.innerHTML = `...<span>Documentação</span>`;
manualBtn.innerHTML = `...<span>Manual</span>`;
alert('Documentação não disponível para este módulo.');
alert('Manual não disponível para este módulo.');
```

Since `dashboard-3d-ui.js` is a **global shared asset** across both pt-br and en languages, the texts needed to be externalized to the language-specific HTML files.

### 3.2 Solution — Data Attribute HTML

The chosen approach was to add a hidden `<div>` (`display: none`) with `id="i18n-texts"` in each language's dashboard-3d component HTML files. Texts are stored as data attributes (`data-*`):

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

### 3.3 Reading in JavaScript

In `dashboard-3d-ui.js`, at the beginning of the `showActionModal` function, texts are read via `dataset`:

```javascript
// ✅ After — texts read from language-specific HTML
showActionModal: function (card) {
    var modal = document.getElementById('module-action-modal');
    if (!modal) return;

    var i18n = document.getElementById('i18n-texts').dataset;
    // Available: i18n.docText, i18n.manualText, i18n.docUnavailable, i18n.manualUnavailable
    ...
```

Texts are then used in button construction:
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

And in alert messages:
```javascript
docBtn.addEventListener('click', function () {
    var docLink = card.getAttribute('data-module-doc');
    if (docLink) {
        window.open(docLink, '_blank');
    } else {
        alert(i18n.docUnavailable); // Text comes from HTML
    }
});
```

### 3.4 Why Data Attributes?

| Alternative | Pros | Cons |
|-------------|------|------|
| **Data attributes on hidden div** ✅ | Simple, no dependencies, native HTML, works with any framework | Requires element to exist in DOM |
| Separate JSON file per language | Organized, scalable | Requires extra HTTP request or PHP inclusion |
| Global JS variable per language | Fast access | Pollutes global scope, requires JS file per language |
| `data-*` directly on `<body>` | Very simple | Less modular, may conflict with other systems |

The `div#i18n-texts` approach is consistent with the existing system pattern (`#dashboard-3d-data` uses `<script type="application/json">` for PHP→JS data).

### 3.5 Modified Files

| File | Modification |
|------|-------------|
| `gestor/modulos/dashboard/resources/pt-br/components/dashboard-3d/dashboard-3d.html` | Added `<div id="i18n-texts">` with pt-br texts after keyboard shortcuts section |
| `gestor/modulos/dashboard/resources/en/components/dashboard-3d/dashboard-3d.html` | Added `<div id="i18n-texts">` with English texts |
| `gestor/assets/dashboard/dashboard-3d-ui.js` | Added `i18n` reading via `dataset`; hardcoded texts replaced with `${i18n.*}` |

---

## 4. Breaking Changes — v2.6.3

| Change | Impact | Migration |
|--------|--------|-----------|
| Mobile/tablet breakpoint **770px → 1024px** | Tablets that previously used desktop layout now use sidebar overlay | No action required — automatic improvement |
| Menu uses **sidebar overlay** on tablets | Main content is no longer displaced horizontally on tablets | No action required |
| New CSS classes on `<body>`: `menu-closed`, `menu-mobile-open`, `menu-no-transition`, `menu-resizing` | CSS customizations that depended on absence of these classes may be affected | Check custom CSS of projects |
| Menu state persists in **localStorage** | On page load, menu opens/closes based on saved state (may surprise if user expected menu always open) | Clear `gestor-menu-closed` and `gestor-menu-width` from localStorage to reset |

---

## 5. Involved File Structure

```
conn2flow/
└── gestor/
    ├── assets/
    │   ├── global/
    │   │   └── global.js                              ← Menu: main logic
    │   └── dashboard/
    │       └── dashboard-3d-ui.js                     ← Dashboard 3D: modal with i18n
    ├── resources/
    │   ├── pt-br/layouts/layout-administrativo-do-gestor/
    │   │   └── layout-administrativo-do-gestor.css    ← Menu: pt-br styles
    │   └── en/layouts/layout-administrativo-do-gestor/
    │       └── layout-administrativo-do-gestor.css    ← Menu: en styles
    └── modulos/
        └── dashboard/
            └── resources/
                ├── pt-br/components/
                │   ├── dashboard-cards/
                │   │   └── dashboard-cards.css        ← Dashboard: tablet 2 columns
                │   └── dashboard-3d/
                │       └── dashboard-3d.html          ← i18n pt-br: data attributes
                └── en/components/
                    ├── dashboard-cards/
                    │   └── dashboard-cards.css        ← Dashboard: tablet 2 columns
                    └── dashboard-3d/
                        └── dashboard-3d.html          ← i18n en: data attributes
```

---

## 6. Version Reference

This implementation corresponds to the **`gestor-v2.6.3`** release of the `conn2flow` repository.

**References:**
- `CHANGELOG.md` — v2.6.3 entry with improvement list
- `CHANGELOG-PT-BR.md` — Portuguese version of the changelog
- `ai-workspace/pt-br/docs/CONN2FLOW-CHANGELOG-HISTORY.md` — detailed version history
- `ai-workspace/en/docs/CONN2FLOW-CHANGELOG-HISTORY.md` — English version history
- `.github/workflows/release-gestor.yml` — GitHub Actions updated with v2.6.3 release notes
