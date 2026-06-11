# Memória de Engenharia — Execução

> **Propósito**: Este diário de bordo é reservado aos **Agentes Executores IA**. Registre aqui aprendizados sobre o ambiente local, particularidades do compilador/build, hacks temporários, bugs resolvidos e lições aprendidas durante a execução de tarefas.
>
> **Permissão**: Leitura e escrita para agentes executores IA. Atualize este arquivo **compulsoriamente** ao término de cada tarefa, registrando novos aprendizados para garantir a persistência do contexto entre sessões.

---

## Dependências & Ambiente Local

*(Registre versões de runtime, SDKs, variáveis de ambiente, particularidades de SO, caminhos locais relevantes.)*

---

## Aprendizados do Compilador / Build

- **Cálculo Automático de Checksums e Versões em JSONs (Não fazer manualmente)**:
  - É totalmente inútil e desnecessário que os agentes percam tempo tentando calcular manualmente os checksums ou versionamento (`version`) nos arquivos descritores `.json` de metadados de recursos.
  - O desenvolvedor/agente deve apenas registrar os novos dados nos arquivos JSON. O cálculo de checksums e atualização de versionamento ocorrem automaticamente pelas rotinas do sistema durante as execuções de deploy/atualização.
  - As rotinas que realizam essa matemática estão nos scripts:
    - `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow\gestor\controladores\agents\arquitetura\atualizacao-dados-recursos.php`
    - `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow\gestor\controladores\atualizacoes\atualizacoes-banco-de-dados.php`

---

## Hacks Locais & Workarounds

*(Registre soluções temporárias aplicadas para contornar bugs de ferramentas, limitações de libs ou problemas de infra.)*

---

## Bugs Resolvidos & Lições Aprendidas

- **Variáveis `[[item#X]]` do widget de menus e o cerco de arrobas (BATCH-016)**: o template salvo no banco guarda as variáveis como `@[[item#X]]@` (cópia literal — ver memória `feedback-conn2flow-variaveis-html-paginas`), mas no fluxo de preview o html vem do editor já como `[[item#X]]` (o `template-load` converte `@[[`→`[[`). Um regex `\[\[item#X\]\]` casa apenas a parte interna e deixa sobrar `@valor@` no HTML renderizado quando a fonte é o banco. **Solução**: no renderizador (`menus.widget.php`), usar regex tolerante `/@?\[\[item#X\]\]@?/` (consome as arrobas adjacentes) e `str_replace` para `[[item#children]]`/`@[[item#children]]@`. Não remover as arrobas dos arquivos de template — só consumi-las no render.

- **Dropdown Fomantic sobre `<select>` e `.val()` (BATCH-017 item 1.3)**: quando o Fomantic converte um `<select class="ui dropdown">` via `.dropdown()`, `$('#id').val()` deixa de refletir a escolha do usuário. No `menus.js`, isso fazia `currentItemType()` cair sempre no fallback `'pagina'` (só a busca de páginas aparecia, nunca os inputs de link-custom/cabeçalho/etc.). **Solução**: ler o valor por `$('#id').dropdown('get value')` (com fallback `.val()` → default) e/ou propagar o `value` do callback `onChange(value, ...)` diretamente para o toggle.

- **Comentário HTML aninhado em componente de recurso (BATCH-017 item 1.2)**: ao documentar um componente, NÃO escrever `<!-- item -->`/`<!-- item-parent -->`/`<!-- no-item -->` dentro de um comentário HTML — comentários não aninham, o primeiro `-->` fecha o comentário externo e o restante vira texto/markup solto no DOM. Pior: `[[item#X]]` que sobrar fora de `<script>`/comentário pode ser processado como variável pelo gestor. Validar com contagem balanceada de `<!--`/`-->` e ausência de `[[item#` fora do bloco `<script type="application/json">`.

- **Simulação do html-editor para `menus` espelha a recursão do widget (BATCH-017 item 1.1)**: diferente do `publisher-highlights` (lista plana de cards replicados N vezes), a simulação de `menus` (`publisherVariablesOrSimulation`, branch `menus` em `html-editor-interface.js`) precisa reproduzir em JS a lógica recursiva de `menus.widget.php` (blocos `item`/`item-parent`/`no-item`, `[[item#children]]` recursivo, montagem da base). A massa mockada é uma **árvore JSON** no componente `html-editor-menus-simulation`; há fallback embutido no JS (`MENUS_SIM_FALLBACK`) para a simulação funcionar mesmo antes de o componente ser registrado no banco pelo pipeline.

- **AJAX público de widgets — `ajaxWidgets`, não `ajaxOpcao` (BATCH-028)**: o roteador AJAX do MÓDULO (`xxx_start()` → `switch($_GESTOR['ajax-opcao'])`) só roda quando a requisição vai para a URL do próprio módulo (painel admin). No site PÚBLICO, a requisição vai para `window.location.href` (a página publicada) e é o `gestor_pagina_widgets_ajax()` (em `gestor/gestor.php`) que processa `$_GESTOR['ajaxWidgets']` (lista de wrapper-ids separada por `<#;>`), chamando `widgets_get()` em modo AJAX → resolve `<modulo>_<func>_ajax` (ex.: `publisher_index_render_ajax`). O `grupo_slug` chega via JSON do wrapper (parâmetro `$params` da função), não por `ajaxRegistroId`. **CRÍTICO**: a função `_ajax` deve setar `$_GESTOR['ajax-json']` e **retornar string vazia** — qualquer retorno não-vazio é tratado como erro 500 por `gestor_pagina_widgets_ajax()`. O `widgetsToAjax` é montado no page load (quando `widgets_get` registra o wrapper) e exposto ao JS como `gestor.widgetsToAjax`.
- **Persistência css_compiled/html_extra_head é via html-editor, não nas views (BATCH-028)**: o componente `html-editor.html` já possui os textareas `name="css_compiled"`/`name="html_extra_head"` com os placeholders `#pagina-css-compiled#`/`#pagina-html-extra-head#` (e os `*-backup`). O html-editor (modos `editar`/`adicionarEditar`) **não** limpa esses placeholders — quem os preenche é o MÓDULO (no `$_GESTOR['pagina']`, após inserir `#html-editor#`). Antes do req-028 os 3 módulos só preenchiam `#pagina-html#`/`#pagina-css#`, deixando os de css_compiled/html_extra_head literais. Os placeholders `#html-original#`/`#css-original#` (e os novos `#css-compiled-original#`) são legado/no-op: não existem em nenhuma view — o trânsito no submit acontece pelos próprios textareas do html-editor.
- **gestor_componente() tem 2 blocos de injeção idênticos (BATCH-028)**: caminho `return_array` (lista de componentes) e caminho único. Ambos foram trocados pela helper `gestor_pagina_recursos_incluir()`. Cuidado ao editar com tabs: as linhas em branco internas das `if` carregam tabs de indentação; as linhas entre `if`s são totalmente vazias. Use `sed -n 'X,Yp' | cat -A` para conferir os bytes antes de Edit.
- **Clonar módulo do gestor por cópia + substituição (BATCH-028)**: para criar `publisher-index` (clone de `publisher-highlights`) usei `Copy-Item -Recurse` + renomeação recursiva de itens com `publisher-highlights` no nome + substituição de conteúdo. **Encoding**: usar `[System.IO.File]::ReadAllText($p,[Text.Encoding]::UTF8)` + `WriteAllText($p,$c, (New-Object System.Text.UTF8Encoding($false)))` (UTF-8 SEM BOM) — `Set-Content -Encoding UTF8` no PowerShell 5.1 injeta BOM e um BOM antes de `<?php` quebra o PHP (headers already sent). Registrar o módulo manualmente em `gestor/db/data/ModulosData.json` e `UsuariosPerfisModulosData.json` (os demais data files — Paginas/Templates/Variaveis/AlvosIa/ModosIa — são populados pelo pipeline a partir do manifest).
- **Variáveis globais de widget no template (BATCH-028)**: além de `[[item#X]]` (itens), o widget `publisher-index` resolve variáveis globais `[[grupo_slug]]`/`[[items_per_page]]`/`[[ordenacao]]` etc. nos data-attributes do contêiner via regex tolerante a arrobas `/@?\[\[chave\]\]@?/` (consome o cerco `@...@` do banco). Blocos condicionais de controle (`search-input`/`sort-select`/`load-more`) seguem o padrão de delimitadores `<!-- nome < --> ... <!-- nome > -->`: mantidos (só removendo os marcadores) ou removidos por inteiro conforme `show_*`/`tem_mais`. Use `preg_replace_callback` ao injetar conteúdo repetido para evitar que `$`/`\` sejam tratados como backreferences.

## Notas Cross-Session

- **Módulo `menus` — contrato da árvore (DEC-023, BATCH-016)**: `fields_schema.selected_items` agora é uma **árvore de objetos tipados** (`type` ∈ pagina/link-custom/cabecalho/link-action/separador; `label`/`url`/`css_classes`/`children`; `page_id` para `pagina`), não mais lista de slugs. `fields_schema` é coluna `json` — mudança de formato **não exige migração**. Retrocompat: lista de strings = itens `pagina` raiz.
- **Editor de árvore = componente próprio**: `menus.js` usa modelo interno **flat-com-`depth`** (padrão WordPress) e DnD bidimensional em **Pointer Events vanilla** (sem jQuery UI/nestedSortable/Sortable.js). Converte flat↔árvore nas fronteiras (hidratação/submit/preview). O `Sortable.js` (CDN) foi removido do `menus.php`.
- **Renderização recursiva**: templates de menu suportam `no-item`/`item`/`item-parent`; o `item-parent` injeta os filhos recursivos em `[[item#children]]`. Templates sem `item-parent` continuam válidos (fallback DFS achata a árvore).
- **Deploy/checksums**: a `version` dos recursos alterados em `menus.json` foi incrementada manualmente (1.1→1.2); os **checksums não** — o pipeline UPSERT (`Update => Core`, rodado pelo operador) recalcula. Validação runtime do BATCH-016 fica pendente com o operador.
- **BATCH-017 (req-017)**: lote corretivo do módulo `menus`. (1) Aba "Variáveis"/"Simular" reintegrada ao html-editor para o alvo `menus` — `html-editor.php` ganhou `case 'menus'` e `menus` no `$backupCallbackMap`; `html_editor_publisher_controls` trata `menus` (variáveis fixas via `target_variables`/`menus_variaveis_template()`); no JS, helper `alvoUsaItemVars()` (highlights+menus) unifica a forma `[[item#X]]`. (2) Novo componente global `html-editor-menus-simulation` (pt-br/en). (3) Fix do alternador de tipo (dropdown Fomantic), placeholder do DnD (altura + "Solte o item aqui" ← →) e hover dos submenus (remoção dos gaps `mt-1`/`ml-1` em `menus-dropdown` e `menus-horizontal-navbar`). `version` 1.3→1.4 só nos 2 templates de hover (checksums intactos). Ver DEC-024. Pendência com o operador: rodar `atualizacao-dados-recursos.php` / `Update => Core` (registra o componente novo + recalcula checksums) e validação runtime.
