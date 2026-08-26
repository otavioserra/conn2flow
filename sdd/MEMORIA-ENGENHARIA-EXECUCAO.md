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

### 2026-08-26 — BATCH-137 (req-135): motion do SO pelo CLI

- O dispatcher resolve aliases sem trocar `InputInterface::getCommandName()`; um único comando pode
  decidir `status|on|off|toggle` pelo nome original.
- No Windows, `SPI_SETCLIENTAREAANIMATION` recebe o booleano no `PVOID` (`IntPtr 0|1`), usa
  `EntryPoint="SystemParametersInfoW"` e flags `3`; não tocar em `MinAnimate` nem no Registro.
- Mudança de preferência do SO pode falhar com `ERROR_ACCESS_DENIED` dentro do sandbox. Validação
  real deve capturar o estado, executar fora do sandbox e restaurar em `finally`.
- Runner de processo injetável permite cobrir Linux/macOS e aliases sem mutar a estação no PHPUnit.

### 2026-08-26 — BATCH-136 (req-134): CLI opera no mirror, não na fonte

- `path_tests`/`target` são a **raiz completa do Gestor** no mirror (`.../photon/config.php`); não acrescentar `/gestor`. Resolução: `path_tests → target → path`, aceitando também raiz com `gestor/config.php`.
- `environment.json` usa paths MSYS (`/c/...`) mesmo com PHP nativo Windows. Converter para `C:\...` antes de `realpath()` e comparar paths sem depender de caixa para derivar `/var/www/sites/<relativo>`.
- O container monta `dev-environment/data/sites` em `/var/www/sites`, não o repositório inteiro. `auth:cookie` copia o gerador para `/tmp`, executa contra o mount e lê o resultado temporário pelo mirror; limpar temporários em sucesso e erro.
- `proc_open()` aceita array de argumentos: evita escape de shell e permite runner injetável. Cobrir em teste Docker ativo e fallback local.
- `.env` de mirror é estado concorrente: capture, altere, restaure e confira depois. Neste lote mudou externamente após a restauração; o bootstrap não contém escritor e o agente preservou o valor mais recente.
- Acesso ao named pipe Docker pode exigir escalonamento. Não interpretar `permission denied` como container parado nem substituir a prova ponta a ponta por fallback local.

### 2026-08-25 — BATCH-134 (req-132): limpeza na última etapa

- `gestor/gestor.php` é roteador e chama `gestor_start()`; função testável fica em `gestor/bibliotecas/gestor.php`, antes do `?>` final.
- Compare árvore DOM, não contagem de strings. Preserve `<pre>`/`<textarea>`, condicionais e quebras entre elementos inline.
- JavaScript exige scanner de estados, preservação de ASI e exclusão de `application/json`/`text/template`; valide o resultado com `node --check`.

### 2026-08-24 — BATCH-129 (req-127): onde o recurso nasce decide se persiste

- `gestor/db/data/*Data.json` é artefato; modos de IA nascem em `resources/<lang>/ai_modes/` e são compilados por `resources:sync`.
- Orçamento deve distribuir vagas por namespace (round-robin); marcador de itens restantes também entra no teto.
- Bloco condicional de prompt valida a ordem dos marcadores e remove a tag mesmo sem o par no conteúdo editável.

## Pendências e histórico

- Detalhes integrais vivem nos BATCHes e em `sdd/validation/`; histórico antigo está em `archive/`.
