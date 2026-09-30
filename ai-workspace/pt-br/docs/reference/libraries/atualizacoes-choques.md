---
title: "Biblioteca atualizacoes-choques.php"
label: "Choques das entregas"
description: "Registro de choques no banco: lista, detalhe com as duas versões e resolução (req-199)."
section: reference
order: 365
sources:
  - gestor/bibliotecas/atualizacoes-choques.php
verified_at: b371b53d
---

# Biblioteca `atualizacoes-choques.php`

Liga o motor puro de [`instalacao-manifesto.php`](instalacao-manifesto.md) à tabela `atualizacoes_choques` (req-199 / BATCH-205). O painel (`admin-atualizacoes`) e a API (`/_api/project/conflicts` e `/_api/project/resolve`) usam as mesmas funções, então a decisão é a mesma venha do painel, do CLI ou da extensão do VS Code.

## Uso

- `atualizacoes_choques_disponivel()` diz se a tabela existe (a migração pode não ter rodado).
- `atualizacoes_choques_listar($pendentes, $limite)` lista os choques, mais recentes primeiro, sem o diff, com as decisões possíveis em `acoes`.
- `atualizacoes_choques_obter($id)` devolve uma linha com o diff.
- `atualizacoes_choques_detalhe($base, $id)` junta a linha e as duas versões (no ar e nova); arquivo binário vai em base64.
- `atualizacoes_choques_resolver($base, $id, $acao, $mesclado, $quem)` aplica a decisão pelo motor e registra `resolucao`, `resolvido_em` e `resolvido_por` na linha e nas outras pendentes do mesmo arquivo e camada (versões anteriores do mesmo choque). Choque já resolvido é recusado.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/atualizacoes-choques.php` por `c2f docs:extract` — 5 funções. Não edite dentro deste bloco.

- `atualizacoes_choques_disponivel(): bool` — [linha 13](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L13)
  A tabela existe (a migração pode não ter rodado ainda).
- `atualizacoes_choques_listar(bool $pendentes = true, int $limite = 100): array` — [linha 26](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L26)
  Choques do registro, mais recentes primeiro.
  Parâmetros:
  - `$pendentes`: Só os sem resolução.
  Retorno: Linhas sem o diff (id, data_criacao, origem, camada, versao, caminho, tipo, motivo,
- `atualizacoes_choques_obter(int $id): ?array` — [linha 41](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L41)
  Uma linha do registro (com o diff), ou null.
- `atualizacoes_choques_detalhe(string $base, int $id): ?array` — [linha 56](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L56)
  Detalhe para quem decide: a linha, as duas versões (no ar e nova) e o diff. Binário vai em base64.
  Retorno: ['choque' => linha, 'no_ar' => string|null, 'nova' => string|null, 'binario' => bool, 'codificacao' => 'texto'|'base64']
- `atualizacoes_choques_resolver(string $base, int $id, string $acao, ?string $mesclado, string $quem): array` — [linha 71](../../../../../gestor/bibliotecas/atualizacoes-choques.php#L71)
  Aplica a decisão (motor) e registra: a linha e as outras pendentes do mesmo arquivo e camada (versões anteriores do mesmo choque) recebem a resolução, a data e quem decidiu.
  Retorno: ['ok' => bool, 'erro' => string, 'acao' => string, 'resolvidos' => int]

<!-- c2f:extract:end -->
