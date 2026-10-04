# BATCH-219: infraestrutura comum para módulos distribuídos (req-211)

Execução da [req-211](../human-requests/archive/req-211.md). Implementado na branch `feat/req-092` por outro executor e integrado na `main` depois de revisão independente; relatório completo, provisionamento do Lab e evidências no `conn2flow-site` (`sdd/implementation/modulos-distribuidos/batch-086-infraestrutura-login-padrao-bridge-e-lab.md`).

**Status**: `in-review` (validado no Lab; revisão humana pendente).

## O que mudou

| Onde | Mudança |
|---|---|
| `gestor/bibliotecas/modulo-distribuido-protocolo.php` (novo) | Envelope assinado com nonce e validade, códigos e tickets de uso único cifrados, contexto de login, rota do proxy no distribuído, rota de iframe no Central, extração das tabelas de uma consulta |
| `gestor/bibliotecas/modulo-distribuido.php` | Login por credenciais no canal desativado; token com renovação; SQL de instrução única, sem comentários; textos em variáveis |
| `gestor/bibliotecas/banco.php` | `banco_query` decide por consulta se ela vai ao distribuído; `banco_last_id` e `banco_linhas_afetadas` seguem a última consulta remota |
| `gestor/gestor.php`, `gestor/bibliotecas/gestor.php`, `usuario.php` | Rotas `_distributed/`, cookies próprios do iframe (`Partitioned`, `SameSite=None`), ponte ligada só em volta do módulo |
| `gestor/modulos/perfil-usuario/` | Ganchos `login.sucesso` e `login.distribuido`; campos de contexto no formulário |
| `gestor/controladores/api/` | Ações `exchange`, `iframe-ticket`, `refresh` com rotação; `signin` responde 410; limite de chamadas próprio do canal |
| `gestor/db/migrations/20261002143002_create_distributed_exchanges_table.php` | Tabela dos códigos, tickets e nonces |
| Componentes `modulo-distribuido-app`, `modulo-distribuido-shell`, `perfil-usuario-distribuido-contexto` | Tela do distribuído sem formulário próprio |

## Achado da revisão, corrigido neste lote

**A lista de tabelas do canal era contornável.** A extração só enxergava `FROM nome` e `JOIN nome`. Onze formas válidas de SQL nomeavam outra tabela sem esse formato e passavam como se usassem só as permitidas, entre elas `FROM (usuarios)`, `JOIN (usuarios)`, `STRAIGHT_JOIN usuarios`, `(TABLE usuarios)`, `{OJ usuarios ...}` e a vírgula depois de um `JOIN ... ON` (`FROM coupons JOIN products ON 1=1, usuarios`). Com o segredo do canal, ou por uma injeção de SQL num módulo do Central, dava para ler `usuarios` no banco do cliente por um canal que deveria alcançar só tabelas de negócio.

`modulo_distribuido_sql_tabelas()` passou a percorrer os tokens da consulta: depois de `FROM`, `JOIN`, `STRAIGHT_JOIN`, `UPDATE` ou `INTO` só vale um nome simples ou `(SELECT`; qualquer outra coisa recusa a consulta. Vírgula dentro de uma lista de tabelas recusa em qualquer posição. `USING`, `TABLE`, `LATERAL`, `PARTITION`, `HANDLER` e chaves recusam. De quebra, `ON DUPLICATE KEY UPDATE` e `FOR UPDATE`, que antes eram recusados por engano, passam.

## Validação

- `ModuloDistribuidoReq092Test` (com 16 formas de fuga recusadas e 8 formas legítimas aceitas), `ModuloDistribuidoTest`, `ModuloDistribuidoRateLimitTest`.
- Sonda com 59 consultas contra a função antes e depois: 11 fugas antes, nenhuma depois.
- Suíte PHPUnit completa e E2E no Lab: números no relatório do site.

## Limites

- Só o módulo `coupons` teve CRUD ponta a ponta. Os outros 26 estão registrados, não validados.
- Segundo fator no login distribuído: o gancho fica depois dele no código; não há E2E.
- Login social e cadastro novo durante o fluxo distribuído: o social passa pelo gancho; o cadastro não, e o usuário termina no painel do Central.
- Cookies do iframe dependem de `Partitioned` em contexto de terceiros: testado só no Chromium.
- A extração de tabelas é uma leitura de tokens, não um analisador de SQL: formas que ela não reconhece são recusadas, inclusive algumas legítimas (`EXTRACT(x FROM y)`, `UPDATE IGNORE t`).

## Pendências

- Revisão humana.
