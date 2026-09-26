---
title: "CSS e Tailwind"
description: "CSS de autoria, CSS derivado e ordem das camadas no runtime."
section: concepts
order: 90
sources:
  - gestor/bibliotecas/gestor.php
  - gestor/gestor.php
  - gestor/controladores/agents/arquitetura/tailwind-recursos.php
verified_at: 3b099ff0
---

# CSS e Tailwind

Layouts, páginas, componentes e templates têm CSS de autoria (`css`) e dois derivados: `css_precompiled`, gerado para o HTML do recurso, e `css_compiled`, gerado pelo editor visual como delta. `css_source_hash` registra a procedência da compilação. O pipeline de [recursos](resources.md) leva a autoria ao banco; `css:rebuild` recompila derivados contra o HTML que está no banco.

`gestor_css_incluir()` insere estilos marcados por papel (`authored`, `compiled` e papéis `data-tailwind-role`), evitando repetição por hash. `gestor_pagina_css()` reúne CSS padrão, de projeto, pré-compilado, do editor e de autoria em ordem definida. Uma dependência escolhida só em runtime, como template por `target`, precisa constar de `tailwind_dependencies` para que as classes sejam compiladas.

> [!IMPORTANT]
> Uma página pode parecer certa no editor e perder classes na publicação se sua dependência dinâmica não entrar na compilação. Inspecione o HTML final e execute `css:audit` quando houver divergência.

Os recursos Tailwind têm CSS pré-compilado por recurso; a folha global isolada não substitui o CSS da página. O editor precisa acumular o baseline das seções inseridas para que o delta conserve as mesmas regras vistas pelo público.
