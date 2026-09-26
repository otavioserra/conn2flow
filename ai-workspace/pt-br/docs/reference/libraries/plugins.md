---
title: "Biblioteca plugins.php"
label: "Plugins (modelo)"
description: "Um arquivo-modelo, sem uso: contém só a função de exemplo template_opcao()."
section: reference
order: 300
sources:
  - gestor/bibliotecas/plugins.php
verified_at: a5ae8605
---

# Biblioteca `plugins.php`

Apesar do nome, **não tem relação com o sistema de plugins**. É um arquivo-modelo de biblioteca: registra `$_GESTOR['biblioteca-template']` e define `template_opcao($params)`, uma função vazia que mostra a convenção de parâmetros nomeados (`foreach($params as $var => $val) $$var = $val;`).

Não está no registro `bibliotecas-dados` e nada a inclui. A instalação e a atualização de plugins ficam em [plugins-installer.php](plugins-installer.md), com as constantes de [plugins-consts.php](plugins-consts.md).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/plugins.php` por `c2f docs:extract` — 1 funções. Não edite dentro deste bloco.

- `template_opcao(array|false $params = false): void` — [linha 29](../../../../../gestor/bibliotecas/plugins.php#L29)
  Gera uma opção de template (função exemplo/template).
  Parâmetros:
  - `$params`: Array de parâmetros nomeados ou false.

<!-- c2f:extract:end -->
