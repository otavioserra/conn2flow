# BATCH-034 - Aprimoramento do Editor HTML Visual

## Escopo do Lote
Este lote traz uma profunda reformulação de usabilidade e novos recursos ao Editor HTML Visual. Ele isola a interação em hover dinâmico e seleção fixa; inclui uma barra de ferramentas flutuante para Duplicar, Editar, Deletar (via `confirm()`) e Arrastar; implementa Drag and Drop (DnD) nativo com placeholders dinâmicos; e cria um fluxo de inserção de novos elementos e widgets (comunicação com banco via AJAX e dropdown popup). 

Adicionalmente, implementa os recursos Premium: Histórico de Ações (Undo/Redo configurável via `config.undoLimit` com padrão `30`), trilha Breadcrumb de navegação do DOM, Editor rápido de classes Tailwind CSS, alças de redimensionamento dinâmico do preview (Resize Handles) com indicador de pixels, e Wrappers Virtuais atômicos para isolamento de Widgets.

---

## Progresso por Slice

O lote foi quebrado nos seguintes slices funcionais:

| Slice | Escopo | Status | Validação |
| --- | --- | --- | --- |
| 1 | Backend: Rota AJAX `html-editor-widgets-list` em `html-editor.php` | complete | `php -l` OK |
| 2 | UI: Botão "+", Undo/Redo e alças de resize no visual-modal (pt-br/en) + i18n | complete | HTML + JSON válidos |
| 3 | Interface: Lógica do Popup, AJAX, atalhos teclado e redimensionamento no pai (novo `html-editor-visual-controls.js`) | complete | `node --check` OK |
| 4 | Visual Editor: Lógica de Duplo Overlay, Barra Flutuante, Tailwind Styler e Breadcrumb (`html-editor.js`) | complete | `node --check` OK |
| 5 | Visual Editor: Mecanismo de Drag and Drop (DnD) e Placeholders no iframe (`html-editor.js`) | complete | `node --check` OK |
| 6 | Visual Editor: Histórico Undo/Redo, Carga de Widgets, Wrappers Virtuais e tags editáveis (`html-editor.js`) | complete | `node --check` OK |

---

## Checklist de Implementação

### 1. Rota AJAX de Widgets (`html-editor.php`)
- [x] Tratar a opção `html-editor-widgets-list` na função `html_editor_ajax_interface()`.
- [x] Consultar e recolher slugs/nomes das tabelas `menus`, `galleries`, `publisher_highlights` e `publisher_index`.
- [x] Retornar o JSON estruturado agrupando os registros.

### 2. Interface de Botões e Alças (`html-editor-visual-modal.html`)
- [x] Adicionar o markup do botão "+" verde e botões desfazer/refazer (setas) em `pt-br/.../html-editor-visual-modal.html`.
- [x] Adicionar alças verticais de arraste (`.iframe-resize-handle-left` e `.iframe-resize-handle-right`) nas laterais do contêiner do iframe.
- [x] Adicionar um elemento indicador flutuante para mostrar a largura em pixels (ex: `1024px`).
- [x] Duplicar as mesmas marcações em `en/.../html-editor-visual-modal.html`.

### 3. Orquestrador da Interface (`html-editor-interface.js`)
- [x] Inicializar o popup Fomantic UI no botão "+" carregando as opções de tags HTML e as abas de widgets.
- [x] Executar AJAX ao focar na aba de widgets para popular a lista com os dados do backend.
- [x] Coordenar a seleção: ao clicar em um elemento/widget, disparar mensagem via `postMessage` ao iframe iniciando o modo de inserção.
- [x] Escutar atalhos de teclado `Ctrl + Z` / `Ctrl + Y` na janela pai e repassar ao iframe via `postMessage`.
- [x] Ligar eventos mouse de arraste nas alças laterais para alterar dinamicamente o `width` do iframe de preview e atualizar a etiqueta de pixels.
- [x] Sincronizar tamanho com cliques nos botões de desktop/tablet/mobile pré-configurados.

### 4. Overlays, Toolbar, Tailwind Styler e Breadcrumb (`html-editor.js`)
- [x] Implementar a separação física de `#html-editor-hover-overlay` (dinâmico no mousemove) e `#html-editor-selection-overlay` (fixo no click).
- [x] Adicionar a barra de ferramentas flutuante `#html-editor-floating-toolbar` acima do overlay de seleção.
- [x] Associar ações aos botões da barra:
  - Duplicar: clona e insere como irmão adjacente inferior do elemento selecionado.
  - Editar: abre o modal do pai (reutilizando a lógica existente).
  - Deletar: chama a confirmação via `confirm()` padrão do navegador. Se aceito, remove o nó do DOM e limpa seleção.
- [x] Implementar o input de classes Tailwind acoplado ao toolbar:
  - Listar as classes correntes do elemento em tags removíveis com "x".
  - Campo input de digitação livre com aplicação no Enter/Blur e autocomplete baseado em classes comuns.
- [x] Implementar o Breadcrumb horizontal integrado ao overlay de seleção:
  - Formatar a trilha DOM desde o contêiner raiz até o nó ativo.
  - Adicionar hover destacando os pais correspondentes no iframe.
  - Adicionar clique para transferir a seleção para o pai selecionado.

### 5. Mecanismo de Drag and Drop (`html-editor.js`)
- [x] Escutar mousedown no botão drag da barra para iniciar o arraste.
- [x] No mousemove do drag, encontrar o elemento sob a mira e desenhar a linha horizontal tracejada `.conn2flow-dnd-placeholder` (antes, depois ou dentro do elemento dependendo do cursor).
- [x] No mouseup, reposicionar o elemento no DOM e limpar overlays de placeholder.

### 6. Histórico, Inclusão, Wrappers Virtuais e Lógica Inclusiva (`html-editor.js`)
- [x] Implementar histórico Undo/Redo no iframe:
  - Arrays `undoStack` e `redoStack` na instância.
  - Configuração `config.undoLimit` com valor padrão `30` (editável).
  - Função helper para empurrar o estado HTML corrente do iframe a cada alteração do DOM.
  - Lógica para restaurar estados via comandos de mensagem (`undo` / `redo`) recebidos da janela pai.
- [x] Escutar comando de inserção de novo elemento ou widget.
- [x] Entrar no estado de inserção e usar a mesma linha tracejada para selecionar a posição de drop.
- [x] Se for imagem, acionar a seleção do ImagePicker no pai e obter o link resultante.
- [x] Ao clicar, inserir o novo elemento ou os comentários correspondentes ao widget no local desejado.
- [x] Implementar o Wrapper de Widgets (`.conn2flow-widget-wrapper`):
  - No load, substituir os comentários de widgets por divs tracejadas laranja/amarelo com tag indicativa.
  - Bloquear interação/seleção dentro do wrapper, tratando-o de forma atômica no hover e click.
  - No save/sincronização, re-converter as divs de wrapper para os comentários originais de widget.
- [x] Expandir a validação `isEditableElement` e `getEditType` para aceitar qualquer tag (não ignorada), mapeando para `code` (outerHTML) se não for imagem ou texto direto.

---

## Validação Esperada
- Executar `php -l` em `html-editor.php` e validar a integridade.
- Executar `node --check` em `html-editor.js` e `html-editor-interface.js`.
- Testar o fluxo completo de edição visual (hover, clique para selecionar, arrastar, duplicar, deletar, adicionar novo e adicionar widget).
- Testar histórico Undo/Redo com atalhos de teclado e botões de cabeçalho.
- Testar navegação por Breadcrumb e aplicação rápida de classes Tailwind.
- Validar redimensionador de tela e atomaticidade dos wrappers de widgets.

---

## Evidência de Validação (BATCH-034) — 2026-06-13

- Validação estática executada (sem ambiente Docker nesta rodada):
  - `php -l gestor/bibliotecas/html-editor.php` → `No syntax errors detected`.
  - `node --check` OK em `gestor/assets/interface/html-editor.js`, `html-editor-interface.js`, `html-editor-visual-controls.js` (novo) e `html-editor-helper.js`.
  - `JSON.parse` OK em `gestor/resources/pt-br/variables.json` e `gestor/resources/en/variables.json`.
- Arquivos alterados/criados:
  - **Criado** `gestor/assets/interface/html-editor-visual-controls.js` (janela pai: painel "+", AJAX `html-editor-widgets-list`, atalhos Ctrl+Z/Y, alças de resize, sync de largura via `data-width`).
  - **Reescrito** `gestor/assets/interface/html-editor.js` (iframe: duplo overlay, toolbar flutuante drag/dup/edit/del, Tailwind styler + datalist, breadcrumb, tags editáveis permissivas, DnD com placeholder, undo/redo `undoLimit=30`, modo de inserção, wrappers virtuais de widget, `getCleanHtml()`).
  - `gestor/bibliotecas/html-editor.php` (rota AJAX `html-editor-widgets-list` + inclusão do novo JS + 3 placeholders i18n no visual-modal).
  - `gestor/assets/interface/html-editor-interface.js` (2 edições cirúrgicas: remoção do handler antigo `.screenPagina`; uso de `htmlEditorGetCleanHtml()` no save com fallback).
  - `gestor/resources/{pt-br,en}/components/html-editor-visual-modal/html-editor-visual-modal.html` (botão "+", undo/redo, alças `.iframe-resize-handle-*`, `.iframe-resize-indicator`, wrapper `.iframe-preview-frame`).
  - `gestor/resources/{pt-br,en}/variables.json` (3 variáveis: `html-editor-add-element-tooltip`/`-undo-tooltip`/`-redo-tooltip`).
- Decisões: design em [DEC-047](../decisions/DECISION-LOG.md#dec-047---2026-06-13---accepted); execução/divergências em [DEC-048](../decisions/DECISION-LOG.md#dec-048---2026-06-13---accepted).
- Pendências (com o operador, após `🗃️ Projects - Update => Core` que sincroniza os assets/componentes, compila o `VariaveisData.json` a partir do `variables.json` e recalcula checksums):
  - **Tags/seleção**: passar o mouse sobre `div`/`section`/contêineres mostra hover; clicar fixa o overlay roxo de seleção persistente; clicar no fundo limpa.
  - **Toolbar**: duplicar cria irmão idêntico; deletar pede `confirm()`; editar abre o modal (text/image/code).
  - **DnD**: arrastar pelo handle reposiciona o nó na linha tracejada (antes/depois/dentro de contêiner vazio).
  - **Inclusão "+"**: inserir elemento HTML e widget (slug do banco via AJAX) na posição do placeholder; imagem abre o ImagePicker.
  - **Undo/Redo**: até 30 estados por botões e Ctrl+Z / Ctrl+Y (foco no pai e no iframe).
  - **Breadcrumb + Tailwind styler**: navegar pela árvore; adicionar/remover classes.
  - **Resize**: arrastar as alças e clicar desktop/tablet/mobile (100%/768px/375px) com indicador de px.
  - **Wrappers de widget**: blocos de widget viram divs tracejadas amarelas atômicas e voltam a comentários no salvar.
