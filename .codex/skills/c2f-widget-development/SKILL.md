---
name: c2f-widget-development
description: "LEIA ANTES de desenvolver ou alterar widgets do sistema e seus renderizadores. Se não ler: widgets quebram o isolamento de escopo, duplicam IDs no DOM e falham na renderização pública."
user-invocable: false
---

# Desenvolvimento de widgets Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar ou modificar widgets (`*.widget.php`, `*.widget.js`), tags `@[[widgets#...]]@` ou controladores de renderização de widgets.
- **SKIP APENAS SE**: Componentes visuais estáticos que não possuem ciclo de vida autônomo de widget.
- **CONSEQUÊNCIA DE IGNORAR**: Colisão de identificadores no DOM ao instanciar múltiplos widgets, perda de dados na injeção de parâmetros dinâmicos e quebra de renderização no site final.

---

1. Injete CSS, head e JavaScript por `gestor_pagina_recursos_incluir([...])`; centralize a inclusão e preserve a deduplicação do helper.
2. Não chame novamente controladores de recursos que o render do widget já inclui.
3. No frontend envie `ajaxOpcao`; no backend trate a mesma ação em `$_GESTOR['ajax-opcao']` e evite nomes reservados pelo fechamento AJAX genérico.
4. Para tokens de item, aceite wrappers opcionais com `/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/` e substitua todas as ocorrências.
5. Mantenha blocos de repetição, vazio e controles compatíveis com o contrato do AI mode/template do widget.
6. Valide duas renderizações na mesma página para detectar duplicação de assets, além do caminho AJAX feliz e de erro.

---

## 7. Contrato Modular de Grid (Dimensões Dinâmicas e Drag-and-Drop)

Todo widget que participa de painéis modulares, dashboards ou layouts customizáveis deve obedecer ao **contrato de grid modular**:

### 7.1 Dimensões Dinâmicas (`width` e `height`)
- Os widgets devem ser declarados com suporte a redimensionamento em unidades de grid (colunas e linhas):
  - `grid_w` (largura em colunas, ex: `1` a `12`).
  - `grid_h` (altura em linhas/unidades de altura, ex: `1` a `6`).
- **Limites Estritos de Redimensionamento**:
  - `min_w`: largura mínima para renderização legível sem truncamento de controles.
  - `max_w`: largura máxima permitida no container.
  - `min_h`: altura mínima que garante exibição do conteúdo essencial sem overflow vertical quebrado.
  - `max_h`: altura máxima recomendada para evitar distorção de proporção.
- O HTML e CSS do widget devem utilizar containers flexíveis (`w-full h-full`) e respeitar overflow interno com scrollbars sutis (`overflow-y-auto overflow-x-hidden`) quando o conteúdo exceder a área útil.

### 7.2 Drag-and-Drop e Preservação de Estado
- Ao mover ou redimensionar widgets em um layout interativo, a biblioteca cliente emite eventos de layout.
- O widget deve manter seletores com atributos de dados isolados (ex: `data-widget-id="<id>"`, `data-widget-instance="<hash>"`) para evitar que clones ou múltiplos widgets na mesma tela colidam event listeners ou propriedades de estilo.

---

## 8. Ciclo de Vida e Re-renderização AJAX (`ajaxOpcao: 'widget-render'`)

Widgets dinâmicos que atualizam seu conteúdo assincronamente (filtros, paginação interna, polling ou atualização pós-redimensionamento) devem adotar o endpoint padronizado:

### 8.1 Chamada Frontend
```javascript
gestorAjax(state, 'widget-render', {
    widget_id: 'meu-widget',
    instance_id: instanceId,
    width: currentGridWidth,
    height: currentGridHeight,
    params: { /* parâmetros específicos do widget */ }
}).then(function(response) {
    if (response.status === 'Ok' && response.data.html) {
        container.innerHTML = response.data.html;
        if (response.data.css) {
            // Injeção dinâmica ou atualização de estilos
        }
        // Reinicializar listeners específicos da instância
        widgetInit(container, response.data);
    }
});
```

### 8.2 Manipulador Backend no Módulo/Controlador
```php
if (!empty($_GESTOR['ajax'])) {
    interface_ajax_iniciar();

    switch ($_GESTOR['ajax-opcao']) {
        case 'widget-render':
            $widget_id = $_REQUEST['widget_id'] ?? '';
            $width = (int)($_REQUEST['width'] ?? 1);
            $height = (int)($_REQUEST['height'] ?? 1);
            $params = $_REQUEST['params'] ?? [];

            $render = widget_renderizar([
                'id' => $widget_id,
                'width' => $width,
                'height' => $height,
                'params' => $params,
                'return_css' => true
            ]);

            $_GESTOR['ajax-json'] = [
                'status' => 'Ok',
                'data' => [
                    'html' => $render['html'] ?? '',
                    'css' => $render['css'] ?? ''
                ]
            ];
            break;
    }

    interface_ajax_finalizar();
    return;
}
```

> [!IMPORTANT]
> A re-renderização via `ajaxOpcao: 'widget-render'` não deve reinjetar dependências de scripts globais (`<script>` no head) já carregadas; use `gestor_pagina_recursos_incluir()` com deduplicação nativa.
