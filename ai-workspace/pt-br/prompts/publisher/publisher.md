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
- **Nome:** Publisher
- **Tabela Principal:** `publisher`

### 2. Estrutura de Dados (Banco de Dados)
A tabela `publisher` armazenará a **definição** do tipo de publicação.

| Coluna | Tipo | Descrição |
| :--- | :--- | :--- |
| `id` | VARCHAR | Identificador único do tipo (ex: `noticias`, `blog-posts`). Primary Key. |
| `name` | VARCHAR | Nome legível (ex: "Notícias"). |
| `template_id` | VARCHAR | ID do Recurso (Página/Template) vinculado em `resources` (ex: `modelo-noticia`). |
| `fields_schema` | JSON | Definição da estrutura dos campos personalizados via JSON. |
| `status` | CHAR(1) | 'A' (Ativo), 'D' (Deletado). |
| `data_criacao` | DATETIME | Data de criação. |
| `data_modificacao` | DATETIME | Data de modificação. |
| `versao` | INT | Controle de versão do registro. |

### 3. Arquivo de Configuração (`publisher.json`)
Deve seguir o padrão de `admin-layouts.json` mas adaptado:
- **Tabela:** Mapeamento das colunas acima.
- **Páginas (Resources):**
    - `publisher` (Listagem)
    - `publisher-adicionar` (Adicionar)
    - `publisher-editar` (Editar)
- **Bibliotecas:** `interface`, `html`, `banco`.

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
Seguindo o padrão de `admin-layouts.php`:

- **Funções Padrão:** Não é necessário reimplementar `listar`, `excluir`, `ativar`/`desativar`. Usar `interface_padroes`.
- **Funções a Implementar:**
    - `publisher_adicionar()`: Validação, criação do ID (slug) a partir do nome, processamento do JSON do schema, `banco_insert_name`.
    - `publisher_editar()`: Carregamento dos dados, validação, `interface_historico_incluir`, `banco_update`.
    - `publisher_interfaces_padroes()`: Configuração dos campos da listagem e filtros.

### 6. Integração com Recursos (Templates)
- O formulário deve ter um dropdown listando os **Templates** disponíveis (filtrados por contexto, se aplicável, ou todos os templates de páginas).
- Sistema de placeholders `@[[publisher#id]]@` deve ser explicado na interface do usuário (tooltip ou help text).

## 🧭 Estrutura de Arquivos Prevista

```
gestor/
  modulos/
    publisher/
      publisher.json          # Configuração e Mapeamento
      publisher.php           # Lógica Backend (Add/Edit)
      publisher.js            # Lógica Frontend (Schema Builder)
      resources/
        pt-br/
          pages/
            publisher/
              publisher.html           # Listagem (Placeholders da tabela)
            publisher-adicionar/
              publisher-adicionar.html # Form Adicionar Names/Template + Schema Builder Container
            publisher-editar/
              publisher-editar.html    # Form Editar + Schema Builder Container
  db/
    migrations/
      ..._create_publisher_table.php
```

## 🧠 Lógica de Negócio (Fluxo)

1.  **Listagem:** Gerenciada pela `interface.php` com base na config do `publisher_interfaces_padroes()`.
2.  **Adição/Edição:**
    - Inputs: Name, Template ID (Select).
    - **Schema Builder:** Área interativa JS onde o usuário adiciona "Rows/Cards" para cada campo.
    - Ao salvar, o JS serializa o array de objetos dos campos em uma string JSON e coloca num input hidden `fields_schema` para o PHP salvar.

## ✅ Progresso da Implementação
- [x] **Passo 1:** Criar a migration (Phinx) para a tabela `publisher`.
- [x] **Passo 2:** Criar estrutura de diretórios e arquivos base (`publisher.json`, `publisher.php`, `publisher.js`).
- [x] **Passo 3:** Configurar `publisher.json` com mapeamento da tabela e páginas.
- [x] **Passo 4:** Criar os arquivos de resources HTML (`publisher.html`, `publisher-adicionar.html`, `publisher-editar.html`).
- [x] **Passo 5:** Implementar `publisher.php` (Funções `adicionar`, `editar`, `start`).
- [x] **Passo 6:** Implementar `publisher.js` (Lógica do Schema Builder visual).
- [ ] **Passo 7:** Testar fluxo completo (Criar, Editar, Listar).

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
