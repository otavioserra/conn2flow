# Prompt Interactive Programming - Gerenciador de Recursos (Upsert/Delete)

## 🎯 Contexto Inicial
Este documento define a especificação técnica para o script `upsert-resources.php` localizado no seguinte caminho `ai-workspace\scripts\resources\upsert-resources.php`. O objetivo é criar uma ferramenta de linha de comando (CLI) robusta para criar, atualizar (upsert) e deletar recursos (páginas, layouts, componentes, templates, variáveis) dentro do ecossistema Conn2Flow.

O script atua como o gerenciador da **Fonte da Verdade** (Source of Truth) do sistema. Ele manipula os arquivos físicos e metadados JSON localizados em `resources/` e `modulos/`. Estes arquivos são posteriormente consumidos pelo script `atualizacao-dados-recursos.php`, que os consolida em arquivos `*Data.json` para serem finalmente aplicados ao banco de dados via `atualizacoes-banco-de-dados.php`.

O script deve ser capaz de manipular recursos em três contextos principais (Gestor, Plugins, Projetos) e em dois escopos (Global, Módulo), lidando com a complexidade de caminhos e estruturas de arquivos diferentes para cada combinação.

## 📝 Especificação Técnica

### 1. Parâmetros da CLI
O script deve aceitar os seguintes argumentos:

| Argumento | Descrição | Padrão | Obrigatório |
| :--- | :--- | :--- | :--- |
| `--target` | Alvo da operação: `gestor`, `plugin`, `project`. | `gestor` | Não |
| `--plugin-type` | Se target for plugin: `public` ou `private`. | - | Sim (se target=plugin) |
| `--scope` | Escopo do recurso: `global` ou `module`. | `global` | Não |
| `--module-id` | ID do módulo (se escopo for module). | - | Sim (se scope=module) |
| `--lang` | Código da linguagem (ex: `pt-br`, `en`). | `pt-br` | Não |
| `--type` | Tipo de recurso: `page`, `layout`, `component`, `template`, `variable`, `prompt_ia`, `modo_ia`, `alvo_ia`. | - | Sim |
| `--id` | ID do recurso ou lista separada por vírgulas (ex: `home,contato`). Substitui `--data` para operações rápidas. | - | Não (mas obrigatório se `--data` não for informado) |
| `--action` | Ação a executar: `upsert` ou `delete`. | `upsert` | Não |
| `--open` | Se presente, abre os arquivos criados/atualizados (físicos e metadados JSON) no editor padrão (VS Code). | - | Não |
| `--interactive` | Ativa o modo interativo (menu CLI) para preencher os parâmetros. | - | Não |
| `--data` | JSON string com os dados do recurso (metadata + content). | - | Sim (se `--id` não for informado) |

### 2. Modo Interativo
Se o script for executado sem argumentos (ou com `--interactive`), ele entrará no modo interativo, guiando o usuário passo a passo com menus coloridos para selecionar:
1. Alvo (Gestor/Plugin/Projeto)
2. Escopo e Módulo
3. Linguagem e Tipo de Recurso
4. Ação (Upsert/Delete)
5. Opção de abrir arquivos
6. Entrada de Dados:
   - **Lista de IDs:** Para criação rápida ou navegação.
   - **JSON Completo:** Para colar um JSON com todos os dados do recurso.

> **Nota:** O modo interativo e as saídas do script utilizam cores ANSI para facilitar a visualização (Verde para sucesso, Ciano para informações, Amarelo para avisos, Vermelho para erros).

### 3. Lógica de Resolução de Caminhos (Raiz)

O script deve determinar a raiz (`{root}`) baseada no `--target`:

#### 2.1. Gestor (Padrão)
- **Caminho:** `gestor/` (relativo à raiz do repositório).

#### 2.2. Projeto
1. Ler `dev-environment/data/environment.json`.
2. Obter ID do projeto ativo em `devEnvironment.projectTarget`.
3. Obter caminho em `devProjects[{projectTarget}].path`.
4. **Raiz:** O caminho resolvido.

#### 2.3. Plugins
1. Ler `dev-environment/data/environment.json`.
2. Identificar arquivo de ambiente do plugin baseado em `--plugin-type` (`public` ou `private`) via `devPluginEnvironmentConfig.{type}.path`.
3. Ler o arquivo de ambiente específico do plugin.
4. Obter ID do plugin ativo em `activePlugin.id`.
5. Obter `source` (caminho base) em `devEnvironment.source`.
6. Buscar no array `plugins` o item onde `id` == `activePlugin.id` e obter o `path`.
7. **Raiz:** Concatenação de `{source}` + `{path}`.

### 3. Estrutura de Dados e Metadados

#### 3.1. Classificação dos Recursos
Os recursos são divididos em três categorias baseadas em sua estrutura física:

1.  **Recursos HTML/CSS:** `page`, `layout`, `component`, `template`.
    *   Possuem arquivos físicos `.html` e `.css`.
    *   Metadados no JSON de mapeamento.

2.  **Recursos Markdown (IA):** `prompt_ia`, `modo_ia`, `alvo_ia`.
    *   Possuem arquivo físico `.md`.
    *   Metadados no JSON de mapeamento.

3.  **Recursos de Dados:** `variable`.
    *   Não possuem arquivos físicos separados.
    *   Dados e metadados residem exclusivamente no JSON (`variables.json`).

#### 3.2. Escopo Global (`--scope global`)
- **Mapeamento:** Ler `{root}/resources/resources.map.php`.
- **Localização dos Metadados:** Definido no array `languages[{lang}][data][{type}s]`.
  - Ex: `pages` -> `pages.json`.
  - Caminho completo do JSON: `{root}/resources/{lang}/{arquivo_json}`.
- **Localização dos Arquivos Físicos:**
  - HTML/CSS: `{root}/resources/{lang}/{type}s/{id}/{id}.html` e `{id}.css`.
  - Markdown: `{root}/resources/{lang}/{type}s/{id}/{id}.md`.

#### 3.3. Escopo Módulo (`--scope module`)
- **Arquivo de Configuração:** `{root}/modulos/{module_id}/{module_id}.json`.
- **Localização dos Metadados:** Dentro deste JSON, na chave `resources.{lang}.{type}s`.
- **Localização dos Arquivos Físicos:**
  - HTML/CSS: `{root}/modulos/{module_id}/resources/{lang}/{type}s/{id}/{id}.html` e `{id}.css`.
  - Markdown: `{root}/modulos/{module_id}/resources/{lang}/{type}s/{id}/{id}.md`.

#### 3.4. Schema de Dados (Input JSON)
O parâmetro `--data` deve respeitar os campos abaixo para cada tipo de recurso. Campos marcados com `*` são obrigatórios (ou possuem fallback lógico).

**1. Layouts (`layout`)**
```json
{
  "id": "string*",
  "name": "string",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (conteúdo)",
  "css": "string (conteúdo)"
}
```

**2. Componentes (`component`)**
```json
{
  "id": "string*",
  "name": "string",
  "module": "string",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (conteúdo)",
  "css": "string (conteúdo)"
}
```

**3. Páginas (`page`)**
```json
{
  "id": "string*",
  "name": "string",
  "layout": "string (Default: layout-pagina-sem-permissao)",
  "path": "string (Default: {id}/)",
  "type": "string (Default: page)",
  "module": "string",
  "option": "string",
  "root": "boolean",
  "without_permission": "boolean",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (conteúdo)",
  "css": "string (conteúdo)"
}
```

**4. Templates (`template`)**
```json
{
  "id": "string*",
  "name": "string",
  "target": "string",
  "thumbnail": "string (url/path)",
  "status": "string (A/I)",
  "version": "string",
  "html": "string (conteúdo)",
  "css": "string (conteúdo)"
}
```

**5. Variáveis (`variable`)**
```json
{
  "id": "string*",
  "value": "string",
  "type": "string",
  "group": "string",
  "module": "string",
  "description": "string"
}
```

**6. Prompts IA (`prompt_ia`) & Modos IA (`modo_ia`)**
```json
{
  "id": "string*",
  "name": "string",
  "target": "string",
  "default": "boolean",
  "status": "string (A/I)",
  "version": "string",
  "md": "string (conteúdo)"
}
```

**7. Alvos IA (`alvo_ia`)**
```json
{
  "id": "string*",
  "name": "string",
  "status": "string (A/I)"
}
```

### 4. Fluxo de Execução (Upsert)

1. **Inicialização:** Parsear argumentos da CLI.
2. **Definição da Raiz:** Executar lógica de resolução de caminhos (Gestor/Plugin/Projeto).
3. **Carregamento de Metadados:**
   - Se Global: Carregar `resources.map.php` e abrir o JSON específico da linguagem/tipo.
   - Se Módulo: Abrir `{module_id}.json`.
4. **Processamento:**
   - Verificar se o recurso já existe (pelo ID).
   - **Tratamento de Conteúdo (Input JSON):**
     - O JSON de entrada (`--data`) deve conter os campos de conteúdo (`html`, `css`, `md`) se aplicável.
     - **HTML/CSS:** Extrair conteúdo de `html` e `css`. Salvar/Sobrescrever arquivos físicos `.html` e `.css`. Remover campos `html` e `css` do objeto de metadados.
     - **Markdown:** Extrair conteúdo de `md`. Salvar/Sobrescrever arquivo físico `.md`. Remover campo `md` do objeto de metadados.
     - **Variáveis:** Manter valor no objeto de metadados.
   - **Atualizar Metadados:** Inserir ou atualizar o objeto no array JSON (mesclando dados novos com existentes).
5. **Persistência:** Salvar o arquivo JSON de metadados atualizado.

### 5. Fluxo de Execução (Delete)

1. **Inicialização & Raiz:** Idem ao Upsert.
2. **Carregamento:** Idem ao Upsert.
3. **Remoção:**
   - Remover o objeto do array JSON de metadados.
   - **Arquivos Físicos:** Deletar a pasta/arquivos físicos correspondentes (se existirem).
4. **Persistência:** Salvar JSON.

## 🤔 Dúvidas e 📝 Sugestões

1. **Input de Dados:** O script assumirá que o JSON de entrada (`--data`) contém os campos `html`, `css` ou `md` com o conteúdo bruto para ser salvo nos arquivos físicos. Esses campos serão removidos do objeto antes de salvar no JSON de metadados.

2. **Versionamento:** O script deve implementar a mesma lógica de incremento de versão (`X.Y`) do `atualizacao-dados-recursos.php`?
   - *Decisão:* **Não.** O script de upsert foca apenas na persistência dos dados. O cálculo de checksum e versionamento é responsabilidade do script `atualizacao-dados-recursos.php` que prepara os dados para o banco.

## ✅ Progresso da Implementação
- [x] Definição do Projeto e Requisitos (MD).
- [x] Implementação da Lógica de Resolução de Caminhos (PHP).
- [x] Implementação da Leitura/Escrita de Metadados (Global/Módulo).
- [x] Implementação da Manipulação de Arquivos Físicos.
- [x] Testes de Upsert (Gestor/Global).
- [x] Testes de Upsert (Módulo).
- [x] Testes de Delete.

---
**Data:** 25/11/2025
**Desenvolvedor:** GitHub Copilot
**Projeto:** Conn2Flow v1.0
