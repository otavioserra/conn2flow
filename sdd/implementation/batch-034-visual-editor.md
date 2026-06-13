# BATCH-034 - Aprimoramento do Editor HTML Visual

## Escopo do Lote
Este lote traz uma profunda reformulação de usabilidade e novos recursos ao Editor HTML Visual. Ele isola a interação em hover dinâmico e seleção fixa; inclui uma barra de ferramentas flutuante para Duplicar, Editar, Deletar (via `confirm()`) e Arrastar; implementa Drag and Drop (DnD) nativo com placeholders dinâmicos; e cria um fluxo de inserção de novos elementos e widgets (comunicação com banco via AJAX e dropdown popup).

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Backend: Rota AJAX `html-editor-widgets-list` em `html-editor.php` | planejado | `php -l` + teste de resposta JSON |
| 2 | UI: Inclusão do botão "+" no cabeçalho do visual-modal (pt-br/en) | planejado | Validação estática HTML |
| 3 | Interface: Lógica do Popup, AJAX de Widgets e comunicação no pai (`html-editor-interface.js`) | planejado | `node --check` |
| 4 | Visual Editor: Lógica de Duplo Overlay e Barra Flutuante com botões no iframe (`html-editor.js`) | planejado | `node --check` |
| 5 | Visual Editor: Mecanismo de Drag and Drop (DnD) e Placeholders no iframe (`html-editor.js`) | planejado | `node --check` |
| 6 | Visual Editor: Carga & Soltar inclusão e expansão de tags editáveis (`html-editor.js`) | planejado | `node --check` + testes manuais |

---

## Checklist de Implementação

### 1. Rota AJAX de Widgets (`html-editor.php`)
- [ ] Tratar a opção `html-editor-widgets-list` na função `html_editor_ajax_interface()`.
- [ ] Consultar e recolher slugs/nomes das tabelas `menus`, `galleries`, `publisher_highlights` e `publisher_index`.
- [ ] Retornar o JSON estruturado agrupando os registros.

### 2. Interface de Botão Superior (`html-editor-visual-modal.html`)
- [ ] Adicionar o markup do botão "+" verde ao lado dos botões de controle de tela em `pt-br/components/html-editor-visual-modal/html-editor-visual-modal.html`.
- [ ] Adicionar o mesmo markup em `en/components/html-editor-visual-modal/html-editor-visual-modal.html`.

### 3. Orquestrador da Interface (`html-editor-interface.js`)
- [ ] Inicializar o popup Fomantic UI no botão "+" carregando as opções de tags HTML e as abas de widgets.
- [ ] Executar AJAX ao focar na aba de widgets para popular a lista com os dados do backend.
- [ ] Coordenar a seleção: ao clicar em um elemento/widget, disparar mensagem via `postMessage` ao iframe iniciando o modo de inserção.
- [ ] Tratar mensagens vindas do iframe de atualização do CodeMirror/sincronização de conteúdo e atualizar o preview.

### 4. Overlays e Barra Flutuante (`html-editor.js`)
- [ ] Implementar a separação física de `#html-editor-hover-overlay` (dinâmico no mousemove) e `#html-editor-selection-overlay` (fixo no click).
- [ ] Adicionar a barra de ferramentas flutuante `#html-editor-floating-toolbar` acima do overlay de seleção.
- [ ] Associar ações aos botões da barra:
  - Duplicar: clona e insere como irmão adjacente inferior do elemento selecionado.
  - Editar: abre o modal do pai (reutilizando a lógica existente).
  - Deletar: chama a confirmação via `confirm()` padrão do navegador. Se aceito, remove o nó do DOM e limpa seleção.

### 5. Mecanismo de Drag and Drop (`html-editor.js`)
- [ ] Escutar mousedown no botão drag da barra para iniciar o arraste.
- [ ] No mousemove do drag, encontrar o elemento sob a mira e desenhar a linha horizontal tracejada `.conn2flow-dnd-placeholder` (antes, depois ou dentro do elemento dependendo do cursor).
- [ ] No mouseup, reposicionar o elemento no DOM e limpar overlays de placeholder.

### 6. Fluxo de Inclusão e Lógica Inclusiva (`html-editor.js`)
- [ ] Escutar comando de inserção de novo elemento ou widget.
- [ ] Entrar no estado de inserção e usar a mesma linha tracejada para selecionar a posição de drop.
- [ ] Se for imagem, acionar a seleção do ImagePicker no pai e obter o link resultante.
- [ ] Ao clicar, inserir o novo elemento ou os comentários correspondentes ao widget no local desejado.
- [ ] Expandir a validação `isEditableElement` e `getEditType` para aceitar qualquer tag (não ignorada), mapeando para `code` (outerHTML) se não for imagem ou texto direto.

---

## Validação Esperada
- Executar `php -l` em `html-editor.php` e validar a integridade.
- Executar `node --check` em `html-editor.js` e `html-editor-interface.js`.
- Testar o fluxo completo de edição visual (hover, clique para selecionar, arrastar, duplicar, deletar, adicionar novo e adicionar widget).
