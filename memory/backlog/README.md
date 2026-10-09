# Backlog SDD — Incubadora de Ideias

Esta pasta guarda ideias de longo prazo, discussões e pesquisas que **não estão autorizadas para implementação**.

## Tipos de item

- `Feature`: funcionalidade isolada com valor de produto.
- `Epic`: iniciativa ampla que será dividida em requisitos/batches.
- `Spike/Research`: investigação técnica sem compromisso de implementação.
- `Architecture`: proposta de mudança estrutural ou decisão futura.

## Ciclo de vida

- `ICEBOX`: ideia de gaveta, sem refinamento ativo.
- `IN-DISCUSSION`: em análise com o Arquiteto IA.
- `READY`: madura para promoção, mas ainda não autorizada para código.

## Intake Gate obrigatório

O backlog é área de rascunho administrada pelo Usuário e pelo Arquiteto IA. O Executor pode lê-lo para contexto, mas é estritamente proibido de implementar, criar batch de execução ou alterar código a partir de um item daqui.

Um item `READY` somente vira trabalho autorizado quando o Usuário o promove explicitamente para `sdd/human-requests/req-XXX.md`, atualiza `CURRENT.md` e associa um batch executável.

Itens promovidos ou encerrados devem ser movidos para `archive/`, mantendo sua referência no `BACKLOG-INDEX.md`.
