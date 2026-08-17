# BATCH-117 — Paridade do Tailwind Browser CDN, Painel de Código na Editbar e Correção de Race Condition na Extração do CSS Compilado

Intake: [req-117.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-117.md)
Validação: [VALIDATION-CHECKLIST.md#batch-117](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation` (implementado em 2026-08-17)

---

## 1. Problema e Diagnóstico

1. **Editor HTML Clássico (`html-editor-interface.js`)**:
   - `updateCSSCompiled` disparava a captura aos 100ms. A presença das regras base no stylesheet inicial do Tailwind Browser fazia a função acreditar que a compilação havia terminado antes de o scanner do Tailwind inserir os utilitários reais dentro de `@layer utilities`. O CSS gravado no banco ficava com as classes utilitárias vazias.

2. **Editbar / Live Editor (`dashboard.toolbar.js`)**:
   - Ao ativar o modo "Editar Página" diretamente na página pública, o script não carregava o runtime do `@tailwindcss/browser` nem o contrato `@theme static`.
   - Se o usuário adicionasse uma classe Tailwind no Styler ou no CodeMirror, o elemento não atualizava visualmente na tela ao vivo.
   - Ao salvar (`performSave`), o payload enviava apenas `html` — nunca extraía nem enviava `css_compiled`. Com isso, `paginas.css_compiled` continuava defasado no banco mesmo após o reload.

3. **Ausência de Painel de Código na Editbar (`dashboard-site-toolbar.html`)**:
   - A Editbar não possuía acesso direto ao código-fonte da página (HTML, HTML Extra Head, CSS e CSS Compilado) como existe no editor clássico (`data-tab="visualizacao-codigo"`).

---

## 2. Escopo de Implementação

### Módulo 1 — Blindagem de Polling e Validação de Utilitários em `html-editor-interface.js`

1. **Função `hasGeneratedUtilities(rules)`**:
   - Inspeciona recursivamente a árvore de regras CSSOM.
   - Detecta blocos `@layer utilities` (`CSSLayerBlockRule` com nome `utilities` e `cssRules.length > 0`) ou regras utilitárias diretas (`rule.type === 1` fora de seletores universais/tags de reset).

2. **Ajuste na Rotina `capture(attempt = 0)`**:
   - Detecta se o corpo do documento possui classes: `const hasHtmlClasses = iframeDoc.body && iframeDoc.body.querySelectorAll('[class]').length > 0`.
   - Avalia prontidão real: `const utilitiesReady = tailwindStyle && (!hasHtmlClasses || hasGeneratedUtilities(tailwindStyle.sheet.cssRules))`.
   - Se `!tailwindStyle` ou `!utilitiesReady`, reagenda a tentativa (`setTimeout(() => capture(attempt + 1), 100)`) com limite seguro de até 40 tentativas (4 segundos).
   - Somente extrai e define o valor de `CodeMirrorCssCompiled` quando a folha de estilo estiver completa e estabilizada.

### Módulo 2 — Injeção do Tailwind Browser e Extração de `css_compiled` no Live Editor (`dashboard.toolbar.js`)

1. **Injeção do Runtime na Ativação (`activateEditor`)**:
   - Em páginas que utilizam Tailwind CSS, injetar o `<style type="text/tailwindcss" data-c2f-tailwind-role="browser-contract">` com os tokens `@theme static` e o script `<script src="https://unpkg.com/@tailwindcss/browser@4.3.0"></script>`.
   - Garante que a adição de classes no Styler / DOM atualize instantaneamente a renderização na página hospedeira.

2. **Extração e Persistência no Salvamento (`performSave`)**:
   - Executar a extração do CSS gerado na página hospedeira (usando `filterRules` / `hasGeneratedUtilities`).
   - Incluir `css_compiled` no corpo do POST de `site-toolbar-save`.
   - O backend em `dashboard.php` (`dashboard_ajax_site_toolbar_save`) já trata o campo `css_compiled` e persistirá a nova versão na tabela `paginas`.

### Módulo 3 — Botão e Painel de "Código" na Editbar (`dashboard-site-toolbar.html`, `dashboard.iframe-toolbar.js`, `dashboard.toolbar.js`)

1. **Markup e Ícone na Toolbar (`dashboard-site-toolbar.html` pt-br/en)**:
   - Botão `#c2f-code-btn` ("Código" / "Code") posicionado ao lado do `#c2f-ai-btn`.
2. **Ponte de Mensagens (`dashboard.iframe-toolbar.js`)**:
   - Posta `c2f-toolbar:edit-code` com as coordenadas do botão para o host.
3. **Painel Flutuante `#c2f-code-panel` (`dashboard.toolbar.js`)**:
   - Isolado na página hospedeira via `isEditorOwned()` em `html-editor.js`, com proteção de cliques (sem vazar seleção) e fechamento em `dismissHostPanels()`.
   - 4 Sub-abas com CodeMirror (`tomorrow-night-bright`, indentUnit 4, refresh ao alternar):
     - **HTML**: HTML limpo do corpo (`html`), sincronizado com o DOM do `c2fEditor`.
     - **HTML Extra Head**: Cabeçalho extra (`html_extra_head`).
     - **CSS**: CSS autoral da página (`css`).
     - **CSS Compilado**: CSS gerado em tempo real pelo Tailwind Browser (`css_compiled`).
4. **Sincronização Bidirecional**:
   - Abrir o painel reflete o estado atual do DOM.
   - Editar no CodeMirror atualiza o DOM e re-dispara a compilação JIT do Tailwind.
   - `performSave` inclui os 4 campos no salvamento via AJAX.

### Módulo 4 — Testes Automatizados (Vitest)

- Adicionar casos de teste no Vitest cobrindo:
  - Detecção correta de `@layer utilities` preenchido vs. vazio.
  - Extração de `css_compiled` em fluxos clássicos e do Live Editor.
  - Abertura, troca de abas e sincronização do painel de Código da Editbar.
  - Tolerância a páginas sem classes utilitárias.

---

## 3. Arquivos Envolvidos

- `gestor/assets/interface/html-editor-interface.js`
- `gestor/modulos/dashboard/dashboard.toolbar.js`
- `gestor/modulos/dashboard/dashboard.iframe-toolbar.js`
- `gestor/modulos/dashboard/resources/pt-br/pages/dashboard-site-toolbar/dashboard-site-toolbar.html`
- `gestor/modulos/dashboard/resources/en/pages/dashboard-site-toolbar/dashboard-site-toolbar.html`
- `gestor/modulos/dashboard/dashboard.php`
- `tests/` (testes Vitest)
- `sdd/human-requests/req-117.md`
- `sdd/human-requests/CURRENT.md`
- `sdd/implementation/BATCH-INDEX.md`
- `sdd/validation/VALIDATION-CHECKLIST.md`

---

## 4. Resultado da implementação (2026-08-17)

### 4.1 O diagnóstico do intake estava incompleto — o que a medição mostrou

Três fatos medidos antes de escrever código mudaram o desenho da correção:

1. **O `browser-contract.css` sem `@import "tailwindcss"` está correto.** Lendo o bundle real do
   `@tailwindcss/browser@4.3.0`: ele concatena os `<style type="text/tailwindcss">` e, se o resultado
   não contiver `@import`, **prefixa `@import "tailwindcss";` sozinho**. Não era essa a falha.

2. **A folha de saída do browser nasce VAZIA.** Ela é criada por `document.head.append(<style>)` e só
   recebe conteúdo quando o build assíncrono termina. Nesse intervalo, o critério antigo — "a última
   `<style>` do `<head>` com regras" — escolhia **qualquer** folha injetada em runtime. O
   `html-editor.js` injeta 4 delas no mesmo `<head>` (UI do editor, CSS de modelo, CSS da IA).
   O risco real não era só "utilities vazio": era **gravar o CSS da UI do editor no banco**.

3. **Os dois modos de falha estavam no banco ao mesmo tempo** (base `photon`): `sobre` e
   `pagina-raiz-do-sistema` com o CSS autoral re-serializado e zero `@layer` (folha errada);
   `teste-de-pagina` com `@layer utilities` vazio (a race condition descrita no intake).

### 4.2 Uma implementação, dois consumidores

A captura virou `window.HtmlEditorCssCapture`, no motor `html-editor.js`:

- **editor clássico**: `html-editor-interface.js` roda na janela PAI e alcança o objeto pelo
  `contentWindow` do iframe (o `srcdoc` herda a origem);
- **Editbar**: `dashboard.toolbar.js` roda na MESMA janela que o motor.

Sem duplicação de lógica delicada entre os dois arquivos, e testável isoladamente.

A folha de saída passou a ser reconhecida pelo **formato** da v4 (camadas nomeadas
theme/base/components/utilities/properties), e três famílias de marcação saem da busca:
`data-c2f-tailwind-role` (o que o editor injeta), `data-tailwind-role` (pré-compilados do runtime) e
o novo `data-c2f-css-role` (filas de CSS autoral e `css_compiled` da página pública). Esta última era
indispensável na Editbar: o `css_compiled` anterior contém `@layer utilities` e seria lido como a
compilação nova, congelando a página no CSS da edição passada.

### 4.3 Política do `css_compiled`: delta contra a cascata real

Decisão do Chefe em 2026-08-17, depois da análise técnica — ver **DEC-111**.

O baseline do editor deixou de ser só o pré-compilado do próprio recurso e passou a ser a cascata que
a página **recebe no runtime**. No editor clássico, `html_editor_css_precompiled_baseline()` concatena
layout + recurso; na Editbar, o baseline é lido do próprio DOM. Com isso a decisão é do dado, sem flag:
página sob layout pré-compilado grava só o delta; página sem cascata offline grava o output completo e
continua autossuficiente.

**Correção de desenho descoberta na medição com Chromium real**: o filtro por assinatura de regra não
segura camadas de fundação. O Preflight do browser 4.3.0 emite
`*, ::after, ::before, ::backdrop, ::file-selector-button` e o bundle offline emite
`*, ::after, ::before, ::backdrop` — nenhuma assinatura casa, o Preflight inteiro era regravado
(economia de só 17%) e, como o `css_compiled` entra DEPOIS do pré-compilado na cascata, a versão do
editor venceria a do build **em produção**. `theme` e `base` passaram a ser decididas por CAMADA;
`utilities`, `properties` e `components` seguem no filtro fino. Economia final: **65%**.

### 4.4 Diferença deliberada em relação ao intake

O intake pede sincronização bidirecional ao vivo nas abas de HTML e CSS. O CSS é aplicado ao vivo
(debounce de 400 ms, reaproveitando a folha `data-c2f-css-role="authored"` que o PHP emitiu, para o
resultado na tela ser o mesmo da página publicada). O **HTML é aplicado por botão explícito**:
reescrever `#c2f-page-content` recria os nós e derruba as anotações do mapeamento in-place
(`data-c2f-variable`, `.c2f-dyn-box`) — a cada tecla, isso destruiria a edição em curso. Depois de
aplicar, o HTML digitado vira também o original de referência (o backup oculto é atualizado e o
`mapTree` roda de novo), então as variáveis aparecem como marcador até o recarregamento.

### 4.5 Findings do review de 2026-08-15 incluídos

**F1** e **F4c** entraram como guardas de build puras em `tailwind-recursos.php`
(`tailwind_recursos_tokens_ausentes`, `tailwind_recursos_utilities_removidas`,
`tailwind_recursos_html_usa_tailwind`), com aviso por padrão e `--tailwind-strict` para promover a
erro. A heurística do F4c(a) foi calibrada contra o inventário real: com `flex`/`grid`/`hidden`
isolados ela disparava em **176** recursos (colisão com `ui grid`/`ui items` do Fomantic); exigindo
duas utilities distintas com valor e excluindo classes Bootstrap, caiu para **4**.

Ela já encontrou um caso legítimo no core: `html-editor-publisher-simulation` (pt-br e en) usa
Tailwind puro e não declara `framework_css` — mesmo defeito do `form-ui`. Registrado como pendência,
fora do escopo aprovado desta rodada.

### 4.6 Cache-bust

- `biblioteca-html-editor`: fallback `1.5.11` → `1.5.12`; o token determinístico do diretório
  `interface` mudou de `cd980099ea856734` para `e0c5a722dbe78ff0`, o que governa os três consumidores;
- `dashboard.json`: módulo `1.0.20` → `1.0.21` (versiona o `dashboard.iframe-toolbar.js`);
- página `dashboard-site-toolbar`: `1.19` → `1.20` com checksums esvaziados; o pipeline recalculou e
  publicou `1.21`.

### 4.7 Validação

Evidência completa em [VALIDATION-CHECKLIST.md](../validation/VALIDATION-CHECKLIST.md#batch-117):
PHPUnit **297/297**, Vitest **220/220**, gerador do core 175 recursos / 0 erros, sync + atualização de
banco no `snapphoton-local` e `GET /photon/sobre/` → HTTP 200 com as marcações novas no `<head>`.
