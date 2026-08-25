---
name: c2f-resources-system
description: "LEIA ANTES de criar ou editar qualquer um dos 11 tipos de recursos nativos (pages, layouts, components, templates, variables, ai_prompts, etc.). Se não ler: recursos não entram no build, perdem vínculos e quebram o deploy."
user-invocable: false
---

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar, estruturar ou alterar arquivos dentro das pastas `resources/<lang>/` de módulos ou projetos.
- **SKIP APENAS SE**: Edição de arquivos não gerenciados pelo sistema de recursos (ex: scripts na raiz de `scripts/`).
- **CONSEQUÊNCIA DE IGNORAR**: Recursos não sincronizados para o banco de dados SQL, ausência de compilação em `*Data.json`, quebra de layouts no site publicado e dados desatualizados.

---

﻿---
name: c2f-resources-system
description: "Use ao criar, editar, compilar ou sincronizar recursos do Conn2Flow (paginas, layouts, componentes, templates, variaveis, prompts IA, formularios, widgets e tabelas declarativas)."
user-invocable: false
---

# Sistema de Recursos do Conn2Flow (`resources/`)

Consulte e aplique as seguintes convenÃ§Ãµes ao manipular o Sistema de Recursos e a arquitetura de compilaÃ§Ã£o de dados do Conn2Flow.

> [!WARNING]
> **REGRA MANDATÃ“RIA DE ARQUIVOS HTML, CSS E MARKDOWN**:
> NUNCA crie arquivos `.html`, `.css` ou `.md` soltos na raiz do projeto, em pastas pÃºblicas estÃ¡ticas ou na raiz de mÃ³dulos PHP!
> TODO e QUALQUER conteÃºdo HTML, CSS ou Markdown para pÃ¡ginas, layouts, componentes, templates ou prompts no Conn2Flow DEVE OBRIGATORIAMENTE ser criado dentro da estrutura do **Sistema de Recursos** (`resources/`), para que possa ser compilado em `*Data.json` e sincronizado no Banco de Dados SQL pelo runtime.

## 1. Arquitetura de Recursos (EdiÃ§Ã£o FÃ­sica -> CompilaÃ§Ã£o -> Banco)

* **Fonte (Source)**: Desenvolvedores criam/editam arquivos fÃ­sicos em `resources/<idioma>/<tipo>/<id>/<id>.<ext>`.
* **Recursos de MÃ³dulo**: `modulos/<modulo-id>/resources/<idioma>/<tipo>/<id>/<id>.<ext>`.
* **Natural Key**: O nome da pasta do recurso (`<id>`) Ã© a chave primÃ¡ria natural no Banco de Dados.
* **CompilaÃ§Ã£o**: O script `atualizacao-dados-recursos.php` lÃª os fontes e gera arquivos estÃ¡ticos em `gestor/db/data/*Data.json` (`PaginasData.json`, `LayoutsData.json`, `ComponentesData.json`).
* **SincronizaÃ§Ã£o**: O script `atualizacoes-banco-de-dados.php` aplica Upsert no Banco respeitando proteÃ§Ãµes de `user_modified = 1` e `project`.

---

## 2. Os 11 Tipos de Recursos Nativos

| Tipo | Tabela SQL | Arquivo Fonte | Uso |
|---|---|---|---|
| `pages` (paginas) | `paginas` | `<id>/<id>.html` + `<id>.css` | PÃ¡ginas com URL, vinculadas a layout via `id_layouts` |
| `layouts` | `layouts` | `<id>/<id>.html` + `<id>.css` | Estrutura externa (header/footer) com slot de inserÃ§Ã£o |
| `components` (componentes) | `componentes` | `<id>/<id>.html` + `<id>.css` | Blocos reutilizÃ¡veis de HTML/CSS |
| `templates` | `templates` | `<id>/<id>.html` | Templates de email, notificaÃ§Ã£o, etc. |
| `variables` (variaveis) | `variaveis` | `variables.json` | Mensagens, labels e textos multilÃ­ngues |
| `ai_prompts` | `prompts` | `<id>/<id>.md` | Prompts e instruÃ§Ãµes de IA em Markdown |
| `ai_modes` | `modos` | `<id>/<id>.md` | System prompts e modos de IA |
| `ai_prompts_targets` | `prompts_targets` | `<id>/<id>.md` | Targets de prompts de IA |
| `forms` (formularios) | `formularios` | `<id>/<id>.html` | FormulÃ¡rios HTML reutilizÃ¡veis |
| `widgets` | `widgets` | `<id>/<id>.html` + `<id>.css` | Widgets visuais de interface |

---

## 3. ConvenÃ§Ãµes de HTML e SeÃ§Ãµes

* Em arquivos de pÃ¡gina (`pages/<id>/<id>.html`), adicione obrigatoriamente os atributos Ã s tags `<section>`:
  ```html
  <section class="text-center mb-16" data-id="1" data-title="hero">
      <!-- ConteÃºdo da seÃ§Ã£o -->
  </section>
  ```
  - `data-id`: Ãndice numÃ©rico sequencial iniciando em 1.
  - `data-title`: Nome semÃ¢ntico simples da seÃ§Ã£o (ex: `hero`, `recursos`, `contato`).

---

## 4. Extensibilidade DinÃ¢mica de Recursos

Tabelas customizadas podem se tornar novos tipos de recursos automaticamente usando `sync_resources: true` em `tables_config.json` ou `<modulo>.json`:

```json
{
  "tabelas": {
    "minha_tabela": {
      "nome": "minha_tabela",
      "config": {
        "sync_resources": true,
        "resources_dir": "minha_tabela",
        "metadata_file": "minha_tabela.json",
        "field_types": {
          "html": "file:html",
          "css": "file:css",
          "conteudo": "file:md",
          "config": "json"
        }
      }
    }
  }
}
```

Os `field_types` suportados sÃ£o:
* `file:html` â€” Arquivo HTML fÃ­sico em `<id>/<id>.html`
* `file:css` â€” Arquivo CSS fÃ­sico em `<id>/<id>.css`
* `file:md` â€” Arquivo Markdown fÃ­sico em `<id>/<id>.md`
* `json` â€” Dados JSON inline no metadado

A compilaÃ§Ã£o gera `[PascalCase]Data.json` (ex: `MinhaTabelaData.json`) automaticamente.
