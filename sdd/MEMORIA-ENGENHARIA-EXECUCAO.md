# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: preservar 3 a 5 tarefas, mirar 3–4,5 KB e podar antes de 5 KB / 50 linhas.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors e mounts Docker.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.

## Tarefas recentes

### 2026-08-26 — BATCH-142 (req-139): galleries denso, modal e corte vertical

- Grade compacta exige largura fracionária no card e `flex-wrap`: desktop validado em 6×110 px
  (médio) e 10×65 px (pequeno), mantendo os breakpoints 3/5 e 2/3.
- Overlay sem layout shift: absoluto, `opacity` e backdrop; no hover o contêiner precisa trocar
  também para `pointer-events:auto` — deixar `none` apenas nos botões funcionava, mas divergia da spec.
- Edição rápida pode reutilizar os listeners delegados do card: o item é atualizado sincronicamente,
  o debounce cuida dos inputs e `onHidden` reconstrói a lista e força o preview.
- `image_position` precisa de allowlist idêntica no CRUD, PHP e widget JS. O template recebe valor,
  classe `object-*` e `data-image-position`; o JS público reaplica antes do early return dos modelos.
- Em validação local, um dry-run do atualizador avançou checksums sem aplicar linhas. Não usar
  `force-all` sobre tenant: carregue no editor o recurso versionado e registre a limitação.

### 2026-08-26 — BATCH-140/141 (req-137/138): picker, grade e MIME

- O `postMessage` do `admin-arquivos` tem seis consumidores: lote = N mensagens de um objeto, não array.
- `renderLista()` reconstrói o DOM; seleção deve ser reaplicada e lida com `hasOwnProperty`.
- Ordem visual/DOM não prova o array privado: intercepte o payload depois do Sortable.
- MIME nasce de um mapa único por extensão; teste a invariante prefixo↔família sobre o mapa real.

### 2026-08-26 — BATCH-137 (req-135): motion do SO pelo CLI

- Dispatcher mantém o nome original do alias; comandos podem decidir `status|on|off|toggle` por ele.
- Preferência do SO pode exigir execução fora do sandbox; capture e restaure o estado em `finally`.

## Pendências e histórico

- Detalhes integrais vivem nos BATCHes e em `sdd/validation/`; histórico antigo está em `archive/`.
