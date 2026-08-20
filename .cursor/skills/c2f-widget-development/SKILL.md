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
