---
name: c2f-resources-system
description: "LEIA ANTES de criar ou editar qualquer um dos 11 tipos de recursos nativos (pages, layouts, components, templates, variables, ai_prompts, etc.). Se não ler: recursos não entram no build, perdem vínculos, mantêm cache stale de JS/CSS e quebram o deploy."
user-invocable: false
---

# Sistema de Recursos do Conn2Flow (`resources/`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Criar, estruturar ou alterar arquivos dentro das pastas `resources/<lang>/` de módulos ou projetos.
- **SKIP APENAS SE**: Edição de arquivos não gerenciados pelo sistema de recursos (ex: scripts na raiz de `scripts/`).
- **CONSEQUÊNCIA DE IGNORAR**: Recursos não sincronizados para o banco de dados SQL, ausência de compilação em `*Data.json`, mascaramento de bugs por cache stale de JavaScript/CSS e quebra de layouts no site publicado.

---

> [!WARNING]
> **REGRA MANDATÓRIA DE ARQUIVOS HTML, CSS E MARKDOWN**:
> NUNCA crie arquivos `.html`, `.css` ou `.md` soltos na raiz do projeto, em pastas públicas estáticas ou na raiz de módulos PHP!
> TODO e QUALQUER conteúdo HTML, CSS ou Markdown para páginas, layouts, componentes, templates ou prompts no Conn2Flow DEVE OBRIGATORIAMENTE ser criado dentro da estrutura do **Sistema de Recursos** (`resources/`), para que possa ser compilado em `*Data.json` e sincronizado no Banco de Dados SQL pelo runtime.

## 1. Arquitetura de Recursos (Edição Física -> Compilação -> Banco)

* **Fonte (Source)**: Desenvolvedores criam/editam arquivos físicos em `resources/<idioma>/<tipo>/<id>/<id>.<ext>`.
* **Recursos de Módulo**: `modulos/<modulo-id>/resources/<idioma>/<tipo>/<id>/<id>.<ext>`.
* **Natural Key**: O nome da pasta do recurso (`<id>`) é a chave primária natural no Banco de Dados.
* **Compilação**: O comando `c2f resources:sync` lê os fontes e gera arquivos estáticos em `gestor/db/data/*Data.json` (`PaginasData.json`, `LayoutsData.json`, `ComponentesData.json`).
* **Sincronização**: O runtime aplica Upsert no Banco respeitando proteções de `user_modified = 1` e `project`.

---

## 2. Os 11 Tipos de Recursos Nativos

| Tipo | Tabela SQL | Arquivo Fonte | Uso |
|---|---|---|---|
| `pages` (paginas) | `paginas` | `<id>/<id>.html` + `<id>.css` | Páginas com URL, vinculadas a layout via `id_layouts` |
| `layouts` | `layouts` | `<id>/<id>.html` + `<id>.css` | Estrutura externa (header/footer) com slot de inserção |
| `components` (componentes) | `componentes` | `<id>/<id>.html` + `<id>.css` | Blocos reutilizáveis de HTML/CSS |
| `templates` | `templates` | `<id>/<id>.html` | Templates de email, notificação, etc. |
| `variables` (variaveis) | `variaveis` | `variables.json` | Mensagens, labels e textos multilíngues |
| `ai_prompts` | `prompts` | `<id>/<id>.md` | Prompts e instruções de IA em Markdown |
| `ai_modes` | `modos` | `<id>/<id>.md` | System prompts e modos de IA |
| `ai_prompts_targets` | `prompts_targets` | `<id>/<id>.md` | Targets de prompts de IA |
| `forms` (formularios) | `formularios` | `<id>/<id>.html` | Formulários HTML reutilizáveis |
| `widgets` | `widgets` | `<id>/<id>.html` + `<id>.css` | Widgets visuais de interface |

---

## 🏷️ 3. Regra Mandatória de Version Bump (Cache-Busting)

Ao criar ou editar qualquer script JavaScript (`<id>.js`) ou folha de estilo (`<id>.css`) em `resources/`:
* O agente DEVE OBRIGATORIAMENTE realizar o **version bump** (incremento de versão, ex: `"versao": "1.0.0"` ➔ `"1.0.1"`) no arquivo de metadados do recurso (`<id>.json`) ou no manifest do módulo (`<modulo>.json`).
* Isso assegura que, ao rodar `c2f resources:sync`, a query string `<script src="...&v=1.0.1">` seja atualizada, forçando o navegador a invalidar o cache antigo imediatamente e prevenindo erros por execução de assets obsoletos (*stale cache*).

---

## 4. Convenções de HTML e Seções

* Em arquivos de página (`pages/<id>/<id>.html`), adicione obrigatoriamente os atributos às tags `<section>`:
  ```html
  <section class="text-center mb-16" data-id="1" data-title="hero">
      <!-- Conteúdo da seção -->
  </section>
  ```
  - `data-id`: Índice numérico sequencial iniciando em 1.
  - `data-title`: Nome semântico simples da seção (ex: `hero`, `recursos`, `contato`).

---

## 5. Extensibilidade Dinâmica de Recursos

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

Os `field_types` suportados são:
* `file:html` — Arquivo HTML físico em `<id>/<id>.html`
* `file:css` — Arquivo CSS físico em `<id>/<id>.css`
* `file:md` — Arquivo Markdown físico em `<id>/<id>.md`
* `json` — Dados JSON inline no metadado

A compilação gera `[PascalCase]Data.json` (ex: `MinhaTabelaData.json`) automaticamente.
