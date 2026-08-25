---
name: c2f-preview-modals-system
description: "LEIA ANTES de implementar ou editar modais de pré-visualização ao vivo no editor. Se não ler: estilos do painel vazam para dentro do preview, o iframe falha em renderizar ou trava por Content Security Policy."
user-invocable: false
---

# Sistema de Modais de Preview e Conhecimento (`CONN2FLOW-PREVIEW-MODALS-SYSTEM.md`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Implementar ou modificar modais que renderizam componentes, templates ou páginas em modo preview dentro do Gestor.
- **SKIP APENAS SE**: Modais simples de diálogo (alertas, confirmações simples) que não renderizam conteúdo HTML de terceiros.
- **CONSEQUÊNCIA DE IGNORAR**: Poluição de CSS do painel administrativo sobre o componente pré-visualizado, falha no cálculo de altura do iframe ou bloqueios de CORS.

---

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
