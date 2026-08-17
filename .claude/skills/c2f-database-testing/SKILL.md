---
name: c2f-database-testing
description: Use ao testar sincronização, migrations ou código de banco Conn2Flow sem tocar dados reais do ambiente.
user-invocable: false
---

# Testes isolados de banco Conn2Flow

1. Prefira SQLite em memória para funções puras, CRUD isolado e fluxos com transporte/resolvers injetáveis.
2. Quando a semântica MySQL for indispensável, use somente o banco dedicado `conn2flow_test` no container local.
3. Nunca rode testes destrutivos contra o banco de desenvolvimento `conn2flow`.
4. Crie o schema mínimo necessário, isole fixtures e remova o banco/tabelas de teste ao final.
5. Use guards de autorun para incluir controladores diretamente e injete PDO/configuração em vez de depender do bootstrap completo.
6. Se um teste modificar manifestos ou data files versionados, restaure apenas esses efeitos colaterais fora do escopo e confirme o diff.
