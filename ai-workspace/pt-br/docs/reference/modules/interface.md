---
title: "Módulo interface"
description: "Catálogo compartilhado de textos da interface administrativa."
section: reference
module: interface
sources:
  - gestor/modulos/interface/interface.json
  - gestor/bibliotecas/interface.php
  - gestor/db/migrations/20250723165549_create_variaveis_table.php
verified_at: c4e05805
---

# Módulo interface

É um módulo de recursos compartilhados que fornece rótulos, mensagens e dicas da interface administrativa em pt-br e en. O JSON não define controlador PHP, páginas próprias nem tabela do módulo; os demais módulos consultam suas variáveis pelo id interface.

## Como usar

Não há rota interface/ declarada no JSON. O operador vê esses textos nas listas, formulários e ações de outros módulos. Para personalizá-los, abra variables/ e selecione interface; salve os valores no idioma desejado. O Gestor lê as variáveis pelo namespace do módulo.

## Referência técnica

interface.json contém resources.pt-br.variables e resources.en.variables, com pares id/value/type; não contém switch de página ou AJAX, widget, template, hook nem hooks.api. Rótulos como field-name e tooltip-button-edit são usados pela biblioteca interface.php. Valores persistidos são registros em variaveis, com modulo=interface e language correspondente; a migration inclui id_variaveis, language, modulo, id, valor, tipo, grupo e descricao. A biblioteca interface.php coordena renderização, validação e ações genéricas de CRUD, mas não é um controlador do módulo interface.

## Limitações confirmadas

> [!CAUTION]
> Um rótulo alterado em um idioma não altera o outro. Tratar interface como página de CRUD ou como tabela própria contradiz o JSON: é um catálogo de variáveis usado pela interface compartilhada.

## Veja também

- [Variáveis](variables.md)
- [Módulos](modulos.md)
