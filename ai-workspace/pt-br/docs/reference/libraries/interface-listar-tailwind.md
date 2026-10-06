---
title: "Biblioteca interface-listar-tailwind.php"
description: "Comportamento, persistência e permissões de interface-listar-tailwind."
section: reference
order: 100
sources:
  - gestor/bibliotecas/interface-listar-tailwind.php
verified_at: 914c7b10
---

# Biblioteca interface-listar-tailwind.php


A variante Tailwind da listagem compartilha configuração e AJAX do interface. Não carrega DataTables; o script interface-listar-tailwind.js consome as colunas autorizadas e a resposta do servidor.

- interface_listar_tailwind_configurar recebe tabela/colunas, valida IDs ordenáveis com interface_listar_coluna_segura e restaura início/quantidade. Quantidade aceita 10, 25, 50 ou 100; padrão 25. Sem ordem declarada, usa a primeira coluna ordenável. HTML é permitido somente em coluna com formatar declarado como array.
- interface_listar_tailwind_finalizar conta registros não excluídos usando o filtro declarado, corrige início fora do total, salva a configuração na sessão por módulo/opção/usuário, publica variáveis JS e monta o componente interface-listar na variante adequada. Ações usam tradução de ícones para Lucide; textos vêm das variáveis da interface.

Status e exclusão continuam no fluxo autorizado da biblioteca interface. Veja [Interface](interface.md) e [Controles](controles.md).

## Funções extraídas

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/interface-listar-tailwind.php` por `c2f docs:extract` — 2 funções. Não edite dentro deste bloco.

- `interface_listar_tailwind_configurar($params, $anterior = Array())` — [linha 4](../../../../../gestor/bibliotecas/interface-listar-tailwind.php#L4)
  Listagem Tailwind (req-220): mesmo contrato AJAX e allowlist do servidor.
- `interface_listar_tailwind_finalizar($params)` — [linha 44](../../../../../gestor/bibliotecas/interface-listar-tailwind.php#L44)

<!-- c2f:extract:end -->
