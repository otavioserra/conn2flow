---
title: "Biblioteca geral.php"
description: "Uma única função, geral_nl2br(), sem chamadores no core."
section: reference
order: 220
sources:
  - gestor/bibliotecas/geral.php
verified_at: c267f123
---

# Biblioteca `geral.php`

Contém só `geral_nl2br($string = '')`: devolve `nl2br($string)` quando o valor não é vazio (segundo `existe()`) e o próprio valor caso contrário. **Não escapa HTML**: para texto vindo do usuário, use `nl2br(htmlspecialchars($texto))`.

Nenhum módulo do core a usa. Existe por compatibilidade; em código novo, chame `nl2br()` direto.

Registrada como `geral` em `bibliotecas-dados` (`gestor_incluir_biblioteca('geral')`).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/geral.php` por `c2f docs:extract` — 1 funções. Não edite dentro deste bloco.

- `geral_nl2br(string $string = ''): string` — [linha 22](../../../../../gestor/bibliotecas/geral.php#L22)

<!-- c2f:extract:end -->
