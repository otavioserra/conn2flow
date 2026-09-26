---
title: "Módulo admin-templates"
description: "Criação e manutenção de modelos HTML para recursos do Gestor."
section: reference
module: admin-templates
sources:
  - gestor/modulos/admin-templates/admin-templates.php
  - gestor/modulos/admin-templates/admin-templates.js
  - gestor/modulos/admin-templates/admin-templates.json
  - gestor/modulos/admin-templates/resources
  - gestor/db/migrations/20251030160430_create_templates_table.php
verified_at: a9a226e0
---

# Módulo admin-templates

Administra modelos de conteúdo reutilizados por outros recursos, sobretudo páginas e publicadores. Cada modelo combina destino, HTML, conteúdo extra do head, CSS, framework e miniatura; não é uma página publicada por si só.

## Como usar

Abra admin-templates/ para listar. Use admin-templates/adicionar/ para criar, admin-templates/editar/?id=<slug> para modificar e admin-templates/clonar/?id=<slug> para criar uma cópia. Escolha o destino do modelo, nome, slug, miniatura e framework; edite HTML/CSS pelo editor. O seletor do recurso consumidor deve apontar para um modelo ativo do idioma correspondente. As quatro rotas existem em pt-br e en.

## Referência técnica

O controlador trata adicionar, editar e clonar; listar, status e exclusão usam a interface compartilhada. O switch AJAX não possui caso ativo. A gravação converte marcadores textuais em referências internas, calcula css_source_hash, registra histórico e backup de conteúdo na edição e incrementa a versão. A clonagem copia conteúdo e metadados para novo registro. admin_templates_alvo_ia() consulta ou valida o target para integração com o editor de IA.

A tabela templates contém id_templates numérico, nome, id, target, thumbnail, plugin, language, html, html_extra_head, css, css_compiled, framework_css, status, versao, datas, flags de atualização e metadados de arquivo/checksum. O índice único da migration é (id, language), sem target. Migrações posteriores adicionam campos de CSS pré-compilado, projeto e hash. O JSON do módulo não define widget, hook nem hooks.api; consumidores consultam a tabela de modelos e filtram idioma, target e status.

## Limitações confirmadas

> [!WARNING]
> O formulário de edição desabilita visualmente a troca de destino, mas o tratamento do POST aceita target enviado. A unicidade de slug também vale entre destinos no mesmo idioma, pois o índice SQL não inclui target. O CRUD registra CSS compilado recebido e o hash, mas não executa a compilação.

> [!CAUTION]
> O ramo de admin_templates_alvo_ia() que consulta um modelo pelo id concatena o identificador na condição SQL. Não trate esse helper como validação de entrada não confiável.

## Veja também

- [Páginas](admin-paginas.md)
- [Publicador](publisher.md)
