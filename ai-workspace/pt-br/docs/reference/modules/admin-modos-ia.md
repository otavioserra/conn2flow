---
title: "Módulo admin-modos-ia"
description: "Modos de instrução da IA por alvo e idioma."
section: reference
module: admin-modos-ia
sources:
  - gestor/modulos/admin-modos-ia/admin-modos-ia.php
  - gestor/modulos/admin-modos-ia/admin-modos-ia.js
  - gestor/modulos/admin-modos-ia/admin-modos-ia.json
  - gestor/modulos/admin-modos-ia/resources
  - gestor/db/migrations/20251013145832_create_modos_ia_table.php
  - gestor/db/migrations/20251013145700_create_alvos_ia_table.php
verified_at: 98ac881d
---

# Módulo admin-modos-ia

Mantém modos de instrução para recursos assistidos por IA. Cada modo tem nome, slug, alvo, prompt, idioma e sinalizador de padrão; os alvos disponíveis vêm da tabela alvos_ia.

## Como usar

Abra admin-modos-ia/, crie em admin-modos-ia/adicionar/ e edite em admin-modos-ia/editar/?id=<slug>. Escolha um alvo do idioma, preencha a instrução no editor CodeMirror e marque padrão se necessário. As rotas existem nos dois idiomas. Listagem, status e exclusão seguem a interface compartilhada.

## Referência técnica

O switch principal trata adicionar e editar; listar/status/exclusão usam a interface padrão. AJAX opcao com verificar-padrao consulta se há prompt padrão ativo para o alvo. O JS configura CodeMirror em modo Markdown. O JSON não define widget, template, hook ou hooks.api. A migration de modos_ia traz id_modos_ia, nome, id, language, alvo, padrao, prompt MEDIUMTEXT, status, versao, datas, file_version e checksum; (id, language) é único. alvos_ia guarda os ids/nome dos alvos por idioma.

## Limitações confirmadas

> [!WARNING]
> Ao marcar um modo como padrão, os ramos de criação e edição limpam padrao em prompts_ia, não em modos_ia. A verificação AJAX também consulta prompts_ia. Portanto, o controle de padrão dos modos não garante unicidade entre modos e pode alterar padrões de prompts. O UPDATE não filtra idioma.

## Veja também

- [Prompts de IA](admin-prompts-ia.md)
- [Servidores de IA](admin-ia.md)
