# 02 — Governança em Tríade

## Por que não um único agente autônomo

Deixar um único agente de IA ler uma requisição e reescrever livremente
código (ou conteúdo) tende a produzir regressões sem revisão e desvio de
arquitetura, porque o mesmo ator planeja, executa e avalia o próprio
trabalho.

## A Tríade: Arquiteto, Executor, Revisor

O modelo de governança do Conn2Flow — Spec-Driven Development (SDD) —
divide o trabalho entre três papéis que compartilham uma única fonte da
verdade, `sdd/`:

- **Arquiteto (macro-orquestrador)**: traduz a intenção humana em
  especificações normativas, registros de decisão (`sdd/decisions/`) e
  requisições formais (`sdd/human-requests/req-XXX.md`). Nunca realiza
  commit ou push de código diretamente.
- **Executor (micro-operador)**: lê a requisição ativa, implementa a menor
  fatia revisável, roda os testes e registra evidência em
  `sdd/implementation/` e `sdd/validation/`.
- **Revisor**: audita o diff de forma findings-first — desvio de
  especificação, desvio de lote, validação ausente — antes de o lote ser
  considerado fechado.
- **Humano-no-Loop**: direciona o Arquiteto e inspeciona o diff do Executor
  antes de qualquer consolidação.

## Fronteiras explícitas e auditáveis

O modelo traça uma linha clara entre o que um agente pode escrever por
conta própria e o que sempre exige um humano ou uma mudança normativa
formal:

- 🟢 **Área operacional** (agente escreve livremente): progresso de
  implementação (`sdd/implementation/`) e evidência de validação
  (`sdd/validation/`).
- 🟡 **Área compartilhada, sob reserva**: novos arquivos
  `sdd/human-requests/req-XXX.md`, criados sob um protocolo de reserva
  atômica para evitar colisão de numeração entre agentes concorrentes.
- 🔴 **Área normativa** (agente apenas lê): `sdd/SPEC.md`, especificações
  numeradas e `sdd/decisions/DECISION-LOG.md`. Uma discordância no nível de
  especificação vira uma Change Request, nunca uma edição direta.

## O espectro de autonomia

Nem toda sessão precisa do mesmo nível de supervisão. O framework reconhece
três níveis explícitos — **Supervisionado** (padrão; sem commit/push/deploy
autônomo), **Autônomo Monitorado** (esteira completa visível ao vivo no
chat, deploy restrito a ambientes de teste locais) e **Autônomo Headless**
(execução em segundo plano com relatório consolidado ao final) — de modo
que o nível de confiança concedido a um agente seja uma escolha deliberada,
não um acidente de como um prompt foi redigido.

## Memória em vez de repetição

Dois diários de engenharia evitam que o contexto precise ser rederivado a
cada sessão: uma memória de Chefia (estilo, convenções, limites
arquiteturais — somente leitura para o Executor) e uma memória de Execução
(particularidades de dependências, comportamento do compilador, bugs
resolvidos — leitura e escrita para o Executor, podada periodicamente para
nunca virar excesso de contexto).
