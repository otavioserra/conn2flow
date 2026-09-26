---
title: "Módulo usuarios-perfis"
description: "Perfis e permissões de módulos e operações."
section: reference
module: usuarios-perfis
sources:
  - gestor/modulos/usuarios-perfis/usuarios-perfis.php
  - gestor/modulos/usuarios-perfis/usuarios-perfis.js
  - gestor/modulos/usuarios-perfis/usuarios-perfis.json
  - gestor/modulos/usuarios-perfis/resources
  - gestor/db/migrations/20250723165543_create_usuarios_perfis_table.php
  - gestor/db/migrations/20250723165544_create_usuarios_perfis_modulos_table.php
  - gestor/db/migrations/20250723165545_create_usuarios_perfis_modulos_operacoes_table.php
  - gestor/db/migrations/20250925211150_add_language_field_to_modules_tables.php
  - gestor/db/migrations/20250904103000_alter_usuarios_perfis_permissoes_add_plugin.php
verified_at: b6839aa1
---

# Módulo `usuarios-perfis`

Define conjuntos de permissões para contas. Um perfil associa slugs de módulos e de operações específicas; o usuário recebe o perfil por `usuarios.id_usuarios_perfis`.

## Como usar

Em `usuarios-perfis/`, consulte os perfis. Use `usuarios-perfis/adicionar/` para informar nome, selecionar módulos e operações e, se necessário, marcar o perfil como padrão. Em `usuarios-perfis/editar/?id=<slug>`, revise seleções e mude o padrão. As rotas são iguais nos dois idiomas. As caixas de seleção do painel têm comandos de marcar e desmarcar o grupo inteiro.

## Referência técnica

O controlador trata `listar`, `adicionar` e `editar`; não há caso AJAX próprio ativo. Status e exclusão pertencem à interface compartilhada. Criação e edição aceitam apenas slugs encontrados entre módulos e operações ativos do idioma; módulos do grupo `bibliotecas` e com `host` preenchido são excluídos da seleção. A edição compara associações atuais e marcadas, insere/remove vínculos, registra histórico e, se o nome mudar, gera outro slug e atualiza `perfil` nas tabelas de vínculo.

`usuarios_perfis` tem `id_usuarios_perfis` numérico, `nome`, `id`, `padrao`, `status`, `versao` e datas; migrações posteriores acrescentam `language` e `plugin`. `usuarios_perfis_modulos` guarda `perfil` e `modulo`; `usuarios_perfis_modulos_operacoes` guarda `perfil` e `operacao`, mais `plugin` acrescentado posteriormente. Os vínculos são strings de slug, sem chave estrangeira nas migrations iniciais. O JSON especifica estratégia de sincronização por `(language,id)`; isso é configuração do compilador, não uma restrição única declarada nas migrations consultadas. Não há widget, template, hooks ou `hooks.api` do módulo.

## Limitações confirmadas

> [!WARNING]
> Na criação, o código limpa `padrao` de todos os perfis antes de inserir o novo, mesmo quando a caixa de padrão não foi marcada. A limpeza não filtra idioma. Uma criação comum pode deixar a instalação sem perfil padrão; revise essa opção após criar.

> [!CAUTION]
> As associações são escritas em várias consultas, sem transação visível no controlador. O código também lê totais de caixas de seleção da sessão para percorrer o formulário; se a sessão e o POST não coincidirem, itens podem ficar de fora. Permissão de módulo e permissão de operação são vínculos separados.

## Veja também

- [Usuários](usuarios.md)
- [Operações de módulos](modulos-operacoes.md)
