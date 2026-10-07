# BATCH-261 — Linha 3.1, fase 3A da lousa: widget "Lousa" para páginas

- **Requisição:** [REQ-252](../human-requests/req-252.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-252`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Widget "Lousa" no cadastro de widgets, com as lousas do sistema como registros
- [x] Renderização na página, sem iframe: widgets pelo sistema de widgets da página, objetos pelo servidor
- [x] Grade e lousa com o mesmo arranjo do Dashboard; opções por item
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Módulo `dashboard-pages` (requisição seguinte)
- [ ] Homologação humana

## O que mudou

| Item | Como ficou |
|---|---|
| **Widget "Lousa"** | Entrada `dashboard` no cadastro de widgets (`Lousa` / `Board`), com a tabela `dashboard_boards`. O editor de páginas lista as lousas do sistema como lista menus e galerias; a página recebe o marcador `widgets#dashboard->render(...)`. |
| **Sem iframe** | `dashboard.widget.php` lê a lousa e pede cada widget ao sistema de widgets da própria página (`widgets_get`). Só entra widget que está no cadastro e tem registro. |
| **Objetos** | Texto, forma, imagem, ícone e botão saem de moldes do componente `dashboard-lousa-widget`. O texto do autor é escapado; cor, imagem, destino, fonte e ícone passam de novo pela normalização. Botão com destino recusado vira rótulo sem link. Objeto sem conteúdo (texto vazio, imagem sem arquivo) não ocupa lugar. |
| **Grade** | 12 colunas a partir de 1024 px, 6 entre 640 e 1023 px (mesma conversão de larguras do Dashboard), 1 abaixo de 640 px. Só folha de estilo. |
| **Lousa** | `dashboard.widget.js` calcula as colunas que cabem no contêiner (células de 90 px, vão de 20 px, linhas de 20 px) e aplica a mesma regra de arranjo do Dashboard. Abaixo de 640 px, uma coluna na ordem das posições. Refaz quando a largura muda. |
| **Opções por item** | Altura, fundo, imagem de fundo com opacidade e preenchimento, espaçamento, moldura, fonte do título e esconder abaixo de 640, 1024 ou 1280 px. |
| **Só leitura** | Não há controle de edição na página. |

## Decisões tomadas no lote

- **Título**: na página o cabeçalho só aparece quando o autor escreveu um título. O nome do widget ("Menus", "Galerias") é informação do painel e não vai para a página.
- **Sem moldura**: na página é sem caixa (sem borda, sombra nem fundo branco). A cor de fundo escolhida continua valendo.
- **Altura**: a que o autor deu no Dashboard; conteúdo maior rola dentro da caixa, como no painel.
- **Normalização em arquivo próprio**: as funções puras de layout saíram de `dashboard.php` para `dashboard-layout.php`, incluído pelo painel e pelo widget. Uma definição só.
- **Lousa dentro de lousa** não renderiza.

## Defeito achado e corrigido durante a validação

As primeiras 24 conferências do roteiro passaram, mas a imagem mostrou a página coberta: os widgets de apresentação e de aviso de cookies usam `position: fixed`, que no Dashboard fica preso ao iframe e na página escapava da caixa. Correção: `contain: layout paint` no corpo de cada item, que passa a ser a referência do conteúdo fixo e o recorta. O roteiro ganhou a conferência que faltava (hoje 26).

## Limites

- **Widget que se mede pela janela** (a apresentação usa a altura da janela) aparece recortado na caixa: no Dashboard o iframe dá a ele uma janela própria; na página a janela é a do visitante.
- **Classes responsivas do Tailwind** dentro de um widget respondem à largura da janela, não à da caixa. Um widget numa caixa estreita em tela larga usa o desenho de tela larga.
- **Aviso de cookies dentro de uma lousa** fica preso à caixa. É widget de página inteira; não faz sentido numa lousa.
- **Google Fonts**: a página pede a folha ao Google quando a lousa usa uma das fontes (pendência de decisão já registrada na REQ-250).
- **Idioma**: a lousa é por idioma, como na REQ-251.
- **Antes do script**, no modo lousa, os itens ficam invisíveis; se o script não rodar, aparecem empilhados depois de 1,2 s.

## Arquivos

- `gestor/modulos/dashboard/dashboard.widget.php`, `dashboard.widget.js`, `dashboard-layout.php` (novo, com funções que saíram de `dashboard.php`)
- `gestor/modulos/dashboard/dashboard.php`, `dashboard.json`
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-lousa-widget/`
- `tests/Unit/PHP/DashboardLousaWidgetReq252Test.php`, `tests/Unit/JS/dashboard.lousa-widget-req252.test.js`; ajuste em `DashboardObjetosReq250Test.php` e `DashboardWidgetsPermissoesReq247Test.php` (leem o arquivo novo)
- `sdd/validation/req252/req252-browser.cjs`, `preparar-paginas.sql`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.702 testes, sem falha em três execuções seguidas. Uma execução anterior acusou 1 erro que não se repetiu e que não identifiquei. |
| Vitest (suíte completa) | 641 testes, sem falha. O arranjo do widget é comparado com o do Dashboard em cinco larguras. |
| `resources:sync`, `assets:minify`, `project:update-all conn2flow-v31-local` | código de saída 0 |
| Navegador, `req252-browser.cjs` como visitante | 26/26 |
| Regressão do Dashboard no mesmo ambiente | REQ-251 18/18, REQ-250 23/23, REQ-248 23/23, REQ-249 11/11, REQ-247 (administrador) 22/22 |

### Como o roteiro foi montado

- As duas páginas de teste foram criadas direto no banco da instalação local (`preparar-paginas.sql`), copiando layout e permissão de uma página pública existente. **A inclusão do widget pelo editor de páginas não foi exercitada**: conferi que o widget e as lousas aparecem nas listas que o editor consulta, não o clique no editor.
- As lousas `roteiro-req-252-grade` e `roteiro-req-252-lousa` e as páginas `/roteiro-req-252-grade/` e `/roteiro-req-252-lousa/` ficaram na instalação de teste, para conferência visual.
- Os widgets usados foram os dois do layout do administrador (apresentação e aviso de cookies). Menu, galeria, formulário e índice de páginas não foram exercitados dentro de uma lousa.

## Critérios de aceite

- [x] O widget aparece no cadastro e lista as lousas como registros.
- [x] Página com o widget mostra widgets e objetos, sem iframe.
- [x] Grade e lousa respeitadas; em tela estreita, uma coluna.
- [x] Opções por item respeitadas.
- [x] Nada do que o autor escreveu vira HTML; imagem e destino só nos formatos aceitos.
- [x] Lousa dentro de lousa não entra em laço; lousa excluída ou inexistente não mostra nada (teste de unidade).
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1.

## Pendências

- Homologação humana (roteiro em `conn2flow-site/sdd/PENDENCIAS-HUMANAS.md`).
- Módulo `dashboard-pages`, espelhado no `publisher-pages` (próxima requisição).
