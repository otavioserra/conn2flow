# BL-021 — Cron: `expressao_cron`/`hora` ignorados e sem trava de sobreposição

- **Tipo**: Bug / Feature
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA
- **Origem**: reescrita de `reference/libraries/cron.md` e `modules/admin-cron.md` (req-179/182), 2026-09-25
- **Componentes**: `gestor/bibliotecas/cron.php`, `gestor/controladores/cron/`, `gestor/modulos/admin-cron/`

## Contexto observado

1. Cada tick executa **todas** as tarefas ativas da frequência (`minutario`, `horario`, `diario`...), na hora em que o tick roda.
2. `hora`, `dia` e `expressao_cron` são gravados e mostrados no painel, mas a engine não os avalia: uma tarefa `diario` com `hora: "03:30"` roda quando o tick diário do servidor rodar.
3. Não há trava contra execução sobreposta: uma tarefa `minutario` que demora mais que o intervalo roda de novo em paralelo.
4. A instalação também não agenda os ticks sozinha; a doc de instalação orienta o crontab manual.

## Proposta

1. Avaliar `expressao_cron` (ou `hora`/`dia`) antes de executar; guardar `ultima_execucao` por tarefa.
2. Trava por tarefa (`GET_LOCK` do MySQL ou arquivo com `flock`) com expiração.
3. Opcional: comando `c2f cron:install` que imprime/instala a linha de crontab do projeto.

## Critérios de aceite (rascunho)

- Tarefa diária com `hora: 03:30` não roda num tick às 10:00.
- Duas execuções simultâneas da mesma tarefa não acontecem.
