---
title: "Módulo admin-plugins"
description: "Cadastro, instalação e atualização de plugins."
section: reference
module: admin-plugins
sources:
  - gestor/modulos/admin-plugins/admin-plugins.php
  - gestor/modulos/admin-plugins/admin-plugins.js
  - gestor/modulos/admin-plugins/admin-plugins.json
  - gestor/modulos/admin-plugins/resources
  - gestor/db/migrations/20250723165533_create_plugins_table.php
  - gestor/db/migrations/20250903130000_alter_plugins_table_add_phase1_fields.php
verified_at: c4e05805
---

# Módulo admin-plugins

Cadastra plugins e controla instalação, atualização e reprocessamento. O registro guarda a origem do pacote; o processamento delega a instalação ao controlador de plugins e atualiza o estado de execução na tabela plugins.

## Como usar

Abra admin-plugins/ para listar, admin-plugins/adicionar/ para cadastrar e admin-plugins/editar/?id=<slug> para configurar. admin-plugins/executar/ apresenta a execução; admin-plugins/teste/ expõe ferramentas de teste. As rotas aparecem em pt-br e en. Escolha origem por arquivo, repositório público, repositório privado ou caminho local conforme o formulário, e acione instalar, atualizar ou reprocessar.

## Referência técnica

O switch principal trata adicionar, editar, executar, acao e teste; listar/status/exclusão usam a interface compartilhada. AJAX update aceita params.acao instalar, atualizar, reprocessar ou status. O mesmo controlador também tem entrada CLI --action=install|update|reprocess e --plugin=<id>. A rotina descobre releases GitHub, baixa artefatos, verifica SHA-256 quando fornecido e invoca plugin_process_cli(); o resultado atualiza status_execucao. O JSON não declara widget, template, hook ou hooks.api do módulo; plugins instalados podem ter seus próprios hooks.

plugins tem id_plugins, id_usuarios, nome, id, status, versao e datas na migration inicial. A migration da fase 1 acrescenta origem_tipo, origem_referencia, origem_branch_tag, origem_credencial_ref, versao_instalada, checksum_pacote, manifest_json, status_execucao e datas de instalação/atualização. O índice de id introduzido nessa migration não é único.

## Limitações confirmadas

> [!WARNING]
> A verificação de checksum só ocorre quando um arquivo SHA-256 está disponível; não confunda ausência de checksum com pacote verificado. Instalar/atualizar executa código de plugin e altera arquivos e banco. A migration inicial não declara unicidade para id, e a migration posterior cria índice não único.

## Veja também

- [Módulos](modulos.md)
