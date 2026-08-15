# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente. Regras consolidadas vivem em `.claude/skills/` e `.cursor/skills/` e são carregadas sob demanda.
>
> **Política**: preservar 3 a 5 tarefas recentes, mirar ~5 KB e podar antes de 10 KB. A memória de Chefia permanece somente leitura.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums de recursos são recalculados pelo deploy.
- `c2f-widget-development`: recursos desduplicados, contrato AJAX e tokens `item#var`.
- `c2f-gd-image-safety`: suporte opcional a formatos GD e captura de `\Throwable`.
- `c2f-database-testing`: SQLite em memória ou MySQL isolado `conn2flow_test`.
- `c2f-mysql-utf8-emoji-encoding`: JSON ASCII-safe para MySQL `utf8` de 3 bytes.

## Tarefas recentes

### 2026-08-14 — req-112 / BATCH-112: sitemap, 301, publisher-pages e meta tags

- **Elemento novo de UI do editor tem de entrar em `isEditorOwned()`** — a regra já estava registrada
  aqui desde o BATCH-106 e eu não a segui no BATCH-110. O sintoma ("hover realça o elemento atrás do
  painel, primeiro clique não responde") parece CSS, mas é o motor tratando a UI como conteúdo via
  `elementsFromPoint`. Três pontos: `isEditorOwned` por id, por `closest`, e `extractUserHtml`.
- **`stopPropagation()` em listener de CAPTURA no contêiner impede o evento de chegar ao próprio
  filho.** Para isolar um painel sem quebrar seus botões, use a fase de BOLHA. Listeners no MESMO
  elemento continuam rodando (só `stopImmediatePropagation` os corta).
- **`interface_modulo_variavel_valor()` chama `gestor_redirecionar_raiz()` quando não acha o
  registro** — um `exit` no meio de um CRUD. Nunca use em caminho de gravação; prefira
  `banco_select_name` direto e trate a ausência com log.
- `arquivo-estatico.php` serve QUALQUER extensão desconhecida a partir de `assets-path` (o `default:`
  do switch). Colocar artefato gerado ali dispensa regra de `.htaccess`.
- Ao afrouxar um filtro de elegibilidade, levante ANTES o que passa a entrar: abrir o sitemap para
  `tipo='sistema'` traria 13 callbacks/confirmações que não são conteúdo.
- A página é gravada por TRÊS superfícies: `admin-paginas`, `publisher-pages` e o `c2f-editbar-save`
  do Live Editor. Gatilho novo de CRUD precisa ser avaliado nas três.
- **Cache-bust do editor visual: bumpe `gestor/bibliotecas/html-editor.php` e mais nada.** Essa
  versão governa os TRÊS consumidores (motor no editor clássico, `dashboard/toolbar.js`, e o motor
  carregado por dentro da Editbar via `gestor.htmlEditorVersao`). Havia uma string `?v=c2fNN` escrita
  à mão no `dashboard.toolbar.js` — eliminada; era o que fazia a correção não chegar ao navegador.
- **Asset de módulo incluído por array sem a chave `versao` herda `$_GESTOR['versao']`** (versão do
  SISTEMA, que só muda em release) — a alteração fica presa no cache mesmo após o deploy. Confira esse
  padrão em outros assets incluídos por array.
- Sintoma que não muda depois da correção: suspeite da ENTREGA antes da lógica. Confirme no DevTools
  qual URL e qual `?v=` o navegador está pedindo.
- Modos de IA com o MESMO alvo aparecem juntos em qualquer select alimentado por
  `site-toolbar-ia-init`. Para mostrar um só, filtre no ponto de montagem (`loadAiInit`) — e mantenha
  fallback para a lista completa, senão instalação sem o modo novo fica com o painel inutilizável.
- Suítes após o batch: PHPUnit **241/241**, Vitest **184/184**.

### 2026-08-13 — CR-001 / BATCH-111: laço de cookie e reversão do bloqueio de analytics

- **Sintoma em relatório de analytics não prova onde está a causa.** "A `cookies-is-mandatory/`
  aparece no GA" foi lido como "o analytics roda nela"; era o oposto — todo cliente sem cookie era
  EMPURRADO para lá. Bot de analytics é stateless por definição: não guarda cookie, não segue
  round-trip. Antes de aceitar um requisito que bloqueia algo, MEÇA quem chega e como.
- **`curl` sem cookie jar é o teste que expõe fluxo de cookie.** Navegador real chegava a 200 em 2
  saltos; cliente stateless e Googlebot entravam em laço infinito. Um `-w "%{num_redirects}
  %{url_effective} %{http_code}"` decide a discussão em segundos.
- **Página que explica um problema não pode estar atrás dele.** `cookies-is-mandatory/` reentrava na
  verificação de cookie ao ser renderizada e fechava o ciclo sobre si mesma. Regra geral: rota de
  sistema não passa pelo portão que ela existe para explicar.
- **Googlebot não persiste cookie entre requisições.** Qualquer gate de cookie na home o cega.
- **Decisão que não é testável tende a regredir.** A regra do laço morava dentro de
  `gestor/gestor.php`, que roda `gestor_start()` no fim do arquivo e não carrega em teste. Extraída
  para função pura na biblioteca, virou 5 casos de blindagem.
- Detecção por lista de tokens é sempre desatualizada. Ela só vale como complemento; quem resolve é
  a correção estrutural. Se a lista for a peça crítica, o desenho está errado.
- Ao remover um comportamento, confira se a função que o nomeava ficou com nome mentiroso e se
  alguém mais a usa (`gestor_pagina_sistema_sem_rastreamento` → `gestor_pagina_rota_sistema`, ainda
  usada pelo sitemap).
- Suítes após o batch: PHPUnit **229/229**, Vitest **178/178**.

### 2026-08-13 — req-110 / BATCH-110: metadados da página, Editbar e sitemap

- **Chave ausente ≠ chave vazia** em arrays de fallback: devolver `['title' => '']` faz a chave
  "vencer" o fallback. Nos extratores, OMITA a chave; nos gravadores, o vazio precisa ir ao banco
  (compare pelo valor do request, não por `isset`).
- `interface_formulario_campos()` resolve `<span>#imagepick-<id>#</span>` DEPOIS que o componente já
  está em `$_GESTOR['pagina']` — dá para colocar um image picker dentro do Editor HTML sem escrever
  uma linha de seletor.
- `admin-paginas`, `publisher-pages` e as publicações gravam na MESMA tabela `paginas`
  (`publisher_id` vincula). Coluna nova vale para os dois; só o formulário é por módulo.
- Operações genéricas (`status`, `excluir`) rodam na `interface` e aceitam
  `$_GESTOR['interface'][opcao]['finalizar']['callbackFunction']` — é o gancho certo, sem tocar a
  biblioteca compartilhada.
- `json_encode` para reescrever JSON de recurso: **nunca** use `JSON_UNESCAPED_SLASHES`. O
  `dashboard.json` usa o escape `\/` do PHP; sem o flag correto o diff explode em ruído (mesma
  armadilha registrada no req-108). Confira também o newline final.
- Em stub de `fetch` de teste, case o valor EXATO de `ajaxOpcao`: `x-page-config` é prefixo de
  `x-page-config-save` e o match por substring devolve a resposta errada, mascarando o teste de erro.
- Suítes após o batch: PHPUnit **223/223**, Vitest **181/181**.

### 2026-08-13 — req-109 / BATCH-109: cookies, crawlers, CSRF e OpenGraph

- **jQuery `.submit()` NÃO dispara evento `submit`**: `$(form).submit()` usa a propagação SIMULADA do
  jQuery (dispatch próprio, não `dispatchEvent`) e sua ação padrão é
  `HTMLFormElement.prototype.submit()`, que também não emite evento. Um
  `document.addEventListener('submit', …, true)` cobre só o clique do usuário. Para pegar TODOS os
  envios são precisos três pontos: captura nativa + handler delegado do jQuery + envelope do
  prototype (com marca `__c2fCsrf` para o asset carregado 2× não empilhar envelopes).
- Iframe `srcdoc` herda a ORIGEM mas não o `<head>` da hospedeira: sem `<meta name="csrf-token">` e
  sem `global.js`. Ler o token de `parent.gestor.csrfToken` é o único caminho lá dentro.
- `gestor.moduloCaminho` já vem com a barra final (`rtrim($caminho,'/').'/'`, gestor.php). Todo
  `+ '/'` em cima disso gera `modulo/opcao//`. O padrão está espalhado por vários módulos.
- `existe()` considera string só de espaços como preenchida — para decidir por conteúdo real, use o
  valor TRIMADO (um teste pegou isso no `twitter:card`).
- GTM/Meta Pixel **não existem no core**; vêm do `html_extra_head` do banco ou do JS do projeto.
  (O bloqueio que este batch introduziu foi REVERTIDO pelo CR-001 — ver a entrada do BATCH-111.)
- Redirecionar bot para `/signin/` faz o preview do link exibir a tela de login. Resposta certa para
  página protegida: `200` só com `<head>` (OpenGraph + noindex), interceptado ANTES de incluir o
  módulo da página.
- `og:image` vazio faz o WhatsApp mostrar card sem imagem em vez de usar o fallback — omita a tag.
- Suítes após o batch: PHPUnit **200/200**, Vitest **171/171**.

## 2026-08-15 — Review dos BATCH-111/112/115 (disparado pelo req-076 do lumix)

- **Tailwind v4 só emite a variável de `@theme` que ele VÊ usada, e ele vê só as utilities do HTML
  escaneado.** CSS autoral de recurso não passa pelo compilador: se consome `var(--color-…)`, o token
  pode não existir na saída. `var()` indefinida invalida a declaração e a propriedade cai para o
  valor inicial — some cor/borda **sem erro em lugar nenhum**. Projeto com tema próprio precisa de
  `@theme static`. O core não sente porque não tem `@theme` nem CSS autoral consumindo token.
- **Em modo bundle o núcleo descarta `resource-precompiled`** — é por isso que a rota declara suas
  dependências. Consequência ainda não coberta: recurso escolhido em RUNTIME (template selecionável
  por `target` no banco) nasce fora do bundle e renderiza sem estilo, em silêncio.
- **Descarte silencioso é o que impede o diagnóstico.** Vale logar em dev quando um sidecar não vazio
  for descartado por causa do bundle.
- `dependency-precompiled` está no bucket e na whitelist do ordenador e **nenhum ponto emite** esse
  papel; os dois includes de template caem no default `resource-precompiled`.
- Sitemap medido no `snapphoton-local`: `/sitemap.xml` entrega o arquivo de `assets/` (roteador vence),
  mas o `sitemap.xml` **antigo continua na raiz** — em instalação onde a regra `!-f` resolva primeiro,
  o arquivo velho passa a ser servido para sempre. Não existe `robots.txt` no core nem no projeto.
- A heurística de "página de confirmação" do sitemap cobre `…/success`, mas as páginas se chamam
  `…-success`: `contacts-success/`, `subscription-checkout/error/` e `/payment/` estão indexadas.
- Acerto a preservar: a MESMA `gestor_pagina_rota_sistema()` alimenta o `noindex` do BATCH-111 e a
  exclusão do sitemap — rota nova entra ou sai dos dois de uma vez.
- **A coluna `css_precompiled` não chegou a `forms`/`forms_search`** (só `templates`, `componentes`,
  `paginas`, `layouts`). Por isso `forms_render()` nunca incluiu o pré-compilado e TODO formulário do
  widget saía sem as utilities dos campos. Projetar a coluna sem `banco_campo_existe()` devolve
  **HTTP 500 em rota pública**, não degradação.
- **Componente sem `framework_css` não é compilado e ninguém avisa.** O `form-ui` — overlay, erros e
  bloqueio de TODOS os formulários — nunca teve `.precompiled.css`; junto disso o dimmer usava
  `bg-opacity-50`, que **não existe no Tailwind v4**. O overlay era injetado e ficava invisível.
- Correção ao finding F4: `dependency-precompiled` não tinha emissor **no core**; o lumix já o usava.
  Agora `forms`/`forms-search` também emitem, e a cascata do checkout foi medida como
  `layout` → `dependency` → `page` → `resource`.
- Findings completos em [reviews/REVIEW-2026-08-15-batches-111-112-115.md](reviews/REVIEW-2026-08-15-batches-111-112-115.md).

## Pendências

- Testes que executam o compilador de recursos podem regenerar data files/checksums. Conferir `git status` e manter apenas alterações pertencentes ao batch corrente.
- Detalhes anteriores ao BATCH-111 permanecem recuperáveis no histórico Git e nos artefatos de `sdd/` (a rodada de backlog de 2026-07-31 está em `sdd/backlog/` e no DEC-102).
