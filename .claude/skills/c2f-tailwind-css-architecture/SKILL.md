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
5. **Cascata e Media Queries**: Nunca use `.hidden` isolado em páginas filhas que conflitem com `lg:flex` ou `md:block` do layout pai; use sempre prefixos explícitos de breakpoint (ex: `hidden lg:flex`).

---

## 🏗️ Autoria vs Derivado (Eliminação do Contorno Manual)

A arquitetura CSS do Conn2Flow separa estritamente os campos de **autoria** (preservados) dos campos **derivados** (sempre recalculáveis):

| Campo no Banco | Classificação | Descrição |
|---|---|---|
| `html` | **AUTORIA** | HTML original editado pelo autor. Preservado conforme `user_modified` e `project`. |
| `css` | **AUTORIA** | CSS original editado pelo autor. Preservado conforme `user_modified` e `project`. |
| `css_precompiled` | **DERIVADO** | CSS intermediário gerado pelo pipeline de compilação Tailwind. Nunca editado manualmente. |
| `css_compiled` | **DERIVADO** | CSS final otimizado servido ao visitante. Nunca editado manualmente. |

> [!CAUTION]
> **Regra de Eliminação do Hack Legacy**: A prática antiga de zerar `paginas.css_compiled = NULL` diretamente no banco era um contorno manual de um problema estrutural. Esta prática é **proibida**. O procedimento correto é usar `c2f css:rebuild` para recalcular o CSS derivado a partir da autoria vigente.

---

## 🔧 Instrumentos de Medição e Reconstrução de CSS

Em vez de inferir estilização por leitura cega de código, utilize os comandos de auditoria:

### `c2f css:audit`
Audita a procedência (`css_source_hash`), cobertura de classes e classes Tailwind embutidas em PHP/JS por tabela.
```bash
./c2f css:audit
```

### `c2f css:audit --url=<rota>`
Audita a página composta real (com layout + componentes) e mapeia classes órfãs ao recurso de origem.
```bash
./c2f css:audit --url=/transformamp/home
```

### `c2f css:rebuild`
Reconstrói o CSS derivado (`css_precompiled` e `css_compiled`) usando o HTML real do banco como fonte.
```bash
./c2f css:rebuild
./c2f css:rebuild --url=/transformamp/home
```

---

## ⚠️ Dívida Técnica: `tailwind_sources` em PHP/JS

Apontar `tailwind_sources` para arquivos `.php` ou `.js` indica classes utilitárias do Tailwind montadas em tempo de execução via JavaScript ou PHP. Isso viola a arquitetura do Conn2Flow, onde PHP/JS não devem carregar marcação ou estilos.

**Classificação**: Dívida técnica a eliminar.
**Ação Corretiva**: Mover a geração de classes para componentes/templates dentro do Sistema de Recursos (`resources/`), onde o compilador Tailwind pode escaneá-las estaticamente.
