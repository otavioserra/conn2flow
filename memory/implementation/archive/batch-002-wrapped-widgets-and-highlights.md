# BATCH-002 - Motor de Widgets Envelopados e Módulo Publisher Highlights

## Escopo do Lote
Este lote implementa a arquitetura de Widgets Envelopados (Wrappers) no core do Conn2Flow, permitindo que marcações estáticas em arquivos HTML físicos sejam capturadas em tempo de execução e substituídas por renderização dinâmica baseada em templates do banco de dados. Além disso, implementa o primeiro módulo sobre essa estrutura: `publisher-highlights` (Destaques Curados), integrado com a biblioteca `html-editor.php` e o editor visual.

---

## Checklist de Implementação

### 1. Motor de Widgets Envelopados (Core Engine)
- [x] Modificar `gestor.php` (`gestor_pagina_widgets`):
  - [x] Criar regex de captura de blocos envelopados por comentários:
    `/<!--\s*widgets#(.+?)\s*<\s*-->([\s\S]*?)<!--\s*widgets#\s*\\1\s*>\s*-->/i`
  - [x] Passar a assinatura capturada e o HTML estático interno para `widgets_get()`.
  - [x] Garantir compatibilidade/fallback com a sintaxe antiga de inline widgets `@[[widgets#...]]@`.
- [x] Modificar `widgets.php` (`widgets_get`):
  - [x] Tratar a chave `'html'` nos parâmetros do array e injetá-la no `$paramsArray` do callback do widget.

### 2. Módulo de Destaques (`publisher-highlights`)
- [x] Criar a tabela `publisher_highlights` via Phinx Migration (`20260701100000_create_publisher_highlights_table.php`):
  - [x] Colunas principais: `name`, `id` (slug), `publisher_id` (slug do publicador), `fields_schema` (configurações JSON de curadoria), `html` (template do banco), `css` (folha de estilo customizada), `language`, `status`, `user_modified`, `system_updated`.
  - [x] Índice único por `id` e `language`.
- [x] Desenhar a tela administrativa CRUD (`modulos/publisher-highlights`):
  - [x] Renomear arquivos e adaptar chamadas duplicando o módulo `publisher` original.
  - [x] Integrar dropdown `publisher_id` para seleção textual do publicador e remover campos desnecessários (`path_prefix`).
  - [x] Implementar vinculador de variáveis no template com colunas reais do publicador em `publisher-highlights-editar.html`.
- [x] Configuração e Prompt IA:
  - [x] Criar `publisher-highlights.json` com dependências e resources.
  - [x] Adicionar o arquivo de Modo IA `publisher-highlights.md` sob a pasta `resources/pt-br/ai_modes/` para guiar a IA na geração de templates com placeholders `@[[item#...]]@` e delimitadores `<!-- item < -->`.
- [x] Modelos Prontos de Destaque:
  - [x] Adicionar 6 templates padrões (`noticias-lista-simples`, `noticias-grid-cards`, `artigos-editorial`, `lives-video-destaque`, `notas-mosaico`, `destaque-principal-carousel`) nas pastas de resources do módulo.
- [x] Integração com Editor HTML (`html-editor.php`):
  - [x] Mapear o alvo `'publisher-highlights'` para histórico de backups.
  - [x] Configurar o Switch de renderização AJAX para carregar variáveis dinâmicas com base no publicador vinculado.
- [x] Mecanismo de Renderização Backend:
  - [x] Buscar registro por slug e carregar template HTML/CSS do banco.
  - [x] Isolar a marcação de loop entre `<!-- item < -->` e `<!-- item > -->`.
  - [x] Executar queries de publicações com base na regra (`manual` ou `latest`) do `fields_schema`.
  - [x] Processar placeholders (`titulo`, `resumo`, `imagem`, `url`, `data`) de cada item e retornar o HTML montado com o CSS embutido.

---

## Validação Realizada
O lote foi integrado e testado com sucesso no core da instalação, certificando o parsing correto dos comentários em layouts de teste e a gravação/edição de blocos de destaque no banco de dados.
