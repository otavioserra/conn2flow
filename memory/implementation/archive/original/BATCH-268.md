# BATCH-268 — Linha 3.1, fase 5 da lousa: medição da lousa publicada

- **Requisição:** [REQ-259](../human-requests/req-259.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.1` (branch `feat/req-259`, entregue na `3.1`). Não está na `main` nem na `3.0`.
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`)

## Live Todo List

- [x] Levantamento do módulo de análise do site
- [x] Identificadores estáveis na marcação da lousa publicada
- [x] Eventos de visita, item visto e clique
- [x] Testes, publicação na instalação da 3.1 e roteiro de navegador
- [ ] Funil de exemplo no módulo de análise do site, com o GA4 recebendo
- [ ] Homologação humana

## Como a medição funciona

O módulo `analytics-manager` (do site) monta eventos do GA4 a partir de funis configurados no painel. Um dos gatilhos é o **evento personalizado**: o módulo escuta o evento de página `c2f:analytics` e, quando `detail.event` é o nome de uma etapa do funil, envia o evento, com `detail.data` disponível nos campos como `{{evento.chave}}`. Nada emitia esse evento até aqui.

A lousa publicada (widget "Lousa", REQ-252) passa a emitir:

| Evento (`detail.event`) | Quando | Dados (`detail.data`) |
|---|---|---|
| `lousa_view` | a lousa foi carregada na página | `lousa`, `modo`, `itens` |
| `lousa_item_view` | metade de um item entrou na tela, uma vez por item | `lousa`, `item`, `tipo`, `titulo` |
| `lousa_click` | clique em link ou botão dentro de um item | `lousa`, `item`, `tipo`, `titulo`, `texto`, `destino` |

- `lousa`: identificador da lousa. `item`: posição do item entre os que entraram na página, a partir de 1. `tipo`: o tipo do widget (`menus`, `forms`…) ou `objeto-` mais o tipo do objeto (`objeto-button`, `objeto-text`…).
- `titulo`: o título que o autor deu ao item. `texto`: o texto do link ou botão clicado, até 80 caracteres. `destino`: o endereço do link, **sem** parâmetros nem âncora.

Para quem prefere gatilho por seletor, a marcação ganhou `data-lousa` na casca e `data-item` e `data-tipo` em cada item: por exemplo, `[data-lousa="campanha"] .c2f-lousa-acao` para os botões de uma lousa.

## O que o core faz e o que não faz

- **Só avisa a página.** Não fala com o GA4, não escreve na camada de dados, não grava nada, não usa cookie nem armazenamento do navegador. Sem módulo de análise escutando, o evento não tem efeito.
- **Nenhum dado do visitante** vai no evento: só dados da lousa e do item, escritos pelo autor.
- Consentimento continua sendo do módulo de análise e do aviso de cookies.

## Exemplo de uso no módulo de análise

Etapa de funil com gatilho "evento personalizado", nome `lousa_click`, rota da página da lousa, e campos como `item_name` = `{{evento.texto}}`, `content_type` = `{{evento.tipo}}`, `link_url` = `{{evento.destino}}`. Para as visitas, etapa `lousa_view` com `content_id` = `{{evento.lousa}}`.

## Limites

- **O caminho até o GA4 não foi exercitado.** O roteiro escuta o evento na página, no papel do módulo de análise. Não configurei funil no `analytics-manager` nem conferi a chegada no GA4; isso depende de um contêiner de medição ligado.
- Clique dentro de um widget que troca o conteúdo por script, sem link nem botão, não gera evento.
- A área de widgets do Dashboard (uso interno, em iframes) não é medida.
- Navegador sem `IntersectionObserver` informa a visita e os cliques, não os itens vistos.

## Arquivos

- `gestor/modulos/dashboard/dashboard.widget.php`, `dashboard.widget.js` (e `.min.js`)
- `gestor/modulos/dashboard/resources/{pt-br,en}/components/dashboard-lousa-widget/dashboard-lousa-widget.html`
- `tests/Unit/JS/dashboard.lousa-medicao-req259.test.js`, `tests/Unit/PHP/DashboardLousaWidgetReq252Test.php`
- `sdd/validation/req259/req259-browser.cjs`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte completa) | 1.716 testes, sem falha |
| Vitest (suíte completa) | 660 testes, sem falha |
| `resources:sync`, `assets:minify`, `project:update-all conn2flow-v31-local` | código de saída 0 |
| Navegador, `req259-browser.cjs`, como visitante | 8/8 |
| Regressão do widget, `req252-browser.cjs` | 26/26 |

O roteiro escuta `c2f:analytics` na página e confere: um evento de visita com os dados da lousa; item visto uma vez por item, mesmo rolando a página duas vezes; o título do autor no evento do item; um evento de clique no botão de chamada, com item, tipo, texto e destino, e nenhum no clique em texto; o contrato de nome e dados em todos; e que a lousa não faz chamada de dados, script nem sinal a terceiros, nem escreve na camada de dados.

**Primeira rodada: 7/8, duas vezes.** A falha era da conferência: ela reprovava qualquer pedido a outro servidor, e a lousa de teste tem um widget de apresentação cuja imagem vem de `conn2flow.com`; a entrada na camada de dados é do layout do site e existe igual numa página sem lousa. A conferência passou a comparar com uma página sem lousa e a olhar o tipo do pedido.

## Critérios de aceite

- [x] Evento de visita com a lousa, o modo e a quantidade de itens.
- [x] Evento de item visto, uma vez por item.
- [x] Evento de clique com a lousa, o item, o tipo, o texto e o destino.
- [x] Marcação com identificador da lousa e de cada item.
- [x] Nenhum dado de visitante nos eventos; nada enviado a terceiros pelo core.
- [x] PHPUnit e Vitest verdes; roteiro de navegador na 3.1.
