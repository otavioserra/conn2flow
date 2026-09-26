---
title: "Módulo usuarios"
description: "Cadastro e manutenção administrativa de contas."
section: reference
module: usuarios
sources:
  - gestor/modulos/usuarios/usuarios.php
  - gestor/modulos/usuarios/usuarios.js
  - gestor/modulos/usuarios/usuarios.json
  - gestor/modulos/usuarios/resources
  - gestor/db/migrations/20250723165538_create_usuarios_table.php
  - gestor/db/migrations/20260706100000_add_two_factor_to_usuarios_table.php
  - gestor/db/migrations/20260706100010_create_usuarios_provedores_table.php
  - gestor/db/migrations/20260818100000_create_usuarios_api_tokens_table.php
verified_at: b6839aa1
---

# Módulo `usuarios`

Administra contas, credenciais e o perfil de permissões associado. É o CRUD administrativo da tabela `usuarios`; os fluxos do próprio titular, autenticação e recuperação de senha ficam em `perfil-usuario`.

## Como usar

Abra `usuarios/` para listar, `usuarios/adicionar/` para criar e `usuarios/editar/?id=<slug>` para alterar. Na criação, informe nome, perfil, e-mail, nome de usuário e senha com pelo menos 12 caracteres. O painel propõe o e-mail como nome de usuário se o campo estiver vazio. Ao inativar ou excluir uma conta, os tokens de sessão dessa conta são removidos. As rotas são iguais em pt-br e en.

## Referência técnica

O controlador trata `listar`, `adicionar`, `editar`, `status` e `excluir`; não há operação AJAX administrativa ativa no `switch` (o caso mostrado está comentado). A interface compartilhada cuida da listagem, histórico, estado e exclusão. Na criação, o código verifica duplicidade de `usuario` e `email`, gera slug a partir do nome, divide nomes e grava hash da senha com `PASSWORD_ARGON2I`. A edição atualiza campos alterados, registra histórico e remove `usuarios_tokens` do usuário após mudanças. O JS normaliza o nome, mostra suas partes e preenche `usuario` com e-mail quando vazio.

`usuarios` contém `id_usuarios` numérico, `id_hosts`, `id_usuarios_perfis`, `nome_conta`, `nome`, `id`, `usuario`, `senha`, `email`, `primeiro_nome`, `nome_do_meio`, `ultimo_nome`, `status`, `versao`, datas, `email_confirmado`, `gestor` e `gestor_perfil`. Migrações posteriores acrescentam campos de 2FA e códigos de recuperação. Tokens pessoais de API ficam em `usuarios_api_tokens`, e provedores sociais em `usuarios_provedores`; não são editados diretamente por este CRUD. A migration inicial não declara unicidade SQL para `usuario` ou `email`: as verificações de duplicidade ocorrem no controlador. O módulo não fornece widget nem templates, e seu JSON não declara hooks ou `hooks.api`.

## Limitações confirmadas

> [!WARNING]
> O bloco que propagaria uma troca de perfil para contas gestoras de um host está protegido por `$desativado_hosts = true` e, portanto, não executa. Alterar o perfil da conta não recalcula aquelas permissões filhas.

> [!CAUTION]
> A remoção explícita em `status`/`excluir` atinge `usuarios_tokens` (sessões), sem revogar nessa rotina os registros de `usuarios_api_tokens`. Não trate a inativação no CRUD como prova de eliminação física de todas as credenciais armazenadas.

## Veja também

- [Perfis de usuários](usuarios-perfis.md)
- [Meu perfil e autenticação](perfil-usuario.md)
