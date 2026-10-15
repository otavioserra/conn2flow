# BATCH-271 — Pontos de extensão do uso de IA: autorizar o pedido e pedido concluído

- **Requisição:** [REQ-262](../human-requests/req-262.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.0` (branch `feat/req-262`, entregue em `main`, `3.0` e `3.1`).
- **Data:** 2026-10-07
- **Coordenada com:** REQ-116 do site (créditos de IA por perfil), que é quem usa os pontos.

## Live Todo List

- [x] `ia-provedores` / `pedido.autorizar` e `pedido.concluido` na camada de provedores
- [x] Identificação do recurso no pedido; o editor e o teste de conexão informam os seus
- [x] Teste automatizado e documentação nos dois idiomas
- [x] Exercitado por um módulo real (o `ai-admin` do site) nos dois ambientes de teste
- [ ] Homologação humana

## O que mudou

Todo pedido de IA passa por `ia_provedor_gerar_texto()` ou `ia_provedor_gerar_imagem()`. As duas passaram a disparar:

| Ponto | Tipo | Quando | Recebe |
|---|---|---|---|
| `ia-provedores` / `pedido.autorizar` | filtro | antes de falar com o provedor | recusa acumulada (vazia) e o contexto |
| `ia-provedores` / `pedido.concluido` | ação | depois da resposta, em sucesso e em falha | contexto mais resultado e consumo |

O contexto traz `tipo` (`texto` ou `imagem`), `provedor`, `modelo`, `recurso` e `referencia`. A conclusão acrescenta `status`, `tokens_entrada`, `tokens_saida` e `imagens`. Nenhum dos dois recebe o conteúdo do pedido nem a chave.

- **Recusa.** Quem se registra em `pedido.autorizar` devolve uma mensagem para recusar. O pedido não vai ao provedor e volta a quem chamou com `status` de erro, `bloqueado` verdadeiro e a mensagem.
- **Recurso.** Quem chama a camada informa `recurso` e `referencia` no pedido. O Assistente IA do editor manda `editor-html` e o módulo; o teste de conexão manda `teste-conexao`.
- **Erro em quem se registrou** vai para o log e o pedido segue. Quem precisa barrar em caso de erro trata isso no próprio callback (o módulo de créditos do site faz assim).
- **Fora do sistema** (teste, script sem os hooks carregados) a camada segue como antes.
- Contagem negativa de tokens vinda do provedor chega como zero.

Sem ninguém registrado, o custo é uma consulta de hooks por ponto e nada muda no comportamento.

## Arquivos

- `gestor/bibliotecas/ia-provedores.php`, `gestor/bibliotecas/ia.php`
- `ai-workspace/pt-br/docs/concepts/hooks.md` e `ai-workspace/en/docs/concepts/hooks.md`
- `tests/Unit/PHP/IaProvedoresUsoReq262Test.php`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte inteira) | 1.755 testes, 20.549 asserções, 5 pulados — OK |
| Teste da requisição | 5 testes, sem rede: contexto sem conteúdo nem chave; recusa impede o pedido; conclusão em falha do provedor; erro no callback não derruba; sem hooks nada muda |
| Uso real | o módulo `ai-admin` do site barra e debita por estes pontos; roteiro dele 25/25 em `v3.1-conn2flow.local` e em `conn2flow.local` |
| Roteiro da REQ-260 (regressão) | 24/24 em `conn2flow.local` |

O Vitest não foi rodado de novo: o lote não toca JavaScript do core.

## O que não foi exercitado

- O caminho de sucesso com resposta real do provedor está coberto pelo roteiro do site, não pelo teste sem rede do core (que cobre recusa, falha de comunicação e as funções dos pontos).
- Mais de um módulo registrado no mesmo ponto.
- `pedido.concluido` para imagem em pedido real (o roteiro do site só fez pedidos de texto nesta rodada).
