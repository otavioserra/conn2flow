---
title: "Módulo admin-layouts"
description: "Edição dos layouts completos das páginas."
section: reference
module: admin-layouts
sources:
  - gestor/modulos/admin-layouts/admin-layouts.php
  - gestor/modulos/admin-layouts/admin-layouts.js
  - gestor/modulos/admin-layouts/admin-layouts.json
  - gestor/modulos/admin-layouts/resources
  - gestor/bibliotecas/gestor.php
  - gestor/gestor.php
  - gestor/db/migrations/20250723165526_create_layouts_table.php
  - gestor/db/migrations/20250813170010_alter_layouts_index_id_language.php
  - gestor/db/migrations/20250827110000_alter_layouts_add_framework_css.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
verified_at: 45812d2e
---

# Módulo `admin-layouts`

Edita a moldura HTML completa que recebe o conteúdo de uma página, inclusive `<head>`, `<body>`, CSS e escolha de framework. O núcleo consulta o layout por id e idioma e funde o corpo da página em `@[[pagina#corpo]]@`.

## Como usar

Abra `admin-layouts/`, crie em `admin-layouts/adicionar/` ou edite em `admin-layouts/editar/?id=<slug>`. Dê um nome, escolha o framework CSS e edite o HTML/CSS no componente `html-editor`. Depois associe o layout à página que deve usá-lo. As rotas são iguais nos dois idiomas.

## Referência técnica

O controlador trata `listar`, `adicionar` e `editar`; status e exclusão vêm da interface compartilhada. O `switch` AJAX não tem caso ativo. Na gravação, os marcadores textuais de variáveis globais são convertidos para a forma interna; o processo é invertido na edição. A edição preserva backup dos campos de conteúdo alterados, registra histórico, incrementa versão, marca `user_modified` e calcula `css_source_hash` a partir de HTML/CSS. O JS do módulo delega o editor à biblioteca `html-editor`. Não há widget, template, hooks ou `hooks.api` definidos no JSON.

`layouts` tem `id_layouts` numérico, `nome`, `id`, `language`, `modulo`, `html` LONGTEXT, `css` MEDIUMTEXT, `status`, `versao`, datas, `user_modified`, `file_version`, `checksum`; migrações acrescentam `framework_css`, `css_compiled`, `css_precompiled`, projeto e hash de procedência. `(id, language)` é único. `gestor_layout()` lê o HTML/CSS do banco para o idioma, inclui CSS pré-compilado com papel `layout-precompiled` antes dos recursos da página e devolve o HTML; em desenvolvimento também pode ler os arquivos físicos correspondentes. `gestor_pagina_layout()` troca `pagina#corpo` pelo conteúdo da página e ajusta o título.

## Limitações confirmadas

> [!CAUTION]
> Renomear o slug do layout não atualiza páginas que guardam o id antigo. A ausência de layout consultado cai em um HTML mínimo de fallback no helper; isso não preserva a estrutura, recursos nem customizações do layout pretendido.

> [!WARNING]
> O CRUD grava `css_compiled` recebido e o hash de procedência, mas não compila CSS por si só. Para Tailwind, o pré-compilado do layout tem papel de cascata próprio; alterar HTML/CSS sem reconstruir recursos pode deixar a página com estilo antigo.

## Veja também

- [Componentes](admin-componentes.md)
- [Páginas](admin-paginas.md)
