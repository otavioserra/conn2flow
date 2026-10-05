# BATCH-249 — REQ-240: harmonização global do Design System Tailwind (Core)

- Status: in-progress. Pilares 1 a 6 e primeiro adendo (`admin-atualizacoes`) implementados, publicados no Lab e validados; segundo adendo (unificação dos arquivos no `admin-arquivos`) em andamento.
- Projeto: conn2flow
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Requisição: [REQ-240](../human-requests/req-240.md), com dois adendos do Engenheiro Chefe (seções 6 e 7). Coordenação: conn2flow-site, REQ-106 / [BATCH-100](../../../conn2flow-site/sdd/implementation/BATCH-100.md).
- Autonomia: `autonomo_monitorado`, com commit, merge e push autorizados pelo Engenheiro Chefe em 2026-10-05.

## Consolidação da REQ-239 / BATCH-248

A REQ-239 rodou em paralelo e terminou sem commit. Com autorização do Engenheiro Chefe, este lote revisou o que ia subir (evidências e diffs sem segredo, sintaxe PHP e JS, `git diff --check` limpo), commitou o trabalho dela nas branches dela e levou para `main` por fast-forward: core `db9ca87b` + `ee5cb51a`, site `025353e1` + `956831ca`. As branches `feat/req-240` e `feat/req-106` foram mescladas com a nova `main` sem conflito. Dois arquivos `.bak-*` de compilação do site ficaram fora do commit.

## Live Todo List

- [x] Auditoria estática dos módulos por pilar.
- [x] Pilar 1: mapa Fomantic → Lucide no PHP (29 para 62 nomes) e no JS em runtime, ícones explícitos no HTML, dicas nos botões só-ícone.
- [x] Pilar 2: abas na folha `c2fc-abas-lista c2fc-anexa` + `c2fc-painel-aba`.
- [x] Pilar 3: conferido no navegador; listagens e formulários já estavam em cartão branco.
- [x] Pilar 4: selects em `c2fc-campo-selecao`, caixas de mensagem em `c2fc-alerta`.
- [x] Pilar 5: cabeçalho `bg-slate-50` nas tabelas que não tinham.
- [x] Pilar 6: nenhum overflow horizontal a 1366 e 390 px.
- [x] Adendo 1: `admin-atualizacoes` redesenhado, com as opções da req-201 expostas.
- [x] Vitest e PHPUnit do core e do site sem falhas.
- [x] Pipeline oficial do Lab (três rodadas, saída 0) e varredura de navegador em 257 telas.
- [ ] Adendo 2: `admin-arquivos` como único módulo de arquivos; aposentar o `arquivos` do site.
- [ ] Homologação humana.

## O que a auditoria mostrou

Os "círculos pretos" não nascem do HTML dos módulos. São o ícone Lucide `circle` usado como fallback em três pontos:

1. Ações de listagem: `interface-listar-tailwind.js` desenha `opcao.lucide || 'circle'`, e `opcao.lucide` vem de `interface_botao_tailwind_icone()`. Ícone Fomantic fora do mapa virava círculo (13 usos no core, 31 no site).
2. Ícones Fomantic no HTML ou montados em JS (`<i class="… icon">`): `admin-tailwind.js` reaproveita o nome Fomantic; nome composto fora do mapa vira `circle`, nome simples que o Lucide não conhece fica invisível.
3. Placeholder literal `data-lucide="circle"` deixado pela conversão.

O "gap desconectado" das abas vinha de `.c2fc-abas-lista { margin-bottom: 12px }` somado ao `py-2` das páginas, com o painel repetindo a borda superior. `.c2fc-abas` (sem `-lista`) não tem regra CSS: a folha de abas é `c2fc-abas-lista`, `c2fc-aba` e `c2fc-painel-aba`.

## Implementação

### Ícones (Pilar 1)

- `interface.php`: 33 traduções novas em `interface_botao_tailwind_icone()`.
- `admin-tailwind.js`: 41 traduções novas no mapa em runtime (ordenação, setas, alças de arrasto, `cube`, `times`, `add`…) e um equivalente para marca sem pictograma no Lucide (`tiktok`).
- HTML com `data-lucide` explícito: `admin-environment` (33), `admin-categorias`, `pages-index`, `usuarios-perfis`, `forms`.
- `forms`: dica e `aria-label` no botão de limpar o autocomplete.

### Abas (Pilar 2)

- `admin-environment`, `forms`, `forms-search`, `galleries`, `menus`, `pages-index`, `publisher-index`: barra anexada ao painel.
- `admin-plugins`: no pt-br do `adicionar` os três painéis estavam dentro da barra de abas e o arquivo abria 5 `<div>` e fechava 2; os outros três arquivos deixavam o `<div>` raiz aberto. Estrutura corrigida e folha canônica nos dois idiomas; PHP e JS deixaram de alternar utilities e mantêm `aria-selected`.
- Mantidos como estão, por decisão: `perfil-usuario` (já na forma canônica da requisição, com Vitest amarrado às classes) e as barras de `forms-submissions` e `marketplace-admin` (abas de página sobre conteúdo livre, sem painel em cartão).

### Selects e alertas (Pilar 4)

- 186 selects do core passam a `c2fc-campo-selecao` (168 por classe, 18 com utilities avulsas em `admin-cron` e `admin-environment`), mais os geradores `controles_select()` e `interface_formulario_campos()` e os selects criados em `galleries.js` e `menus.js`.
- `controles.css`: a regra de `c2fc-campo-selecao` tinha só o estado base. Ganhou foco, hover, desabilitado e inválido; sem isso a troca tiraria o anel de foco de todos os selects.
- 128 caixas de mensagem passam a `c2fc-alerta`. Entre elas, as de `publisher-index`, `pages-index` e `publisher-pages` usavam `c2fc-aviso`, que é a classe do aviso flutuante (fundo escuro), e as "negative" de `publisher` estavam pintadas de azul.
- `controles.css`: `c2fc-alerta` sem o invólucro `.content` volta a ser bloco e todo alerta quebra palavras longas. Sem isso, uma URL longa em `publisher-pages/editar` gerava 26 px de overflow a 390 px (regressão desta própria conversão, achada pela varredura e corrigida).
- Variantes `c2fc-alerta-sucesso` e `c2fc-alerta-aviso`.

### Tabelas (Pilar 5)

- `admin-atualizacoes`: cabeçalho `bg-slate-50` nas três tabelas.

### Adendo 1 — `admin-atualizacoes`

A tela mostrava as opções como flags de CLI cruas, sem agrupamento; `--wipe` ficava entre as demais; o botão de executar funcionava sem modo escolhido e sem confirmação; quatro opções aceitas pelo PHP desde a req-201 não tinham controle.

- Resumo no topo: versão instalada, última execução e choques pendentes.
- Passo 1: três modos como cartões com explicação. Passo 2: opções em grupos (Segurança, Pacote da versão, e em "Opções avançadas" Banco de dados e Diagnóstico), com rótulo, explicação e a flag ao lado; `--wipe` isolada em zona de perigo. Passo 3: executar.
- Opções novas na tela, já aceitas pelo backend: `--no-health`, `--no-rollback`, `--health-url`, `--health-ip`.
- Botão de executar habilita só com modo escolhido; execução que altera a instalação pede confirmação (simulação e só-download seguem direto).
- Histórico, logs e choques em abas; status traduzido; contagem de choques pendentes na aba.
- 59 variáveis novas por idioma e 7 textos revistos (somando os dois idiomas). O contrato do atualizador não mudou.

Ficou de fora, como proposta: disparar o rollback pela tela. A rotina existe (`rollbackExecucao`, `--rollback` e `/_api/system/rollback`), mas é ação destrutiva nova pela web e merece requisição própria.

### Guardas de teste

`tests/Unit/PHP/IconesLucideReq240Test.php` (6 testes): todo ícone declarado por módulo tem tradução; toda tradução existe no pacote Lucide embarcado; nenhum `data-lucide="circle"` de marcador de lugar; select do painel usa a classe padronizada; toda página de módulo tem `<div>` balanceado.

## Evidências

| Verificação | Resultado |
| --- | --- |
| `IconesLucideReq240Test` antes da correção | falha, listando os 9 nomes sem tradução |
| Core PHPUnit (árvore principal) | 1.605 testes, 15.885 asserções, sem falhas; 5 pulados e 7 depreciações já existentes |
| Core Vitest | 46 arquivos, 541 testes |
| Pipeline `project:update-all conn2flow-site-local --confirmar-remoto` | três rodadas, saída 0 |
| Varredura de navegador, 1ª rodada | 257 telas; círculos em 4 telas, ícones não desenhados em 2, selects fora do padrão em 4, cabeçalhos em 2, overflow em 1 |
| Varredura de navegador, 2ª rodada | círculos 0, ícones não desenhados 0, selects 0, cabeçalhos 0, overflow 0 |
| `admin-atualizacoes` com interação | botão desabilitado até escolher o modo, rótulo traduzido, abas alternando, nenhum marcador cru, 17 ícones desenhados, sem overflow a 390 px |

Roteiros: [varredura](../validation/req240-browser.cjs), [resumo](../validation/req240-browser-summary.cjs), [sonda](../validation/req240-probe.cjs). Dados: [resultado](../validation/req240-browser-results.json) e [capturas](../validation/evidence-req240/resumo-segunda-varredura.txt). A varredura descobre as rotas nos manifestos; 72 das 257 redirecionam por precisar de um registro (telas de edição sem `id`), e nas listagens o roteiro segue o primeiro link de edição.

## Limites e achados fora do escopo

- O resumo da varredura ainda lista "P3 bloco principal sem cartão branco" em 50 telas de listagem: é falso-positivo do medidor (ele parte de um elemento fora do cartão). As listagens foram conferidas por captura e são cartões brancos. O medidor foi ajustado depois da segunda rodada e não foi reexecutado.
- "Marcador cru no texto" em 19 telas: 17 são editores de modelo mostrando os próprios placeholders, de propósito. Os dois reais eram do `gateways-pagamentos` (corrigido no BATCH-100).
- `modulos-grupos-distribuido/` (site): a listagem dispara um POST que termina em erro 500 desde 2026-10-04 (15 ocorrências no log do Lab). O módulo é um piloto que usa a listagem genérica sem declarar a configuração dela. Não corrigido: completar o piloto é funcionalidade.
- `galleries/editar` e `admin-templates/editar`: um 404 de imagem em registros de teste, dado e não código.
- `dashboard/`: erro de leitura de cookie dentro do iframe isolado de widget apareceu uma vez em três execuções; não reproduzido.
- A cada pipeline o core registra `paginas +2`: dois registros são reinseridos em toda rodada. Não investigado.
- Navegador em pt-br; o inglês está coberto pelos contratos e pela compilação. O comportamento novo do `admin-atualizacoes.js` foi validado no navegador e não ganhou teste Vitest.
- Nenhuma atualização do sistema foi executada pela tela nova: só seleção de modo, abas e leitura.
