# BATCH-253 — REQ-244

Status: implemented-pending-homologation. Branch: feat/req-244. Site coordenado: REQ-109 / BATCH-103, feat/req-109.

## Escopo

Entrega completa de CSS/head/tema e assets oficiais ao iframe de widgets; geometria da área útil; paridade de Apresentações. Worktrees isoladas, sem alteração da req-243.

## Live Todo

- [x] Backend: colunas opcionais, template independente do CSS compilado, contrato de tema e assets.
- [x] Frontend: Tailwind Browser, Lucide e gestor.raiz no documento isolado; geometria.
- [x] Site: renderizador de Apresentações e altura embedded.
- [x] Vitest Core 553 e Site 13; PHPUnit Site 31 / 1679 asserções.
- [x] PHPUnit Core: 1629 testes / 16228 asserções, sem falhas/erros; 4 skipped e deprecações.
- [x] Pipeline oficial no Lab (Core e Site compilados sem erros).
- [x] Paridade visual, catálogo, controles e resize: 29/29 verificações do navegador.
- [ ] Homologação humana / consolidação em main.

## Evidências

Teste PHP atualizado falha no código anterior por ausência de .search-icon do template quando css_compiled está preenchido. Core resources:sync --resource=dashboard-cards-tailwind: saída 0, 2 recursos compilados; aviso de PUBLIC_PATH não informado para dist.

O teste JS de hidratação também foi executado com `C2F_DASHBOARD_SOURCE` apontando à fonte anterior: falha em `flex-col`, como esperado. No código final ele verifica ainda a ordem base → tema → compilador → CSS do widget.

Roteiro de navegador: [widgets-browser.cjs](../../validation/req244/widgets-browser.cjs). Preferências restauradas no finally.

Resultado final em 2026-10-06: [29/29 verificações](../../validation/req244/evidencias/resultado.json), oito slides com cores/fontes/gradientes iguais à referência em 1366 e 390 px, setas/pontos/tela cheia, resize e cinco widgets adicionais. [Piloto no Dashboard](../../validation/req244/evidencias/dashboard-piloto.png) e [referência](../../validation/req244/evidencias/referencia.png). Sem exceções JS ou console.error. Pipeline final com saída 0, fontes deste lote sem divergência de hash; `css:rebuild` sem erros. Registro de métricas em [checks.json](../../validation/req244/evidencias/checks.json).

## Implementação e revisão

`dashboard_ajax_widget_render()` consulta as colunas disponíveis, registra o template antes da autoria e reúne os incrementos das filas de head/CSS. O template não depende mais de `css_compiled` vazio. O retorno fornece o contrato de tema e URLs oficiais de Tailwind Browser/Lucide. O iframe permanece com origem opaca (`sandbox="allow-scripts"`); a inicialização de ícones e resize roda dentro dele.

Cards usam flex em coluna, body sem padding e iframe com largura/altura de 100%. A raiz do srcdoc identifica o contexto Dashboard. No Site, o renderizador inline de Apresentações inclui template e todas as folhas do registro; o deck embedded ocupa a altura útil somente nesse contexto. `css_precompiled` do registro é opcional porque a tabela real não tem essa coluna em todas as instalações.

A comparação ampliada aos oito slides encontrou cinco diferenças tipográficas no desktop quando o compilador vinha por último. O srcdoc agora separa o bloco inicial marcado `layout-precompiled` e o coloca antes do tema/compilador; as demais folhas ficam depois. Assim o tema preserva Inter e as folhas do widget mantêm a precedência da página convencional. Não há ajustes específicos às classes do piloto.

O teste real encontrou `document.cookie` proibido no aviso de cookies: no Dashboard o controlador usa seu comportamento existente de demonstração, com decisão apenas em memória. Um teste simula getter/setter que lançam SecurityError e verifica inicialização e aceite sem acessar cookies. Páginas convencionais mantêm a persistência existente.

Revisão findings-first: sem achados bloqueantes no código final. Conferidos filtros de idioma/status/target, escape SQL, envelope AJAX, sandbox, precedência template/autoria, assets e versões. Os artefatos gerados alheios foram retirados do diff nas worktrees próprias; nenhum arquivo da REQ-243 foi alterado.

## Comandos e limites da evidência

- Core: `npm run test`, 48 arquivos / 553 testes; `vendor/bin/phpunit -c phpunit.xml`, 1629 testes / 16228 asserções. Quatro skipped e deprecações já reportadas pela suíte.
- Site: `npm run test`, 13 testes; PHPUnit com `sdd/validation/req238-phpunit.xml`, 31 testes / 1679 asserções. PHP executado no Lab sobre as fontes das worktrees via `/mnt/c`; o Site não tem composer.json próprio.
- As primeiras execuções PHP falharam por CRLF em testes que extraem funções/trechos literalmente (Stripe/CssRebuild no Core e ArquivosApi no Site). Normalização local para LF resolveu; esses arquivos não têm alteração semântica no lote.
- Compilação inicial: `resources:sync --resource=dashboard-cards-tailwind` e `project:sync-resources conn2flow-site-local` (equivalente oficial para compilar o Site a partir do CLI Core). Pipeline completo sequencial: `project:update-all conn2flow-site-local --no-wait`.
- `docs:audit`: 0 erros, 276 avisos de documentação anterior; nenhuma assinatura de biblioteca ou tabela foi alterada.
- Lab compartilhado: a publicação da REQ-243 sobrescreveu temporariamente esta versão. Republicação feita depois de seu resultado de navegador, conforme autorização humana. A evidência se refere à versão publicada por este lote; outra publicação posterior pode substituí-la.
- O rsync usa preservação de arquivos mais novos: a primeira republicação deixou Dashboard/cookie-consent divergentes no hash. As datas das fontes deste lote foram atualizadas e o pipeline repetido. Diferenças restantes de interface, admin-arquivos/admin-cron e galleries pertencem à REQ-243 e foram preservadas; dez sobras de assets/3d-catalog no destino são anteriores ao lote. A sessão de agente foi renovada após a sessão antiga deixar de autenticar.
- A referência de Apresentações usa outro contexto de navegador: compartilhar o contexto fez o Chrome entregar cliques ao elemento iframe do pai. Com contextos separados, eventos reais de mouse chegaram ao deck e a entrada em tela cheia funcionou. Escape não encerra fullscreen no headless; o roteiro usa o próprio botão para sair.
- O catálogo adicional é verificado quanto a renderização, compilador e exceções JS. Paginação remota, submissão de formulários e navegação externa desses módulos não foram alteradas nem homologadas funcionalmente neste lote.
- Memória de execução saudável (22.407 bytes / 158 linhas): não reescrita nem podada.

## Correção posterior: precedência entre folhas no iframe (2026-10-06, executor da REQ-243)

**Sintoma**: primeiro slide certo, slides seguintes empilhados numa coluna só. Não aparecia no primeiro slide, o que escondeu o defeito das conferências anteriores.

**Causa**: o iframe recebia a folha completa gerada pelo compilador do navegador e, **depois** dela, a folha pré-compilada do template (4 KB, parcial). As duas ficam na camada `utilities`; na mesma camada vence a que vem depois. A utility simples da folha parcial (`grid-cols-1`, `flex-col`) vencia a responsiva da folha completa (`md:grid-cols-[1fr_auto_1fr]`, `md:flex-row`). Medido no slide 3: `grid-template-columns` de uma coluna com a regra `md:` presente no CSS.

**Correção**: em `dashboard.js` o compilador entra depois das folhas do widget, então a folha que ele gera é a última da camada. CSS de autoria não tem camada e continua vencendo. No site (REQ-109), `presentations_widget_render_inline` deixa de incluir a pré-compilada do template: com ela a página pública passava a ter o mesmo defeito (a página só funcionava no Lab porque a REQ-109 não estava publicada).

**Evidência**: `sdd/validation/req244/widget-apresentacao-precedencia.cjs`, **24/24**. Navega pela seta nos oito slides e compara 27 propriedades computadas de cada elemento entre o widget e a página na mesma largura: zero diferença. Confere também que nenhuma pré-compilada vem depois da folha gerada e que `galleries`, `forms-search`, `cookie-consent`, `menus` e `pages-index` seguem inteiros. Imagens em `sdd/validation/req244/evidencias-precedencia/`. Guarda em `tests/Unit/JS/dashboard.widgets-req236.test.js` (folha parcial antes do compilador).

**Limite**: a ordem depende de o compilador do navegador anexar a folha ao fim do `head` no momento em que roda; o roteiro falha se isso mudar numa troca de versão. Um registro salvo pelo editor (`css_compiled`) segue a mesma ordem e não foi exercitado com apresentação editada.

## Extensão: configurações por widget (2026-10-06, pedido direto do Engenheiro Chefe)

Botão novo no cabeçalho do card, ao lado do de trocar o widget, visível só no modo de edição. Abre um pop-up com a aparência daquele widget. O botão de trocar passou ao ícone `arrow-left-right`; o de configurações ficou com `settings-2`.

| Controle | Efeito | Padrão |
|---|---|---|
| Mostrar cabeçalho | desligado, o cabeçalho some fora do modo de edição e o widget ocupa o card inteiro; no modo de edição ele aparece sempre (borda tracejada) | ligado |
| Título | texto próprio no cabeçalho e no `title` do iframe; vazio usa o nome do widget; trocar o widget limpa | vazio |
| Cor de fundo própria | cor do card; em fundo escuro o cabeçalho passa a texto claro | desligada |
| Mostrar borda e sombra | desligado, o card fica sem moldura fora da edição | ligado |
| Margem interna | nenhuma, pequena (8 px), média (16 px) ou grande (24 px) em volta do conteúdo | nenhuma |
| Atualizar sozinho | recarrega o widget a cada 1, 5 ou 15 minutos, só com a aba visível e fora do modo de edição | não atualizar |

**Como foi feito**: as opções vão em `options` de cada item de `dashboard_widgets_layout` (a mesma preferência do usuário, sem tabela nova). `normalizeOptions()` descarta valor fora da lista ao ler e ao gravar; a cor só passa como `#rrggbb`. `applyOptions()` aplica no card que já está na tela, sem recarregar o iframe. Chaves do pop-up no estilo do menu de opções do Dashboard (`dashboard-menu-item` com `dashboard-edit-track`). 21 variáveis por idioma em `dashboard.json`.

**Evidência**: `tests/Unit/JS/dashboard.widget-config.test.js` (5 testes, usa o pop-up do componente real nos dois idiomas) e `sdd/validation/req244/widget-config-browser.cjs` no Lab, **15/15**: botão só na edição, pop-up centralizado, aplicar sem recarregar o iframe, cabeçalho some fora da edição, volta do servidor depois de recarregar, sem moldura, 390 px e restaurar padrão. Imagens em `sdd/validation/req244/evidencias-config/`. PHPUnit 1.672 e Vitest 578 verdes; roteiro de precedência repetido, 24/24.

**Limites**: a atualização automática foi exercitada só no teste com relógio simulado, não no Lab. O layout é gravado inteiro a cada mudança: dois navegadores do mesmo usuário abertos ao mesmo tempo sobrescrevem um ao outro (já era assim para posição e tamanho). O conteúdo do iframe não recebe a cor: widget com fundo próprio opaco cobre a cor do card, que aparece só na margem e no cabeçalho.
