---
title: "Módulo admin-componentes"
description: "Edição de componentes HTML/CSS reutilizáveis."
section: reference
module: admin-componentes
sources:
  - gestor/modulos/admin-componentes/admin-componentes.php
  - gestor/modulos/admin-componentes/admin-componentes.js
  - gestor/modulos/admin-componentes/admin-componentes.json
  - gestor/modulos/admin-componentes/resources
  - gestor/bibliotecas/gestor.php
  - gestor/db/migrations/20250723165440_create_componentes_table.php
  - gestor/db/migrations/20250813153000_alter_componentes_index_id_language.php
  - gestor/db/migrations/20250827110020_alter_componentes_add_framework_css.php
  - gestor/db/migrations/20250918120000_add_css_compiled_to_tables.php
  - gestor/db/migrations/20250918130000_add_html_extra_head_to_tables.php
  - gestor/db/migrations/20260814130000_add_css_precompiled_to_resource_tables.php
  - gestor/db/migrations/20260828100000_add_css_source_hash_to_resource_tables.php
verified_at: 45812d2e
---

# Módulo `admin-componentes`

Edita fragmentos reutilizáveis de HTML e CSS, por idioma e, opcionalmente, por módulo. O núcleo os insere por `gestor_componente()` e incorpora seus recursos à página.

## Como usar

Abra `admin-componentes/`, crie em `admin-componentes/adicionar/` ou edite em `admin-componentes/editar/?id=<slug>`. Informe nome, framework CSS e módulo quando aplicável; escreva HTML, CSS e HTML adicional de `<head>` no editor. O editor de código é fornecido pela biblioteca `html-editor`; o JS deste módulo não implementa editor paralelo. As rotas são iguais em pt-br e en.

## Referência técnica

O controlador trata `listar`, `adicionar` e `editar`; status e exclusão usam a interface compartilhada. O `switch` AJAX não tem caso ativo. O HTML e CSS salvos usam marcadores internos de variáveis globais; o controlador converte a forma textual do editor na forma de armazenamento e inverte a conversão ao editar. A edição faz backup dos campos de conteúdo alterados, registra histórico, incrementa versão e marca `user_modified=1`. O código grava `css_source_hash` calculado a partir de HTML/CSS para controlar a procedência do CSS. Não há widget, template, hooks ou `hooks.api` declarados pelo módulo.

`componentes` tem `id_componentes` numérico, `id`, `nome`, `language`, `modulo`, `html`/`css` MEDIUMTEXT, `status`, `versao`, datas, `user_modified`, `file_version` e `checksum`; migrações adicionam `framework_css`, `css_compiled`, `css_precompiled`, `html_extra_head`, projeto e hash de procedência. `(id, language)` é único. `gestor_componente(['id'=>'...'])` consulta por id e idioma, aceita filtro opcional de módulo e retorna HTML; `return_css` devolve HTML e recursos separados. Na saída comum, inclui CSS, pré-compilado, compilado e head na página. No ambiente de desenvolvimento, o helper também pode carregar HTML/CSS de recursos físicos do módulo/raiz quando o registro aponta para eles.

## Limitações confirmadas

> [!CAUTION]
> Mudar o slug de um componente no editor não atualiza chamadas `gestor_componente()` ou marcadores que referenciem o id antigo. `return_css` entrega recursos ao chamador; ele precisa incluí-los no contexto apropriado.

> [!WARNING]
> A inserção de CSS compilado não é uma compilação iniciada pelo CRUD: o controlador grava o conteúdo enviado e seu hash de procedência. A existência de `css_source_hash` não prova que o CSS compilado corresponde ao HTML/CSS atual sem verificação do pipeline.

## Veja também

- [Layouts](admin-layouts.md)
- [Páginas](admin-paginas.md)
