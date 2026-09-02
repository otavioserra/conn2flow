# Memória de Engenharia — Execução

> **Propósito**: contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: é proibido podar abaixo de 50 KB / 200 linhas; emitir alerta preventivo nesse patamar, podar obrigatoriamente ao atingir 75 KB / 300 linhas e mirar ~25 KB, preservando 20 a 25 tarefas e aprendizados recentes. O fim da sessão ou do batch não aciona poda.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors, transportes e mounts.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.

## Tarefas recentes

### 2026-09-02 — BATCH-159 (req-156): release remoto consome derivados locais

- O `manager:release` local é a fronteira de geração dos derivados de recursos Tailwind. O workflow
  `release-gestor.yml` deve apenas testar o estado commitado, remover fontes de autoria e empacotar;
  recompilar no runner Linux invalida o cache gerado no Windows e pode criar um ZIP posterior aos
  testes.
- Remover junto `Generate resources and per-resource Tailwind CSS` e `Commit Resources Updates`:
  manter só a primeira elimina a recompilação, mas ainda deixaria um commit remoto sem propósito.
  `release-instalador.yml` já segue esse padrão enxuto.
- Validação registrada: YAML lint nos dois workflows; PHPUnit 1073/1073; Vitest 382/382. O Vitest
  pode imprimir `ECONNREFUSED 127.0.0.1:3000` em teardown do happy-dom e ainda encerrar com sucesso;
  conferir o resumo final e o exit code antes de classificar como falha.

### 2026-09-02 — BATCH-157 (REQ-035 / req-155): checksum derivado E dependente do fim de linha

- **O checksum do recurso é DERIVADO, não autoria.** `atualizacao-dados-recursos.php` grava o md5 do
  HTML de volta no manifesto (`ORIGIN_UPDATE_MODULE`). Teste que exige campo VAZIO num campo que o
  pipeline preenche briga com o pipeline; o invariante certo é a COINCIDÊNCIA com o arquivo.
- **`buildChecksum()` calcula o md5 dos BYTES do disco, e o disco varia por plataforma.** Índice em
  LF + `core.autocrlf` no Windows = árvore em CRLF (`git ls-files --eol` → `i/lf w/crlf`). São 233
  quebras de linha neste arquivo: hash completamente distinto do que o runner Linux calcula.
  **Sintoma:** CI acusando "checksum divergente" com dois hashes plausíveis e nada errado no
  recurso. **Antes de regravar o hash, rode `git ls-files --eol`** — regravar no Windows só repete
  o laço na release seguinte.
- **Simular o runner é barato e conclusivo**: converter o arquivo para LF em disco, rodar a suíte,
  `git checkout` depois. Provou a correção sem esperar o CI.
- **`git checkout` de um arquivo com `autocrlf=input` devolve LF** — e é essa forma canônica que o
  `resources:sync` deve compilar. O `PaginasData.json` estava com `
` DENTRO do HTML servido em
  runtime pela mesma causa.
- **`schema-metadata.json` muda todo sync** (`generated_at`): esse arquivo sozinho no `git status`
  não indica mudança real.
- **`resources:sync` do núcleo reporta `Tarefas de Cron: 0`**: nenhum módulo do core declara a chave
  `cron`. Tarefas de projeto são compiladas por `project:sync-resources`.
- **Outro agente commitou por cima do meu lote** (`3d705ee7` arrastou `AdminCronReq032Test.php` junto
  do release 2.10.3). Antes de preparar commit, confira `git log -1 -- <arquivo>`: a árvore pode ter
  ficado limpa porque alguém já levou a alteração, não porque nada foi feito.


### 2026-09-02 — BATCH-156 (req-154): templates Tailwind no preview

- Os 72 modelos por idioma, 36 Tailwind, sidecars, `TemplatesData.json` e banco estavam íntegros.
  A causa era a cascata do iframe: Tailwind pré-compilado em `@layer` seguido por Fomantic sem camada;
  CSS não estratificado vence utilities estratificadas. A/B: `py-20` 80→70px, `gap-12` 48→42px e
  CTA branco→transparente. Em preview Tailwind, preserve scripts legados, mas não `semantic.min.css`.
- Ao inserir seção, nunca substitua o baseline da página pelo sidecar do fragmento: concatene ambos.
  Substituição integral de página pode trocar o baseline. `resources:sync --force` recompilou 237/237.
- Guarda atual: 72 HTMLs/thumbnails + 36 sidecars por idioma e presença no CSS de cada utility usada
  das famílias padding, margin, gap, spacing, background, rounded e shadow.

### 2026-09-02 — BATCH-155 (req-153 / REQ-034): transporte SSH e bootstrap CLI

- Tenants saíram do `conn2flow-app` para VM/HestiaCP. Verifique `environment.json`/`docker ps`; SSH
  remoto exige destino declarativo, `BatchMode`, argumentos citados para o shell remoto,
  `sudo rsync` e devolução de posse por `chown`.
- `config.php` não pode impor `SERVER_NAME=localhost` no CLI: o sufixo do cookie fica incorreto e a
  sessão é ignorada com 302 silencioso. Checksum de `<modulo>.json` pertence ao compilador;
  `ORIGIN_UPDATE_MODULE` o repõe, então a guarda aceita vazio ou md5 coincidente com o arquivo.

### Histórico anterior

BATCH-144 (autoria x derivado no CSS; runtime serve do banco, disco só com `DEVELOPMENT_ENV`) e
BATCH-146/147 (cópias congeladas de widget, alvo do CLI e assets locais) foram podados por limite
de tamanho. O registro integral vive em `sdd/implementation/BATCH-144.md`, `BATCH-146.md` e
`BATCH-147.md`.
