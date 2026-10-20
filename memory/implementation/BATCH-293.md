---
id: "BATCH-293"
title: "Cache do Tailwind por recurso portável, Data.json sem troca de quebra de linha e diff de choque sem estouro (REQ-284)"
status: "implemented-pending-homologation"
date: "2026-10-19"
author: "executor"
target_repo: "conn2flow"
summary_short: "Compilar o conn2flow-site de outra pasta do core ou com outra caixa de drive passou de 1207 recursos recompilados (528 s) para 0 (7 s), com Data.json idênticos byte a byte. Detecção automática sem ler seeds; diff de choque sem estouro de memória; skills mandam project:deploy para mudança só de projeto."
---

# BATCH-293 — REQ-284

- Branch `feat/req-284`, worktree `GIT/conn2flow-req284` (base `main` 763d606b). Commits: `9b2520a0`, `e62ae17c`, `4762f70a`, `df90ff44`.
- Requisição: [req-284.md](../human-requests/req-284.md). Origem: incidente da REQ-141/REQ-142 do conn2flow-site.

## Live Todo

- [x] Diagnóstico com dados (manifesto commitado x execução da worktree; experimento isolado com o CLI 4.3.3).
- [x] `tailwind_recursos_chave_fonte()`: fonte no fingerprint como `gestor:`/`core:` + caminho relativo; manifesto versão 2.
- [x] `TAILWIND_RECURSOS_FORA_DA_DETECCAO` com `@source not` nos inputs de layout/bundle.
- [x] `readFileIfExists()` normaliza `\r\n` → `\n` em extensões de texto antes do checksum.
- [x] `instalacao_diff_arquivos()`: tamanho conferido no disco antes de ler.
- [x] Skills `c2f-executor-agent` e `c2f-project-pipeline-and-tasks` nos 4 kits versionados (e no `.claude` local).
- [x] Medição no conn2flow-site e diff de seletores.
- [ ] Release do core e homologação humana.

## Evidências

| Situação | Tailwind | Tempo |
| --- | --- | --- |
| Antes: core em outra pasta (`conn2flow-req141-main`) | 1207 de 1295 recompilados | 528 s (Tailwind) |
| Reconstrução única com manifesto v2 | 1295 | 488 s |
| Depois: core em outra worktree (`conn2flow-req284-b`) | 0 compilados, 1295 em cache | 7 s no total |
| Depois: caminho do core em minúsculas (`c:/users/...`) | 0 compilados | 5,6 s |

- `*Data.json` do site idênticos byte a byte entre duas pastas do core (só o `schema-metadata.json` difere, pelo carimbo `generated_at`).
- Diff de seletores (328 `.precompiled.css`): 126 classes saem; nenhuma usada como classe em HTML/PHP/JS de autoria (as 9 que aparecem em texto são CSS inline, prosa de docs ou mensagem de teste). Cerca de 10 KB a menos por página.
- Reprodução do input real da `3d-catalog-groups-adicionar`: `.bg-blue-900` presente com `db/data` na varredura e ausente sem ela; só existia nos seeds.
- PHPUnit: `TailwindRecursosTest` 26/26, `DbDataCompilerTest` 2/2, `InstalacaoManifestoTest` 28/28, `AtualizacoesManifestoIntegracaoTest` 5/5, `RecoverFilesTest` 3/3, `RecuperacaoDadosRecursosTest` 19/19, `PipelineSkipCssReq269Test` 6/6, `RefinamentosReq242Test` 16/16, `ModaisSistemaTailwindTest` 4/4, `Req204LayoutMultiPerfilTest` 6/6, `TailwindGuardasTest` 20/20. `AdminCronReq032Test` com 2 warnings, iguais na árvore principal.
- `ai:sync`: os 4 kits versionados 45/45.

## Decisões

- **Detecção automática mantida, com exclusões.** Cortá-la tiraria de layouts as classes que só aparecem em PHP/JS de módulo. O experimento mostrou que `@source not "db/data"` exclui os seeds e preserva o PHP de módulo.
- **Sem cache central separado.** O manifesto do projeto já é único (fica no repositório do projeto) e, com chaves relativas, casa com qualquer pasta do core; os recursos do core usam o manifesto versionado do próprio checkout.
- **Normalização só em texto** (`html`, `css`, `js`, `md`, `json`, `txt`, `svg`, `xml`); binário segue byte a byte.

## Observações

- A primeira compilação de cada projeto com este core recompila tudo uma vez (manifesto v2) e sobe versão/checksum dos manifestos uma vez (normalização LF).
- `CssRegeneracaoTest::testCssRebuildReconheceCheckoutCoreEInstalacaoPlana` falha em worktree com `vendor` por junção, com ou sem esta mudança; passa na árvore principal.
- Arquivos achados pela detecção automática (PHP/JS de módulo) continuam fora do fingerprint: mudar só um deles não invalida o cache de layouts. Pré-existente.
- O Lab roda o core da `main`: o `project:deploy` do site continua recebendo 500 ali até este core chegar. Além disso, os `db/data` que o rsync da REQ-141 levou ao Lab ficam como "editados" e entram como choque para decisão no painel.
