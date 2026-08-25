---
name: c2f-database-testing
description: "LEIA ANTES de rodar ou criar testes automatizados de banco de dados (PHPUnit / SQLite / MySQL). Se não ler: testes poluem o banco principal, falham por funções MySQL não suportadas em SQLite ou mascaram regressões."
user-invocable: false
---

# Testes isolados de banco Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Escrever, executar ou debugar testes automatizados unitários ou de integração que acessam tabelas de banco de dados.
- **SKIP APENAS SE**: Testes de unidade puros (com mocking completo) ou testes exclusivos de JavaScript (Vitest/happy-dom).
- **CONSEQUÊNCIA DE IGNORAR**: Testes falham no CI/CD por incompatibilidade SQLite/MySQL ou poluem indevidamente a base de desenvolvimento local `conn2flow_test`.

---

1. Prefira SQLite em memória para funções puras, CRUD isolado e fluxos com transporte/resolvers injetáveis.
2. Quando a semântica MySQL for indispensável, use somente o banco dedicado `conn2flow_test` no container local.
3. Nunca rode testes destrutivos contra o banco de desenvolvimento `conn2flow`.
4. Crie o schema mínimo necessário, isole fixtures e remova o banco/tabelas de teste ao final.
5. Use guards de autorun para incluir controladores diretamente e injete PDO/configuração em vez de depender do bootstrap completo.
6. Se um teste modificar manifestos ou data files versionados, restaure apenas esses efeitos colaterais fora do escopo e confirme o diff.
