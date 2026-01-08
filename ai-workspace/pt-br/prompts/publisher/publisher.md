# Projeto: Módulo Publisher (Gerenciador de Tipos de Publicação)

## 🎯 Contexto Inicial
Este projeto visa a criação de um novo módulo no sistema Conn2Flow chamado **Publisher**. O objetivo principal é permitir a criação de **Tipos de Publicação Personalizados** (similar ao conceito de *Custom Post Types* do WordPress).

O módulo permitirá que administradores definam estruturas de dados (schemas) para diferentes tipos de conteúdo (ex: Notícias, Artigos, Documentações), viculando-os a **Templates** do sistema de recursos para renderização.

**Contexto Anterior:** 
- A solicitação inicial define o escopo de planejamento.
- Referência técnica de módulos: `ai-workspace\pt-br\templates\modulos\modulo_id.md`.
- Referência de recursos: `ai-workspace\pt-br\docs\CONN2FLOW-SISTEMA-RECURSOS.md`.
- **Referência de Implementação (CRUD/Config/JS):** `gestor\modulos\admin-layouts\`.

## 📖 Bibliotecas e Dependências
- **Módulo Base:** Estrutura padrão do Conn2Flow (`gestor`, `banco`, `modelo`, `interface`).
- **Recursos (Resources):** Integração com o sistema de `pages` e `layouts` para templates.
- **Interface:** Componentes de formulário dinâmicos para o "Construtor de Campos".

## 📝 Especificações do Projeto

### 1. Definição do Módulo
- **ID do Módulo:** `publisher`
- **Nome:** Publisher Definições
- **Tabela Principal:** `publisher`

### 2. Estrutura de Dados (Banco de Dados)
A tabela `publisher` armazenará a **definição** do tipo de publicação.

| Coluna | Tipo | Descrição |
| :--- | :--- | :--- |
| `id` | VARCHAR(100) | Identificador único do tipo (ex: `noticias`, `blog-posts`). Primary Key. |
| `id_publisher` | INT | ID numérico auto-incremento. |
| `name` | VARCHAR(255) | Nome legível (ex: "Notícias"). |
| `template_id` | VARCHAR(255) | ID do Recurso (Página/Template) vinculado em `resources` (ex: `modelo-noticia`). |
| `fields_schema` | JSON | Definição da estrutura dos campos personalizados via JSON. |
| `plugin` | VARCHAR(255) | Plugin associado (opcional). |
| `language` | VARCHAR(10) | Idioma (padrão: 'pt-br'). |
| `status` | CHAR(1) | 'A' (Ativo), 'I' (Inativo), 'D' (Deletado). |
| `versao` | INT | Controle de versão do registro. |
| `data_criacao` | DATETIME | Data de criação. |
| `data_modificacao` | DATETIME | Data de modificação. |
| `user_modified` | TINYINT | Flag de modificação por usuário. |
| `system_updated` | TINYINT | Flag de atualização por sistema. |

**Índices:**
- UNIQUE: `id` + `language`
- `plugin`
- `language`

### 3. Arquivo de Configuração (`publisher.json`)
Configuração completa do módulo:
- **Versão:** 1.0.0
- **Bibliotecas:** `interface`, `html`
- **Tabela:** Mapeamento completo das colunas.
- **Páginas (Resources):**
    - `publisher` (Listagem): layout-administrativo-do-gestor, tipo system, opção listar, raiz true, versão 1.0
    - `publisher-adicionar` (Adicionar): layout-administrativo-do-gestor, tipo system, opção adicionar, versão 1.1
    - `publisher-editar` (Editar): layout-administrativo-do-gestor, tipo system, opção editar, versão 1.1
- **Checksums:** Calculados para cada página.

### 4. Gerenciador de Campos (Schema Builder) - Frontend (`publisher.js`)
O campo `fields_schema` (JSON) será manipulado por uma interface JS dinâmica que permitirá adicionar/remover campos.
Tipos de campos iniciais suportados:
1.  **Título** (`text`)
2.  **Descrição** (`textarea`)
3.  **Imagem** (`image` / media library)
4.  **Texto** (`html` / rich text)

**Exemplo de estrutura JSON (`fields_schema`):**
```json
[
  {
    "id": "titulo_principal",
    "label": "Título da Matéria",
    "type": "text",
    "placeholder": "Insira o título",
    "mandatory": true
  }
]
```

### 5. Backend (`publisher.php`)
Implementação completa seguindo o padrão do sistema:

- **Funções Implementadas:**
    - `publisher_adicionar()`: Validação de campos obrigatórios (name, template_id), geração de ID slug, verificação de unicidade, inserção no banco com fields_schema JSON.
    - `publisher_editar()`: Carregamento do registro, validação, atualização, inclusão no histórico.
    - `publisher_interfaces_padroes()`: Configuração da listagem com colunas name, template_id, data_modificacao; opções editar, ativar/desativar, excluir; botão adicionar.
    - `publisher_start()`: Estrutura padrão com suporte a AJAX (futuro).

- **Integrações:**
    - Select de templates: Busca páginas ativas da tabela `paginas`.
    - Histórico: Registra alterações em name, template_id, fields_schema.
    - Validação: Usa `interface_validacao_campos_obrigatorios`.

### 6. Integração com Recursos (Templates)
- O formulário tem um dropdown listando os **Templates** disponíveis (páginas ativas).
- Sistema de placeholders `@[[publisher#id]]@` deve ser explicado na interface do usuário (tooltip ou help text).

## 🧭 Estrutura de Arquivos Implementada

```
gestor/
  modulos/
    publisher/
      publisher.json          # Configuração completa
      publisher.php           # Lógica Backend completa (Add/Edit/List)
      publisher.js            # Lógica Frontend (Schema Builder) - Pendente implementação detalhada
      resources/
        pt-br/
          pages/
            publisher/
              publisher.html           # Listagem (Placeholders da tabela)
            publisher-adicionar/
              publisher-adicionar.html # Form Adicionar com Schema Builder Container
            publisher-editar/
              publisher-editar.html    # Form Editar com Schema Builder Container
  db/
    migrations/
      20260106180000_create_publisher_table.php  # Migração completa
    data/
      ModulosData.json        # Adicionado módulo publisher (pt-br/en)
      PaginasData.json        # Atualizadas páginas do módulo com layouts e tipos
```

## 🧠 Lógica de Negócio (Fluxo)

1.  **Listagem:** Gerenciada pela `interface.php` com base na config do `publisher_interfaces_padroes()`.
2.  **Adição/Edição:**
    - Inputs: Name, Template ID (Select).
    - **Schema Builder:** Área interativa JS onde o usuário adiciona "Rows/Cards" para cada campo.
    - Ao salvar, o JS serializa o array de objetos dos campos em uma string JSON e coloca num input hidden `fields_schema` para o PHP salvar.

## ✅ Progresso da Implementação
- [x] **Passo 1:** Criar a migration (Phinx) para a tabela `publisher` com todos os campos e índices.
- [x] **Passo 2:** Criar estrutura de diretórios e arquivos base (`publisher.json`, `publisher.php`, `publisher.js`).
- [x] **Passo 3:** Configurar `publisher.json` com mapeamento completo da tabela e páginas detalhadas.
- [x] **Passo 4:** Criar os arquivos de resources HTML (`publisher.html`, `publisher-adicionar.html`, `publisher-editar.html`).
- [x] **Passo 5:** Implementar `publisher.php` completo (Funções `adicionar`, `editar`, `interfaces_padroes`, `start`).
- [x] **Passo 6:** Implementar `publisher.js` (Lógica do Schema Builder visual) - Estrutura base criada, implementação detalhada pendente.
- [x] **Passo 7:** Integrar módulo no sistema (ModulosData.json, PaginasData.json atualizados).
- [ ] **Passo 8:** Testar fluxo completo (Criar, Editar, Listar) e finalizar Schema Builder JS.

## 🤔 Dúvidas e 📝 Sugestões
- **Sugestão:** A coluna `fields_schema` em JSON facilita muito a evolução (adicionar widgets como Chat/Galeria no futuro).
    - *Resposta:* Sim, mantido.
- **Dúvida:** API/Helper para consumo externo?
    - *Resposta:* Definido que haverá um módulo futuro `publisher_pages`. Não implementar helpers agora.
- **Questão Nova:** Para o "Construtor de Campos", podemos usar uma abordagem simplificada onde cada campo é uma linha na tabela HTML manipulada via JS (Add/Remove Row), e ao final serializamos?
    - *Proposta:* Sim, usar HTML/JS puro com jQuery (padrão do sistema) para adicionar blocos de campos visualmente.

---
**Data:** 2026-01-06
**Desenvolvedor:** GitHub Copilot
**Projeto:** Conn2Flow
