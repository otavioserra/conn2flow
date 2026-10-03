# BATCH-224: módulos distribuídos — catálogo local, estado da conta e painel só de visualização (req-216)

Execução da [req-216](../human-requests/req-216.md). Parte do core da REQ-097 do `conn2flow-site`, onde estão o relatório (`sdd/implementation/modulos-distribuidos/batch-091-acesso-pelo-perfil-e-estado-da-conta.md`) e a arquitetura (`sdd/specs/modulos-distribuidos-arquitetura.md`).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` | Catálogo local (`modulo_distribuido_catalogo_local()`, `_modulos_locais()`, `_tabelas_locais()`); estado da conta no Central (`modulo_distribuido_conta()`) e no cliente (`modulo_distribuido_conta_local()`, `_conta_estado()`, `_aceita_venda_nova()`); execução pelo plano somado aos módulos já ativados; rotina recusada com `read-only` quando não é de leitura; trava do painel em `modulo_distribuido_modulo_iniciar`; aviso no topo (`modulo_distribuido_aviso_conta()`); saída do iframe para o destino |
| `gestor/bibliotecas/modulo-distribuido.php` | `banco_distribuido_query` recusa escrita no modo `somente-leitura` |
| `gestor/controladores/api/api-module-central.php`, `api.php` | Ação `estado` (slug reservado `_conta`); `permissao` devolve `destino` e `conta` e recusa conta encerrada |
| `gestor/controladores/api/api-module-distributed.php` | Usa o catálogo local quando o `.env` não lista módulos e tabelas |
| `gestor/gestor.php` | Aviso da conta nas páginas do painel distribuído |

## Catálogo local

O `.env` do cliente deixa de precisar de `MODULO_DISTRIBUIDO_MODULES` e `MODULO_DISTRIBUIDO_TABLES`: sem eles, valem os do `project/distributed-modules.json` do pacote. A lista do `.env`, se existir, ainda manda (compatível com hosts antigos). Sem `app-id` (o próprio Central), a lista local é vazia: o Central não proxia os próprios módulos.

## Estado da conta

- O projeto registra `$_CONFIG['modulo-distribuido']['account-provider']`, que recebe o `app-id` e devolve `{estado, modulos, destino, mensagem}`. Sem provedor, ou se ele falhar, a conta é `ativo` com todos os módulos.
- Estados: `ativo`, `carencia`, `suspenso`, `encerrado` (`MODULO_DISTRIBUIDO_ESTADOS`).
- O cliente pergunta pela ação `estado` (resposta assinada pelo Central) e guarda em `distributed_exchanges` (`kind = conta`, id `sha256('conta|<app>')`) por `MODULO_DISTRIBUIDO_CONTA_TTL` = 600 s. Central fora do ar ou resposta não assinada: vale o último estado. Nunca respondeu: ativo.
- Os módulos do plano somam-se aos `ativados` (todo módulo que já executou continua executando).

## Painel por estado

| Estado | Painel |
|---|---|
| `ativo` | Normal |
| `carencia` | Normal, com aviso e link para o destino |
| `suspenso` | Banco distribuído só de leitura; rotina só com `['leitura' => true]`; POST de formulário e opções `excluir`, `status`, `clonar` recebem página de "somente visualização" com o link; aviso no topo |
| `encerrado` | A janela sai do iframe para o destino |

Perfil sem o módulo também vai ao destino (`permissao` devolve `destino`).

## Validação

- `tests/Unit/PHP/ModuloDistribuidoContaReq216Test.php`: 9 testes (catálogo, `.env`, Central sem `app-id`, provedor, cache e ativados, Central fora do ar e resposta forjada, nunca respondeu, trava de execução, canal só de leitura, detecção de escrita, ação `estado`).
- `ModuloDistribuidoRotinaReq215Test.php` ajustado ao catálogo local. Canal: 82 testes OK. Suíte: 1.458, só a falha anterior `ProjectSshDeployReq034Test` (fim de linha).
- No Lab, pelo site: `req097-estados-e2e.cjs` 18/18 e a regressão dos roteiros distribuídos (req092 17/17, req094 36/36, req095 17/17, req096 54/54 e 37/37).

## Limites

- O estado chega ao cliente em até 10 minutos; no painel é na hora.
- Tirar um módulo do plano não desliga a execução dele no cliente se já executou (decisão: não derrubar pós-venda).
