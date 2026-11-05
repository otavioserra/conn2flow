---
id: "BL-048"
title: "BL-048 — Selects Customizados com Busca e Paginação AJAX para Coleções Extensas (> 50 Registros) no Core UI"
status: "PROMOTED"
promoted_to: "REQ-300 (BATCH-309)"
date: "2026-11-04"
author: "architect"
target_repo: "conn2flow"
summary_short: "Evolução do componente de select customizado da UI 3.0 para carregar até 50 registros estáticos e disparar busca remota AJAX ao digitar."
summary_medium: "Evita sobrecarga de renderização HTML e consumo de memória ao listar centenas de pacientes ou registros em selects nos módulos clínicos e administrativos."
---

# BL-048 — Selects Customizados com Busca e Paginação AJAX para Coleções Extensas

- **Tipo:** Feature / UI Core Subsystem
- **Status:** PROMOTED → [req-300.md](../human-requests/req-300.md) (BATCH-309), 2026-11-05, por decisão do operador
- **Prioridade:** Média (Planejado para escala de clínicas com alta volumetria de pacientes)
- **Origem:** Observação do Engenheiro Chefe durante homologação da Versão 2.0 do SnapPhoton

---

## 1. Contexto

Atualmente, selects de pacientes e outros registros são populados estaticamente no HTML da página ou via JSON inicial. Para clínicas com dezenas de pacientes isso é rápido e leve, mas para clínicas com centenas ou milhares de registros ativos, a listagem estática completa se torna pesada.

---

## 2. Proposta Técnica

- Estender o componente `form-ui` / select customizado para suportar o atributo `data-c2f-select-ajax="/endpoint"`;
- Modo híbrido: carrega inicialmente os primeiros 50 registros (mais recentes/frequentes);
- Ao digitar no campo de busca interno do select (com *debounce* de 300 ms), efetua chamada assíncrona AJAX retornando os registros correspondentes e atualizando o dropdown dinamicamente.
