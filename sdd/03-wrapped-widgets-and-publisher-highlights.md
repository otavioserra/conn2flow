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
- **[publisher-highlights.json](../gestor/modulos/publisher-highlights/publisher-highlights.json)**: Declara dependências das bibliotecas (`interface`, `html`), tabelas do banco e caminhos dos templates e páginas CRUD.
- **Modo IA ([publisher-highlights.md](../gestor/modulos/publisher-highlights/resources/pt-br/ai_modes/publisher-highlights/publisher-highlights.md))**: Instruções para a IA estruturar o HTML com delimitadores de repetição e placeholders de dados.
- **Editor HTML ([html-editor.php](../gestor/bibliotecas/html-editor.php))**: Integrado para habilitar histórico de alterações de HTML e CSS específicos para o alvo `'publisher-highlights'`.
- **CRUD e Interface de Vinculação**: Interface interativa em [publisher-highlights-editar.html](../gestor/modulos/publisher-highlights/resources/pt-br/pages/publisher-highlights-editar/publisher-highlights-editar.html) para selecionar o publicador e vincular os placeholders `@[[item#...]]@` com as colunas reais do publicador.

### 3. Mecanismo de Renderização do Widget
1. O widget localiza o registro em `publisher_highlights` usando a slug.
2. Carrega o template HTML (`html`) e CSS (`css`) correspondentes armazenados no banco de dados.
3. Se as colunas estiverem vazias ou o registro não existir, o widget não executa a renderização para evitar exibir mockups estáticos como conteúdo ao vivo em produção.
4. Isola o trecho contido entre os delimitadores de repetição: `<!-- item < -->` e `<!-- item > -->`.
5. Busca os dados na tabela do publicador configurado (`publisher_id`) de acordo com as regras (`rule`, `count`, `order_by`) do `fields_schema`.
6. Substitui placeholders básicos (`@[[item#titulo]]@`, `@[[item#resumo]]@`, `@[[item#imagem]]@`, `@[[item#url]]@`, `@[[item#data]]@`) e campos mapeados no `variable_mapping`.
7. Junta os itens, injeta o CSS customizado e retorna o bloco renderizado.

### 4. Extensões do `fields_schema`

- `rule`: `latest` (padrão) ou `manual`.
- `count`: inteiro >= 1.
- `selected_items`: array de slugs textuais (regra `manual`).
- `order_by`: opcional, aplicado apenas quando `rule = 'latest'`. Valores aceitos: `title_asc`, `title_desc`, `date_asc`, `date_desc` (padrão). Ver [DEC-017](decisions/DECISION-LOG.md#dec-017---2026-05-26---accepted).
- `variable_mapping`: mapa `nome_variavel_template -> nome_campo_do_publisher`.

---

## 5. Arquitetura de Seleção Manual e Ciclo de Vida do Dropdown

Para garantir a estabilidade das atualizações dinâmicas, o campo visual de seleção múltipla `selected_items` no painel de curadoria do módulo `publisher-highlights` é gerenciado de forma controlada através de uma classe isolada:

### A. Ciclo de Vida Controlado e Inicialização Sob Demanda
O dropdown visual do Fomantic UI não é inicializado prematuramente na montagem do DOM. Sua ativação ocorre estritamente quando o modo de alimentação "Manual" torna-se visível na tela. Isso previne conflitos com o estado do formulário e evita a duplicação ou quebra visual gerada pelo Fomantic ao reconstruir componentes clonados ou atualizados dinamicamente.

### B. Desmembramento em Duas Etapas (Busca e Hidratação)
Para eliminar os problemas causados pela busca manual acoplada ao input.search e pela constante destruição/reconstrução do componente, o carregamento dos itens curados utiliza duas etapas independentes controladas manualmente por AJAX:
1. **Busca Incremental (Autocompletar)**: Ao digitar no campo de pesquisa, uma requisição AJAX manual é disparada para o endpoint `publisher-pages-search`, retornando os registros correspondentes que satisfazem a consulta textual.
2. **Hidratação de Itens Selecionados**: Ao carregar a tela de edição ou clonagem, ou ao alternar o publicador, os slugs textuais armazenados em `selected_items` são hidratados disparando uma requisição AJAX para `publisher-pages-fetch`, que traz os nomes amigáveis associados aos slugs para exibir corretamente as tags de seleção múltipla.

### C. Componente Reutilizável (`jquery-custom-dropdown.js`)
Toda a lógica e ciclo de vida do componente foram isolados no arquivo centralizado da plataforma:
- **Caminho**: [jquery-custom-dropdown.js](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/gestor/assets/interface/jquery-custom-dropdown.js)
- **Classe**: `PublisherHighlightsCustomDropdown`
- **Responsabilidades**: `init`, `reset`, `search`, `hydrate`, `refresh`, sincronização automática do select oculto e tratamento de internacionalização.

### D. Inclusão Centralizada no Backend
Em conformidade com a arquitetura da plataforma, em vez de servir o script diretamente da pasta do módulo, a classe Javascript reside na pasta pública de ativos do gestor (`gestor/assets/interface/`) e é incluída via backend através do método padrão da plataforma nas telas de adicionar, editar e clonar:
```php
gestor_pagina_javascript_incluir('biblioteca', [
    'caminho' => 'jquery-custom-dropdown',
    'biblioteca' => 'interface',
]);
```

### E. Tradução Contextual
O componente suporta tradução em tempo de execução para os alertas exibidos ao usuário (por exemplo, exibindo "Nenhum item encontrado" quando o idioma de controle do gestor está definido como `pt-br`, caindo para o inglês padrão nos demais idiomas).

---

## 6. Ciclo de Vida e Inicialização do Preview do Widget

Para garantir a estabilidade do preview interno no painel administrativo (onde o HTML/CSS editado no CodeMirror é simulado dinamicamente dentro de um iframe), a inicialização e o ciclo de vida do preview utilizam um fluxo baseado em validação de dependências e retries controlados:

### A. Prevenção de Condições de Corrida
A renderização do preview (através da função `refreshWidgetPreview`) depende de múltiplos elementos estarem prontos no DOM e no escopo global (o iframe de preview e as funções expostas pelo editor CodeMirror, como `html_editor_get_html` e `html_editor_get_css`). 
Para evitar falhas intermitentes de renderização no carregamento da página, clonagem ou reabertura da tela:
1. **Validação de Dependências**: A função de atualização valida a presença física do iframe e a existência das funções do editor antes de proceder.
2. **Retry Controlado**: Caso o editor ou o iframe ainda não tenham sido totalmente inicializados, o sistema executa um retry controlado de até 8 tentativas (espaçadas em 150ms), impedindo loops contínuos e resolvendo o atraso natural de inicialização de recursos pesados.
3. **Atraso Pós-Carregamento do Template**: Após o carregamento dinâmico de um template visual, a rotina agenda um disparo de renderização com atraso para garantir que o editor CodeMirror tenha absorvido o HTML/CSS correspondente.

### B. Consolidação de Cache e Atualização Forçada
O mecanismo de controle de snapshot (cache do estado do preview) evita requisições redundantes, mas opera com garantias de atualização:
1. **Consolidação Pós-Renderização**: O estado do preview (snapshot) só é considerado "consolidado" e cacheado após uma renderização válida com sucesso.
2. **Atualização Forçada por Aba**: Ao clicar ou alternar para a aba "Pré-Visualização" (`hep-preview`), o sistema ignora o cache pré-existente e força uma renderização imediata atualizada.

