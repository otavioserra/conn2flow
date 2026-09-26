---
title: "Widgets"
description: "Marcadores, callbacks de módulo, renderização e limites dos widgets."
section: concepts
order: 50
sources:
  - gestor/bibliotecas/widgets.php
  - gestor/gestor.php
verified_at: 3b099ff0
---

# Widgets

Um widget insere HTML calculado por um módulo na página. O formato atual usa `<!-- widgets#MODULO->FUNCAO(JSON) < -->` e um marcador de fechamento com `>`; o HTML entre eles é passado ao callback como `html`, para servir de modelo visual. O formato inline `@[[widgets#MODULO->FUNCAO(JSON)]]@` continua aceito.

`gestor_pagina_widgets()` localiza os marcadores na página e chama `widgets_get()`. Este procura `gestor/modulos/<modulo>/<modulo>.widget.php`, converte hífens do módulo em sublinhados e tenta primeiro `<modulo>_<funcao>`; só então tenta o nome sem prefixo. Em AJAX, procura os mesmos nomes com sufixo `_ajax`. O JSON inválido vira array vazio.

O identificador deve usar a forma modular. O caminho de compatibilidade para um nome simples devolve string vazia: não há busca de um registro de widget no banco. Um callback que devolve vazio também deixa o marcador original na página, pois a substituição só ocorre quando há resultado. Um widget de plugin não é localizado por esse caminho, que aponta somente a `modulos/`.

No Live Editor, os marcadores de abertura e fechamento ficam ao redor do HTML renderizado para delimitar cada widget. Para visitantes, são removidos quando há substituição. Veja a [biblioteca widgets](../reference/libraries/widgets.md) para a assinatura.
