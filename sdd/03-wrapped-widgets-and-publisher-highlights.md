# 03 Especificação Normativa: Arquitetura de Widgets Envelopados e Módulo Publisher Highlights

## Objetivo e Contexto

Dinamizar layouts e páginas HTML reduzindo a dependência de marcações de loops e variáveis complexas em arquivos físicos. A nova arquitetura de **Widgets Envelopados (Wrappers)** intercepta marcações de comentários HTML estruturados em tempo de execução e as substitui pela renderização dinâmica de widgets específicos, cujos templates e folhas de estilo residem diretamente no banco de dados e são editados via painel administrativo (`html-editor.php`).

O primeiro componente construído sobre essa arquitetura é o módulo de destaques curados **`publisher-highlights`**.

---

## Requisitos de Implementação

### 1. Sintaxe de Widget Envelopado
Os arquivos físicos (`.html`) conterão blocos estáticos envelopados por marcadores no formato de comentários HTML com a assinatura do widget.
Exemplo:
```html
<!-- widgets#publisher-highlights->render({"grupo_slug": "noticias-home"}) < -->
<div class="container-estatico">
    <h2>Destaques de Exemplo (Preview)</h2>
    <!-- Bloco de visualização estático para designers -->
</div>
<!-- widgets#publisher-highlights->render({"grupo_slug": "noticias-home"}) > -->
```
Em tempo de execução, o motor do sistema extrairá o HTML interno como template base e executará a substituição pelo retorno dinâmico do widget correspondente.

### 2. Modificações no Núcleo do Sistema (conn2flow)

#### A. Gestor Engine (`gestor.php` - função `gestor_pagina_widgets`)
- **Captura**: Regex que identifica blocos envelopados pelos marcadores:
  `"/<!--\s*widgets#(.+?)\s*<\s*-->([\s\S]*?)<!--\s*widgets#\s*\\1\s*>\s*-->/i"`
- **Lógica**:
  1. Captura a assinatura do widget (Grupo 1) e o HTML estático interno (Grupo 2).
  2. Invoca `widgets_get()` passando a assinatura e o HTML estático (como parâmetro `'html'`).
  3. Substitui todo o bloco (incluindo comentários) pelo HTML resultante.
  4. Mantém compatibilidade com a sintaxe legada `@[[widgets#...]]@` como fallback.

#### B. Biblioteca de Widgets (`widgets.php` - função `widgets_get`)
- **Injeção de Template**: Ao processar a chamada do widget, extrai a chave `'html'` recebida no array `$params` e a injeta como parâmetro no callback específico do widget:
  ```php
  if (isset($html)) {
      $paramsArray['html'] = $html;
  }
  ```

---

## Módulo Publisher Highlights (`publisher-highlights`)

Este módulo gerencia a curadoria de blocos de destaques baseados nos registros do módulo de publicações (`publisher`).

### 1. Estrutura do Banco de Dados (Phinx Migration)
- **Tabela**: `publisher_highlights`
- **Colunas**:
  - `id_publisher_highlights` (PK, auto-incremento)
  - `id_usuarios` (int, proprietário/criador)
  - `name` (varchar 255) -> Nome amigável do bloco
  - `id` (varchar 100) -> Identificador / slug único (`grupo_slug`)
  - `publisher_id` (varchar 100) -> Slug/ID textual do publicador vinculado (ex: `'noticias'`), não ID numérico.
  - `fields_schema` (json) -> Configurações de negócio (`rule` manual/latest, `count` limite, e array `selected_items` contendo os slugs textuais das publicações selecionadas).
  - `html` (mediumtext) -> Template HTML editável com placeholders `@[[item#NOME_VAR]]@` e tags de loop `<!-- item < --> ... <!-- item > -->`.
  - `css` (text) -> CSS customizado para estilização local do widget.
  - `plugin` (varchar 255)
  - `language` (varchar 10)
  - `status` (char 1, default 'A')
  - `versao` (int)
  - `data_criacao` (datetime)
  - `data_modificacao` (datetime)
  - `user_modified` (tinyint)
  - `system_updated` (tinyint)
- **Índice**: Único `['id', 'language']`.

### 2. Configurações e Arquivos do Módulo
- **[publisher-highlights.json](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/modulos/publisher-highlights/publisher-highlights.json)**: Declara dependências das bibliotecas (`interface`, `html`), tabelas do banco e caminhos dos templates e páginas CRUD.
- **Modo IA ([publisher-highlights.md](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/modulos/publisher-highlights/resources/pt-br/ai_modes/publisher-highlights/publisher-highlights.md))**: Instruções para a IA estruturar o HTML com delimitadores de repetição e placeholders de dados.
- **Editor HTML ([html-editor.php](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/bibliotecas/html-editor.php))**: Integrado para habilitar histórico de alterações de HTML e CSS específicos para o alvo `'publisher-highlights'`.
- **CRUD e Interface de Vinculação**: Interface interativa em [publisher-highlights-editar.html](file:///C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/modulos/publisher-highlights/resources/pt-br/pages/publisher-highlights-editar/publisher-highlights-editar.html) para selecionar o publicador e vincular os placeholders `@[[item#...]]@` com as colunas reais do publicador.

### 3. Mecanismo de Renderização do Widget
1. O widget localiza o registro em `publisher_highlights` usando a slug.
2. Carrega o template HTML (`html`) e CSS (`css`) correspondentes armazenados no banco de dados.
3. Se as colunas estiverem vazias ou o registro não existir, o widget não executa a renderização para evitar exibir mockups estáticos como conteúdo ao vivo em produção.
4. Isola o trecho contido entre os delimitadores de repetição: `<!-- item < -->` e `<!-- item > -->`.
5. Busca os dados na tabela do publicador configurado (`publisher_id`) de acordo com as regras (`rule` e `count`) do `fields_schema`.
6. Substitui placeholders básicos (`@[[item#titulo]]@`, `@[[item#resumo]]@`, `@[[item#imagem]]@`, `@[[item#url]]@`, `@[[item#data]]@`) e campos mapeados no `variable_mapping`.
7. Junta os itens, injeta o CSS customizado e retorna o bloco renderizado.
