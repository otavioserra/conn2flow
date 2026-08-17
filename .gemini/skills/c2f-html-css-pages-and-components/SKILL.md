---
name: c2f-html-css-pages-and-components
description: Use ao criar, editar ou estruturar arquivos HTML, CSS ou Markdown para páginas, layouts, componentes e seções no Conn2Flow. Redireciona obrigatoriamente para o Sistema de Recursos (c2f-resources-system).
user-invocable: false
---

# Governança de Arquivos HTML, CSS e Markdown no Conn2Flow

> [!WARNING]
> **ATENÇÃO AGENTE: PROIBIDO CRIAR ARQUIVOS ESTÁTICOS SOLTOS**
> NUNCA crie arquivos `.html`, `.css` ou `.md` soltos na raiz do projeto, em diretórios públicos estáticos (ex: `public/`, `assets/`) ou na raiz de módulos PHP!

## Obrigatoriedade do Sistema de Recursos (`c2f-resources-system`)

No Conn2Flow, **todo conteúdo visual ou instrução textual** (páginas, layouts, componentes, templates, variáveis e prompts de IA) DEVE residir no **Sistema de Recursos** (`resources/`).

### Onde os arquivos DEVEM ser criados:

1. **Recursos Globais**:
   - Páginas: `gestor/resources/<idioma>/pages/<id>/<id>.html`
   - Layouts: `gestor/resources/<idioma>/layouts/<id>/<id>.html`
   - Componentes: `gestor/resources/<idioma>/components/<id>/<id>.html`

2. **Recursos de Módulo**:
   - Páginas: `modulos/<modulo-id>/resources/<idioma>/pages/<id>/<id>.html`
   - Componentes: `modulos/<modulo-id>/resources/<idioma>/components/<id>/<id>.html`

### Próximo Passo Obrigatório:
Consulte e aplique a skill principal **`c2f-resources-system`** para compilação (`atualizacao-dados-recursos.php`), metadados em arquivos JSON e atribuição de seções (`data-id` e `data-title`).
