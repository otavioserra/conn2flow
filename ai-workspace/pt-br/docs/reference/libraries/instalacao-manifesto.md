---
title: "Biblioteca instalacao-manifesto.php"
label: "Manifesto de instalação"
description: "Manifesto por camada, precedência projeto > plugin > core, choques, snapshot e rollback (req-198)."
section: reference
order: 360
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
verified_at: eb96c5c7
---

# Biblioteca `instalacao-manifesto.php`

Manifesto de instalação por camada (req-198). O core é "canibalizável": um projeto ou plugin pode sobrepor qualquer arquivo do core e continuar recebendo atualizações. Cada entrega grava o que entregou em `installation/manifests/<camada>.json` (caminho e sha256). A precedência é `projeto` > `plugin:<id>` > `core`.

`instalacao_planejar()` compara o pacote com os manifestos e o disco e separa:

| Grupo | O que acontece |
|---|---|
| `escrever` | arquivo novo ou intacto: é escrito |
| `preservar` | a camada de cima sobrepõe (`sobreposto`) ou houve mudança no servidor (`editado`): a versão nova vai para `backups/overrides/<versão>/` e vira choque |
| `retirar` | a camada deixou de entregar e o arquivo está intacto: sai |
| `retirar_choque` | a camada deixou de entregar, mas o arquivo foi editado: fica e vira choque |
| `originais` | o projeto vai sobrescrever a versão do core: ela é guardada em `installation/originals/` |
| `restaurar` | o projeto deixou de sobrepor: a versão do core guardada volta |

`instalacao_aplicar()` executa o plano. No modo `copiar` (API), ele mesmo escreve os arquivos. No modo `preparar` (atualização do sistema), só tira do pacote o que é preservado, e o atualizador move o staging. A primeira entrega de uma camada escreve tudo e grava a linha de base.

**Choques.** Os choques são gravados como JSON pendente em `installation/choques/`. `instalacao_choques_gravar_pendentes()` leva os pendentes para a tabela `atualizacoes_choques` depois da etapa de banco.

**Snapshot e rollback (BATCH-203).** `instalacao_snapshot_criar()` guarda só o que o plano vai mudar, e `instalacao_snapshot_restaurar()` volta os arquivos e os manifestos.

A biblioteca é **pura** (sem Gestor): roda no atualizador, inclusive a partir do staging, e na API.

## Uso

**Manifestos**
- `instalacao_manifesto_arquivo($base, $camada)` dá o caminho do manifesto da camada.
- `instalacao_manifesto_ler()` lê um manifesto; `instalacao_manifestos()` lê todos.
- `instalacao_manifesto_gravar()` grava de forma atômica.
- `instalacao_nivel($camada)` dá a precedência: `projeto` 2, `plugin:*` 1, `core` 0.

**Mapas de arquivos**
- `instalacao_mapa($raiz, $ignorar)` gera o mapa `caminho → sha256` de uma árvore.
- `instalacao_fora($rel)` diz se o caminho fica fora do manifesto (pastas protegidas e `installation/`).
- `instalacao_rel()` converte para caminho relativo com `/`.
- `instalacao_caminho_base()` normaliza a raiz.
- `instalacao_hash_disco()` dá o hash do arquivo no disco.

**Plano e aplicação**
- `instalacao_planejar()` e `instalacao_aplicar()`, descritos acima.
- `instalacao_diff($antes, $depois)` gera o diff textual guardado no choque; arquivo binário ou grande demais recebe só um aviso.

**Choques**
- `instalacao_choques_registrar()` grava o JSON pendente.
- `instalacao_choques_gravar_pendentes($base, $inserir)` insere cada choque pela função recebida e marca o arquivo como gravado.
- `instalacao_choque_pendente_filtro($linha, $escapar)` monta o filtro SQL de um choque igual ainda pendente; quem grava pula a linha quando ele existe.

**Snapshot**
- `instalacao_snapshot_criar()` e `instalacao_snapshot_restaurar()`, descritos acima.
- `instalacao_snapshot_anotar()` acrescenta dados ao `snapshot.json`, por exemplo o dump do banco.
- `instalacao_snapshot_podar($raiz, $manter)` mantém só os mais recentes.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/instalacao-manifesto.php` por `c2f docs:extract` — 20 funções. Não edite dentro deste bloco.

- `instalacao_caminho_base(string $base): string` — [linha 32](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L32)
- `instalacao_manifesto_arquivo(string $base, string $camada): string` — [linha 34](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L34)
- `instalacao_nivel(string $camada): int` — [linha 40](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L40)
  Nível de precedência de uma camada (maior vence).
- `instalacao_manifesto_ler(string $base, string $camada): ?array` — [linha 47](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L47)
  Retorno: ['camada','versao','gerado_em','arquivos' => [rel => sha256]]
- `instalacao_manifestos(string $base): array` — [linha 55](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L55)
  Todos os manifestos gravados na instalação, por camada.
- `instalacao_manifesto_gravar(string $base, string $camada, array $arquivos, string $versao): bool` — [linha 65](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L65)
- `instalacao_rel(string $raiz, string $caminho): string` — [linha 76](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L76)
  Caminho relativo com `/`.
- `instalacao_fora(string $rel): bool` — [linha 81](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L81)
  O caminho relativo fica fora do manifesto? (pastas protegidas e `installation/`)
- `instalacao_mapa(string $raiz, array $ignorar = []): array` — [linha 91](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L91)
  Mapa `rel → sha256` de uma árvore (pacote no staging ou instalação).
  Parâmetros:
  - `$ignorar`: Caminhos relativos exatos a ignorar (ex.: artefatos do próprio pacote).
- `instalacao_hash_disco(string $base, string $rel): ?string` — [linha 106](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L106)
- `instalacao_planejar(string $base, string $camada, array $pacote, bool $completo, ?array $manifestos = null, ?array $listaCompleta = null): array` — [linha 126](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L126)
  Plano de uma entrega.
  Parâmetros:
  - `$base`: Raiz da instalação.
  - `$camada`: 'core' | 'plugin:<id>' | 'projeto'.
  - `$pacote`: Mapa `rel → sha256` do que chega.
  - `$completo`: O pacote traz TUDO que a camada entrega (senão não há retirada).
  - `$manifestos`: Manifestos atuais (null = lê do disco).
  - `$listaCompleta`: Mapa `rel → sha256` de TUDO que a camada entrega, quando o pacote
  Retorno: ['primeira' => bool, 'escrever' => rel[], 'preservar' => [rel => info], 'retirar' => rel[],
- `instalacao_aplicar(string $base, string $pacoteRaiz, string $camada, array $plano, string $versao, string $modo = 'copiar'): array` — [linha 191](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L191)
  Aplica o plano: guarda originais, escreve, preserva (cópia nova em `backups/overrides/`), retira, restaura e grava o manifesto. Os arquivos a escrever são COPIADOS do pacote (`$modo = 'copiar'`) ou apenas removidos do pacote quando preservados, para quem aplica por conta própria (`'preparar'`, usado pela atualização do sistema, que move o staging depois).
  Retorno: ['escritos' => n, 'preservados' => n, 'retirados' => n, 'restaurados' => n,
- `instalacao_diff(string $antes, string $depois, string $rotulo = ''): ?string` — [linha 237](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L237)
  Diff unificado curto entre dois textos (arquivo no ar × versão nova). Binário ou grande demais: só o aviso. Suficiente para o registro de choques; o merge fica para a req-199.
- `instalacao_choques_registrar(string $base, string $origem, string $camada, string $versao, ?string $execucao, array $choques): ?string` — [linha 264](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L264)
  Guarda os choques de uma entrega num JSON pendente (`installation/choques/*.pendente.json`). A gravação na tabela `atualizacoes_choques` acontece depois da etapa de banco (a tabela pode ainda não existir na etapa de arquivos) por `instalacao_choques_gravar_pendentes()`.
- `instalacao_choque_pendente_filtro(array $linha, callable $escapar): string` — [linha 278](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L278)
  Filtro SQL (sem o `WHERE`) de um choque igual ainda pendente: mesmo caminho, camada, motivo e hashes, sem resolução. Quem grava pula a linha quando ele existe, para a mesma situação não virar uma linha por atualização. `$escapar` escapa um valor para SQL.
- `instalacao_choques_gravar_pendentes(string $base, callable $inserir): int` — [linha 293](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L293)
  Grava os choques pendentes com `$inserir(array $linha): bool` (colunas de `atualizacoes_choques`) e renomeia cada arquivo para `.gravado.json`. Arquivo com falha continua pendente para a próxima vez.
  Retorno: Choques gravados.
- `instalacao_snapshot_criar(string $base, array $plano, string $dir, array $meta = []): array` — [linha 323](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L323)
  Snapshot seletivo antes de aplicar um plano: guarda só o que vai ser sobrescrito, removido ou restaurado (não a instalação inteira), a lista do que é novo (sai no rollback) e os manifestos atuais. Grava `<dir>/snapshot.json`.
  Retorno: ['sobrescritos' => n, 'removidos' => n, 'novos' => n]
- `instalacao_snapshot_anotar(string $dir, array $dados): void` — [linha 351](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L351)
  Acrescenta dados ao `snapshot.json` (ex.: caminho do dump do banco).
- `instalacao_snapshot_restaurar(string $base, string $dir): array` — [linha 364](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L364)
  Volta os arquivos ao estado do snapshot: tira os novos, devolve os sobrescritos e os removidos e restaura os manifestos. O banco é à parte (dump anotado no snapshot).
  Retorno: ['restaurados' => n, 'removidos_novos' => n, 'falhas' => rel[]] ou ['erro' => texto]
- `instalacao_snapshot_podar(string $raiz, int $manter = 5): int` — [linha 386](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L386)
  Mantém só os `$manter` snapshots mais recentes numa pasta.

<!-- c2f:extract:end -->
