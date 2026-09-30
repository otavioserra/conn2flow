---
title: "instalacao-manifesto.php library"
label: "Installation manifest"
description: "Per-layer manifest, project > plugin > core precedence, clashes, snapshot and rollback (req-198)."
section: reference
order: 360
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
verified_at: 100adc3a
---

# `instalacao-manifesto.php` library

Per-layer installation manifest (req-198). The core can be "cannibalized": a project or plugin may override any core file and still receive updates. Every delivery records what it delivered in `installation/manifests/<layer>.json` (path and sha256). Precedence is `projeto` > `plugin:<id>` > `core`.

`instalacao_planejar()` compares the package with the manifests and the disk and splits:

| Group | What happens |
|---|---|
| `escrever` | new or intact file: it is written |
| `preservar` | an upper layer overrides it (`sobreposto`) or it changed on the server (`editado`): the new version goes to `backups/overrides/<version>/` and becomes a clash |
| `retirar` | the layer stopped delivering it and the file is intact: removed |
| `retirar_choque` | the layer stopped delivering it but the file was edited: kept, recorded as a clash |
| `originais` | the project is about to overwrite the core version: it is kept in `installation/originals/` |
| `restaurar` | the project stopped overriding: the kept core version comes back |

`instalacao_aplicar()` runs the plan. In `copiar` mode (API) it writes the files itself. In `preparar` mode (system update) it only removes the preserved files from the package, and the updater moves the staging. The first delivery of a layer writes everything and records the baseline.

**Clashes.** Clashes are stored as pending JSON in `installation/choques/`. `instalacao_choques_gravar_pendentes()` moves pending clashes into the `atualizacoes_choques` table after the database stage.

**Snapshot and rollback (BATCH-203).** `instalacao_snapshot_criar()` keeps only what the plan will change, and `instalacao_snapshot_restaurar()` brings files and manifests back.

The library is **pure** (no Gestor): it runs in the updater, also from staging, and in the API.

## Usage

**Manifests**
- `instalacao_manifesto_arquivo($base, $camada)` gives the path of the layer manifest.
- `instalacao_manifesto_ler()` reads one manifest; `instalacao_manifestos()` reads all of them.
- `instalacao_manifesto_gravar()` writes atomically.
- `instalacao_nivel($camada)` gives the precedence: `projeto` 2, `plugin:*` 1, `core` 0.

**File maps**
- `instalacao_mapa($raiz, $ignorar)` builds the `path → sha256` map of a tree.
- `instalacao_fora($rel)` tells whether a path stays out of the manifest (protected folders and `installation/`).
- `instalacao_rel()` converts to a relative path with `/`.
- `instalacao_caminho_base()` normalizes the root.
- `instalacao_hash_disco()` gives the hash of the file on disk.

**Plan and apply**
- `instalacao_planejar()` and `instalacao_aplicar()`, described above.
- `instalacao_diff($antes, $depois)` builds the text diff kept with the clash; a binary or oversized file gets only a notice.

**Clashes**
- `instalacao_choques_registrar()` writes the pending JSON.
- `instalacao_choques_gravar_pendentes($base, $inserir)` inserts each clash through the given function and marks the file as recorded.
- `instalacao_choque_pendente_filtro($linha, $escapar)` builds the SQL filter of an identical clash still pending; the writer skips the row when it exists.

**Snapshot**
- `instalacao_snapshot_criar()` and `instalacao_snapshot_restaurar()`, described above.
- `instalacao_snapshot_anotar()` adds data to `snapshot.json`, for example the database dump.
- `instalacao_snapshot_podar($raiz, $manter)` keeps only the most recent ones.

**Database and check (BATCH-204)**, shared by the system update and the API deploy:
- `instalacao_snapshot_dir($base, $id)` gives the snapshot folder (a numeric id becomes `exec-<id>`; characters outside `[A-Za-z0-9_-]` are dropped);
- `instalacao_banco_dump($dir, $banco)` and `instalacao_banco_restaurar($dump, $banco)` dump and restore with `mysqldump`/`mysql`; `instalacao_banco_processo()` runs the pipe with the password in `MYSQL_PWD`, and `instalacao_shell_pipefail()` uses bash with `pipefail` when available (Debian's `/bin/sh` is dash);
- `instalacao_saude_log_offset($base)` marks the log position before the delivery; `instalacao_saude_verificar()` looks for a new fatal and makes the HTTP request (DNS, then `127.0.0.1`, no connection is a warning, waits for OPcache); `instalacao_saude_http()` makes one request.

**Clash resolution (req-199 / BATCH-205).** One engine for up and down, project and core:
- `instalacao_choque_acoes($choque)` gives the decisions valid for the reason: `editado` → overwrite, keep, merge; `sobreposto` → keep, merge (undoing the override belongs in the owner layer repository); `retirado-editado` → overwrite (accepts the removal) or keep;
- `instalacao_choque_versoes($base, $choque)` reads the live version and the new one (copy in `backups/overrides/`);
- `instalacao_choque_resolver($base, $choque, $acao, $mesclado)` applies it on disk. `manter` records a rule in `installation/regras.json` (`instalacao_regras_ler()` / `instalacao_regras_gravar()`): the next delivery with the disk as it was at the decision is born resolved (`manter-regra`). Overwrite and merge remove the rule;
- the planner creates no clash when the layer delivers the same content as the previous delivery: there is nothing new to decide.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/instalacao-manifesto.php` by `c2f docs:extract` — 33 functions. Do not edit inside this block.

- `instalacao_caminho_base(string $base): string` — [line 32](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L32)
- `instalacao_manifesto_arquivo(string $base, string $camada): string` — [line 34](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L34)
- `instalacao_nivel(string $camada): int` — [line 40](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L40)
  Nível de precedência de uma camada (maior vence).
- `instalacao_manifesto_ler(string $base, string $camada): ?array` — [line 47](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L47)
  Returns: ['camada','versao','gerado_em','arquivos' => [rel => sha256]]
- `instalacao_manifestos(string $base): array` — [line 55](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L55)
  Todos os manifestos gravados na instalação, por camada.
- `instalacao_manifesto_gravar(string $base, string $camada, array $arquivos, string $versao): bool` — [line 65](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L65)
- `instalacao_rel(string $raiz, string $caminho): string` — [line 76](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L76)
  Caminho relativo com `/`.
- `instalacao_fora(string $rel): bool` — [line 81](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L81)
  O caminho relativo fica fora do manifesto? (pastas protegidas e `installation/`)
- `instalacao_mapa(string $raiz, array $ignorar = []): array` — [line 91](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L91)
  Mapa `rel → sha256` de uma árvore (pacote no staging ou instalação).
  Parameters:
  - `$ignorar`: Caminhos relativos exatos a ignorar (ex.: artefatos do próprio pacote).
- `instalacao_hash_disco(string $base, string $rel): ?string` — [line 106](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L106)
- `instalacao_planejar(string $base, string $camada, array $pacote, bool $completo, ?array $manifestos = null, ?array $listaCompleta = null): array` — [line 126](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L126)
  Plano de uma entrega.
  Parameters:
  - `$base`: Raiz da instalação.
  - `$camada`: 'core' | 'plugin:<id>' | 'projeto'.
  - `$pacote`: Mapa `rel → sha256` do que chega.
  - `$completo`: O pacote traz TUDO que a camada entrega (senão não há retirada).
  - `$manifestos`: Manifestos atuais (null = lê do disco).
  - `$listaCompleta`: Mapa `rel → sha256` de TUDO que a camada entrega, quando o pacote
  Returns: ['primeira' => bool, 'escrever' => rel[], 'preservar' => [rel => info], 'retirar' => rel[],
- `instalacao_aplicar(string $base, string $pacoteRaiz, string $camada, array $plano, string $versao, string $modo = 'copiar'): array` — [line 202](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L202)
  Aplica o plano: guarda originais, escreve, preserva (cópia nova em `backups/overrides/`), retira, restaura e grava o manifesto. Os arquivos a escrever são COPIADOS do pacote (`$modo = 'copiar'`) ou apenas removidos do pacote quando preservados, para quem aplica por conta própria (`'preparar'`, usado pela atualização do sistema, que move o staging depois).
  Returns: ['escritos' => n, 'preservados' => n, 'retirados' => n, 'restaurados' => n,
- `instalacao_diff(string $antes, string $depois, string $rotulo = ''): ?string` — [line 248](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L248)
  Diff unificado curto entre dois textos (arquivo no ar × versão nova). Binário ou grande demais: só o aviso. Suficiente para o registro de choques; o merge fica para a req-199.
- `instalacao_choques_registrar(string $base, string $origem, string $camada, string $versao, ?string $execucao, array $choques): ?string` — [line 275](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L275)
  Guarda os choques de uma entrega num JSON pendente (`installation/choques/*.pendente.json`). A gravação na tabela `atualizacoes_choques` acontece depois da etapa de banco (a tabela pode ainda não existir na etapa de arquivos) por `instalacao_choques_gravar_pendentes()`.
- `instalacao_choque_pendente_filtro(array $linha, callable $escapar): string` — [line 289](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L289)
  Filtro SQL (sem o `WHERE`) de um choque igual ainda pendente: mesmo caminho, camada, motivo e hashes, sem resolução. Quem grava pula a linha quando ele existe, para a mesma situação não virar uma linha por atualização. `$escapar` escapa um valor para SQL.
- `instalacao_choques_gravar_pendentes(string $base, callable $inserir): int` — [line 304](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L304)
  Grava os choques pendentes com `$inserir(array $linha): bool` (colunas de `atualizacoes_choques`) e renomeia cada arquivo para `.gravado.json`. Arquivo com falha continua pendente para a próxima vez.
  Returns: Choques gravados.
- `instalacao_snapshot_criar(string $base, array $plano, string $dir, array $meta = []): array` — [line 335](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L335)
  Snapshot seletivo antes de aplicar um plano: guarda só o que vai ser sobrescrito, removido ou restaurado (não a instalação inteira), a lista do que é novo (sai no rollback) e os manifestos atuais. Grava `<dir>/snapshot.json`.
  Returns: ['sobrescritos' => n, 'removidos' => n, 'novos' => n]
- `instalacao_snapshot_anotar(string $dir, array $dados): void` — [line 363](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L363)
  Acrescenta dados ao `snapshot.json` (ex.: caminho do dump do banco).
- `instalacao_snapshot_restaurar(string $base, string $dir): array` — [line 376](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L376)
  Volta os arquivos ao estado do snapshot: tira os novos, devolve os sobrescritos e os removidos e restaura os manifestos. O banco é à parte (dump anotado no snapshot).
  Returns: ['restaurados' => n, 'removidos_novos' => n, 'falhas' => rel[]] ou ['erro' => texto]
- `instalacao_snapshot_podar(string $raiz, int $manter = 5): int` — [line 398](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L398)
  Mantém só os `$manter` snapshots mais recentes numa pasta.
- `instalacao_snapshot_dir(string $base, string $id): ?string` — [line 417](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L417)
  Pasta do snapshot de uma execução. Id só com dígitos vira `exec-<id>` (atualização do sistema); os outros (ex.: `api-20260930-120000-ab12`) valem como estão. Caracteres fora de `[A-Za-z0-9_-]` saem.
- `instalacao_shell_pipefail(string $cmd): array` — [line 429](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L429)
  Comando de shell com `pipefail` (a falha do `mysqldump` não some atrás do `gzip`). O `/bin/sh` do Debian e do Ubuntu é o dash, que não tem `pipefail` e sai com código 2 no `set -o pipefail`; por isso usa o bash quando existe e, sem ele, o `sh` sem `pipefail` (o tamanho do arquivo ainda é conferido).
- `instalacao_banco_processo(string $cmd, array $banco): array` — [line 435](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L435)
  Roda um pipe de shell com a senha do banco em `MYSQL_PWD`. @return array ['codigo' => int, 'erro' => string]
- `instalacao_banco_dump(string $dir, array $banco): array` — [line 452](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L452)
  Dump do banco (`mysqldump --single-transaction`, gzip) em `<dir>/banco.sql.gz`, anotado no `snapshot.json`. `$banco`: host, usuario, senha, nome (o `$_BANCO` do Gestor).
  Returns: ['ok' => bool, 'arquivo' => string|null, 'mb' => float, 'erro' => string]
- `instalacao_banco_restaurar(string $dump, array $banco): array` — [line 467](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L467)
  Restaura `banco.sql.gz` no banco. @return array ['ok' => bool, 'erro' => string]
- `instalacao_saude_log_offset(string $base): int` — [line 477](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L477)
  Tamanho atual do log de erros do PHP (para achar fatais novos depois).
- `instalacao_saude_verificar(string $base, int $offsetLog, string $dominio, array $opcoes = []): array` — [line 497](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L497)
  Verificação depois de uma entrega: sem erro fatal novo no log do PHP e a raiz do site respondendo abaixo de 500.
  Returns: ['ok' => bool, 'motivos' => string[], 'http' => int|null, 'avisos' => string[]]
- `instalacao_saude_http(string $url, ?string $ip): int` — [line 532](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L532)
  Código HTTP de `$url` (0 sem conexão). Com `$ip`, o host da URL é resolvido para ele.
- `instalacao_regras_ler(string $base): array` — [line 553](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L553)
  Regras que as resoluções deixam (`installation/regras.json`): `rel → [acao, camada, motivo, hash_disco, hash_novo, em]`.
- `instalacao_regras_gravar(string $base, array $regras): bool` — [line 560](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L560)
  Grava as regras de forma atômica.
- `instalacao_choque_acoes(array $choque): array` — [line 577](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L577)
  Decisões possíveis para um choque, pelo motivo: - `editado` (mudança no servidor): sobrescrever com a versão nova, manter a do servidor ou mesclar; - `sobreposto` (a camada de cima é a dona): manter ou mesclar. Sobrescrever não vale aqui: a próxima entrega da camada dona escreveria de novo; o lugar de desfazer a sobreposição é o repositório dela; - `retirado-editado`: sobrescrever (aceita a retirada, o arquivo sai) ou manter; - `original-ausente`: manter.
- `instalacao_choque_versoes(string $base, array $choque): array` — [line 591](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L591)
  As duas versões de um choque: a que está no ar (disco) e a nova (cópia guardada em `backups/overrides/`).
  Returns: ['no_ar' => string|null, 'nova' => string|null, 'binario' => bool]
- `instalacao_choque_resolver(string $base, array $choque, string $acao, ?string $mesclado = null): array` — [line 613](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L613)
  Aplica a decisão sobre um choque no disco e guarda a regra que vale nas próximas entregas. - `sobrescrever`: a versão nova (cópia) vai para o lugar; numa retirada, o arquivo sai; - `manter`: nada muda no disco; a regra faz a próxima entrega do mesmo conteúdo já nascer resolvida; - `mesclar`: `$mesclado` vai para o lugar (a próxima entrega com versão nova volta a pedir decisão).
  Parameters:
  - `$choque`: Linha do registro: caminho, camada, motivo, copia, hash_novo.
  Returns: ['ok' => bool, 'erro' => string, 'acao' => string, 'hash_disco' => string|null]

<!-- c2f:extract:end -->
