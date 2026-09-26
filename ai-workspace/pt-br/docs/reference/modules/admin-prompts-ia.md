---
title: "Módulo admin-prompts-ia"
description: "Biblioteca de prompts da IA por alvo e idioma."
section: reference
module: admin-prompts-ia
sources:
  - gestor/modulos/admin-prompts-ia/admin-prompts-ia.php
  - gestor/modulos/admin-prompts-ia/admin-prompts-ia.js
  - gestor/modulos/admin-prompts-ia/admin-prompts-ia.json
  - gestor/modulos/admin-prompts-ia/resources
  - gestor/db/migrations/20251007105018_create_prompts_ia_table.php
  - gestor/db/migrations/20260331100000_add_id_usuarios_to_prompts_ia.php
  - gestor/db/migrations/20251013145700_create_alvos_ia_table.php
verified_at: 98ac881d
---

# Módulo admin-prompts-ia

Mantém prompts reutilizáveis para os alvos de IA. Cada registro tem nome, slug, alvo, idioma, texto e sinalizador de padrão; o alvo é escolhido entre os registros de alvos_ia.

## Como usar

Abra admin-prompts-ia/, crie em admin-prompts-ia/adicionar/ e edite em admin-prompts-ia/editar/?id=<slug>. Escolha o alvo, escreva o prompt no CodeMirror e, se couber, marque-o como padrão. As três rotas existem em pt-br e en. A interface compartilhada fornece listagem, status e exclusão.

## Referência técnica

O switch principal trata adicionar e editar; AJAX opcao/verificar-padrao conta prompts ativos com padrao=1 para o alvo. O JS apenas configura CodeMirror em Markdown. A criação grava id_usuarios do operador. O filtro hook_apply_filters('admin-prompts-ia', 'padrao.update.where', ...) permite restringir o UPDATE que limpa o padrão; o JSON do módulo não declara hooks.api, widget ou template próprio.

A migration de prompts_ia cria id_prompts_ia, nome, id, language, alvo, padrao, prompt MEDIUMTEXT, status, versao, datas, file_version e checksum, com índice único (id, language); uma migration posterior acrescenta id_usuarios. alvos_ia define as escolhas de alvo por idioma.

## Limitações confirmadas

> [!WARNING]
> Sem filtro adicional, marcar um prompt como padrão limpa padrao de todos os prompts com o mesmo alvo, sem filtrar idioma ou usuário. A checagem AJAX também não filtra idioma/usuário. Embora a criação registre id_usuarios, os caminhos de edição/listagem do controlador não impõem escopo de propriedade por si só.

## Veja também

- [Modos de IA](admin-modos-ia.md)
- [Servidores de IA](admin-ia.md)
