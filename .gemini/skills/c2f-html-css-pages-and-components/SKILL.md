---
name: c2f-html-css-pages-and-components
description: "LEIA ANTES de criar ou alterar telas, páginas, layouts ou componentes HTML/CSS. Se não ler: arquivos estáticos soltos fora de resources/ não entram no build, não sincronizam para o banco e quebram no site publicado."
user-invocable: false
---

# Governança de Arquivos HTML, CSS e Markdown no Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Escrever, editar ou migrar marcações HTML, estilos CSS ou layouts do sistema.
- **SKIP APENAS SE**: Manipulação de lógica puramente de backend em PHP ou APIs sem renderização visual.
- **CONSEQUÊNCIA DE IGNORAR**: Criação de arquivos estáticos soltos que são ignorados pelo pipeline de sincronização, não chegam ao banco de dados e geram páginas 404 em produção.

---

> [!WARNING]
> **ATENÇÃO AGENTE: PROIBIDO CRIAR ARQUIVOS ESTÁTICOS SOLTOS**
> NUNCA crie arquivos `.html`, `.css` ou `.md` soltos na raiz do projeto, em diretórios públicos estáticos (ex: `public/`, `assets/`) ou na raiz de módulos PHP!

## 1. Obrigatoriedade do Sistema de Recursos (`c2f-resources-system`)

No Conn2Flow, **todo conteúdo visual ou instrução textual** (páginas, layouts, componentes, templates, variáveis e prompts de IA) DEVE residir no **Sistema de Recursos** (`resources/`).

### Onde os arquivos DEVEM ser criados:

1. **Recursos Globais**:
   - Páginas: `gestor/resources/<idioma>/pages/<id>/<id>.html`
   - Layouts: `gestor/resources/<idioma>/layouts/<id>/<id>.html`
   - Componentes: `gestor/resources/<idioma>/components/<id>/<id>.html`

2. **Recursos de Módulo**:
   - Páginas: `modulos/<modulo-id>/resources/<idioma>/pages/<id>/<id>.html`
   - Componentes: `modulos/<modulo-id>/resources/<idioma>/components/<id>/<id>.html`

---

## 2. A Regra Arquitetural de 2 Níveis do `HTML_SANITIZE`

O Conn2Flow implementa entrega inteligente e bifurcada de HTML controlada pela variável `HTML_SANITIZE` no `.env`:

```mermaid
flowchart TD
    Req["Requisição de Página HTML"] --> Check{"gestor_dashboard_toolbar_ativo()?"}
    Check -- "NÃO (Visitante Público)" --> Sanitize["HTML_SANITIZE Ativo (100%)<br/>Remove comentários HTML/CSS/JS e minifica"]
    Sanitize --> OutputPublic["HTML Minificado e Limpo<br/>Máxima performance e privacidade"]
    Check -- "SIM (Admin / Live Editor / Sessão IA)" --> Bypass["HTML_SANITIZE Bypassado (100%)<br/>Preserva comentários, indentação e marcadores <!-- widgets# -->"]
    Bypass --> OutputAdmin["HTML Verbatim Intacto<br/>Live Editor e Agentes funcionam com precisão"]
```

### A. Visitante Público / Anônimo (`gestor_dashboard_toolbar_ativo() === false`):
* `gestor_html_higienizar()` executa **100%**, removendo todos os comentários (`<!-- ... -->`, `/* ... */`, `// ...`) e compactando espaços em branco.
* **Resultado**: Segurança, privacidade e performance máxima para o público geral (zero vazamento de comentários internos ou notas de engenharia).

### B. Administrador Autenticado / Live Editor / Sessão IA (`gestor_dashboard_toolbar_ativo() === true`):
* `gestor_pagina_higienizar_ativo()` retorna **`false` incondicionalmente**.
* O sanitizador de HTML é **100% BYPASSADO / DESLIGADO**.
* **Resultado**: O HTML é entregue **exatamente como no original** — com todos os comentários de raciocínio, notas de arquitetura, seções (`data-id`, `data-title`) e marcadores de widgets (`<!-- widgets#... -->` e `<!-- /widgets#... -->`) preservados intactos para o funcionamento do Live Editor (`dashboard.toolbar.js`) e ferramentas de inspeção de IA.

---

### Próximo Passo Obrigatório:
Consulte e aplique a skill principal **`c2f-resources-system`** para compilação (`c2f resources:sync`), metadados em arquivos JSON e atribuição de seções (`data-id` e `data-title`).
