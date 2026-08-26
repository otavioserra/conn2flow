# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: preservar 3 a 5 tarefas, mirar 3–4,5 KB e podar antes de 5 KB / 50 linhas.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums de recursos são recalculados pelo deploy.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco de desenvolvimento.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`; template alimenta o merge aditivo.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors e mounts Docker.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.

## Tarefas recentes

### 2026-08-26 — BATCH-140/141 (req-137/138): picker em lote, grade e MIME real

- O canal `postMessage` do `admin-arquivos` tem SEIS consumidores e todos leem UM objeto por evento:
  despacho em lote = N mensagens, nunca um array. O campo `tipo` carrega o MIME.
- Layout shift no hover morre trocando `display:none/flex` por `position:absolute` + `opacity`. O
  overlay exige `pointer-events:none` no contêiner e z-index maior no que estava sob ele (checkbox).
- `renderLista()` reconstrói o HTML a cada troca de modo/filtro/página: o estado de seleção precisa
  ser reaplicado no DOM, e a leitura tem que ser `hasOwnProperty` — arquivo chamado `constructor`
  acha propriedade herdada e nasce marcado.
- **Encolher a miniatura não é encolher a caixa.** Para virar grade: `flex-wrap:wrap` no contêiner E
  largura fracionária no item. Em flex-wrap a ordem de leitura (esquerda→direita, descendo) mapeia
  1:1 na ordem do DOM, então o `onEnd` do Sortable, que relê o DOM, segue correto sem condicional.
- Ordem certa no DOM não prova ordem certa no array interno: interceptar o payload enviado ao
  servidor é o que fecha a prova quando a variável é privada ao `ready`.
- `admin-arquivos` devolvia `$tipo.'/'.extensao` como "mime" (`file/pdf`, `image/jpg`), exibido cru
  ao usuário. Consumidores só testam o PREFIXO — a invariante prefixo↔família virou TESTE sobre as
  extensões reais, não comentário.
- Guarda estática que cita o código defeituoso no próprio comentário dispara a si mesma; descreva o
  defeito em prosa. Guarda que precisa de exceção não protege.
- Fomantic: `checkbox('set checked')` NÃO dispara o `change` nativo; em teste headless clique no
  `.ui.checkbox` (o `<label>` vazio tem altura 0). `c2f page:inspect` só mede estado estático —
  hover/arraste/reload pedem script Playwright DENTRO do repositório (`temp/` é git-ignored).

### 2026-08-26 — BATCH-137 (req-135): motion do SO pelo CLI

- O dispatcher resolve aliases sem trocar `InputInterface::getCommandName()`; um único comando pode
  decidir `status|on|off|toggle` pelo nome original.
- No Windows, `SPI_SETCLIENTAREAANIMATION` recebe o booleano no `PVOID` (`IntPtr 0|1`), usa
  `EntryPoint="SystemParametersInfoW"` e flags `3`; não tocar em `MinAnimate` nem no Registro.
- Mudança de preferência do SO pode falhar com `ERROR_ACCESS_DENIED` dentro do sandbox. Validação
  real deve capturar o estado, executar fora do sandbox e restaurar em `finally`.
- Runner de processo injetável permite cobrir Linux/macOS e aliases sem mutar a estação no PHPUnit.

## Pendências e histórico

- Detalhes integrais vivem nos BATCHes e em `sdd/validation/`; histórico antigo está em `archive/`.
