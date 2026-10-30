---
id: "BL-046"
title: "BL-046 — Logo do painel configurável na aba Site do admin-environment"
status: "IN-DISCUSSION"
date: "2026-10-30"
author: "executor"
target_repo: "conn2flow"
summary_short: "Permitir trocar pelo painel o logo do layout principal, que hoje é fixo em images/logo-principal.png no core."
summary_medium: "Pedido do Engenheiro Chefe depois de ver o logo da Conn2Flow no painel do Photon.\nA opção fica na aba Site do admin-environment e vale para o layout principal do sistema."
---

# BL-046 — Logo do painel configurável na aba Site do `admin-environment`

- **Tipo:** Feature / Core / Interface
- **Status:** IN-DISCUSSION
- **Criado em:** 2026-10-30
- **Origem:** Engenheiro Chefe, durante a REQ-297.

## Problema

As telas que usam o layout principal do sistema mostram o logo da Conn2Flow em qualquer projeto. O componente `admin-topbar-tailwind` aponta fixo para `images/logo-principal.png`. No Photon, a dashboard aparece com o logo da Conn2Flow.

Layouts próprios de projeto, como o `photon-admin` (agenda, anamnese e demais telas internas do Photon), já têm o logo deles e ficam como estão.

## Direção indicada pelo Engenheiro Chefe

- Criar a opção de troca do logo no `admin-environment`, na aba **Site**.
- Corrige o que não tem layout próprio, como a dashboard, que usa o layout principal.
- Não levar o menu superior para as telas internas do Photon: elas já têm o logo.

## Pontos a definir

- Onde guardar o arquivo enviado, para sobreviver às atualizações do core.
- Quais lugares passam a respeitar a opção. Além do topo do painel, `layout-pagina-sem-permissao-tailwind` também usa o logo.
- Qual logo aparece enquanto nada for configurado.

## Relacionado

[BL-047](BL-047-topo-do-painel-diferente-entre-projetos.md), mesma área da tela.
