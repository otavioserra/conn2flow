# BATCH-112 — Sitemap em assets, 301, aba SEO no publisher-pages, isolamento da Editbar e meta tags

Intake: [req-112.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-112.md)
Validação: [VALIDATION-CHECKLIST.md#batch-112](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/VALIDATION-CHECKLIST.md)
Decisão: DEC-107
Depende de: BATCH-110 (colunas de metadados, biblioteca de sitemap, painel da Editbar)

---

## Módulo 1 — Sitemap

**Localização.** `sitemap_caminho_arquivo()` passou de `ROOT_PATH` para `assets/`. Na raiz o arquivo
dependia da regra `RewriteCond %{SCRIPT_FILENAME} !-f` do `.htaccess`, que varia por instalação. Em
`assets/` quem entrega é o `arquivo-estatico.php` do próprio core: o `default:` do switch de extensão
já resolve qualquer extensão desconhecida contra `assets-path`, e `xml` já está na tabela de
Content-Type (`application/xml`). **Nenhuma alteração foi necessária no controlador de estáticos nem
no `.htaccess`** — `https://dominio/sitemap.xml` passa a funcionar por um caminho que é do core.

**Limpeza do slug antigo.** `sitemap_sincronizar_pagina()` e `sitemap_sincronizar_por_id()` ganharam
`$caminhoAntigo`. Quando presente, a URL antiga é removida **antes** de a nova entrar — senão o XML
fica com as duas e o buscador continua visitando um endereço que agora responde 301.

**Elegibilidade.** O `tipo` deixou de ser critério: telas públicas gravadas como `tipo='sistema'`
(`/signin/`, `/signup/`, `/forgot-password/`) passam a entrar. O painel administrativo continua fora
porque exige permissão, que é o critério principal.

> **Ajuste de escopo, com motivo.** Um levantamento das páginas públicas do core mostrou que apenas
> abrir para `tipo='sistema'` faria entrar no sitemap **13 rotas que não são conteúdo**: callbacks de
> OAuth (`oauth-callback`, `social-login`), processadores (`forms-submissions-process`), telas
> intermediárias (`signin-2fa`, `validate-user`) e páginas de confirmação. Indexar isso gasta
> orçamento de rastreio e leva o visitante a telas sem contexto. Criei
> `sitemap_caminho_nao_indexavel()` para barrar essas rotas — as três que o intake nomeia continuam
> entrando normalmente.

## Módulo 2 — Registro 301

**Causa do defeito.** O bloco usava
`interface_modulo_variavel_valor(Array('variavel' => 'id_paginas'))`. Essa helper chama
`gestor_redirecionar_raiz()` quando não encontra o registro — ou seja, um `exit` no meio da gravação,
que abortava o 301 **sem aviso** — e ainda aplica um filtro por `id_hosts` que não vale para estes
módulos.

Substituída por um `banco_select_name` direto na tabela, com o id textual atual (já considerando a
renomeação) + idioma + status. Sem o id numérico, o 301 é pulado e o motivo vai para o log, em vez de
derrubar a requisição. Acrescentada também uma checagem anti-duplicata: o mesmo caminho antigo pode
já ter sido registrado numa edição anterior (A → B → A → B geraria duas linhas idênticas).

Replicado no `publisher-pages`, que tinha o mesmo bloco com o mesmo defeito.

## Módulo 3 — publisher-pages

- Cinco colunas de SEO em `camposBanco`, gravação (adicionar/clonar), atualização (editar) e leitura,
  espelhando o `admin-paginas`.
- Aba "SEO & Compartilhamento" nas três telas (`seo` passado ao `html_editor_componente`), com o
  campo `imagepick` de imagem de destaque.
- Sincronização de sitemap em **adicionar, editar (com limpeza da URL antiga), clonar, status e
  excluir** — os dois últimos por `callbackFunction`, como no `admin-paginas`.

## Módulo 4 — `$fileId` no Image Picker

No ramo de fallback físico (arquivo existe em disco mas não tem linha em `arquivos`), `$fileId` nunca
era definido; como `$found` vira `true` logo depois, o bloco de padrões não roda e a troca de
`#file-id#` disparava `PHP Warning: Undefined variable $fileId`. Passa a receber
`$campo['caminho']` — desde o BATCH-090 o caminho relativo É o identificador do arquivo (é o que o
picker devolve em `id`).

## Módulo 5 — Isolamento do painel da Editbar

**Causa-raiz.** `c2f-page-config-panel` e `c2f-page-config-picker` **não estavam em
`isEditorOwned()`** — omissão minha no BATCH-110. O motor usa `elementsFromPoint` + `isEditorOwned`
para decidir o que é UI e o que é conteúdo; sem o registro, o hover realçava o elemento ATRÁS do
painel e o primeiro clique era consumido pela seleção em vez de acionar o botão.

Registrados nos três pontos do contrato de UI do editor (`isEditorOwned` por id, `isEditorOwned` por
`closest`, e `extractUserHtml`), mais `z-index`/`pointer-events`/`isolation` explícitos e barreira de
propagação de eventos.

> **Detalhe que quase virou bug:** a barreira precisa ficar na fase de **bolha**. Em captura, o
> `stopPropagation()` impediria o evento de chegar ao próprio botão dentro do painel — quebrando
> exatamente o que o intake pede para consertar. Há teste cobrindo isso.

## Módulo 6 — Meta description e keywords

- Migração `20260814100000` com `meta_descricao` (text) e `meta_keywords` (varchar 500).
- `gestor_meta_seo_tags()`, `gestor_meta_keywords_normalizar()` e `gestor_meta_seo_existe()` (puras).
- `gestor_meta_seo_dados()` resolve com fallback em cascata: metadado próprio → `og_descricao` da
  própria página (quem preencheu só o texto social não repete) → `config.php`
  (`site-description` / nova `site-keywords`).
- Renderização em `gestor_pagina_extra_head_e_javascript()`, pulando páginas que já trazem
  `description` própria — mesmo cuidado do OpenGraph.
- Campos nos três formulários: `admin-paginas`, `publisher-pages` e painel da Editbar.
- `keywords` só é emitida quando há valor: nenhum buscador relevante a usa para ranquear há anos, e a
  tag vazia é ruído.

## Extra — sitemap no `c2f-editbar-save` (pedido do Chefe durante a rodada)

A Editbar é a **terceira** superfície que grava a página, e a única que não sincronizava o sitemap.
Verifiquei o `dashboard_ajax_site_toolbar_save()`: ele grava só `html`/`css`/`css_compiled`/
`html_extra_head` — **não sobrescreve os metadados** nem toca no `caminho`, então não havia risco de
perda de dados nem necessidade de 301 ali. Mas atualiza `data_modificacao`, e sem a sincronização o
`<lastmod>` ficava defasado justamente na superfície de edição mais usada. Acrescentada a chamada,
com a falha isolada em `try/catch`.

---

## Arquivos alterados

| Arquivo | Módulos |
| --- | --- |
| `gestor/db/migrations/20260814100000_add_meta_seo_to_paginas.php` | M6 (novo) |
| `gestor/bibliotecas/sitemap.php` | M1 |
| `gestor/bibliotecas/gestor.php` | M6 |
| `gestor/bibliotecas/interface.php` | M4 |
| `gestor/bibliotecas/html-editor.php` | M6 |
| `gestor/gestor.php` | M6 |
| `gestor/config.php` | M6 (`site-keywords`) |
| `gestor/assets/interface/html-editor.js` | M5 |
| `gestor/modulos/admin-paginas/admin-paginas.php` | M1, M2, M6 |
| `gestor/modulos/publisher-pages/publisher-pages.php` | M2, M3, M6 |
| `gestor/modulos/dashboard/dashboard.php` | M6 + extra |
| `gestor/modulos/dashboard/dashboard.toolbar.js` | M5, M6 |
| `gestor/resources/{pt-br,en}/components/html-editor-seo/` | M6 |
| `gestor/resources/{pt-br,en}/{components,variables}.json` | M6 |
| `tests/Unit/PHP/SitemapTest.php` | +6 casos, 2 reescritos |
| `tests/Unit/PHP/CrawlersOpenGraphTest.php` | +6 casos |
| `tests/Unit/JS/dashboard.page-config.test.js` | +3 casos |

Cache-bust: `dashboard.json` 1.0.19→1.0.20, `publisher-pages.json` 1.0.0→1.0.1,
componente `html-editor-seo` 1.1→1.2 com checksums esvaziados.

---

## Rodada 2 — o M5 não chegava ao navegador (homologação do Chefe, mesma data)

O Chefe reportou que o painel continuava selecionando o elemento atrás, mesmo depois da correção — e
que bumpar `biblioteca-html-editor` para `1.5.10` não resolveu.

**Diagnóstico: o código estava certo e nunca era carregado.** Existem DOIS caminhos de cache-bust
para o mesmo `html-editor.js`, e eu tratei só um deles:

| Caminho | Cache-bust | Situação |
| --- | --- | --- |
| Editor **clássico** | `biblioteca-html-editor.versao` (`html-editor.php`) | ✅ o bump do Chefe funcionou aqui |
| **Live Editor** | string FIXA `?v=c2f18` em `dashboard.toolbar.js:176` | ❌ não bumpada — servia o arquivo em cache |

Por isso a versão da biblioteca não teve efeito: ela não governa o caminho do Live Editor. Bumpado
para `?v=c2f19`.

**Segundo problema, encontrado no caminho.** O próprio `dashboard.toolbar.js` era injetado por
`gestor_pagina_javascript_incluir(['tipo' => 'toolbar', 'modulo_id' => 'dashboard'])` **sem a chave
`versao`** — e nesse caso a helper cai em `$_GESTOR['versao']`, a versão do SISTEMA, que só muda a
cada release. Ou seja: toda alteração nesse arquivo entre releases ficava presa no cache do
navegador, mesmo após o deploy. As correções de M5/M6 que fiz nele estavam no mesmo buraco.

### Cache-bust unificado (decisão do Chefe)

A primeira correção usou a versão do módulo `dashboard`. O Chefe apontou o desenho melhor: usar a
versão da **biblioteca `html-editor`**, porque a Editbar e o motor mudam JUNTOS — mexer na barra quase
sempre implica mexer no editor. Uma versão só, no arquivo que já se edita ao trabalhar no editor.

`gestor/bibliotecas/html-editor.php` passou a ser o cache-bust **único** dos três consumidores:

| Consumidor | Como recebe a versão |
| --- | --- |
| `interface/html-editor.js` no editor clássico | `gestor_pagina_javascript_incluir('biblioteca', 'html-editor')` |
| `dashboard/toolbar.js` da Editbar | `gestor_dashboard_toolbar()`, chave `versao` explícita |
| `interface/html-editor.js` dentro da Editbar | variável JS `gestor.htmlEditorVersao` |

A string fixa `?v=c2fNN` foi **eliminada** do `dashboard.toolbar.js`; a função `versaoHtmlEditor()` lê
`gestor.htmlEditorVersao`, com degradação para `gestor.versao` e depois vazio — nunca mais um número
escrito à mão que alguém precise lembrar de bumpar. `gestor_modulos_dados()` ganhou guarda de
`is_file` no caminho (id inválido emitia warning no HTML público).

**Regra que fica**: alterou `html-editor.js`, `html-editor-interface.js` ou `dashboard.toolbar.js`?
Bumpe `gestor/bibliotecas/html-editor.php`. É o único lugar. Versão desta rodada: `1.5.11`.

## Rodada 3 — só o `paginas-editbar` no select de IA da Editbar (fora do escopo, pedido do Chefe)

O `#c2f-ai-mode` da Editbar listava dois modos. Os dois têm alvo `paginas`, então a rota
`site-toolbar-ia-init` devolve ambos — e `loadAiInit()` montava o select com a lista inteira.

Filtro aplicado **só nesse ponto**, como pedido: o modo clássico `paginas` continua registrado e
disponível no editor dos módulos (ele gera uma SEÇÃO inteira; a Editbar edita elemento isolado).
A decisão saiu para o método puro `aiModosVisiveis()`, com **fallback deliberado para a lista
completa** — numa instalação que ainda não recebeu o `paginas-editbar`, esvaziar o select deixaria o
Assistente de IA inutilizável. 3 casos de teste.

