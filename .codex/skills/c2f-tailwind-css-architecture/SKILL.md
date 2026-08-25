---
name: c2f-tailwind-css-architecture
description: "LEIA OBRIGATORIAMENTE antes de criar, alterar ou migrar qualquer tela, layout, página ou componente que utilize Tailwind CSS v4. Previne conflitos de cascata, mascaramento por css_compiled em banco, descarte de estilos em runtime e quebra de builds."
user-invocable: false
---

# Governança e Arquitetura do Tailwind CSS v4 no Conn2Flow e Projetos

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar, alterar, migrar ou debugar qualquer layout, página, componente ou template com classes do Tailwind CSS v4.
- **SKIP APENAS SE**: Tarefas puramente de backend em PHP ou APIs sem renderização visual.
- **CONSEQUÊNCIA DE IGNORAR**: Quebra da cascata CSS (sidebar oculta no desktop), mascaramento de código pelo cache de banco (`css_compiled`) e descarte de estilos em runtime.

---

## ⛔ Regras Invioláveis
1. **NUNCA execute comandos CLI manuais do Tailwind** (`npx tailwindcss`, etc.). Use sempre `./c2f resources:sync` (ou `php atualizacao-dados-recursos.php`).
2. **Todo HTML/CSS visual deve residir no Sistema de Recursos**: `resources/<idioma>/<tipo>/<id>/<id>.html` e `<id>.json`.
3. **Metadados JSON**: Todo recurso Tailwind DEVE declarar `"framework_css": "tailwindcss"` em seu arquivo de metadados `<id>.json`.
4. **Templates Dinâmicos em Runtime (Finding F2)**: Declare dependências de templates dinâmicos no array `"tailwind_dependencies": ["id-1", "id-2"]` do JSON do recurso pai.
5. **Limpeza Obrigatória de Cache no Banco (`css_compiled`)**: Ao editar arquivos físicos em disco, zere `paginas.css_compiled = NULL` no banco de dados para que o arquivo `.precompiled.css` entre em vigor sem mascaramento.
6. **Cascata e Media Queries**: Nunca use `.hidden` isolado em páginas filhas que conflitem com `lg:flex` ou `md:block` do layout pai; use sempre prefixos explícitos de breakpoint (ex: `hidden lg:flex`).
