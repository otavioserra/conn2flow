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
