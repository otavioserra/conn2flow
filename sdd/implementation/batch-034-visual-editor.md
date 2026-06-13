# BATCH-034 - Aprimoramento do Editor HTML Visual

## Escopo do Lote
Este lote traz uma profunda reformulação de usabilidade e novos recursos ao Editor HTML Visual. Ele isola a interação em hover dinâmico e seleção fixa; inclui uma barra de ferramentas flutuante para Duplicar, Editar, Deletar (via `confirm()`) e Arrastar; implementa Drag and Drop (DnD) nativo com placeholders dinâmicos; e cria um fluxo de inserção de novos elementos e widgets (comunicação com banco via AJAX e dropdown popup). 

Adicionalmente, implementa os recursos Premium: Histórico de Ações (Undo/Redo configurável via `config.undoLimit` com padrão `30`), trilha Breadcrumb de navegação do DOM, Editor rápido de classes Tailwind CSS, alças de redimensionamento dinâmico do preview (Resize Handles) com indicador de pixels, e Wrappers Virtuais atômicos para isolamento de Widgets.

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Backend: Rota AJAX `html-editor-widgets-list` em `html-editor.php` | planejado | `php -l` + teste de resposta JSON |
| 2 | UI: Botão "+", Undo/Redo e alças de resize no visual-modal (pt-br/en) | planejado | Validação estática HTML |
| 3 | Interface: Lógica do Popup, AJAX, atalhos teclado e redimensionamento no pai (`html-editor-interface.js`) | planejado | `node --check` |
| 4 | Visual Editor: Lógica de Duplo Overlay, Barra Flutuante, Tailwind Styler e Breadcrumb (`html-editor.js`) | planejado | `node --check` |
| 5 | Visual Editor: Mecanismo de Drag and Drop (DnD) e Placeholders no iframe (`html-editor.js`) | planejado | `node --check` |
| 6 | Visual Editor: Histórico Undo/Redo, Carga de Widgets, Wrappers Virtuais e tags editáveis (`html-editor.js`) | planejado | `node --check` + testes manuais |

---

## Checklist de Implementação

### 1. Rota AJAX de Widgets (`html-editor.php`)
- [ ] Tratar a opção `html-editor-widgets-list` na função `html_editor_ajax_interface()`.
- [ ] Consultar e recolher slugs/nomes das tabelas `menus`, `galleries`, `publisher_highlights` e `publisher_index`.
- [ ] Retornar o JSON estruturado agrupando os registros.

### 2. Interface de Botões e Alças (`html-editor-visual-modal.html`)
- [ ] Adicionar o markup do botão "+" verde e botões desfazer/refazer (setas) em `pt-br/.../html-editor-visual-modal.html`.
- [ ] Adicionar alças verticais de arraste (`.iframe-resize-handle-left` e `.iframe-resize-handle-right`) nas laterais do contêiner do iframe.
- [ ] Adicionar um elemento indicador flutuante para mostrar a largura em pixels (ex: `1024px`).
- [ ] Duplicar as mesmas marcações em `en/.../html-editor-visual-modal.html`.

### 3. Orquestrador da Interface (`html-editor-interface.js`)
- [ ] Inicializar o popup Fomantic UI no botão "+" carregando as opções de tags HTML e as abas de widgets.
- [ ] Executar AJAX ao focar na aba de widgets para popular a lista com os dados do backend.
- [ ] Coordenar a seleção: ao clicar em um elemento/widget, disparar mensagem via `postMessage` ao iframe iniciando o modo de inserção.
- [ ] Escutar atalhos de teclado `Ctrl + Z` / `Ctrl + Y` na janela pai e repassar ao iframe via `postMessage`.
- [ ] Ligar eventos mouse de arraste nas alças laterais para alterar dinamicamente o `width` do iframe de preview e atualizar a etiqueta de pixels.
- [ ] Sincronizar tamanho com cliques nos botões de desktop/tablet/mobile pré-configurados.

### 4. Overlays, Toolbar, Tailwind Styler e Breadcrumb (`html-editor.js`)
- [ ] Implementar a separação física de `#html-editor-hover-overlay` (dinâmico no mousemove) e `#html-editor-selection-overlay` (fixo no click).
- [ ] Adicionar a barra de ferramentas flutuante `#html-editor-floating-toolbar` acima do overlay de seleção.
- [ ] Associar ações aos botões da barra:
  - Duplicar: clona e insere como irmão adjacente inferior do elemento selecionado.
  - Editar: abre o modal do pai (reutilizando a lógica existente).
  - Deletar: chama a confirmação via `confirm()` padrão do navegador. Se aceito, remove o nó do DOM e limpa seleção.
- [ ] Implementar o input de classes Tailwind acoplado ao toolbar:
  - Listar as classes correntes do elemento em tags removíveis com "x".
  - Campo input de digitação livre com aplicação no Enter/Blur e autocomplete baseado em classes comuns.
- [ ] Implementar o Breadcrumb horizontal integrado ao overlay de seleção:
  - Formatar a trilha DOM desde o contêiner raiz até o nó ativo.
  - Adicionar hover destacando os pais correspondentes no iframe.
  - Adicionar clique para transferir a seleção para o pai selecionado.

### 5. Mecanismo de Drag and Drop (`html-editor.js`)
- [ ] Escutar mousedown no botão drag da barra para iniciar o arraste.
- [ ] No mousemove do drag, encontrar o elemento sob a mira e desenhar a linha horizontal tracejada `.conn2flow-dnd-placeholder` (antes, depois ou dentro do elemento dependendo do cursor).
- [ ] No mouseup, reposicionar o elemento no DOM e limpar overlays de placeholder.

### 6. Histórico, Inclusão, Wrappers Virtuais e Lógica Inclusiva (`html-editor.js`)
- [ ] Implementar histórico Undo/Redo no iframe:
  - Arrays `undoStack` e `redoStack` na instância.
  - Configuração `config.undoLimit` com valor padrão `30` (editável).
  - Função helper para empurrar o estado HTML corrente do iframe a cada alteração do DOM.
  - Lógica para restaurar estados via comandos de mensagem (`undo` / `redo`) recebidos da janela pai.
- [ ] Escutar comando de inserção de novo elemento ou widget.
- [ ] Entrar no estado de inserção e usar a mesma linha tracejada para selecionar a posição de drop.
- [ ] Se for imagem, acionar a seleção do ImagePicker no pai e obter o link resultante.
- [ ] Ao clicar, inserir o novo elemento ou os comentários correspondentes ao widget no local desejado.
- [ ] Implementar o Wrapper de Widgets (`.conn2flow-widget-wrapper`):
  - No load, substituir os comentários de widgets por divs tracejadas laranja/amarelo com tag indicativa.
  - Bloquear interação/seleção dentro do wrapper, tratando-o de forma atômica no hover e click.
  - No save/sincronização, re-converter as divs de wrapper para os comentários originais de widget.
- [ ] Expandir a validação `isEditableElement` e `getEditType` para aceitar qualquer tag (não ignorada), mapeando para `code` (outerHTML) se não for imagem ou texto direto.

---

## Validação Esperada
- Executar `php -l` em `html-editor.php` e validar a integridade.
- Executar `node --check` em `html-editor.js` e `html-editor-interface.js`.
- Testar o fluxo completo de edição visual (hover, clique para selecionar, arrastar, duplicar, deletar, adicionar novo e adicionar widget).
- Testar histórico Undo/Redo com atalhos de teclado e botões de cabeçalho.
- Testar navegação por Breadcrumb e aplicação rápida de classes Tailwind.
- Validar redimensionador de tela e atomaticidade dos wrappers de widgets.
