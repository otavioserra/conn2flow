---
title: "Visão do Conn2Flow"
description: "Conteúdo como recurso, automação pelo CLI e evolução do produto."
section: concepts
order: 130
sources:
  - gestor/controladores/api/api.php
  - cli/c2f.php
  - gestor/gestor.php
verified_at: 3b099ff0
---

# Visão do Conn2Flow

O Conn2Flow trata páginas, módulos e recursos como dados que podem ser compilados, inspecionados e implantados. O painel é uma das interfaces desses dados. O CLI `c2f` oferece comandos para recursos, banco, projetos e releases; a API usa autenticação por token para operações próprias. O roteador público continua servindo páginas a partir do banco. Essa divisão permite automatizar trabalho sem transformar o HTML do painel na fonte de verdade.

O repositório mantém requisições, decisões, lotes e validações em `sdd/`. Esse processo organiza trabalho de agentes e humanos, mas é uma regra de governança do projeto, não uma garantia imposta pelo runtime PHP. Consulte a [arquitetura](architecture.md) e o [guia de documentação](../guides/documentation.md) para os contratos implementados.

Integrações e aplicações complementares podem consumir as superfícies do core. Planos de gateway de IA, coordenação entre repositórios e clientes móveis são direção de produto; não se deve deduzir deles endpoints, permissões ou disponibilidade que o código deste repositório não implementa.
