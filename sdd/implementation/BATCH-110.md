# BATCH-110 — Metadados da Página, Imagem de Destaque, UI na Editbar/Editor e Sitemap XML

Intake: [req-110.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-110.md)
Validação: [VALIDATION-CHECKLIST.md#batch-110](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/validation/VALIDATION-CHECKLIST.md)
Decisão: DEC-105
Depende de: BATCH-109 (o consumo dos metadados é o `$_GESTOR['pagina#og']` de `gestor_open_graph_dados()`)

---

## Módulo 1 — Colunas e CRUD

**Migração** `20260813120000_add_seo_metadata_to_paginas.php` acrescenta a `paginas`:

| Coluna | Tipo | Papel |
| --- | --- | --- |
| `imagem_destaque` | `varchar(500)` | caminho relativo do arquivo (`og:image`) |
| `og_titulo` | `varchar(255)` | título social; vazio cai no nome da página |
| `og_descricao` | `text` | descrição social; vazio cai no `SITE_DESCRIPTION` |

Guardamos o **caminho**, e não o `id_arquivos`, pelo mesmo motivo do BATCH-090: desde a migração para
a árvore física de arquivos, o caminho é o identificador estável — o `imagepick` da `interface.php`
já aceita as duas formas e resolve a miniatura pelo arquivo em disco quando não há registro.

**`admin-paginas.php`** — as três colunas entram em `adicionar`, `editar` e `clonar`
(`$camposBanco`, gravação e leitura). Na edição a comparação usa o valor do request **sempre
presente** (não `isset`): limpar o título social precisa gravar vazio e devolver o fallback, o que
`isset` sozinho não permitiria.

**`gestor.php`** — o roteador seleciona as três colunas e preenche
`$_GESTOR['pagina#og'] = gestor_pagina_og_do_registro($paginas[0])`. A nova função (pura, na
biblioteca `gestor`) só devolve as chaves REALMENTE preenchidas: uma chave presente e vazia venceria
o nome da página dentro de `gestor_open_graph_dados()`.

## Módulo 2 — Aba "SEO & Compartilhamento" no Editor HTML

- Novo componente `html-editor-seo` (pt-br/en) com título social, descrição social e o placeholder
  `<span>#imagepick-featured-image#</span>`.
- O componente `html-editor` ganhou a aba entre blocos removíveis
  (`<!-- seo-html-editor-menu -->` / `<!-- seo-html-editor-tab -->`), no mesmo padrão dos blocos do
  publisher: `html_editor_componente()` só a monta quando o chamador informa `seo`. Layouts,
  componentes e demais alvos continuam com o conjunto de abas de antes.
- A imagem é resolvida pelo campo `imagepick` de `interface_formulario_campos()`, que substitui o
  placeholder DEPOIS que o componente já está dentro de `$_GESTOR['pagina']` — por isso a aba
  funciona sem nenhum código novo de picker.
- 9 variáveis novas em `gestor/resources/{pt-br,en}/variables.json`; componente registrado em
  `components.json` com `version 1.0` e checksums vazios; `html-editor` teve os checksums esvaziados
  e a versão bumpada (pt-br `1.24→1.25`, en `1.10→1.11`), conforme a skill `c2f-json-resources-sync`.

## Módulo 3 — Botão "Configurações" na Editbar

- Botão `c2f-page-config-btn` ao lado do `c2f-ai-btn` na página `dashboard-site-toolbar` (pt-br/en).
- `dashboard.iframe-toolbar.js` posta `c2f-toolbar:page-config` com a posição do botão e o `page_id`.
- `dashboard.toolbar.js` monta o painel na página HOSPEDEIRA (não no iframe da barra): título social,
  descrição social e imagem de destaque com "Escolher…" / "Remover", botão Salvar e linha de status.
  O seletor de arquivos abre como overlay do `admin-arquivos` em iframe e a seleção chega pelo mesmo
  contrato do picker do editor (`{moduloId, moduloOpcao, data: "<json>"}`).
- `dismissHostPanels()` passou a fechar também este painel — **exceto** com o seletor de arquivos
  aberto, senão um clique na Editbar derrubaria o painel por baixo dele e o usuário perderia o texto
  já digitado.
- Backend: `site-toolbar-page-config` (leitura) e `site-toolbar-page-config-save` (gravação), ambos
  atrás de `gestor_acesso('editar','admin-paginas')` **e** do isolamento multiusuário
  `dashboard_site_toolbar_verificar_permissao_pagina()` — o mesmo dos backups. O caminho da imagem
  passa por `arquivo_caminho_relativo_seguro()` antes de ir ao banco.

## Módulo 4 — Sitemap XML

Nova biblioteca `gestor/bibliotecas/sitemap.php` (registrada em `config.php`), com o arquivo na raiz
pública — onde o `.htaccess` do projeto o serve direto (`RewriteCond %{SCRIPT_FILENAME} !-f`).

- `sitemap_pagina_elegivel()` (pura): exclui páginas de sistema (`tipo != 'pagina'`), páginas que
  exigem permissão (`sem_permissao` falso), inativas/excluídas, rotas utilitárias do gestor
  (reaproveitando `gestor_pagina_sistema_sem_rastreamento()` do BATCH-109, mais signin/signup/
  dashboard) e páginas fora da janela de publicação do BATCH-075.
- `sitemap_xml_montar()`, `sitemap_xml_upsert()`, `sitemap_xml_remover()` e `sitemap_data_w3c()`
  (puras): geração completa e edição **incremental** por manipulação de string — assim as entradas
  que não mudaram permanecem byte a byte, inclusive as que alguém acrescentou à mão.
- `sitemap_sincronizar_pagina()` / `sitemap_sincronizar_por_id()`: arquivo ausente ou corrompido →
  geração completa; caso contrário, upsert ou remoção da entrada única.
- Acionamento: chamadas diretas após gravar em `adicionar`/`editar`/`clonar`, e
  `callbackFunction` nas operações genéricas de `status` e `excluir` (executadas pela `interface`,
  que já dispara o callback antes do redirecionamento). Falha de sitemap nunca interrompe o CRUD —
  é artefato derivado e vai para o log.

---

## Arquivos alterados

| Arquivo | Módulos |
| --- | --- |
| `gestor/db/migrations/20260813120000_add_seo_metadata_to_paginas.php` | M1 (novo) |
| `gestor/bibliotecas/sitemap.php` | M4 (novo) |
| `gestor/bibliotecas/gestor.php` | M1 (`gestor_pagina_og_do_registro`) |
| `gestor/bibliotecas/html-editor.php` | M2 |
| `gestor/gestor.php` | M1 |
| `gestor/config.php` | M4 (biblioteca) + `url-full-http-sem-lang` |
| `gestor/modulos/admin-paginas/admin-paginas.php` | M1, M2, M4 |
| `gestor/modulos/dashboard/dashboard.php` | M3 (2 endpoints) |
| `gestor/modulos/dashboard/dashboard.toolbar.js` | M3 |
| `gestor/modulos/dashboard/dashboard.iframe-toolbar.js` | M3 |
| `gestor/modulos/dashboard/dashboard.json` | M3 (cache-bust `1.0.18→1.0.19`) |
| `gestor/modulos/dashboard/resources/{pt-br,en}/pages/dashboard-site-toolbar/` | M3 |
| `gestor/resources/{pt-br,en}/components/html-editor-seo/` | M2 (novo) |
| `gestor/resources/{pt-br,en}/components/html-editor/html-editor.html` | M2 |
| `gestor/resources/{pt-br,en}/components.json` e `variables.json` | M2 |
| `tests/Unit/PHP/SitemapTest.php` | novo — 19 casos |
| `tests/Unit/PHP/CrawlersOpenGraphTest.php` | +4 casos (M1) |
| `tests/Unit/JS/dashboard.page-config.test.js` | novo — 10 casos |

## Escopo não atendido, com motivo

O intake cita `publisher-pages` no Módulo 1. As publicações do `publisher-pages` gravam na MESMA
tabela `paginas` (a coluna `publisher_id` vincula à publicação), então as três colunas novas já
existem para elas e o roteador já as lê — o que falta é apenas o formulário do módulo, cujo CRUD é
independente do `admin-paginas` e não foi alterado nesta rodada. O caminho de edição disponível para
uma página de publicação é o painel da Editbar (Módulo 3), que funciona para qualquer página. Abrir a
aba de SEO também no `publisher-pages` é um slice pequeno e isolado — vale um batch próprio, para não
misturar duas superfícies de CRUD na mesma validação.
