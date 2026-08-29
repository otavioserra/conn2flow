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

### 2026-08-28 — BATCH-144 (req-141): autoria x derivado no CSS

- Runtime serve TUDO do banco (`gestor.php:2782`); disco só com `DEVELOPMENT_ENV=true`. O compilador
  offline varre `resources/` — por isso o CSS entregue vinha de um HTML que não é o entregue.
- `html`/`css` são AUTORIA; `css_precompiled`/`css_compiled` são DERIVADOS e sempre recalculáveis.
  Preservar derivado como autoria (o que `preserve_on_user_modified` fazia) cria híbrido incoerente.
- Publicação nasce no banco e nunca teve arquivo: o CLI jamais a alcança. Ela herda o CSS do template.
- Medir cobertura = classes do HTML sem regra em nenhuma folha. Filtre `framework_css=tailwindcss`
  (Fomantic vem por CDN) e ignore `group`/`peer` (marcadores sem regra própria).
- `c2f css:audit` e `c2f css:rebuild --url` (compila do HTML RENDERIZADO, sem depender de
  `tailwind_sources`). O Tailwind CLI precisa do input DENTRO da árvore com `node_modules`.
- Escape em heredoc Python: barra-b e barra-s viram BYTES DE CONTROLE na string. Use raw string
  ou chr(92), e confira o arquivo depois — assert que falha no meio deixa funcao chamada e nao definida.

### 2026-08-28 — BATCH-143 (req-140): URL sanitizada x nome físico

- `arquivo_nome_sanitizar()` troca espaço por hífen e colapsa `--`. Qualquer nome que o sistema
  GRAVA precisa satisfazer `sanitizar(n) === n`, senão a URL publicada aponta para outro arquivo.
- O controlador estático não carrega `bibliotecas/arquivo.php`: o bootstrap só inclui `banco`,
  `gestor`, `modelo` e `hooks`. Use `require_once $_GESTOR['bibliotecas-path'].'arquivo.php'`.
- A reescrita do `.htaccess` usa a flag `[B]`, então `caminho-total` chega JÁ decodificado; `%20`
  só sobrevive literal em servidor sem essa flag.
- Para casar URL com nome físico divergente, compare pelo RESULTADO da sanitização de cada entrada
  do diretório. Adivinhar a troca de hífen custa 2^n acessos a disco e ainda perde nome misto.
- POST autenticado exige CSRF mesmo com `ajax=sim` (req-107; a skill de AJAX está desatualizada):
  leia o `<meta name="csrf-token">` da página e mande em `X-CSRF-Token`. O `curl` do Git Bash não
  resolve `/tmp` em `-F @arquivo` — use caminho no formato Windows.

### 2026-08-26 — BATCH-142 (req-139): galleries denso, modal e corte vertical

- Grade compacta exige largura fracionária no card e `flex-wrap` (6×110 px e 10×65 px medidos);
  overlay sem layout shift precisa de `pointer-events:auto` no contêiner ao hover.
- `image_position` exige allowlist idêntica no CRUD, PHP e widget JS.
- Dry-run do atualizador avança checksums sem aplicar linhas: não usar `force-all` sobre tenant.

## Pendências e histórico

- Detalhes integrais vivem nos BATCHes e em `sdd/validation/`; histórico antigo está em `archive/`.
