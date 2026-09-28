# BL-027 — Sincronização de banco carrega tabelas inteiras na memória

- **Tipo**: Bug / Performance
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA (deploy por API falha com HTTP 500 quando `paginas` cresce)
- **Origem**: deploy do conn2flow-site no Lab, 2026-09-28 (req-191)
- **Componentes**: `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` (`sincronizarTabela`, `SELECT *` + `fetchAll`)

## Contexto

A req-191 contornou o sintoma elevando o `memory_limit` do endpoint para 1024 MB. A causa continua: cada tabela é lida inteira, com HTML e CSS compilado de todas as páginas, para comparar com o JSON.

## Proposta (rascunho)

1. Ler só a chave natural e um hash das colunas comparadas; buscar a linha completa apenas quando o hash difere.
2. Ou iterar com cursor (`fetch` em laço) indexando por chave natural.

## Critérios de aceite (rascunho)

- Deploy de projeto com 1.000 páginas dentro de 128 MB.
- Mesmo resultado de inserções/atualizações que o algoritmo atual.
