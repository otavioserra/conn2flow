---
title: "Módulo admin-categorias"
description: "Categorias hierárquicas associadas a módulos e arquivos."
section: reference
module: admin-categorias
sources:
  - gestor/modulos/admin-categorias/admin-categorias.php
  - gestor/modulos/admin-categorias/admin-categorias.js
  - gestor/modulos/admin-categorias/admin-categorias.json
  - gestor/modulos/admin-categorias/resources
  - gestor/db/migrations/20250723165439_create_categorias_table.php
  - gestor/db/migrations/20260717120000_create_arquivos_disco_categorias_table.php
verified_at: 45812d2e
---

# Módulo `admin-categorias`

Organiza categorias em árvore, associadas a um módulo e, no gerenciador de arquivos atual, a caminhos físicos de arquivo.

## Como usar

Abra `admin-categorias/`, crie uma categoria raiz em `admin-categorias/adicionar/` escolhendo o módulo, ou use `admin-categorias/adicionar-filho/?id=<slug>` sobre uma categoria existente. Edite nome, módulo ou plugin em `admin-categorias/editar/?id=<slug>`. A tela de edição mostra a trilha de ancestrais. As rotas são iguais nos dois idiomas; os registros de categoria não têm coluna de idioma.

## Referência técnica

O controlador trata `listar`, `adicionar`, `adicionar-filho` e `editar`; status e exclusão usam a interface compartilhada. Não há caso AJAX próprio ativo, widget, template, hooks ou `hooks.api` no JSON. `adicionar-filho` procura o pai não excluído pelo slug, herda seu `id_modulos` e grava seu `id_categorias` como `id_categorias_pai`. A árvore de ancestrais é montada recursivamente para o breadcrumb da edição. O JS do módulo não acrescenta fluxo de dados próprio.

`categorias` contém `id_categorias` numérico, `id_usuarios`, `id_modulos`, `id_categorias_pai`, `nome`, `id`, `plugin`, `status`, `versao` e datas. A migration inicial não declara FK, índice único para slug ou coluna `language`. O JSON usa sincronização por PK. O gerenciador de arquivos usa `arquivos_disco_categorias.id_categorias` para vincular categorias a caminhos físicos; a antiga relação `arquivos_categorias` fica para registros legados.

## Limitações confirmadas

> [!WARNING]
> Renomear uma categoria pode alterar seu slug, mas os filhos referenciam o id numérico e preservam a hierarquia. O breadcrumb recursivo não tem detecção de ciclos; uma cadeia pai corrompida no banco pode impedir a montagem da tela.

> [!CAUTION]
> O cadastro de filho herda o módulo do pai, mas a edição permite mudar `id_modulos` sem percorrer descendentes. Isso pode produzir uma árvore com módulos diferentes. A relação com arquivos físicos é por id numérico de categoria, independentemente do slug.

## Veja também

- [Arquivos](admin-arquivos.md)
- [Módulos](modulos.md)
