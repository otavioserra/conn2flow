---
title: "atualizacoes-choques.php library"
label: "Delivery clashes"
description: "Clash records in the database: list, detail with both versions and resolution (req-199)."
section: reference
order: 365
sources:
  - gestor/bibliotecas/atualizacoes-choques.php
verified_at: b371b53d
---

# `atualizacoes-choques.php` library

Connects the pure engine of [`instalacao-manifesto.php`](instalacao-manifesto.md) to the `atualizacoes_choques` table (req-199 / BATCH-205). The panel (`admin-atualizacoes`) and the API (`/_api/project/conflicts` and `/_api/project/resolve`) use the same functions, so a decision is the same whether it comes from the panel, the CLI or the VS Code extension.

## Usage

- `atualizacoes_choques_disponivel()` tells whether the table exists (the migration may not have run).
- `atualizacoes_choques_listar($pendentes, $limite)` lists clashes, most recent first, without the diff, with the possible decisions in `acoes`.
- `atualizacoes_choques_obter($id)` returns one row with the diff.
- `atualizacoes_choques_detalhe($base, $id)` joins the row and both versions (live and new); a binary file comes in base64.
- `atualizacoes_choques_resolver($base, $id, $acao, $mesclado, $quem)` applies the decision through the engine and records `resolucao`, `resolvido_em` and `resolvido_por` on the row and on the other pending rows of the same file and layer (earlier versions of the same clash). An already resolved clash is refused.
- `atualizacoes_choques_resolver_registro($choque, $acao)` resolves a **record** clash (`db:<table>?<key>`, from declarative data removal, BATCH-207): "overwrite" accepts the removal (`status='D'` or deleted) and "keep" leaves the record.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/atualizacoes-choques.php` by `c2f docs:extract` — 6 functions. Do not edit inside this block.

- `atualizacoes_choques_disponivel(): bool` — [line 13](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L13)
  A tabela existe (a migração pode não ter rodado ainda).
- `atualizacoes_choques_listar(bool $pendentes = true, int $limite = 100): array` — [line 26](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L26)
  Choques do registro, mais recentes primeiro.
  Parameters:
  - `$pendentes`: Só os sem resolução.
  Returns: Linhas sem o diff (id, data_criacao, origem, camada, versao, caminho, tipo, motivo,
- `atualizacoes_choques_obter(int $id): ?array` — [line 41](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L41)
  Uma linha do registro (com o diff), ou null.
- `atualizacoes_choques_detalhe(string $base, int $id): ?array` — [line 56](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L56)
  Detalhe para quem decide: a linha, as duas versões (no ar e nova) e o diff. Binário vai em base64.
  Returns: ['choque' => linha, 'no_ar' => string|null, 'nova' => string|null, 'binario' => bool, 'codificacao' => 'texto'|'base64']
- `atualizacoes_choques_resolver_registro(array $c, string $acao): array` — [line 72](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L72)
  req-199 / BATCH-207: choque de **registro** (`db:<tabela>?<chave>`), de uma retirada declarativa em que o registro foi editado online. `sobrescrever` aceita a retirada (`status='D'` quando a tabela tem status, senão sai); `manter` deixa o registro (o manifesto do dono já não o lista, então não volta a chocar).
  Returns: ['ok' => bool, 'erro' => string, 'acao' => string]
- `atualizacoes_choques_resolver(string $base, int $id, string $acao, ?string $mesclado, string $quem): array` — [line 102](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L102)
  Aplica a decisão (motor) e registra: a linha e as outras pendentes do mesmo arquivo e camada (versões anteriores do mesmo choque) recebem a resolução, a data e quem decidiu.
  Returns: ['ok' => bool, 'erro' => string, 'acao' => string, 'resolvidos' => int]

<!-- c2f:extract:end -->
