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

### Proporcionalidade de recompilação — REQ-075 / Core REQ-269

Classifique o diff antes de compilar:

- **Categoria A:** backend PHP/APIs/migrations/regras de negócio sem alteração de HTML, classes Tailwind, CSS ou fontes/configuração de compilação. Não recompile Tailwind. Se sincronização local for necessária, use `./c2f project:update-all <id> --skip-css` ou `./c2f manager:update-all --skip-css`. Mudanças exclusivamente de Core/banco admitem `./c2f project:sync-core <id> --skip-css` seguido de `./c2f project:sync-db <id> --core-resources`.
- **Categoria B:** documentação, skills, testes unitários e memória MDD. Execute apenas os testes/checks locais pertinentes; nenhum pipeline de deploy no Lab.
- **Categoria C:** telas/templates HTML/componentes/classes/CSS autoral/fontes de compilação. Execute o pipeline completo sem `--skip-css`; mudanças mistas com impacto visual também são C.

Ignorar CSS não significa ignorar dados: recursos alterados ainda precisam gerar seus `*Data.json` e chegar ao banco pelo pipeline. A flag dispensa Tailwind e CSS derivado, preservando as demais etapas. A via cirúrgica de Core/banco não compila recursos do projeto. Mantenha version bump e invalidação de cache para scripts/estilos alterados. Não faça build completo em A nem deploy em B; não prometa duração <45 segundos sem medir no ambiente real.

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

## 🗄️ 3.1. Fonte da Verdade em Runtime (Banco vs Disco)

> [!IMPORTANT]
> O runtime do Conn2Flow serve HTML e CSS exclusivamente do **BANCO DE DADOS** (`gestor.php:2782`). O diretório `resources/` é lido diretamente apenas sob `DEVELOPMENT_ENV=true`.

**Fluxo Arquitetural**:
```
resources/ (disco) → c2f resources:sync → *Data.json → Upsert no Banco SQL → Runtime serve do Banco
```

* **Disco (`resources/`)**: É a **semente de compilação** — a fonte de autoria onde desenvolvedores criam e editam.
* **Banco de Dados**: É a **fonte da verdade em runtime** — o que o visitante vê e o que o Gestor edita.
* **Pipeline Oficial**: Alterações em disco SÓ chegam ao runtime após execução de `c2f manager:update-all` (para o sistema) ou `c2f project:update-all <id>` (para projetos).

**Ao investigar o comportamento de uma página publicada:**
1. Inspecione o banco PRIMEIRO (tabelas `paginas`, `layouts`, `componentes`).
2. Compare com `resources/` para identificar divergências.
3. NUNCA assuma que o conteúdo em disco é o que está sendo servido.

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

Nota de autoria: na via cirúrgica Core/banco, --core-resources preserva o dono das sementes do Core. Dados e recursos próprios do projeto exigem sua sincronização específica; não use essa flag para transferir autoria.
