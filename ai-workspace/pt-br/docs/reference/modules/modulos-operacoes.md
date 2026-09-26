---
title: "Módulo modulos-operacoes"
description: "Cadastro das operações granulares ligadas aos módulos."
section: reference
module: modulos-operacoes
sources:
  - gestor/modulos/modulos-operacoes/modulos-operacoes.php
  - gestor/modulos/modulos-operacoes/modulos-operacoes.js
  - gestor/modulos/modulos-operacoes/modulos-operacoes.json
  - gestor/modulos/modulos-operacoes/resources
  - gestor/db/migrations/20250723165529_create_modulos_operacoes_table.php
  - gestor/db/migrations/20250902140000_alter_modulos_operacoes_table_add_modulo_id_remove_id_modulos.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
verified_at: b6839aa1
---

# Módulo `modulos-operacoes`

Registra operações nomeadas de um módulo para atribuição granular em perfis de usuários. O slug `operacao` é o vínculo utilizado pela tabela de permissões; `id` identifica o registro administrativo.

## Como usar

Abra `modulos-operacoes/`, crie uma entrada em `modulos-operacoes/adicionar/` com nome e módulo, e informe o código da operação quando precisar diferenciá-lo do nome. Edite em `modulos-operacoes/editar/?id=<slug>`. Na tela de perfis, selecione a operação para concedê-la. As rotas são iguais nos dois idiomas.

## Referência técnica

O controlador trata `listar`, `adicionar` e `editar`; não há ação AJAX ativa. Status e exclusão vêm da interface compartilhada. A criação exige nome e seleção de módulo. `id` é gerado a partir do nome no idioma; `operacao` é gerada a partir do valor informado ou do nome, com teste de colisão dentro de `modulo_id`. Edição pode trocar nome, módulo e `operacao`, além de incrementar versão e registrar histórico. O JS atual não implementa comportamento de edição além da inicialização da página.

`modulos_operacoes` tem `id_modulos_operacoes` numérico, `id_usuarios`, `nome`, `id`, `operacao`, `status`, `versao` e datas. A migração posterior substituiu a chave numérica `id_modulos` por `modulo_id` string; outra acrescentou `language`. A migração consultada não declara chave estrangeira nem índice único para os slugs. O JSON do módulo define a tabela para sincronização, mas não fornece widget, template, hooks ou `hooks.api`.

`usuarios_perfis_modulos_operacoes.operacao` guarda o slug concedido ao perfil. A operação deve corresponder ao código que o consumidor verifica; o cadastro sozinho não acrescenta uma checagem de permissão ao código do módulo.

## Limitações confirmadas

> [!WARNING]
> A edição de `operacao` atualiza o registro, mas não propaga o novo slug para `usuarios_perfis_modulos_operacoes`. Perfis que concediam o código antigo precisam ser revistos após uma renomeação.

> [!CAUTION]
> O valor de `operacao` tem unicidade calculada pelo controlador dentro de `modulo_id`, sem restrição SQL correspondente na migration lida. Integrações que escrevem diretamente no banco não herdam essa proteção.

## Veja também

- [Perfis de usuários](usuarios-perfis.md)
- [Módulos](modulos.md)
