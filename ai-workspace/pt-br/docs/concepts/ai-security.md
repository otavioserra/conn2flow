---
title: "Segurança da IA e superfícies administrativas"
description: "Limites de acesso do editor de IA e riscos conhecidos da revisão do core."
section: concepts
order: 120
sources:
  - gestor/bibliotecas/ia.php
  - gestor/bibliotecas/interface.php
  - gestor/bibliotecas/seguranca.php
  - gestor/controladores/api/api-module-distributed.php
verified_at: 3b099ff0
---

# Segurança da IA e superfícies administrativas

O editor de IA carrega modelos e prompts por `ia.php`. Filtros como `ia.models.available` e `ia.prompts.load.where` permitem reduzir o que aparece para um usuário. Isso não equivale a autorização de escrita: as rotas de editar e excluir prompts aplicam filtros próprios de consulta e precisam ser avaliadas separadamente em projetos multiusuário.

A revisão de segurança do core registrou onze achados ainda propostos para correção (req-181). Eles abrangem: consulta e ordenação da listagem administrativa, ações mutáveis por GET, validação de nomes de coluna, escapes em funções compartilhadas, limites do segundo fator, tipos de upload ativos no navegador, vínculo e duração de sessão, verificação de e-mail no login social, sigilo de logs SMTP, autoria de prompts e repetição no canal de módulos distribuídos. Não trate a existência da proposta como correção implementada.

> [!CAUTION]
> Em instalações multiusuário, restrinja quem pode editar prompts e revise os endpoints administrativos expostos aos perfis de menor privilégio. Proteja logs e trate HTML/SVG enviados por usuários como conteúdo ativo.

A [referência de IA](../reference/libraries/ia.md), [interface](../reference/libraries/interface.md) e [segurança](../reference/libraries/seguranca.md) descrevem as funções concretas. O acompanhamento das correções ocorre na requisição de segurança, não nesta página pública.
