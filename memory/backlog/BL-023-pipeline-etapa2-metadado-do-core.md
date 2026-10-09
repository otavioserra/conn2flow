# BL-023 — Pipeline de projeto: a etapa 2 sincroniza dados do projeto com o `schema-metadata.json` do core

- **Tipo**: Bug / DevOps
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: MÉDIA (quebra o `project:update-all` quando uma tabela só do projeto muda)
- **Origem**: deploy da req-186 no Lab (`conn2flow-site-local`), 2026-09-26
- **Componentes**: `project:update-all` (etapas 1, 2 e 4), `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` (`sincronizarTabela`, `pkPorTabela`, `descobrirPK`)

## Contexto observado

1. A etapa 1 (`project:sync-core`) copia o `gestor/db/data/` do core por cima do projeto remoto, inclusive o `schema-metadata.json`. Os `*Data.json` que só o projeto tem (ex.: `MenusData.json`) continuam lá, da rodada anterior.
2. A etapa 2 roda o atualizador com esse estado misto. O metadado do core não tem a tabela `menus` (ela vem de `project_tables_config.json`), então a tabela cai no modo `pk`.
3. Sem `id_menus` no JSON, `descobrirPK()` escolhe `id`, que se repete entre idiomas. O índice por PK colapsa pt-br e en numa entrada, e o UPDATE `WHERE id='docs-sidebar'` tenta gravar `language='en'` também na linha pt-br: `Duplicate entry 'docs-sidebar-en' for key 'id'`, com ROLLBACK da etapa inteira.
4. Só aparece quando o checksum do `MenusData.json` muda; nas outras rodadas a tabela é pulada.
5. Contorno usado: `project:sync-files` e depois `project:sync-db`, e o `project:update-all` voltou a passar.
6. Observação relacionada: em cada rodada, as etapas 2 e 5 atualizam as mesmas 2 páginas (`paginas ~2`) em sentidos opostos (dados do core e depois do projeto).

## Proposta

1. Na etapa 2, sincronizar só as tabelas que o metadado do core declara; ignorar `*Data.json` sem entrada no contrato, em vez de cair no modo `pk` heurístico.
2. No modo `pk`, recusar (com log) quando a PK escolhida não é única entre os registros do JSON.
3. Investigar se a etapa 2 ainda é necessária num projeto, dado que a etapa 5 roda com os dados completos.

## Critérios de aceite (rascunho)

- `project:update-all` passa numa rodada em que `MenusData.json` mudou.
- Teste unitário: JSON com `id` repetido entre idiomas e sem metadado não gera UPDATE cruzado.
