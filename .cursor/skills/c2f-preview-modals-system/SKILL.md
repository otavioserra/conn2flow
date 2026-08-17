---
name: c2f-preview-modals-system
description: Use ao implementar modais de pré-visualização, assistentes de ajuda contextual e exibição de preview de componentes/layouts no Conn2Flow.
user-invocable: false
---

# Sistema de Modais de Preview e Conhecimento (`CONN2FLOW-PREVIEW-MODALS-SYSTEM.md`)

Consulte e aplique as seguintes convenções para criação e exibição de modais dinâmicos no Conn2Flow:

## 1. Modais de Pré-Visualização no Gestor

* **Propósito**: Exibir prévia em tempo real de páginas, layouts, componentes ou conteúdos gerados por IA antes da publicação final.
* **Injeção de Modal Semantic UI**:
  ```html
  <div class="ui modal" id="modal-preview-recurso">
      <i class="close icon"></i>
      <div class="header">Pré-visualização do Recurso</div>
      <div class="scrolling content">
          <iframe id="iframe-preview" src="about:blank"></iframe>
      </div>
  </div>
  ```

---

## 2. Invocação via JavaScript

```javascript
function abrirPreviewRecurso(urlPreview) {
    $('#iframe-preview').attr('src', urlPreview);
    $('#modal-preview-recurso').modal('show');
}
```
