# 📚 Sistema de Recursos - Conn2Flow

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Metadados de Projeto no Pull System](#metadados-de-projeto-no-pull-system)
- [Conceitos Fundamentais](#conceitos-fundamentais)
- [Tipos de Recursos](#tipos-de-recursos)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Entidades do Sistema](#entidades-do-sistema)
- [Fluxo de Trabalho](#fluxo-de-trabalho)
- [Ferramentas de Gerenciamento](#ferramentas-de-gerenciamento)
- [Multilinguagem e Versionamento](#multilinguagem-e-versionamento)

---

## 🎯 Visão Geral e Filosofia

O **Sistema de Recursos** do Conn2Flow foi projetado para resolver um problema fundamental no desenvolvimento de software: **a complexidade de versionar dados de Banco de Dados.**

### O Problema
Dados estruturais (como o layout de uma página, configurações de variáveis ou prompts de IA) geralmente residem no banco de dados. Isso torna difícil:
1.  Rastrear quem mudou o quê e quando.
2.  Reverter alterações específicas sem restaurar um backup inteiro.
3.  Mesclar trabalho de diferentes desenvolvedores (Merge Conflicts).

### A Solução: Arquitetura de Compilação de Recursos
A arquitetura do Conn2Flow trata os recursos como código-fonte que precisa ser "compilado" antes de ir para o banco de dados.

1.  **Fonte (Source)**: Arquivos físicos (`.html`, `.css`, `.json`, `.md`) editáveis pelo desenvolvedor.
2.  **Compilação (Build)**: Um script processa esses arquivos e gera um "pacote de dados" em formato JSON (`*Data.json`).
3.  **Transporte**: O Git versiona tanto os fontes quanto os JSONs gerados.
4.  **Execução (Runtime)**: Na instalação/atualização, o sistema lê os JSONs e atualiza o Banco de Dados.

O fluxo correto é: **Edição Física -> Processamento (Gera JSON) -> Commit/Release -> Consumo pelo Atualizador -> Banco de Dados**.

---

## Metadados de Projeto no Pull System

Em projetos implantados em produção, as pastas locais de fonte (`resources/`, `db/` e `modulos/*/resources/`) não precisam existir no servidor: o runtime consome os recursos diretamente do banco de dados. Por isso, o servidor não consegue consultar o `resources/project_tables_config.json` local quando o Pull System executa `_api/project/recover`.

Para desacoplar o servidor dos fontes locais, o compilador gera `project-schema-metadata.json` na raiz do gestor do projeto sempre que `resources/project_tables_config.json` existe. Esse arquivo é empacotado no deploy e instalado no servidor ao lado de `db/` e `controladores/`. No pull, a API e o CLI `recuperacao-banco-de-dados.php` leem esse manifesto e mesclam suas tabelas com o contrato padrão `db/data/schema-metadata.json`. O arquivo `resources/tables_config.json` fica reservado ao core, enquanto overrides e tabelas customizadas do projeto devem viver em `resources/project_tables_config.json`.

Formato resumido:

```json
{
  "tabelas": {
    "menus": {
      "nome": "menus",
      "data_file": "MenusData.json",
      "strategy": "natural_key"
    }
  }
}
```

Exemplo de recurso de módulo configurado no projeto sem alterar o manifesto original do Core:

```json
{
  "tabelas": {
    "menus": {
      "nome": "menus",
      "id": "id",
      "id_numerico": "id_menus",
      "config": {
        "scope": "module",
        "modulo": "menus",
        "strategy": "natural_key",
        "natural_key_columns": ["language", "module", "id"],
        "sync_resources": true,
        "metadata_file": "menus.json",
        "field_types": {
          "html": "file:html"
        }
      }
    }
  }
}
```

Exemplo de recurso global gravado em `resources/`:

```json
{
  "tabelas": {
    "widgets_demo": {
      "nome": "widgets_demo",
      "id": "id",
      "id_numerico": "id_widgets_demo",
      "config": {
        "strategy": "natural_key",
        "natural_key_columns": ["language", "id"],
        "sync_resources": true,
        "resources_dir": "widgets_demo",
        "metadata_file": "widgets_demo.json",
        "field_types": {
          "html": "file:html",
          "fields_schema": "json"
        }
      }
    }
  }
}
```

Exemplo de tabela exclusivamente de banco de dados, sem arquivos físicos de recurso:

```json
{
  "tabelas": {
    "arquivos": {
      "nome": "arquivos",
      "id": "id",
      "id_numerico": "id_arquivos",
      "config": {
        "strategy": "natural_key",
        "natural_key_columns": ["id"],
        "sync_resources": false
      }
    }
  }
}
```

---

## 🧠 Conceitos Fundamentais

### O que é um Recurso?
Um recurso é composto essencialmente por **Metadados** e **Conteúdo**.

- **Metadados**: Configurações, IDs, nomes, vínculos e propriedades. Geralmente armazenados em arquivos JSON.
- **Conteúdo**: O corpo principal do recurso. Pode ser um ou mais arquivos físicos (`.html`, `.css`, `.md`) ou estar embutido no próprio JSON (como no caso de variáveis).

### Ciclo de Vida
1.  **Criação/Edição**: Desenvolvedor cria/edita arquivos na pasta `resources/`.
2.  **Sincronização**: Scripts processam os arquivos e atualizam o Banco de Dados.
3.  **Consumo**: O sistema (`gestor.php`) lê do Banco de Dados para renderizar a página.

---

## 🌍 Tipos de Recursos

### 1. Recursos Globais
Localizados em `gestor/resources/`. São acessíveis por todo o sistema e não dependem de um módulo específico.
- **Exemplos**: Layout padrão, Página de Login, Componentes de UI (Botões, Modais).

### 2. Recursos de Módulo
Localizados em `modulos/{modulo-id}/resources/`. São específicos de um módulo e encapsulados nele.
- **Exemplos**: Página de Dashboard do Módulo, Componentes específicos de um relatório.

---

## 📂 Estrutura de Arquivos

A estrutura de pastas segue um padrão rigoroso para garantir a detecção automática pelos scripts de sincronização.
**Nota**: Não existe uma pasta intermediária `lang`. Os idiomas ficam diretamente na raiz de resources.

### Estrutura Global (`gestor/resources/`)
```
gestor/resources/
├── pt-br/                     # Idioma Português
│   ├── layouts/               # Layouts
│   ├── pages/                 # Páginas
│   └── components/            # Componentes
├── en/                        # Idioma Inglês
│   └── ...
├── components.json            # Metadados globais de componentes
├── layouts.json               # Metadados globais de layouts
├── pages.json                 # Metadados globais de páginas
├── variables.json             # Variáveis globais
└── resources.map.php          # Mapa de versões e checksums
```

### Estrutura de Módulo (`modulos/{id}/resources/`)
```
modulos/{id}/resources/
├── {id}.json                  # Configuração do módulo
└── resources/
    └── pt-br/
        ├── layouts/
        ├── pages/
        └── components/
```

### Anatomia de um Recurso
Cada recurso possui uma pasta com seu ID contendo os arquivos.

**IMPORTANTE**: O nome da pasta (ID) é também o **Identificador (Natural Key)** do registro na tabela do banco de dados no campo `id`. Isso garante o vínculo preciso entre o arquivo físico e o dado relacional.

```
pages/
└── minha-pagina-exemplo/          # ID do recurso e NK no Banco
    ├── minha-pagina-exemplo.html  # Conteúdo HTML
    └── minha-pagina-exemplo.css   # Estilos CSS (Opcional)
```

Os metadados ficam nos arquivos JSON raiz (`pages.json`, etc.):
```json
{
    "id": "minha-pagina-exemplo",
    "name": "Minha Página Exemplo",
    "caminho": "/exemplo",
    "id_layouts": "layout-padrao"
}
```

---

## 🏗️ Entidades do Sistema

O sistema divide os recursos em categorias específicas, cada uma com um propósito claro:

### 1. Recursos Visuais

#### 📄 Páginas (`paginas`)
São os elementos finais publicados e acessíveis via URL.
- **Função**: Exibir conteúdo específico para o usuário.
- **Vínculo**: Toda página é obrigatoriamente "filha" de um Layout.
- **Exemplo**: "Home", "Fale Conosco", "Dashboard".

#### 🏗️ Layouts (`layouts`)
Funcionam como a "casca" ou estrutura da página (similar à união de Header + Footer no WordPress).
- **Função**: Definir a estrutura comum que se repete (cabeçalho, rodapé, menus laterais).
- **Slot**: Possui um marcador de posição onde a Página será inserida.
- **Exemplo**: "Layout Administrativo" (com sidebar), "Layout Público" (com menu superior).

#### 🧩 Componentes (`componentes`)
Pedaços de HTML reutilizáveis que podem aparecer em múltiplos lugares.
- **Função**: Evitar repetição de código. Podem ser usados dentro de Páginas, Layouts ou até dentro de outros Componentes.
- **Exemplo**: "Botão de Ação", "Card de Notícia", "Modal de Confirmação".

#### 📋 Templates (`templates`)
Modelos prontos e pré-configurados de outros recursos.
- **Função**: Acelerar a criação de novas Páginas, Layouts ou Componentes fornecendo uma base padronizada.

### 2. Configuração

#### 🔧 Variáveis (`variaveis`)
Valores dinâmicos que permitem configuração via Painel Administrativo.
- **Função**: Permitir que administradores alterem comportamentos ou textos sem mexer no código.
- **Exemplo**: "Título do Site", "Cor Primária", "Chave de API".

### 3. Ecossistema de IA (`ai_*`)

O sistema possui uma estrutura robusta para gerenciar instruções de Inteligência Artificial, armazenadas em arquivos Markdown (`.md`).

#### 🤖 Prompts de IA (`ai_prompts`)
Instruções de nível de usuário ("User Prompts").
- **Função**: Armazenar pedidos específicos de criação. O usuário pode criar vários prompts para diferentes fins.
- **Exemplo**: "Crie uma landing page para uma advogada com seções de 'Sobre' e 'Contato'".

#### ⚙️ Modos de IA (`ai_modes`)
Instruções técnicas de nível de sistema ("System Prompts").
- **Função**: Orientar a IA sobre **como** formatar a resposta, não **o que** responder. É o "manual de instruções" técnico.
- **Fluxo**: O prompt final enviado à IA é geralmente a soma: `Modo + Prompt`.
- **Exemplo**: "Modo HTML/CSS" (Instrui a IA a retornar código envolto em marcadores específicos e usar classes TailwindCSS ou outro framework CSS).

#### 🎯 Alvos de IA (`ai_prompts_targets`)
Abstração que define o "Tipo de Dado" ou destino da geração.
- **Função**: Organizar e categorizar o que está sendo gerado. Serve para o sistema entender onde salvar ou como tratar o retorno da IA.
- **Exemplo**: "Páginas", "Layouts", "Menus", "Notícias". Se o alvo é "Páginas", o sistema sabe que deve tratar o resultado com o tipo página.

---

## 🔄 Fluxo de Trabalho Detalhado

### Fase 1: Desenvolvimento (Local)
1.  **Edição**: O desenvolvedor cria ou edita recursos na pasta `resources/` (manualmente ou via CLI).
2.  **Compilação de Recursos**: Executa-se o script `atualizacao-dados-recursos.php`.
    - Ele lê todos os arquivos físicos.
    - Calcula checksums (HTML/CSS/MD).
    - Gera os arquivos de dados estáticos em `gestor/db/data/` (ex: `PaginasData.json`, `LayoutsData.json`).
3.  **Commit**: Os arquivos físicos E os arquivos JSON gerados são commitados no Git.

### Fase 2: Deploy e Atualização (Servidor)
1.  **Release**: O pacote do sistema (ZIP) contém os arquivos `*Data.json` atualizados.
2.  **Instalação/Update**: O script `atualizacoes-banco-de-dados.php` é executado.
    - Ele lê os arquivos `*Data.json`.
    - Compara com o Banco de Dados atual.
    - Realiza o **Upsert** (Insert ou Update) respeitando regras de proteção.

---

## 🛠️ Ferramentas de Gerenciamento

### 1. CLI de Recursos (`upsert-resources.php`)
Ferramenta poderosa de linha de comando (CLI) que atua como a **"Fonte da Verdade"** para criação, edição e remoção de recursos. Ela gerencia tanto os metadados (JSON) quanto os arquivos físicos (HTML/CSS/MD).

- **Local**: `ai-workspace/scripts/resources/upsert-resources.php`
- **Modos de Uso**:
    - **Interativo**: Menu guiado com cores e opções (basta rodar sem argumentos ou com `--interactive`).
    - **Argumentos**: Execução direta para automação ou uso rápido.

#### Parâmetros Principais
| Parâmetro | Descrição | Opções |
| :--- | :--- | :--- |
| `--action` | Ação a ser executada | `upsert` (criar/atualizar), `delete`, `copy` |
| `--lang` | Linguagem do recurso | Ex: `pt-br`, `en`, `es` |
| `--target` | Alvo da operação | `gestor` (padrão), `plugin`, `project` |
| `--scope` | Escopo do recurso | `global` (padrão), `module` |
| `--type` | Tipo do recurso | `page`, `layout`, `component`, `variable`, `prompt_ia`, etc. |
| `--id` | Identificador do recurso | Ex: `minha-pagina`, `meu-componente` |
| `--module-id` | ID do módulo (se scope=module) | Ex: `dashboard`, `admin-users` |
| `--plugin-type` | Tipo do plugin (se target=plugin) | `public`, `private` |
| `--open` | Abre arquivos no VS Code | `true` (flag) |
| `--new-id` | Novo ID (apenas para ação `copy`) | Ex: `minha-pagina-copia` |

#### Funcionalidade de Cópia (`copy`)
Permite clonar recursos entre diferentes contextos (ex: copiar uma página Global para dentro de um Módulo, ou do Gestor para um Projeto).
- **Parâmetros de Origem**: `--source-target`, `--source-scope`, `--source-module-id`, `--source-lang` etc.
- **Renomeação**: Use `--new-id` para salvar com um nome diferente no destino.

#### Exemplos de Uso
```bash
# Modo Interativo (Recomendado)
php ai-workspace/scripts/resources/upsert-resources.php

# Criar uma página global e abrir no editor
php ai-workspace/scripts/resources/upsert-resources.php --type=page --id=nova-pagina --open

# Copiar um componente global para um módulo
php ai-workspace/scripts/resources/upsert-resources.php --action=copy --type=component --id=botao-padrao --source-target=gestor --source-scope=global --source-module-id=meu-modulo --source-lang=pt-br --target=gestor --scope=module --module-id=meu-modulo --lang=en --new-id=botao-padrao-copia

# Deletar um recurso
php ai-workspace/scripts/resources/upsert-resources.php --action=delete --type=layout --id=layout-antigo
```

### 2. O "Compilador": `atualizacao-dados-recursos.php`
**Responsabilidade**: Transformar arquivos físicos em dados estruturados para o banco.
- **Local**: `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`
- **Entrada**: Lê pastas `gestor/resources/` e `modulos/*/resources/`.
- **Processamento**:
    - Aplica regras de unicidade (IDs únicos por idioma/módulo).
    - Calcula Checksums: Se o HTML/CSS mudou, incrementa a versão.
    - Detecta Órfãos: Recursos inválidos ou duplicados são segregados.
- **Saída**: Gera arquivos JSON na pasta `gestor/db/data/`:
    - `LayoutsData.json`, `PaginasData.json`, `ComponentesData.json`, `VariaveisData.json`, etc.

### 3. O "Sincronizador": `atualizacoes-banco-de-dados.php`
**Responsabilidade**: Consumir os dados JSON e aplicar no Banco de Dados SQL.
- **Local**: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`
- **Execução**: Roda durante a instalação do sistema ou atualização via painel.
- **Lógica de Upsert**:
    - Lê os arquivos `*Data.json` gerados pelo passo anterior.
    - Compara registro a registro com a tabela SQL correspondente.
    - Se o registro não existe -> **INSERT**.
    - Se o registro existe e é diferente -> **UPDATE**.
- **Proteção de Dados (`user_modified` e `project`)**:
    O script possui mecanismos de segurança para não sobrescrever personalizações:
    1.  **Modificação de Usuário**: Se o registro no banco tiver `user_modified = 1` (indicando que o usuário editou via painel), a atualização é ignorada.
    2.  **Proteção de Projeto**: Se o registro pertencer a um projeto específico (coluna `project` preenchida) e a atualização atual for do sistema (não do projeto), a atualização também é ignorada.
    
    Isso garante que personalizações manuais e desenvolvimentos específicos de projetos não sejam perdidos em atualizações gerais do sistema.

---

## 🌐 Multilinguagem e Versionamento

### Sistema Híbrido Multilíngue
O sistema suporta múltiplos idiomas através da estrutura de pastas `lang/{idioma}/`.
- O arquivo `resources.map.php` mapeia quais recursos existem em quais idiomas.
- O sistema carrega o recurso correto baseado na preferência do usuário ou configuração do domínio.

### Versionamento Automático
Cada alteração em arquivos físicos gera um novo **Checksum**.
- Se o checksum mudar, a versão do recurso é incrementada (v1.0 -> v1.1).
- Isso garante que caches sejam invalidados e atualizações sejam aplicadas corretamente.

---

## 🎨 Convenções de Desenvolvimento HTML

### Atributos de Seção em Páginas
Sempre que criar ou editar arquivos HTML para páginas (em `resources/*/pages/`), adicione os seguintes atributos às tags `<section>` principais:

- **`data-id`**: Valor numérico incremental começando do 1, sequencial por página (ex.: `data-id="1"`, `data-id="2"`).
- **`data-title`**: Nome semântico da seção em texto simples, sem formatação especial (ex.: `data-title="hero"`, `data-title="conn2flow-starter"`).

**Exemplo**:
```html
<section class="text-center mb-16" data-id="1" data-title="hero">
    <!-- Conteúdo da seção -->
</section>
```

**Propósito**:
- Facilita a identificação e manipulação de seções via JavaScript ou CSS.
- Padroniza a estrutura para agentes de IA e desenvolvedores.
- Melhora a semântica e acessibilidade do código.

Essa convenção deve ser seguida em todas as páginas criadas no sistema de recursos.

---

## 🧩 Sincronização Declarativa de Tabelas Customizadas (BATCH-056)

A partir do req-056, **qualquer tabela** — de módulo ou global — pode entrar na esteira de compilação de recursos de forma puramente declarativa, sem escrever scripts PHP de hook.

### Onde declarar
- **Módulo**: no bloco `"tabela"."config"` do manifesto `modulos/<modulo>/<modulo>.json`.
- **Global do core** (tabelas sem módulo dono mantidas pelo sistema): no arquivo `gestor/resources/tables_config.json`, sob `"tabelas"."<tabela>"."config"`.
- **Global/projeto** (overrides e tabelas customizadas do projeto): no arquivo `gestor/resources/project_tables_config.json`, sob `"tabelas"."<tabela>"."config"`.

A chave `"config"` pode ser um **objeto único** (1 tabela) ou um **array de objetos** (N tabelas); use `"tabela_nome"` dentro de cada elemento para nomear a tabela quando o array declarar mais de uma.

### Diretivas de sincronização de recursos
| Chave | Função |
| --- | --- |
| `sync_resources` | `true` gera automaticamente `<PascalCase>Data.json` para a tabela. |
| `resources_dir` | Subpasta de recursos (default = nome da tabela). |
| `metadata_file` | JSON externo com a lista de registros; se ausente, lê inline em `resources -> <idioma> -> <tabela>`. |
| `field_types` | Conversões por campo: `"json"` (codifica array/objeto) e `"file:<ext>"` (injeta o conteúdo de `<id>/<id>.<ext>`). |

### Resolução de caminhos
- **Conteúdo físico** (`file:<ext>`): `<base>/resources/<idioma>/<resources_dir\|tabela>/<id>/<id>.<ext>` (BOM UTF-8 removido).
- **Metadados externos** (`metadata_file`):
  - Módulo: `modulos/<modulo>/resources/<idioma>/<resources_dir\|tabela>/<metadata_file>`.
  - Global: `gestor/resources/<idioma>/<metadata_file>` (ou `.../<resources_dir>/<metadata_file>` se `resources_dir` for explícito).

Cada registro recebe colunas padronizadas (`language`, `status='A'`, `versao`, `user_modified=0`) e um `checksum` MD5 (sobre os arquivos `file:*` ou sobre o payload) usado para reaproveitar a `versao` quando nada muda entre deploys.
