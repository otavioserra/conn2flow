# BATCH-257 — Área de widgets com dois modos (grade e lousa), janela cheia, opções flutuantes e Tailwind 4.3.3

- **Requisição:** [REQ-248](../../human-requests/archive/req-248.md)
- **Status:** `implemented-pending-homologation`
- **Branch:** `feat/req-248`, a partir da `main` com a REQ-247 integrada.
- **Data:** 2026-10-06
- **Modo:** autônomo monitorado; publicação só no Lab local.

## Live Todo List

- [x] Lousa: colunas pela largura, posição livre, arrasto com sombra, quem está no caminho desce
- [x] Grade preservada como modo padrão, com a ordenação por arrasto de antes
- [x] Botão de troca entre os dois modos, mantendo a proporção de largura
- [x] Widgets por linha na grade (1, 2, 3, 4 ou 6)
- [x] Janela cheia no lugar da tela cheia
- [x] Opções em botão flutuante, com menu que fica aberto
- [x] Modo e posição no layout publicado por perfil e nos layouts salvos
- [x] Links de dentro do widget abrindo na página de fora
- [x] Tailwind 4.3.3 fixado e tudo recompilado com ele
- [x] Testes, Lab e roteiro de navegador
- [ ] Homologação humana

## Mudança de rumo durante o lote

O pedido inicial era trocar a grade pela lousa. Com a lousa já escrita, o Engenheiro Chefe pediu para não substituir: "em vez de você fazer versões destrutivas, cria dois modos, a atual que está muito boa e é fácil de mexer e essa nova", com um botão de troca, e depois pediu que a grade permitisse escolher quantos widgets cabem por linha. O lote entrega os dois modos; a REQ-248 foi atualizada com essa decisão.

## O que mudou

| Item | Como ficou |
|---|---|
| **Grade** (padrão) | Malha de 12 colunas, widgets em ordem, arrasto reordena (Sortable), largura de 2 a 12. É o comportamento de antes, sem mudança. |
| **Widgets por linha** | Seletor no menu, só na grade: 1, 2, 3, 4 ou 6. Dá a todos a mesma largura; cada um ainda pode ser redimensionado. |
| **Lousa** | Colunas calculadas pela largura disponível (célula de no mínimo 90 px, distância de 20 px na horizontal e na vertical, no máximo 24). Cada widget guarda coluna e linha (`x`, `y`). Abaixo de 640 px, uma coluna. |
| **Arrasto na lousa** | Pela alça, com sombra tracejada na célula de destino. Solta em qualquer célula, inclusive deixando vão. Quem estiver no lugar desce. Os iframes não recarregam. |
| **Sem faixa vazia no topo** | Vão entre widgets fica onde o usuário deixou; espaço vazio acima de todos não: o conjunto sobe até a primeira linha. Achado pela foto da janela cheia, que saía vazia depois de arrastar widgets para baixo. |
| **Lousa mais estreita** | O widget que não cabe na coluna guardada encosta na borda e, se colidir, desce. Nada é gravado só por mudar a largura da janela. |
| **Troca de modo** | Chave "Modo lousa (posição livre)" no menu. A largura muda de unidade e mantém a proporção (para a lousa arredonda para baixo; de volta, para cima). Da lousa para a grade, a ordem vira a de leitura da tela. Preferência `dashboard_widgets_modo`. |
| **Janela cheia** | Substitui a tela cheia: a área de widgets cobre o navegador inteiro, por cima do menu lateral e do topo. `Esc` sai (se houver pop-up aberto, fecha o pop-up primeiro). |
| **Opções flutuantes** | O botão Opções fica fixo no canto inferior direito, para o Dashboard inteiro. O menu abre para cima e só fecha clicando de novo no botão. |
| **Links dentro do widget** | Link e formulário de dentro do iframe abrem na página de fora (`base target="_top"` e `allow-top-navigation-by-user-activation` no sandbox), só por clique do usuário. Âncora interna (`#secao`) continua dentro do widget. O iframe segue sem `allow-same-origin`. |
| **Layout por perfil e layouts salvos** | Guardam o modo e as posições. O registro publicado passou a ser `{modo, widgets}`; o formato anterior (só a lista) continua lido, como grade. |

Arquivos: `gestor/modulos/dashboard/dashboard.js` (`arrange`, `layout`, `commit`, `setMode`, arrasto, `setWindow`), `dashboard.php` (`dashboard_widgets_layout_ler`, `dashboard_widgets_celula`, modo na publicação), componente `dashboard-cards-tailwind` (HTML e folha), três variáveis por idioma.

### Tailwind 4.3.3

Decisão do Engenheiro Chefe: 4.3.3 é a versão oficial. `package.json` passou a `^4.3.3` e o `package-lock.json` foi regenerado só para a família do Tailwind e as dependências dela. Todos os gerados foram recompilados com 4.3.3 (mais de 500 arquivos, desfazendo a recompilação em 4.3.0 da Fase B).

## Guardas de teste

- `tests/Unit/JS/dashboard.widgets-req248.test.js`: 19 testes. Arranjo automático, posição guardada com vão, colisão, lousa larga e estreita, uma coluna, arrasto, redimensionamento em células, duplicar, posições inválidas, grade como padrão, widgets por linha, troca de modo e proporção, visualizador, janela cheia, menu que fica aberto, modo em layout salvo e publicado.
- `tests/Unit/PHP/DashboardWidgetsPermissoesReq247Test.php`: modo e posição no layout publicado, leitura do formato anterior.
- Ajustados: `DashboardWidgetsReq233Test` (a proibição de linhas automáticas vale fora da regra `.is-board`), `dashboard.widgets-req247.test.js` (janela cheia no lugar da tela cheia).

## Evidências

- PHPUnit **1.684** e Vitest **606**, sem falhas.
- Pipeline do Lab com saída 0.
- Roteiro `sdd/validation/req248/req248-browser.cjs`: **23/23**. Botão flutuante e menu aberto, grade com 3 e 4 por linha, lousa sem sobreposição e com 20 px entre vizinhos, proporção na troca, arrasto com vão, soltar sobre outro, persistência, janela cheia com mais colunas, `Esc`, lousa estreita e 390 px, volta à grade, estado do administrador devolvido.
- Regressões no Lab sobre esta branch, já com o Tailwind 4.3.3: REQ-247 **43/43** nas quatro fases, configurações do widget **16/16**, precedência da apresentação **23/23**, REQ-243 **158/158**, REQ-245 **19/19**.
- Imagens em `sdd/validation/req248/evidencias/`.

## Limites e observações

- O widget passou a poder levar a página de fora a outro endereço quando o usuário clica num link dele. É o pedido; a contrapartida é que um widget com conteúdo de terceiros pode tirar o usuário do painel com um clique.
- O commit do lote leva junto a recompilação em 4.3.3: os dados gerados (`ComponentesData.json` e outros) misturam as duas mudanças e não deu para separar em dois commits.
- **Arrasto não rola a página**: para levar um widget além da área visível, solta-se e arrasta-se de novo.
- **Editar a lousa numa tela estreita regrava as posições** de todos pelo arranjo daquela largura; o arranjo da tela larga se perde.
- A largura é um número só para os dois modos; a troca converte por proporção e o arredondamento pode mudar um widget em uma coluna.
- Trocar de modo recarrega os iframes dos widgets.
- A janela cheia cobre a página dentro do navegador; não é a tela cheia do sistema.
- O menu de opções aberto pode cobrir o canto de um widget; fecha no próprio botão.
- Arquivos `.md` de prompts de IA continuam com fim de linha misto no repositório; as alterações que isso provoca em `ModosIaData.json` e `PromptsIaData.json` foram descartadas, não corrigidas.
- A instalação `node_modules` da árvore principal ainda está em 4.3.0: rodar `npm ci` nela depois de atualizar a `main`.
- A documentação pública do Dashboard não foi atualizada.
