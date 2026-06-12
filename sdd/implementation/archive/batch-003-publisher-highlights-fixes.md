# BATCH-003 - Correções e Melhorias no Módulo Publisher Highlights

## Origem

Intake humano [req-004.md](../human-requests/req-004.md) - Correções e melhorias identificadas nos testes visuais/fluxo do módulo `publisher-highlights` entregue em [BATCH-002](batch-002-wrapped-widgets-and-highlights.md).

## Escopo do Lote

Slice corretivo do módulo `publisher-highlights` sem mudar o requisito normativo de [03-wrapped-widgets-and-publisher-highlights.md](../03-wrapped-widgets-and-publisher-highlights.md). Adiciona uma extensão pequena ao `fields_schema` (`order_by`) e expande a integração com `html-editor` para variáveis `[[item#...]]`.

---

## Checklist de Implementação

### 1. Formulário Completo em Adicionar/Clonar
- [x] Alinhar `publisher-highlights-adicionar.html` com `publisher-highlights-editar.html` (regra de curadoria, modelo visual, mapeamento, editor HTML/CSS).
- [x] Alinhar `publisher-highlights-clonar.html` com `publisher-highlights-editar.html` mantendo os hidden `html-original`/`css-original` para clonagem fiel.
- [x] Em `publisher-highlights.php`, gravar `fields_schema`, `html` e `css` também em `_adicionar()` e `_clonar()` (não apenas em `_editar()`).
- [x] Garantir que `html_editor_componente()` é renderizado em adicionar/clonar via placeholder `#html-editor#`.

### 2. Placeholder `#template_placeholder_option#`
- [x] Em `publisher-highlights.php`, substituir `#template_placeholder_option#` pela tradução `gestor_variaveis(['modulo' => 'admin-templates', 'id' => 'form-name-placeholder'])` nas três rotas (adicionar/editar/clonar).

### 3. Dropdown `template_id` Dinâmico
- [x] Criar função `publisher_highlights_template_options($selected_id)` listando templates ativos com `target='publisher-highlights'` na linguagem corrente, ordenados por `nome`.
- [x] Preencher placeholder `#template_id_options#` no HTML e marcar `selected` o template ativo (apenas na edição/clonagem).
- [x] Chamar a função nas três rotas.

### 4. Visibilidade Dinâmica `template-options-wrapper`
- [x] Aplicar `style="display:none;"` no segmento `.template-options-wrapper` por padrão.
- [x] Em `publisher-highlights.js`, monitorar `select[name="template_id"]` e exibir/ocultar o bloco conforme valor.

### 5. Ordenação na Regra "Automática"
- [x] Adicionar campo `order_by` no `fields_schema` (default `date_desc`).
- [x] Adicionar dropdown `#order_by` no formulário visível apenas quando `rule === 'latest'`.
- [x] Opções suportadas: `title_asc`, `title_desc`, `date_asc`, `date_desc`.
- [x] Em `publisher-highlights.widget.php`, mapear `order_by` -> SQL na função `publisher_highlights_widget_buscar_publicacoes`.

### 6. Autocomplete Múltiplo Fomantic (Regra "Manual")
- [x] Substituir `<textarea name="selected_items">` por `<select multiple class="ui multiple search selection dropdown">`.
- [x] Adicionar endpoint AJAX `publisher-pages-search` que retorna `paginas` ativas filtradas por `publisher_id` (e busca opcional por nome).
- [x] No JS, configurar `apiSettings` do dropdown para consultar o endpoint usando o `publisher_id` selecionado.
- [x] Pré-hidratar opções no carregamento (editar/clonar) a partir de `schema.selected_items` (resolvendo nomes via AJAX `publisher-pages-fetch`).
- [x] Limpar seleção quando `publisher_id` muda.
- [x] Preservar a ordem da seleção do dropdown ao serializar `selected_items` no submit.

### 7. Variáveis `[[item#...]]` no Editor HTML/CSS
- [x] Em `html-editor.php`, quando `$alvo === 'publisher-highlights'`, passar uma `target_variables` (variáveis `item#X` extraídas do template ou do `variable_mapping`) para a interface JS via `gestor_js_variavel_incluir`.
- [x] Em `html-editor-interface.js`, detectar o alvo `publisher-highlights` e:
  - usar regex `/\[\[item#([a-zA-Z0-9_\-]+)\]\]/g` em `publisherGetAllVariables`, `publisherVariablesSearch` e funções de adicionar/remover variáveis.
  - renderizar a coluna "variável" como `[[item#NOME]]` em vez de `[[publisher#TIPO#ID]]`.
- [x] No `publisher-highlights.js`, alimentar a lista de variáveis via callback do AJAX `template-load` quando o alvo for `publisher-highlights`.

---

## Itens fora do escopo deste batch

- Migração de schema de banco de dados (a tabela `publisher_highlights` mantém estrutura atual; `template_id` continua sendo um auxiliar de UI, não persistido).
- Redesenho da camada de abstração do `html-editor`. O suporte a `publisher-highlights` é incremental sobre a camada existente.

---

## Alvo de Validação

Ver [VALIDATION-CHECKLIST.md#batch-003](../validation/VALIDATION-CHECKLIST.md#batch-003).
