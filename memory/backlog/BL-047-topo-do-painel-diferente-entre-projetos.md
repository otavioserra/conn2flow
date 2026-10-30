---
id: "BL-047"
title: "BL-047 — Topo do painel com aparência diferente entre projetos"
status: "IN-DISCUSSION"
date: "2026-10-30"
author: "executor"
target_repo: "conn2flow"
summary_short: "No Photon, o topo do painel fica colado ao logo e com outra cor; a classe h-16 existe no HTML, mas não tem efeito."
summary_medium: "Em conn2flow.local o topo tem 64 px, com folga em volta do logo; em snapphoton.local tem 45 px.\nProvável falta de CSS do componente admin-topbar-tailwind nas páginas do Photon. Causa ainda não confirmada."
---

# BL-047 — Topo do painel com aparência diferente entre projetos

- **Tipo:** Bug visual / Core e projetos / Tailwind
- **Status:** IN-DISCUSSION
- **Criado em:** 2026-10-30
- **Origem:** Engenheiro Chefe, durante a REQ-297.

## Sintoma

Em `https://conn2flow.local/`, o topo do painel tem folga acima e abaixo do logo. Em `https://snapphoton.local/`, em telas como `admin-environment/`, o topo fica colado à imagem e tem cor de fundo e outros detalhes diferentes.

Como reproduzir: abrir `admin-environment/` autenticado nos dois sites e comparar.

## Medição (2026-10-30, 1366 px)

| Site | Tela | Altura do `<header>` | Topo do logo | Altura do logo |
| --- | --- | --- | --- | --- |
| `conn2flow.local` | `dashboard/`, `admin-environment/` | 64 px | 10 px | 44 px |
| `snapphoton.local` | `dashboard/`, `admin-environment/` | 45 px | 0 px | 44 px |

O `<header>` tem a classe `h-16` nos dois sites, mas no Photon ela não faz efeito. Nessas páginas, os dois sites têm `css_precompiled` do mesmo tamanho, ambos sem `.h-16`; a regra está no `css_precompiled` do componente `admin-topbar-tailwind`. Portanto, a diferença deve estar em como a folha do componente chega à página em cada projeto.

Hipótese a confirmar: é o mesmo tipo de problema do logo gigante da REQ-297 (utilities que não chegam à página), mas com outra causa, pois os 68 registros presos já foram soltos no Lab do Photon. A cor de fundo não foi medida: o elemento medido é transparente.

## Critério de aceite proposto

O topo do painel tem a mesma altura, a mesma folga e a mesma cor em `conn2flow.local` e `snapphoton.local`, em desktop e celular.

## Relacionado

[BL-046](BL-046-logo-configuravel-admin-environment.md), mesma área da tela.
