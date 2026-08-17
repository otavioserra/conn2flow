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

### 2026-08-17 — BATCH-118: findings F2, F3, F4, F7–F10 e a regressão do @layer theme

- **Descartar `@layer theme` por camada foi o F1 batendo na minha própria correção.** O theme do
  layout só tem os tokens que o LAYOUT usa; utility nova da página referencia `var(--text-3xl)`
  inexistente, a declaração é invalidada e a propriedade cai para o inicial. Medido com Chromium:
  13 de 21 elementos mudavam ao salvar. O corte no theme tem de ser por DECLARAÇÃO. Só `@layer base`
  (Preflight), que não depende do conteúdo, pode ser decidido por camada.
- **Comparar estilos COMPUTADOS entre dois estados é o teste que fecha discussão de CSS.** Duas
  páginas Playwright (uma com o runtime ativo, outra só com o precompiled + o delta capturado),
  `getComputedStyle` em cada elemento, diff por propriedade. Achou a causa em uma execução.
- **O review pode errar o nome da coisa.** O F4 falava dos "dois includes de template" de
  `bibliotecas/gestor.php`; eles estão dentro de `gestor_layout()`. O papel certo era
  `layout-precompiled` (primeiro na ordem), não `dependency-precompiled` — e isso agravava o
  finding, porque theme/base/Preflight iam para o fim da cascata.
- **Dedup precisa da chave inteira.** `paginas_301` deduplicava só por `caminho`, mas a tabela não
  tem `language` e o caminho é agnóstico: a linha do pt-br bloqueava a do en para a MESMA página.
  A chave real é (`caminho`, `id_paginas`); na leitura, desempata o mais recente que resolva no
  idioma corrente.
- `/robots.txt` é servido por `assets/robots.txt` pelo mesmo caminho do sitemap — o `default:` do
  `arquivo-estatico.php` resolve extensão desconhecida contra `assets-path`, e `txt` já está mapeado.
  Verificado por HTTP antes de escrever a função.
- Artefato derivado que muda de lugar tem de apagar o antigo, sob trava de autoria: o `sitemap.xml`
  da raiz sobreviveu ao req-112 e, onde o `!-f` do `.htaccess` resolva primeiro, seria servido para
  sempre.
- Suítes após o batch: PHPUnit **315/315**, Vitest **228/228**.

### 2026-08-17 — req-117 / BATCH-117: captura do CSS compilado, Tailwind na Editbar e painel de Código

- **Antes de aceitar o diagnóstico de um intake, leia o runtime de terceiros.** O intake dizia que a
  causa era a checagem encerrar cedo por causa das regras base. Lendo o bundle do
  `@tailwindcss/browser@4.3.0`: (a) ele **prefixa `@import "tailwindcss";` sozinho** quando o
  contrato não traz um — o `browser-contract.css` só com `@theme static` está correto; (b) a folha
  de saída nasce **vazia** (`document.head.append`) e só é preenchida no fim do build. O defeito real
  era o critério POSICIONAL ("última `<style>` com regras"): o `html-editor.js` injeta 4 `<style>` no
  mesmo `<head>`, então o que ia para o banco podia ser o CSS da UI do editor.
- **Todo `<style>` que o sistema emite tem de declarar seu papel.** O `css_compiled` gravado contém
  `@layer utilities` — a mesma assinatura da saída do compilador. Sem `data-c2f-css-role`, a Editbar
  releria o próprio valor antigo como compilação nova e a página congelaria na edição anterior.
- **Filtro por assinatura de regra NÃO segura camada de fundação.** Preflight do browser 4.3.0 emite
  `*, ::after, ::before, ::backdrop, ::file-selector-button`; o bundle offline emite sem o último —
  nenhuma assinatura casa. E como `css_compiled` entra DEPOIS do pré-compilado na cascata
  (`gestor.php`), a versão do editor venceria a do build **em produção**. Só `@layer base` sai por
  camada; `theme` foi corrigido para sair por DECLARAÇÃO no BATCH-118 (ver a entrada acima).
- **Página criada pelo usuário NUNCA terá `css_precompiled`**: `tailwind_recursos_descobrir()` varre
  arquivos físicos em `resources/`, e ela vive só no banco (333 de 374 no photon). Mas o LAYOUT tem —
  e é dele que vêm theme, Preflight e utilities. Por isso o baseline do editor tem de ser a cascata,
  não o pré-compilado do próprio recurso.
- Verificação com **Chromium real via Playwright** (`page.setContent` + bundle da unpkg + HTML real do
  banco) validou o que happy-dom não alcança: ele não implementa `CSSLayerBlockRule` nem monta
  `sheet.cssRules` a partir de `textContent`. Nos testes unitários, CSSOM simulado com classes falsas
  cujo `constructor.name` casa.
- **Heurística de aviso precisa ser calibrada contra o inventário, não contra a intuição.** A
  detecção de "HTML usa Tailwind" com `flex|grid|hidden` isolados disparou em **176** recursos —
  colide com `ui grid`/`ui items` do Fomantic. Exigindo duas utilities distintas COM valor e
  excluindo classes Bootstrap (`justify-content-*`, `text-primary`), caiu para 4.
- `site-toolbar-render` é o lugar certo para o contrato `@theme static` da Editbar: só quem entra em
  edição precisa dele, e embuti-lo na página pública custaria KB por pageview anônimo.
- Correção de caminho de gravação **não corrige dado já gravado**. As 3 páginas defeituosas do photon
  só se recuperam ao serem salvas de novo.
- Suítes após o batch: PHPUnit **297/297**, Vitest **220/220**.

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

## Review de 2026-08-15: estado

Todos os findings (F1–F10) foram fechados entre os BATCH-117 e BATCH-118. Dívidas registradas:
dependência de template por `target` (sugestão 2 do F2) e a decisão de arquitetura sobre Tailwind no
painel administrativo. Acerto a preservar: a MESMA `gestor_pagina_rota_sistema()` alimenta o `noindex`
do BATCH-111, a exclusão do sitemap e agora o `robots.txt` — rota nova entra ou sai dos três de uma
vez. Texto completo em
[reviews/REVIEW-2026-08-15-batches-111-112-115.md](reviews/REVIEW-2026-08-15-batches-111-112-115.md).

## Pendências

- Testes que executam o compilador de recursos podem regenerar data files/checksums. Conferir `git status` e manter apenas alterações pertencentes ao batch corrente.
- Detalhes anteriores ao BATCH-111 permanecem recuperáveis no histórico Git e nos artefatos de `sdd/` (a rodada de backlog de 2026-07-31 está em `sdd/backlog/` e no DEC-102).
