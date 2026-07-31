---
name: c2f-database-testing
description: Use ao testar sincronização, migrations ou código de banco Conn2Flow sem tocar dados reais do ambiente.
---

# Testes isolados de banco

1. Prefira SQLite em memória para funções puras, CRUD isolado e dependências injetáveis.
2. Quando MySQL for indispensável, use somente o banco dedicado `conn2flow_test` no container local.
3. Nunca rode testes destrutivos contra o banco de desenvolvimento `conn2flow`.
4. Crie schema mínimo, isole fixtures e remova o banco/tabelas de teste ao final.
5. Use guards de autorun e confirme que manifestos/data files não ficaram alterados por efeito colateral.
