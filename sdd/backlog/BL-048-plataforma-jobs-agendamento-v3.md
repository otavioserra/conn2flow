# BL-048 — Plataforma de jobs, agendamento e execução resiliente da v3

- **Tipo:** Architecture/Reliability/Background Processing
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** cron, filas persistentes, retries, locks, idempotência e tarefas demoradas
- **Relacionados:** BL-011, BL-029, BL-032, BL-037, BL-041, BL-044, BL-045, BL-047

## Problema

Publicação, sitemap, e-mails, integrações, uploads, manutenção e futuras rotinas de agentes não devem depender de uma requisição web longa. Hoje há cron e rotinas específicas, enquanto o BL-044 precisa de runner/outbox. Criar um mecanismo diferente por módulo repetiria locks, retries, logs e tratamento de falhas.

## Decisão proposta

Criar uma plataforma mínima `C2FJobs` no core. O primeiro backend pode usar tabela SQL + runner CLI/cron, adequado a cPanel e Docker; adapters futuros para filas externas não alteram o contrato dos casos de uso.

Outbox transacional e fila de execução são conceitos distintos:

- a outbox garante que um fato de domínio não se perca após o commit;
- o job representa uma unidade executável, agendada, observável e repetível com segurança.

BL-044 deve ser o primeiro consumidor, não uma implementação paralela.

## Contrato mínimo

- tipo e versão do job;
- payload pequeno, validado e sem segredo em claro;
- installation/site id e correlation id;
- estado, prioridade, disponibilidade e número de tentativas;
- idempotency/deduplication key;
- timeout/lease e heartbeat quando necessário;
- política de retry com backoff/jitter e limite;
- resultado resumido, erro sanitizado e dead-letter/reprocessamento controlado.

## Regras

- handler OO, pequeno e com dependências explícitas;
- retry não pode duplicar efeitos externos;
- concorrência usa claim atômico/lock compatível com MySQL e PostgreSQL;
- jobs grandes referenciam arquivos/objetos, não armazenam blobs na tabela;
- cancellation e reprocessamento exigem capability e auditoria;
- cron apenas dispara o runner; regra de negócio fica no caso de uso;
- request web responde com estado/execução, não fica em polling infinito sem limite.

## Implantação progressiva

1. runner síncrono de teste sob o mesmo contrato;
2. tabela SQL, CLI e cron local;
3. outbox/publicação, sitemap e cache;
4. e-mails/webhooks e tarefas de manutenção;
5. avaliar fila externa somente com necessidade comprovada.

## Testes mínimos

- claim concorrente, lease expirado e retomada após crash;
- retry/backoff, dead-letter e reprocessamento autorizado;
- idempotência interna e de integrações externas;
- isolamento entre instalações;
- paridade MySQL/PostgreSQL;
- payload inválido/versionamento incompatível;
- execução sem daemon via cron e execução contínua em Docker.

## Critérios de aceite

- tarefas demoradas críticas não dependem da sessão web permanecer ativa;
- duplicação, crash e retry não corrompem estado nem repetem efeitos;
- plataforma funciona em hospedagem simples e em worker dedicado;
- painel operacional mostra estado sem revelar payload sensível;
- produtores/consumidores têm contratos versionados;
- BL-044 reutiliza a infraestrutura comum.

## Próxima ação

Promover uma PoC com tabela, runner CLI e um consumidor local idempotente do BL-044. Medir concorrência e portabilidade antes de incluir e-mail, webhook ou integração externa.
