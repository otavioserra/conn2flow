---
id: "BATCH-291"
title: "Estabilização do teste assíncrono de XHR no global-csrf-refresh (REQ-282)"
status: "complete"
date: "2026-10-16"
author: "architect"
target_repo: "conn2flow"
---

# BATCH-291: Estabilização de temporização assíncrona em XHR no teste de CSRF

## Arquivos Alterados
- `tests/Unit/JS/global-csrf-refresh.test.js`: adoção de `await vi.waitFor(...)` e aumento da janela base de `esperar(ms = 50)`.

## Evidências de Teste
- Vitest suite completa: 63 test files passed, 700 passed (0 failed).
- `tests/Unit/JS/global-csrf-refresh.test.js`: 23 passed (0 failed).
