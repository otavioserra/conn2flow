# BATCH-242: Correção Estrutural da UI V3.0 — Widgets, Capas, Topbar e Largura Útil (req-233)

- **Status:** `complete`
- **Data:** 2026-10-04
- **Repositório:** `conn2flow` (Core) em `c:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req233`
- **Branch:** `feat/req-233`
- **Requisição:** [req-233](../human-requests/req-233.md)
- **Origem:** Correção estrutural das regressões e pendências do BATCH-241 (req-232).

---

## Escopo

Implementar as correções estruturais da interface V3.0:
1. **Grade de Widgets:** Grid canônico de 12 colunas no desktop, altura mínima real de `min-h-[220px]` (eliminando `grid-auto-rows: 0.5rem` / 8px).
2. **Alça de Redimensionamento:** Drag & resize no canto inferior direito com snapping proporcional em larguras válidas (4, 6, 8, 12 colunas) e alturas modulares (1x / 2x), com persistência no backend.
3. **Seletor Direto no Card de Widget:** Botão e dropdown/seletor evidente no cabeçalho de cada card para trocar qual métrica/widget roda naquele bloco em 1 clique, com requisição AJAX de atualização.
4. **Capas de Módulos (Aba Módulos):** Altura de `9rem` (144px) na densidade M e `12rem` (192px) na densidade G, `object-fit: cover 100%`, alça de arrasto flutuante translúcida sem deslocar o ícone SVG de fallback.
5. **Topbar Administrativa:** Dropdown de perfil com largura `w-80`, tooltips posicionadas abaixo (`data-c2f-dica-pos="bottom"`), modal de favoritos com espaçamento adequado sem sobreposição e suporte a reordenação por arrasto, e logo ampliada em ~35%.
6. **Largura Útil do Painel:** Seletor de largura útil do `<main data-admin-main>` (Normal `max-w-7xl`, Expandido `max-w-[1600px]`, Total `w-full max-w-none px-6`) com persistência no backend.
7. **Compilação e Entrega Limpa:** `assets:minify`, testes automatizados focados e commits atômicos de arquivos explícitos.

---

## Live Todo List — Critérios de Aceite

- [x] **CA-1 (Widgets com Geometria e Altura Real):** Os cards de widgets na aba de Widgets possuem altura mínima funcional (`min-h-[220px]`), sem esmagamento vertical, e grid responsivo de 12 colunas.
- [x] **CA-2 (Redimensionamento Fluido):** A alça no canto inferior direito do widget permite arrastar com o mouse para alterar a largura (4, 6, 8, 12 colunas) e altura de forma intuitiva, com persistência da preferência.
- [x] **CA-3 (Seletor Direto de Widgets):** No card do widget há opção evidente para trocar de imediato qual widget roda naquele espaço.
- [x] **CA-4 (Capas dos Módulos Visíveis e Nítidas):** Na densidade M, os cards de módulos têm altura de 9rem (144px), `object-fit: cover` 100% e alça de arrasto não intrusiva.
- [x] **CA-5 (Topbar Aprimorada):** Dropdown de perfil espaçoso (`w-80`), tooltips abrindo para baixo, modal de favoritos sem sobreposição de textos e logo em destaque.
- [x] **CA-6 (Largura Útil Configurável):** Opção funcional para expandir a largura útil do `<main>` em monitores amplos.
- [x] **CA-7 (Compilação e Entrega Limpa):** `assets:minify` executado sem erros, arquivos específicos comitados (`git add <arquivos>`) e testes focados verdes.

---

## Superfícies Alteradas

- `gestor/db/migrations/20261005120000_add_ordem_to_usuarios_topbar_favoritos.php`
- `gestor/bibliotecas/admin-topbar.php`
- `gestor/assets/global/admin-topbar.js` e `admin-topbar.min.js`
- `gestor/assets/global/admin-tailwind.js` e `admin-tailwind.min.js`
- `gestor/resources/{pt-br,en}/components/admin-topbar-tailwind/admin-topbar-tailwind.html`
- `gestor/resources/{pt-br,en}/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html`
- `gestor/resources/{pt-br,en}/variables.json`
- `gestor/modulos/dashboard/dashboard.js` e `dashboard.min.js`
- `gestor/modulos/dashboard/dashboard.json`
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.css`
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html`
- Testes unitários focados PHP (`tests/Unit/PHP/DashboardWidgetsReq233Test.php`, `DashboardCoversReq233Test.php`, `AdminTopbarReq233Test.php`) e JS (`tests/Unit/JS/admin-tailwind.test.js`).

---

## Evidências de Validação

1. **Testes PHPUnit Focados (5 testes, 86 asserções):**
   - `DashboardWidgetsReq233Test`: Grid 12 colunas, altura mínima funcional 220px/460px, snap proporcional (4, 6, 8, 12), proibição absoluta de `grid-auto-rows` e `repeat(24`, presença da alça de redimensionamento e seletor evidente no card.
   - `DashboardCoversReq233Test`: Altura de 9rem em densidade M, 12rem em densidade G, `object-fit: cover 100%`, alça de arrasto com posição absoluta `top-2.5 left-2.5` translúcida.
   - `AdminTopbarReq233Test`: Logo `h-11` (~35% maior), dropdown `w-80`, tooltips `data-c2f-dica-pos="bottom"`, ordenação drag & drop de favoritos e persistência de largura no `<main data-admin-main>`.
   - Resultado: `OK (5 tests, 86 assertions)`.

2. **Minificação de Assets (`php cli/c2f.php assets:minify`):**
   - Executado via Terser.
   - Resultado: 57 arquivos minificados gerados com sucesso, 0 falhas, redução de 55% no tamanho total.

3. **Sincronização de Recursos (`php cli/c2f.php resources:sync`):**
   - 3.316 recursos catalogados e sincronizados com sucesso no banco de dados SQLite/MySQL local.
