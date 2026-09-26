---
title: "Interface administrativa"
description: "Menus, CRUD, componentes Tailwind e visualização em iframe."
section: concepts
order: 100
sources:
  - gestor/gestor.php
  - gestor/bibliotecas/interface.php
  - gestor/resources/pt-br/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html
  - gestor/modulos/admin-layouts/resources/pt-br/components/modal-layout/modal-layout.html
verified_at: 3b099ff0
---

# Interface administrativa

O Gestor monta o menu a partir dos módulos e grupos disponíveis para o usuário. `gestor_pagina_menu()` escolhe o componente `menu-principal-sistema-tailwind` quando a tela usa Tailwind, ou o menu clássico nos demais casos. O layout administrativo Tailwind fornece a estrutura da página; `interface.php` monta formulários, listagens, histórico e alertas para módulos que usam o CRUD padrão.

As telas de edição de layouts e componentes usam modais com iframe de pré-visualização. O iframe recebe um documento separado para mostrar o recurso com sua própria cascata de CSS. O conteúdo visto ali depende do HTML, CSS e variáveis resolvidos pelo fluxo de preview; confirme o resultado publicado após salvar. Um modal de alerta pode aparecer também em telas públicas, por isso seus estilos Tailwind são dependências do sistema.

A lista administrativa usa DataTables com resposta AJAX do módulo. Os controles de status e exclusão são montados pela interface. Veja a [referência da biblioteca interface](../reference/libraries/interface.md) para seus contratos e limitações de segurança atuais.

> [!NOTE]
> Uma tela Tailwind e uma tela clássica podem conviver na mesma instalação; o componente de menu segue o tipo da página renderizada.
