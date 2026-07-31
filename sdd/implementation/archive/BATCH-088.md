# BATCH-088 — Criação dos Módulos/Widgets "forms-search" e "pages-index"

- **Intake**: [req-088.md](../human-requests/req-088.md)
- **Alvo de validação**: [VALIDATION-CHECKLIST.md#batch-088](../validation/VALIDATION-CHECKLIST.md#batch-088)
- **Decisão**: [DEC-089](../decisions/DECISION-LOG.md#dec-089---2026-07-15---accepted)
- **Base**: clone de `gestor/modulos/forms/` (→ `forms-search`) e `gestor/modulos/publisher-index/` (→ `pages-index`).
- **Fora de escopo**: req-089 (outro agente). Este batch trata apenas o req-088.

## Decisões de arquitetura (confirmadas com o Engenheiro Chefe)

1. **Analytics de busca**: o registro em `forms_search_submissions` acontece **no widget `pages-index`**,
   sempre que ele processa uma busca — tanto no page load (`?search=termo`) quanto no AJAX de busca da
   própria página de resultados. A função registradora (`forms_search_registrar_busca()`) vive no
   `forms-search` (dono da tabela) e é invocada pelo `pages-index` com guard `function_exists`
   (desacoplamento leve; se o forms-search não existir, o pages-index segue funcionando).
2. **Forma do widget `forms-search`**: mantém a **dinamicidade de campos** do `forms` (renderizador de
   `fields_schema.fields[]`), porém o `<form>` público usa **`method="get"`** e `action` = página alvo.
   O `pages-index` só interpreta o parâmetro `search`; campos adicionais futuros entram apenas editando
   os modelos, sem refazer a metodologia. O widget de busca NÃO usa o controlador AJAX/reCAPTCHA do
   `forms` (submit é navegação nativa GET).

## Escopo

### 1 — Módulo `pages-index` (clone de `publisher-index`)
- Renomear: id `publisher-index`→`pages-index`, tabela `publisher_index`→`pages_index`,
  id_numérico `id_publisher_index`→`id_pages_index`, funções/arquivos/JS correspondentes.
- **Remover** o conceito de `publisher_id`; a consulta passa a ser **direta à tabela `paginas`**:
  `WHERE status='A' AND language='$language' AND tipo='pagina' AND sem_permissao=1`.
- **Busca**: intercepta GET `search`; filtra `nome LIKE '%termo%' OR html LIKE '%termo%'`.
  Ordenação por `title_asc|title_desc|date_asc|date_desc` (data = `data_modificacao`).
- **Variáveis de item fixas** (em `pages_index_widget_render_itens`):
  `[[item#title]]`=`nome`; `[[item#summary]]`=`strip_tags(html)` truncado ~200 chars;
  `[[item#url]]`=`url-raiz`+`caminho`.
- Remover curadoria manual / tipos-de-campo-imagem / JOIN em `publisher_pages` (não se aplicam).
- Registrar busca em `forms_search_submissions` (page load + AJAX) via helper do forms-search.
- **Página semente pública** `pages-index/` declarada no manifest (`type: pagina`, `without_permission: 1`),
  com o widget `pages-index` embutido, para servir de destino padrão de buscas globais.

### 2 — Módulo `forms-search` (clone de `forms`)
- Renomear: id `forms`→`forms-search`, tabela `forms`→`forms_search`,
  `forms_submissions`→`forms_search_submissions`, id_numérico `id_forms`→`id_forms_search`,
  funções/arquivos/JS correspondentes.
- **Widget público** (`forms-search.widget.php`): renderiza `<form method="get" action="{url-raiz}{alvo}">`,
  mantém o renderizador de campos, garante o campo `name="search"`. Sem controlador AJAX/reCAPTCHA.
- **Config do alvo no admin**: o `fields_schema.form_action` guarda a página/URL alvo do `action`
  (campo já existente no schema do forms; reaproveitado como destino da busca).
- **Helper de analytics**: `forms_search_registrar_busca($termo, $form_id=null)` grava em
  `forms_search_submissions` (consumido pelo `pages-index`).

### 3 — Banco de dados
- Migração `20260715120000_create_forms_search_and_pages_index_tables.php` criando:
  - `forms_search` (espelha `forms` já com html/css/css_compiled/html_extra_head).
  - `forms_search_submissions` (espelha `forms_submissions`).
  - `pages_index` (espelha `publisher_index` sem `publisher_id`, PK `id_pages_index`).

### 4 — Registro dos módulos
- `gestor/db/data/ModulosData.json` + `UsuariosPerfisModulosData.json` (pt-br + en) para os 2 módulos.
- Manifests `pages-index.json` / `forms-search.json` declaram tabela (`config`/natural_key), widgets,
  templates, pages, ai_modes, variables (o pipeline popula os *Data.json derivados).

## Validação
- Estática: `php -l` em todos os PHP novos; `node --check` nos JS novos; JSON válido nos manifests/data.
- `composer test` sem regressão (76/76 baseline).
- Runtime (deploy `Update => Core` + migração): **pendente com o operador** — widgets listados,
  página `pages-index/` gerada, form GET envia `?search=` para o alvo, resultados exibidos, log gravado.

## Notas de checksums/versão
- Recursos novos nascem com `version: "1.0"` e checksums vazios; o compilador recalcula no deploy
  (ver MEMORIA-ENGENHARIA-EXECUCAO — não editar checksums manualmente).
