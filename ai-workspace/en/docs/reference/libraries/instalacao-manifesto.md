---
title: "instalacao-manifesto.php library"
label: "Installation manifest"
description: "Per-layer manifest, project > plugin > core precedence, clashes, snapshot and rollback (req-198)."
section: reference
order: 360
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
verified_at: eb96c5c7
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

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/instalacao-manifesto.php` by `c2f docs:extract` — 20 functions. Do not edit inside this block.

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
- `instalacao_aplicar(string $base, string $pacoteRaiz, string $camada, array $plano, string $versao, string $modo = 'copiar'): array` — [line 191](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L191)
  Aplica o plano: guarda originais, escreve, preserva (cópia nova em `backups/overrides/`), retira, restaura e grava o manifesto. Os arquivos a escrever são COPIADOS do pacote (`$modo = 'copiar'`) ou apenas removidos do pacote quando preservados, para quem aplica por conta própria (`'preparar'`, usado pela atualização do sistema, que move o staging depois).
  Returns: ['escritos' => n, 'preservados' => n, 'retirados' => n, 'restaurados' => n,
- `instalacao_diff(string $antes, string $depois, string $rotulo = ''): ?string` — [line 237](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L237)
  Diff unificado curto entre dois textos (arquivo no ar × versão nova). Binário ou grande demais: só o aviso. Suficiente para o registro de choques; o merge fica para a req-199.
- `instalacao_choques_registrar(string $base, string $origem, string $camada, string $versao, ?string $execucao, array $choques): ?string` — [line 264](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L264)
  Guarda os choques de uma entrega num JSON pendente (`installation/choques/*.pendente.json`). A gravação na tabela `atualizacoes_choques` acontece depois da etapa de banco (a tabela pode ainda não existir na etapa de arquivos) por `instalacao_choques_gravar_pendentes()`.
- `instalacao_choque_pendente_filtro(array $linha, callable $escapar): string` — [line 278](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L278)
  Filtro SQL (sem o `WHERE`) de um choque igual ainda pendente: mesmo caminho, camada, motivo e hashes, sem resolução. Quem grava pula a linha quando ele existe, para a mesma situação não virar uma linha por atualização. `$escapar` escapa um valor para SQL.
- `instalacao_choques_gravar_pendentes(string $base, callable $inserir): int` — [line 293](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L293)
  Grava os choques pendentes com `$inserir(array $linha): bool` (colunas de `atualizacoes_choques`) e renomeia cada arquivo para `.gravado.json`. Arquivo com falha continua pendente para a próxima vez.
  Returns: Choques gravados.
- `instalacao_snapshot_criar(string $base, array $plano, string $dir, array $meta = []): array` — [line 323](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L323)
  Snapshot seletivo antes de aplicar um plano: guarda só o que vai ser sobrescrito, removido ou restaurado (não a instalação inteira), a lista do que é novo (sai no rollback) e os manifestos atuais. Grava `<dir>/snapshot.json`.
  Returns: ['sobrescritos' => n, 'removidos' => n, 'novos' => n]
- `instalacao_snapshot_anotar(string $dir, array $dados): void` — [line 351](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L351)
  Acrescenta dados ao `snapshot.json` (ex.: caminho do dump do banco).
- `instalacao_snapshot_restaurar(string $base, string $dir): array` — [line 364](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L364)
  Volta os arquivos ao estado do snapshot: tira os novos, devolve os sobrescritos e os removidos e restaura os manifestos. O banco é à parte (dump anotado no snapshot).
  Returns: ['restaurados' => n, 'removidos_novos' => n, 'falhas' => rel[]] ou ['erro' => texto]
- `instalacao_snapshot_podar(string $raiz, int $manter = 5): int` — [line 386](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L386)
  Mantém só os `$manter` snapshots mais recentes numa pasta.

<!-- c2f:extract:end -->
