---
name: c2f-resources-system
description: Use ao criar, editar, compilar ou sincronizar recursos do Conn2Flow (páginas, layouts, componentes, templates, variáveis, prompts de IA e tabelas declarativas).
user-invocable: false
---

# Sistema de Recursos do Conn2Flow (`resources/`)

Consulte e aplique as seguintes convenções ao manipular o Sistema de Recursos e a arquitetura de compilação de dados do Conn2Flow:

## 1. Arquitetura de Recursos (Edição Física ➔ Compilação ➔ Banco)

* **Fonte (Source)**: Desenvolvedores criam/editam arquivos físicos em `resources/<idioma>/<tipo>/<id>/<id>.<ext>`.
* **Natural Key**: O nome da pasta do recurso (`<id>`) é a chave primária natural no Banco de Dados.
* **Compilação**: O script `atualizacao-dados-recursos.php` lê os fontes e gera arquivos estáticos em `gestor/db/data/*Data.json` (`PaginasData.json`, `LayoutsData.json`, `ComponentesData.json`).
* **Sincronização**: O script `atualizacoes-banco-de-dados.php` aplica Upsert no Banco respeitando proteções de `user_modified = 1` e `project`.

---

## 2. Estrutura de Pastas e Tipos

* **Recursos Globais**: `gestor/resources/<lang>/pages|layouts|components/` + JSONs raízes (`pages.json`, etc.).
* **Recursos de Módulo**: `modulos/<modulo-id>/resources/resources/<lang>/pages|layouts|components/` + `<modulo-id>.json`.
* **Entidades Principais**:
  - **Páginas (`paginas`)**: Elementos com URL; vinculados a um layout via `id_layouts`.
  - **Layouts (`layouts`)**: Estrutura externa (header/footer) contendo o slot de inserção da página.
  - **Componentes (`componentes`)**: Blocos reutilizáveis de HTML/CSS.
  - **Prompts & Modos IA (`ai_prompts`, `ai_modes`)**: Instruções e system prompts armazenados em Markdown (`.md`).

---

## 3. CLI de Gerenciamento (`upsert-resources.php`)

Use a ferramenta CLI para criar, clonar ou remover recursos com sincronização de metadados e fontes:

* **Modo Interativo**:
  ```bash
  php ai-workspace/scripts/resources/upsert-resources.php
  ```
* **Criar Página Global e Abrir no Editor**:
  ```bash
  php ai-workspace/scripts/resources/upsert-resources.php --type=page --id=minha-pagina --open
  ```
* **Copiar / Clonar Recurso**:
  ```bash
  php ai-workspace/scripts/resources/upsert-resources.php --action=copy --type=component --id=botao-padrao --source-target=gestor --target=project --new-id=botao-custom
  ```
* **Deletar Recurso**:
  ```bash
  php ai-workspace/scripts/resources/upsert-resources.php --action=delete --type=layout --id=layout-antigo
  ```

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

## 5. Sincronização Declarativa de Tabelas Customizadas

Em `modulos/<modulo>/<modulo>.json` ou `resources/project_tables_config.json`:
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
          "html": "file:html"
        }
      }
    }
  }
}
```
