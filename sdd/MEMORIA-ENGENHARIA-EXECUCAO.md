# Memória de Engenharia — Execução

> **Propósito**: Este diário de bordo é reservado aos **Agentes Executores IA**. Registre aqui aprendizados sobre o ambiente local, particularidades do compilador/build, hacks temporários, bugs resolvidos e lições aprendidas durante a execução de tarefas.
>
> **Permissão**: Leitura e escrita para agentes executores IA. Atualize este arquivo **compulsoriamente** ao término de cada tarefa, registrando novos aprendizados para garantir a persistência do contexto entre sessões.

---

## Dependências & Ambiente Local

*(Registre versões de runtime, SDKs, variáveis de ambiente, particularidades de SO, caminhos locais relevantes.)*

---

## Aprendizados do Compilador / Build

*(Registre flags necessárias, ordem de build, workarounds de compilação, tempo de build esperado, etc.)*

---

## Hacks Locais & Workarounds

*(Registre soluções temporárias aplicadas para contornar bugs de ferramentas, limitações de libs ou problemas de infra.)*

---

## Bugs Resolvidos & Lições Aprendidas

- **Variáveis `[[item#X]]` do widget de menus e o cerco de arrobas (BATCH-016)**: o template salvo no banco guarda as variáveis como `@[[item#X]]@` (cópia literal — ver memória `feedback-conn2flow-variaveis-html-paginas`), mas no fluxo de preview o html vem do editor já como `[[item#X]]` (o `template-load` converte `@[[`→`[[`). Um regex `\[\[item#X\]\]` casa apenas a parte interna e deixa sobrar `@valor@` no HTML renderizado quando a fonte é o banco. **Solução**: no renderizador (`menus.widget.php`), usar regex tolerante `/@?\[\[item#X\]\]@?/` (consome as arrobas adjacentes) e `str_replace` para `[[item#children]]`/`@[[item#children]]@`. Não remover as arrobas dos arquivos de template — só consumi-las no render.

## Notas Cross-Session

- **Módulo `menus` — contrato da árvore (DEC-023, BATCH-016)**: `fields_schema.selected_items` agora é uma **árvore de objetos tipados** (`type` ∈ pagina/link-custom/cabecalho/link-action/separador; `label`/`url`/`css_classes`/`children`; `page_id` para `pagina`), não mais lista de slugs. `fields_schema` é coluna `json` — mudança de formato **não exige migração**. Retrocompat: lista de strings = itens `pagina` raiz.
- **Editor de árvore = componente próprio**: `menus.js` usa modelo interno **flat-com-`depth`** (padrão WordPress) e DnD bidimensional em **Pointer Events vanilla** (sem jQuery UI/nestedSortable/Sortable.js). Converte flat↔árvore nas fronteiras (hidratação/submit/preview). O `Sortable.js` (CDN) foi removido do `menus.php`.
- **Renderização recursiva**: templates de menu suportam `no-item`/`item`/`item-parent`; o `item-parent` injeta os filhos recursivos em `[[item#children]]`. Templates sem `item-parent` continuam válidos (fallback DFS achata a árvore).
- **Deploy/checksums**: a `version` dos recursos alterados em `menus.json` foi incrementada manualmente (1.1→1.2); os **checksums não** — o pipeline UPSERT (`Update => Core`, rodado pelo operador) recalcula. Validação runtime do BATCH-016 fica pendente com o operador.
