# BATCH-223: módulos distribuídos — rotina local pedida pelo Central (req-215)

Execução da [req-215](../human-requests/req-215.md). Parte do core da REQ-096 do `conn2flow-site`, onde está o relatório da execução no cliente dos módulos restantes (`sdd/implementation/modulos-distribuidos/batch-090-execucao-no-cliente-dos-modulos-restantes.md`).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` | `modulo_distribuido_rotina($nome, $args)` (Central), `modulo_distribuido_rotina_resolver()` e `modulo_distribuido_rotina_executar()` (cliente); `modulo_distribuido_execucao_ativa($modulo)`; `modulo_distribuido_consulta_canonica()`; o proxy não intercepta módulo cuja cópia declara `panel: false` e leva os parâmetros do endereço ao iframe |
| `gestor/controladores/api/api-module-distributed.php`, `api.php` | Ação `rotina` no canal assinado |
| `gestor/controladores/api/api-module-central.php` | O ticket do iframe guarda os parâmetros do endereço, reconstruídos e limpos |
| `gestor/gestor.php`, `bibliotecas/widgets.php`, `bibliotecas/hooks.php`, `bibliotecas/cron.php`, `controladores/plataforma-gateways/plataforma-gateways.php` | Página, widget, gancho, tarefa agendada e webhook de cópia de execução não contratada ficam inertes |

## Rotina local

O painel roda no Central; alguns efeitos só existem no site do cliente (e-mail com a identidade do site, página pública, estorno com a credencial local). O Central pede a rotina por nome, com argumentos, pelo canal assinado (HMAC, carimbo de tempo, nonce de uso único); o cliente executa a função que o manifesto da cópia de execução declarou em `routines` e devolve o retorno.

- Nome de rotina, nome de função, arquivo (sem barra) e bibliotecas têm formato fechado; o nome pedido nunca vira nome de função.
- Só para módulo contratado pela instalação.
- A saída impressa pela rotina é descartada; a exceção fica no log do cliente e o Central recebe só `routine-failed`.
- Quem pediu (operador do Central) e o idioma chegam à rotina em `$_GESTOR['distributed-routine']`.
- Fora de contexto distribuído, `modulo_distribuido_rotina()` devolve `null` e o módulo segue com o comportamento local.

## Cópia de execução contratada

Todo host distribuído recebe o mesmo overlay, com a cópia de execução de todos os módulos. Uma cópia (`"scope": "distributed-execution"`) só age quando o módulo está entre os contratados do `.env`, ou quando um dos módulos de `active_with` está (a vitrine não tem painel e vem com os produtos). Módulo comum e módulo de plugin não são afetados. Tarefa de módulo não contratado sai como `aviso`, não como erro.

## Parâmetros do endereço no iframe

Link direto para uma tela do painel com parâmetros (`orders/details/?id=...`) e o retorno de autorização de terceiros (`.../callback/?code=...&state=...`) perdiam os parâmetros ao entrar no iframe. Agora seguem: só parâmetros `GET` simples, sem o do roteador nem os de AJAX, reconstruídos no cliente e de novo no Central, com limite de 2.000 caracteres. O retorno ao site depois do login também os mantém.

## Validação

- `ModuloDistribuidoRotinaReq215Test`: 16 testes (rotina declarada, recusas, falha e saída sem vazamento, idioma e solicitante, Central fora de contexto, ida e volta assinada, repetição recusada, transporte falho, contratação, pontos de entrada, parâmetros do endereço).
- Suíte completa: 1.449 testes, 1 falha anterior ao lote (`ProjectSshDeployReq034Test`), em ordem aleatória; 461 testes JS.
- No Lab, pelo `conn2flow-site`: rotina pedida pelo Central e executada no tenant (consulta, token do pedido, ações administrativas de assinatura, gravação de arquivos), rotina não declarada, de outro módulo ou de módulo só painel recusada; loja e checkout servidos pelo tenant com `store` na lista de módulos; link direto com parâmetros chegando ao iframe. Roteiros `req096-loja-e2e.cjs` 54/54 e `req096-assinaturas-e2e.cjs` 37/37.

## Limites

- A resposta de recusa do cliente chega ao Central como falha de transporte (o cliente responde 403 e o transporte não lê corpo de erro). O efeito é o mesmo: falha fechada.
- O retorno de autorização de terceiros pelo iframe não foi testado com aplicativo real.
