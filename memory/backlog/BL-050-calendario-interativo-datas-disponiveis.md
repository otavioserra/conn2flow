---
id: "BL-050"
title: "BL-050 — Componente de Calendário Interativo com Destaque de Datas Disponíveis no Agendamento Público"
status: "PROMOTED"
promoted_to: "REQ-300 (BATCH-309)"
date: "2026-11-04"
author: "architect"
target_repo: "conn2flow"
summary_short: "Substituição do input date nativo por componente visual de calendário exibindo dias de atendimento abertos e bloqueados."
summary_medium: "Melhora a experiência de autoagendamento permitindo que o paciente visualize diretamente os dias disponíveis no mês sem ter que clicar em datas fechadas."
---

# BL-050 — Componente de Calendário Interativo com Destaque de Datas Disponíveis

- **Tipo:** Feature / UI Core Component
- **Status:** PROMOTED → [req-300.md](../human-requests/req-300.md) (BATCH-309), 2026-11-05, por decisão do operador
- **Prioridade:** Média (Melhoria de UX no Agendamento Público)
- **Origem:** Observação do Engenheiro Chefe durante homologação da página `/agendar/`

---

## 1. Contexto

Atualmente a seleção de data no autoagendamento público utiliza o `<input type="date">` nativo do navegador. O paciente não tem indicação visual prévia de quais dias da semana a clínica atende ou possui vagas antes de selecionar uma data específica.

---

## 2. Proposta Técnica

- Criar um componente de calendário mensal responsivo integrado à UI 3.0;
- Marcar visualmente os dias com horários abertos (verde/destaque), dias sem vaga (cinza desabilitado) e dias em que a clínica não opera (desabilitados);
- Permitir navegar entre meses com transição suave e seleção instantânea de slots.
