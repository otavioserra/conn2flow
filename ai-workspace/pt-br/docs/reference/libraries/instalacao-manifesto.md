---
title: "Biblioteca instalacao-manifesto.php"
label: "Manifesto de instalação"
description: "Manifesto por camada, precedência projeto > plugin > core, choques, snapshot e rollback (req-198)."
section: reference
order: 360
sources:
  - gestor/bibliotecas/instalacao-manifesto.php
verified_at: 152dfd72
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
- `instalacao_recuperacao_caminho_valido($rel)` recusa caminho oculto, privado, absoluto ou com travessia antes de inventariar e extrair.
- `instalacao_recuperacao_arquivo_seguro($base, $rel, $hash)` confere contenção, destino real e hash antes de incluir um arquivo no ZIP.
- `instalacao_recuperacao_inventario($base, $camadas, $caminhos, $estados)` atribui cada divergência à camada dona e filtra o resultado; ignora links simbólicos e pastas privadas.
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

**Banco e verificação (BATCH-204)**, compartilhados pela atualização do sistema e pelo deploy por API:
- `instalacao_snapshot_dir($base, $id)` dá a pasta do snapshot (id numérico vira `exec-<id>`; caracteres fora de `[A-Za-z0-9_-]` saem);
- `instalacao_banco_dump($dir, $banco)` e `instalacao_banco_restaurar($dump, $banco)` fazem o dump e a restauração com `mysqldump`/`mysql`; `instalacao_banco_processo()` roda o pipe com a senha em `MYSQL_PWD`, e `instalacao_shell_pipefail()` usa o bash com `pipefail` quando existe (o `/bin/sh` do Debian é o dash);
- `instalacao_saude_log_offset($base)` marca a posição do log antes da entrega; `instalacao_saude_verificar()` procura fatal novo e faz a requisição HTTP (DNS, depois `127.0.0.1`, sem conexão é aviso, espera o OPcache); `instalacao_saude_http()` faz uma requisição.

**Resolução de choques (req-199 / BATCH-205).** Um motor para subir e descer, projeto e core:
- `instalacao_choque_acoes($choque)` dá as decisões que valem para o motivo: `editado` → sobrescrever, manter, mesclar; `sobreposto` → manter, mesclar (desfazer a sobreposição é no repositório da camada dona); `retirado-editado` → sobrescrever (aceita a retirada) ou manter;
- `instalacao_choque_versoes($base, $choque)` lê a versão no ar e a nova (cópia em `backups/overrides/`);
- `instalacao_choque_resolver($base, $choque, $acao, $mesclado, $gravarRegras)` aplica no disco. `manter` grava regra em `installation/regras.json` (`instalacao_regras_ler()` / `instalacao_regras_gravar()`): a próxima entrega com o disco igual ao da decisão já nasce resolvida (`manter-regra`). Sobrescrever e mesclar apagam a regra. Na descida, `copia_externa` aponta para a cópia baixada e `gravarRegras=false` não grava regras de deploy no repositório local;
- o planejador não gera choque quando a camada entrega o mesmo conteúdo da entrega anterior: não há nada novo a decidir.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/instalacao-manifesto.php` por `c2f docs:extract` — 36 funções. Não edite dentro deste bloco.

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
- `instalacao_recuperacao_caminho_valido(string $rel): bool` — [linha 87](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L87)
  Caminho seguro para leitura ou extração: sem segmentos especiais, ocultos ou pastas privadas.
- `instalacao_recuperacao_arquivo_seguro(string $base, string $rel, string $hash): ?string` — [linha 96](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L96)
  Confere contenção, destino real e hash antes de colocar um arquivo no ZIP.
- `instalacao_recuperacao_inventario(string $base, array $camadas = [], array $caminhos = [], array $estados = []): array` — [linha 109](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L109)
  Inventário de divergências, atribuído à camada dona de cada caminho.
- `instalacao_mapa(string $raiz, array $ignorar = []): array` — [linha 163](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L163)
  Mapa `rel → sha256` de uma árvore (pacote no staging ou instalação).
  Parâmetros:
  - `$ignorar`: Caminhos relativos exatos a ignorar (ex.: artefatos do próprio pacote).
- `instalacao_hash_disco(string $base, string $rel): ?string` — [linha 178](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L178)
- `instalacao_planejar(string $base, string $camada, array $pacote, bool $completo, ?array $manifestos = null, ?array $listaCompleta = null): array` — [linha 198](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L198)
  Plano de uma entrega.
  Parâmetros:
  - `$base`: Raiz da instalação.
  - `$camada`: 'core' | 'plugin:<id>' | 'projeto'.
  - `$pacote`: Mapa `rel → sha256` do que chega.
  - `$completo`: O pacote traz TUDO que a camada entrega (senão não há retirada).
  - `$manifestos`: Manifestos atuais (null = lê do disco).
  - `$listaCompleta`: Mapa `rel → sha256` de TUDO que a camada entrega, quando o pacote
  Retorno: ['primeira' => bool, 'escrever' => rel[], 'preservar' => [rel => info], 'retirar' => rel[],
- `instalacao_aplicar(string $base, string $pacoteRaiz, string $camada, array $plano, string $versao, string $modo = 'copiar'): array` — [linha 274](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L274)
  Aplica o plano: guarda originais, escreve, preserva (cópia nova em `backups/overrides/`), retira, restaura e grava o manifesto. Os arquivos a escrever são COPIADOS do pacote (`$modo = 'copiar'`) ou apenas removidos do pacote quando preservados, para quem aplica por conta própria (`'preparar'`, usado pela atualização do sistema, que move o staging depois).
  Retorno: ['escritos' => n, 'preservados' => n, 'retirados' => n, 'restaurados' => n,
- `instalacao_diff(string $antes, string $depois, string $rotulo = ''): ?string` — [linha 320](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L320)
  Diff unificado curto entre dois textos (arquivo no ar × versão nova). Binário ou grande demais: só o aviso. Suficiente para o registro de choques; o merge fica para a req-199.
- `instalacao_choques_registrar(string $base, string $origem, string $camada, string $versao, ?string $execucao, array $choques): ?string` — [linha 347](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L347)
  Guarda os choques de uma entrega num JSON pendente (`installation/choques/*.pendente.json`). A gravação na tabela `atualizacoes_choques` acontece depois da etapa de banco (a tabela pode ainda não existir na etapa de arquivos) por `instalacao_choques_gravar_pendentes()`.
- `instalacao_choque_pendente_filtro(array $linha, callable $escapar): string` — [linha 361](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L361)
  Filtro SQL (sem o `WHERE`) de um choque igual ainda pendente: mesmo caminho, camada, motivo e hashes, sem resolução. Quem grava pula a linha quando ele existe, para a mesma situação não virar uma linha por atualização. `$escapar` escapa um valor para SQL.
- `instalacao_choques_gravar_pendentes(string $base, callable $inserir): int` — [linha 376](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L376)
  Grava os choques pendentes com `$inserir(array $linha): bool` (colunas de `atualizacoes_choques`) e renomeia cada arquivo para `.gravado.json`. Arquivo com falha continua pendente para a próxima vez.
  Retorno: Choques gravados.
- `instalacao_snapshot_criar(string $base, array $plano, string $dir, array $meta = []): array` — [linha 407](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L407)
  Snapshot seletivo antes de aplicar um plano: guarda só o que vai ser sobrescrito, removido ou restaurado (não a instalação inteira), a lista do que é novo (sai no rollback) e os manifestos atuais. Grava `<dir>/snapshot.json`.
  Retorno: ['sobrescritos' => n, 'removidos' => n, 'novos' => n]
- `instalacao_snapshot_anotar(string $dir, array $dados): void` — [linha 435](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L435)
  Acrescenta dados ao `snapshot.json` (ex.: caminho do dump do banco).
- `instalacao_snapshot_restaurar(string $base, string $dir): array` — [linha 448](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L448)
  Volta os arquivos ao estado do snapshot: tira os novos, devolve os sobrescritos e os removidos e restaura os manifestos. O banco é à parte (dump anotado no snapshot).
  Retorno: ['restaurados' => n, 'removidos_novos' => n, 'falhas' => rel[]] ou ['erro' => texto]
- `instalacao_snapshot_podar(string $raiz, int $manter = 5): int` — [linha 470](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L470)
  Mantém só os `$manter` snapshots mais recentes numa pasta.
- `instalacao_snapshot_dir(string $base, string $id): ?string` — [linha 489](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L489)
  Pasta do snapshot de uma execução. Id só com dígitos vira `exec-<id>` (atualização do sistema); os outros (ex.: `api-20260930-120000-ab12`) valem como estão. Caracteres fora de `[A-Za-z0-9_-]` saem.
- `instalacao_shell_pipefail(string $cmd): array` — [linha 501](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L501)
  Comando de shell com `pipefail` (a falha do `mysqldump` não some atrás do `gzip`). O `/bin/sh` do Debian e do Ubuntu é o dash, que não tem `pipefail` e sai com código 2 no `set -o pipefail`; por isso usa o bash quando existe e, sem ele, o `sh` sem `pipefail` (o tamanho do arquivo ainda é conferido).
- `instalacao_banco_processo(string $cmd, array $banco): array` — [linha 507](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L507)
  Roda um pipe de shell com a senha do banco em `MYSQL_PWD`. @return array ['codigo' => int, 'erro' => string]
- `instalacao_banco_dump(string $dir, array $banco): array` — [linha 524](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L524)
  Dump do banco (`mysqldump --single-transaction`, gzip) em `<dir>/banco.sql.gz`, anotado no `snapshot.json`. `$banco`: host, usuario, senha, nome (o `$_BANCO` do Gestor).
  Retorno: ['ok' => bool, 'arquivo' => string|null, 'mb' => float, 'erro' => string]
- `instalacao_banco_restaurar(string $dump, array $banco): array` — [linha 539](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L539)
  Restaura `banco.sql.gz` no banco. @return array ['ok' => bool, 'erro' => string]
- `instalacao_saude_log_offset(string $base): int` — [linha 549](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L549)
  Tamanho atual do log de erros do PHP (para achar fatais novos depois).
- `instalacao_saude_verificar(string $base, int $offsetLog, string $dominio, array $opcoes = []): array` — [linha 569](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L569)
  Verificação depois de uma entrega: sem erro fatal novo no log do PHP e a raiz do site respondendo abaixo de 500.
  Retorno: ['ok' => bool, 'motivos' => string[], 'http' => int|null, 'avisos' => string[]]
- `instalacao_saude_http(string $url, ?string $ip): int` — [linha 604](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L604)
  Código HTTP de `$url` (0 sem conexão). Com `$ip`, o host da URL é resolvido para ele.
- `instalacao_regras_ler(string $base): array` — [linha 625](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L625)
  Regras que as resoluções deixam (`installation/regras.json`): `rel → [acao, camada, motivo, hash_disco, hash_novo, em]`.
- `instalacao_regras_gravar(string $base, array $regras): bool` — [linha 632](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L632)
  Grava as regras de forma atômica.
- `instalacao_choque_acoes(array $choque): array` — [linha 649](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L649)
  Decisões possíveis para um choque, pelo motivo: - `editado` (mudança no servidor): sobrescrever com a versão nova, manter a do servidor ou mesclar; - `sobreposto` (a camada de cima é a dona): manter ou mesclar. Sobrescrever não vale aqui: a próxima entrega da camada dona escreveria de novo; o lugar de desfazer a sobreposição é o repositório dela; - `retirado-editado`: sobrescrever (aceita a retirada, o arquivo sai) ou manter; - `original-ausente`: manter.
- `instalacao_choque_versoes(string $base, array $choque): array` — [linha 663](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L663)
  As duas versões de um choque: a que está no ar (disco) e a nova (cópia guardada em `backups/overrides/`).
  Retorno: ['no_ar' => string|null, 'nova' => string|null, 'binario' => bool]
- `instalacao_choque_resolver(string $base, array $choque, string $acao, ?string $mesclado = null, bool $gravarRegras = true): array` — [linha 685](../../../../../gestor/bibliotecas/instalacao-manifesto.php#L685)
  Aplica a decisão sobre um choque no disco e guarda a regra que vale nas próximas entregas. - `sobrescrever`: a versão nova (cópia) vai para o lugar; numa retirada, o arquivo sai; - `manter`: nada muda no disco; a regra faz a próxima entrega do mesmo conteúdo já nascer resolvida; - `mesclar`: `$mesclado` vai para o lugar (a próxima entrega com versão nova volta a pedir decisão).
  Parâmetros:
  - `$choque`: Linha do registro: caminho, camada, motivo, copia, hash_novo.
  Retorno: ['ok' => bool, 'erro' => string, 'acao' => string, 'hash_disco' => string|null]

<!-- c2f:extract:end -->
