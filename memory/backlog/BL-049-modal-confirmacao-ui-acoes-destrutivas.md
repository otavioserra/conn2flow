---
id: "BL-049"
title: "BL-049 — Modais de Confirmação UI 3.0 para Ações Destrutivas e Arquivamento (Substituição de confirm() Nativo)"
status: "PROMOTED"
promoted_to: "REQ-300 (BATCH-309)"
date: "2026-11-04"
author: "architect"
target_repo: "conn2flow"
summary_short: "Substituição de window.confirm() nativo pelo modal padronizado interface-delecao-modal-tailwind nas ações de arquivar/excluir registros."
summary_medium: "Padroniza a experiência visual e acessibilidade em botões de arquivamento, exclusão ou cancelamento nos módulos do sistema."
---

# BL-049 — Modais de Confirmação UI 3.0 para Ações Destrutivas e Arquivamento

- **Tipo:** Refinement / UI Core Component
- **Status:** PROMOTED → [req-300.md](../human-requests/req-300.md) (BATCH-309), 2026-11-05, por decisão do operador
- **Prioridade:** Média (Melhoria visual de consistência)
- **Origem:** Observação do Engenheiro Chefe ao clicar em 'Arquivar' em `/pacientes/`

---

## 1. Contexto

Ações como "Arquivar Paciente" em `/pacientes/` acionam o diálogo `window.confirm()` nativo do navegador. O Core Conn2Flow já possui a infraestrutura de modais Tailwind estilizados (`interface-delecao-modal-tailwind` e `interface-alerta-modal-tailwind`).

---

## 2. Proposta Técnica

- Criar um helper JavaScript acessível via `c2f.confirm({ title, message, confirmText, cancelText, type: 'danger'|'warning' })` retornando uma Promise;
- Adotar o modal estilizado do Core em todos os botões de exclusão, cancelamento e arquivamento dos módulos.
