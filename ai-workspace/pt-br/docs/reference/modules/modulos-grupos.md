---
title: "Módulo modulos-grupos"
description: "Grupos do catálogo de módulos e sua apresentação no menu."
section: reference
module: modulos-grupos
sources:
  - gestor/modulos/modulos-grupos/modulos-grupos.php
  - gestor/modulos/modulos-grupos/modulos-grupos.js
  - gestor/modulos/modulos-grupos/modulos-grupos.json
  - gestor/modulos/modulos-grupos/resources
  - gestor/db/migrations/20250723165528_create_modulos_grupos_table.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
  - gestor/db/migrations/20250904100000_alter_modulos_grupos_operacoes_add_plugin.php
  - gestor/db/migrations/20260820100000_alter_modulos_grupos_add_menu_label.php
verified_at: 45812d2e
---

# Módulo `modulos-grupos`

Agrupa módulos no catálogo e no menu administrativo. Cada grupo tem nome, slug, opção de host, rótulo de menu e ordem de apresentação.

## Como usar

Abra `modulos-grupos/`, crie em `modulos-grupos/adicionar/`, edite em `modulos-grupos/editar/?id=<slug>` ou clone em `modulos-grupos/clonar/?id=<slug>`. Informe nome; opcionalmente ajuste `menu_label`, `ordemMenu` e host. Depois associe módulos ao slug do grupo no módulo `modulos`. As rotas são iguais em pt-br e en.

## Referência técnica

O controlador trata `listar`, `adicionar`, `editar` e `clonar`; status e exclusão vêm da interface compartilhada. O `switch` AJAX não tem caso ativo. Criação/clone geram slug por idioma. Edição pode gerar novo slug ao mudar nome, atualiza campos alterados e registra histórico. `menu_label` vazio vira NULL; `ordemMenu` só aceita valor numérico, que é convertido para inteiro. O JSON não define widget, template, hooks ou `hooks.api`.

`modulos_grupos` tem `id_modulos_grupos` numérico, `id_usuarios`, `nome`, `id`, `host`, `status`, `versao`, datas e `ordemMenu`; migrações posteriores adicionam `language`, `plugin` e `menu_label`. `modulos.modulo_grupo_id` guarda o slug textual do grupo. As migrations consultadas não declaram chave estrangeira para esse vínculo.

## Limitações confirmadas

> [!WARNING]
> A edição de nome altera o slug do grupo, mas não atualiza `modulos.modulo_grupo_id`. Módulos vinculados ao slug antigo precisam de revisão. O campo `host` e o rótulo do menu controlam classificação/apresentação; não concedem permissão por si mesmos.

## Veja também

- [Módulos](modulos.md)
- [Perfis de usuários](usuarios-perfis.md)
