---
name: c2f-tailwind-css-architecture
description: "LEIA OBRIGATORIAMENTE antes de criar, alterar ou migrar qualquer tela, layout, página ou componente que utilize Tailwind CSS v4. Previne conflitos de cascata, mascaramento por css_compiled em banco, descarte de estilos em runtime e quebra de builds."
user-invocable: false
---

# Governança e Arquitetura do Tailwind CSS v4 no Conn2Flow e Projetos

## Diagnóstico de CSS em runtime com Chrome DevTools MCP

Após sincronizar pelo pipeline, siga `c2f-agent-visual-inspection`: capture screenshot e snapshot em desktop/mobile e após alternar estados dinâmicos. Meça o nó com `evaluate_script` (`getComputedStyle`, `getBoundingClientRect`) e correlacione classes reais, breakpoint, display, posição e overflow com o CSS entregue pelo SQL.

Quando `get_css_styles` estiver no catálogo da sessão, use-o para examinar regras correspondentes e estilos computados. Compare regras sobrescritas, especificidade, ordem e contexto flex/grid; uma declaração aplicada pode ser inativa por incompatibilidade de contexto. Não trate o indicador visual de estilos inativos do DevTools como uma ferramenta MCP garantida. Sem essa ferramenta, combine medições e inspeção das folhas carregadas, registrando a limitação.

Correlacione com `c2f css:audit --url=<rota>` e reconstrua derivados pelo `c2f css:rebuild` autorizado. Não use alterações transitórias no DOM/CSS nem updates diretos em `css_compiled` como solução final; valide novamente o recurso sincronizado.

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar, alterar, migrar ou debugar qualquer layout, página, componente ou template com classes do Tailwind CSS v4.
- **SKIP APENAS SE**: Tarefas puramente de backend em PHP ou APIs sem renderização visual.
- **CONSEQUÊNCIA DE IGNORAR**: Quebra da cascata CSS (sidebar oculta no desktop), mascaramento de código pelo cache de banco (`css_compiled`) e descarte de estilos em runtime.

---

## ⛔ Regras Invioláveis
1. **NUNCA execute comandos manuais via `npx`** (`npx tailwindcss`, etc.). Use sempre `./c2f resources:sync` (ou `php atualizacao-dados-recursos.php`). Quando a invocação direta for estritamente necessária em depuração local, use **exclusivamente os binários locais em `node_modules/.bin/tailwindcss.cmd` e `node_modules/.bin/terser.cmd`** (Armadilha 17: proibição absoluta de `npx`).
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

---

## 📋 Registro Obrigatório de `tailwind_sources` e `tailwind_sources_reason`

### Problema

O compilador prévio de Tailwind analisa unicamente o HTML estático do recurso. Classes utilitárias montadas dinamicamente em PHP (ex: badges de status), manipuladas em JS (`classList.toggle('hidden')`) ou configuradas em JSON de módulos **não entram no bundle gerado**, causando falhas visuais silenciosas.

### Diretriz Normativa

Para páginas do gestor/site que utilizam classes Tailwind fora do HTML estático, declare os metadados no `<id>.json` do recurso:

```json
{
  "tailwind_sources": [
    "../../../../modulos/ecommerce/ecommerce-checkout.php"
  ],
  "tailwind_sources_reason": "Classes de badge de status (bg-green-100, text-red-600) são montadas em PHP com base no estado do pedido"
}
```

| Campo | Obrigatório | Descrição |
|---|---|---|
| `tailwind_sources` | Sim | Array de caminhos relativos aos arquivos PHP/JS que contêm classes Tailwind dinâmicas |
| `tailwind_sources_reason` | **Sim** | Justificativa textual para a inclusão — o build **acusa erro fatal** se a razão for omitida |

### Alternativa em Bibliotecas do Core

Para bibliotecas do Core que geram HTML com classes Tailwind, encapsule a paleta dinâmica num elemento `<template>` inerte no próprio componente HTML do recurso:

```html
<!-- Paleta de classes dinâmicas para o scanner Tailwind -->
<template data-tailwind-palette>
  <span class="bg-green-100 text-green-800 bg-red-100 text-red-800 bg-yellow-100 text-yellow-800"></span>
</template>
```

> [!CAUTION]
> Sem o registro de `tailwind_sources`, classes utilitárias usadas exclusivamente em PHP/JS são **eliminadas do CSS compilado** no build. O erro é silencioso — a página renderiza sem as classes, causando quebra visual sem mensagem de erro.

---

## ⚙️ Resolução de Binários Locais e Migração Modular (`node_modules/.bin/`)

Para garantir imunidade ao cache volátil do NPM e evitar falhas de PATH no Windows (Armadilha 17):
- Todo pipeline ou script que invoca o compilador Tailwind ou minificador Terser deve referenciar diretamente os binários locais em `node_modules/.bin/tailwindcss.cmd` (Windows) ou `node_modules/.bin/tailwindcss` (Linux/macOS), sem intermediar chamadas via `npx`.
- Ao migrar módulos administrativos de Fomantic-UI para Tailwind, consulte e aplique a skill canônica `c2f-tailwind-module-migration`, respeitando a ordem de bundles (`$_GESTOR['tailwind-page-bundle'] = true`), as variantes `-tailwind` em `interface_componente_variante()` e as classes utilitárias isoladas no HTML de recurso.

