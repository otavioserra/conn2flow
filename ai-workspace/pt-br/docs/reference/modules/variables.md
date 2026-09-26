---
title: "Módulo variables"
description: "Edição das variáveis de configuração por módulo e idioma."
section: reference
module: variables
sources:
  - gestor/modulos/variables/variables.php
  - gestor/modulos/variables/variables.js
  - gestor/modulos/variables/variables.json
  - gestor/modulos/variables/resources
  - gestor/bibliotecas/configuracao.php
  - gestor/db/migrations/20250723165549_create_variaveis_table.php
verified_at: a9a226e0
---

# Módulo variables

Oferece a tela de configuração das variáveis de cada módulo. O seletor escolhe um registro de modulos, enquanto os cards de edição são montados pela biblioteca configuracao a partir dos registros de variaveis no idioma corrente.

## Como usar

Abra variables/ e selecione o módulo no campo de busca; a URL carrega o parâmetro id do módulo escolhido. Edite, adicione ou retire variáveis nos cards e salve. A mesma rota e o mesmo fluxo existem em pt-br e en. O seletor exclui o grupo bibliotecas.

## Referência técnica

O único caso principal do controlador é variables; a interface é alteracoes. O AJAX opcao apenas retorna payload vazio e status Ok. configuracao_administracao() monta os campos; configuracao_administracao_salvar() compara o formulário com as linhas existentes por modulo e language, atualiza ou insere, exclui as linhas ausentes e incrementa versao/data_modificacao do registro em modulos quando há mudança. Não há widget, template, hook ou hooks.api específico no JSON.

O JSON do módulo aponta sua tabela principal para modulos, porque a tela também exibe os metadados do módulo selecionado. A tabela efetiva dos valores é variaveis: id_variaveis, language, modulo, id, valor MEDIUMTEXT, tipo, grupo e descricao na migration inicial. Os tipos da biblioteca incluem string, text, bool, number, quantidade, dinheiro, css, js, html, editor-texto e tipos de data.

## Limitações confirmadas

> [!WARNING]
> Esta tela não é um CRUD de modulos. Os antigos botões de status/exclusão foram removidos porque operavam sobre modulos e podiam desativar ou apagar o módulo inteiro. Não existem páginas próprias variables/adicionar/ ou variables/editar/.

> [!CAUTION]
> A busca do módulo concatena id e idioma diretamente na condição SQL; a biblioteca de configuração também concatena modulo e language em consultas. As escritas percorrem várias linhas sem transação explícita neste fluxo.

## Veja também

- [Módulos](modulos.md)
