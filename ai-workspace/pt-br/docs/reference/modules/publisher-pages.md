---
title: "Módulo publisher-pages"
description: "Publicações com campos estruturados, HTML materializado, CSS herdado e movimentação entre publicadores."
section: reference
module: publisher-pages
sources:
  - gestor/modulos/publisher-pages/publisher-pages.php
  - gestor/modulos/publisher-pages/publisher-pages.js
  - gestor/modulos/publisher-pages/publisher-pages.json
  - gestor/modulos/publisher-pages/resources
  - gestor/bibliotecas/banco.php
  - gestor/gestor.php
  - gestor/db/data/ModulosOperacoesData.json
  - gestor/db/migrations/20250723165530_create_paginas_table.php
  - gestor/db/migrations/20250814130000_alter_paginas_add_updated_flags.php
  - gestor/db/migrations/20250827110010_alter_paginas_add_framework_css.php
  - gestor/db/migrations/20250903160000_alter_recursos_add_plugin_id.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20250918130000_add_html_extra_head_to_tables.php
  - gestor/db/migrations/20251113120000_add_project_field_to_resource_tables.php
  - gestor/db/migrations/20260127163000_add_publisher_id_to_paginas_table.php
  - gestor/db/migrations/20260712100000_add_publish_window_to_paginas.php
  - gestor/db/migrations/20260813120000_add_seo_metadata_to_paginas.php
  - gestor/db/migrations/20260814100000_add_meta_seo_to_paginas.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
  - gestor/db/migrations/20250723165531_create_paginas_301_table.php
  - gestor/db/migrations/20260127162100_create_publisher_pages_table.php
  - gestor/db/migrations/20260713130000_expand_publisher_pages_identifiers.php
verified_at: 837c383f
---

# Módulo `publisher-pages`

Cria publicações a partir das definições de `publisher`. Cada publicação é uma página em `paginas`, acompanhada de valores estruturados e HTML de template em `publisher_pages`. O HTML preenchido fica salvo na página para o runtime.

## Como usar

1. Abra `publisher-pages/` e filtre pelo publicador. O filtro inicial é `tipo=pagina`; também há tipo sistema/ambos, módulo e `publisher_id`.
2. Use `publisher-pages/adicionar/?publisher_id=<slug>` para selecionar a definição. Preencha nome, caminho, layout e campos do publicador. A interface suporta texto, textarea, HTML com Quill e imagem pelo seletor de arquivos.
3. Revise o template e o preview. O navegador compõe o HTML preenchido; textarea troca quebras por `<br>`, imagens recebem a raiz e HTML usa o conteúdo do Quill.
4. Configure SEO, imagem de destaque e datas; a opção `sem_permissao` só pode ser alterada com a operação `permissao-pagina`.
5. Salve. Edição: `publisher-pages/editar/?id=<slug>`; clonagem: `publisher-pages/clonar/?id=<slug>`. As rotas permanecem iguais nos JSONs pt-br/en, relativas à raiz do idioma.
6. Para trocar o tipo de publicação, use o modal **Mover Publicação**, selecione o destino e confirme. Revise os campos e o desenho depois da movimentação: não há conversão automática para o template do destino.

O JS combina prefixo do usuário, prefixo do publicador e caminho normalizado, retirando prefixos já presentes para evitar duplicação. A seleção do publicador pode recarregar a tela; salve o trabalho antes de mudar de contexto.

## Referência técnica

### Operações, permissões e hooks

O controlador configura `listar` e despacha `adicionar`, `editar`, `clonar`. Status/exclusão são operações da interface compartilhada com callbacks de sitemap. O filtro da listagem exige `publisher_id IS NOT NULL`, não um JOIN obrigatório com `publisher_pages`.

AJAX próprio:

| Caso | Entrada/resultado |
|---|---|
| `editor-html-switch` | `editor_checked=sim` ativa variável booleana do módulo; demais valores desativam; retorna `status=Ok` |
| `mover-publicador` | `page_id` (fallback `ajaxRegistroId`) e `new_publisher_id`; retorna `Ok` com mensagem/redirect ou `Error` com mensagem |

A semente de operações declara `modificar-permissao-do-publicador-da-pagina`, operação `permissao-pagina`. O backend verifica essa operação antes de gravar `sem_permissao`. O handler de movimentação não faz uma verificação local adicional de operação específica: depende da autorização da interface que o chama.

O filtro `hook_apply_filters('publisher-pages', 'listar.publisher-select', $publisher)` permite alterar a lista de publicadores. Não há `hooks.api` ou inscrições `hooks` neste JSON, nem widget público próprio. Os templates de publicação pertencem ao módulo `publisher`, alvo `publisher`; os recursos deste módulo fornecem as telas e os fragmentos de campos.

### Tabelas e formato dos valores

A tabela principal do CRUD é `paginas`: id, nome, caminho, layout, conteúdo, CSS, permissão, status, datas e SEO seguem o contrato de [admin-paginas](admin-paginas.md). `paginas.publisher_id` é string até 255.

| Coluna de `publisher_pages` | Tipo/contrato |
|---|---|
| `id_publisher_pages` | Chave numérica |
| `page_id`, `publisher_id` | Strings obrigatórias até 255, ampliadas pela migration de julho de 2026 |
| `language` | String até 10, padrão pt-br |
| `fields_values` | JSON nullable |
| `html_template` | MEDIUMTEXT nullable |

Há índice único em `(page_id, language)` e índices em publicador/idioma. Essa tabela não tem status, versão ou datas próprias; esses metadados pertencem à página.

```json
[
  {"id":"titulo","value":"Minha publicação"},
  {"id":"conteudo","value":"<p>Texto do artigo.</p>"},
  {"id":"imagem_destaque","value":"uploads/capa.webp"}
]
```

Os ids vêm de `publisher.fields_schema.fields`. No envio, `publisher_fields_schema` descreve os campos; o PHP lê `field_<id>` ou `field_<id>-caminho` para imagem. O JSON é codificado com Unicode e barras não escapados e depois escapado para SQL.

`html` enviado conserva os marcadores do template em `publisher_pages.html_template`; `htmlWithValues` vai para `paginas.html`. Ao adicionar/clonar, HTML não vazio do editor é preservado. Se vier vazio, os helpers tentam recuperar HTML do template ativo de um publicador ativo. O fallback apenas fornece a estrutura: não substitui por si os valores personalizados no servidor.

### CSS, edição e datas

Criação/clonagem podem herdar `templates.css_precompiled`. `publisher_pages_css_derivado()` devolve esse CSS e sua assinatura, calculada com HTML/CSS finais, CSS do template, CSS do layout e versão do compilador. Sem CSS pré-compilado do template, devolve ambos vazios. O editor também envia `css_compiled` e `html_extra_head`.

A edição atualiza a página e os dados estruturados separadamente; renomear pode gerar novo slug e atualizar `page_id`. Guarda histórico/backups e marca versão/origem de modificação. Mudança de caminho registra 301 e atualiza sitemap; exceções de sitemap não abortam a gravação. SEO inclui título/descrição sociais, meta description/keywords e caminho da imagem.

Datas usam o conversor compartilhado e a janela de publicação do roteador, descrita em [admin-paginas](admin-paginas.md). Não existe workflow de rascunho/revisão/aprovação neste controlador. A criação grava status `A`.

### Mover publicador

O handler exige página e destino existentes no idioma e não excluídos, rejeita destino igual à origem e verifica colisão quando o caminho muda. Destino inativo é aceito porque a consulta usa `status!='D'`.

Só substitui o prefixo quando o prefixo antigo não está vazio e corresponde ao começo do caminho, sem distinguir caixa. Caso contrário, mantém a URL. Atualiza `paginas.publisher_id` e `publisher_pages.publisher_id`, versão/data/`user_modified` da página, histórico e, se mudou caminho, 301/sitemap. Não remapeia `fields_values`, não troca `html_template`, HTML publicado ou CSS.

## Defeitos e divergências do legado

> [!CAUTION]
> Na inclusão, `publisher` recebido é inserido como `publisher_id` sem `banco_escape_field()`; `banco_insert_name()` não escapa valores. Há também consultas antigas que concatenam o id do publicador diretamente. A seleção visual não deve ser tratada como validação de SQL para requisições construídas fora da tela.

> [!WARNING]
> A coleta usa `if($_REQUEST[$post_nome])`: string `"0"` e campos vazios são omitidos. Quando todos os valores ficam vazios na edição, o código pode tentar gravar string vazia em `fields_values`, que é JSON, em vez de `[]`. Além disso, o esquema é recebido do cliente e a flag `mandatory` não é validada por esses laços de persistência.

> [!WARNING]
> As escritas em `paginas` e `publisher_pages`, inclusive movimentação, não são envolvidas em transação neste controlador e não verificam o resultado de cada UPDATE/INSERT antes de continuar. Não há garantia local de atomicidade ou de que a mensagem de sucesso confirme ambas as tabelas.

> [!WARNING]
> A tela de edição não hidrata as datas da janela, nem expõe os parâmetros de limpeza esperados pelo backend. Alterações de datas só são anexadas quando o acumulador de UPDATE da página já existe. Confira as limitações descritas em admin-paginas.

O legado em português usava nomes inexistentes como `publicador_paginas` e uma tabela de taxonomias. O modelo real é `paginas` + `publisher_pages`; não há CRUD de tags/categorias nem aprovação editorial implementados aqui.

## Veja também

- [Definições de publicação](publisher.md)
- [Administração de páginas](admin-paginas.md)
- [Índice de publicações](publisher-index.md)
- [Destaques](publisher-highlights.md)
