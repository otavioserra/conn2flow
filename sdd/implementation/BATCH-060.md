# BATCH-060 — Pipeline de Metadados de Projeto e Desacoplamento (Pull System)

- **Intake**: [req-060.md](../human-requests/req-060.md)
- **Status**: ready-for-intake
- **Alvo de validação**: VALIDATION-CHECKLIST.md#batch-060

## Contexto

Este lote resolve o desacoplamento de metadados do projeto entre o ambiente local (desenvolvimento) e o de execução (produção). Como o servidor não possui as pastas `resources/` ou `db/data` ativas, ele não tem acesso ao `tables_config.json`. A solução é fazer com que o compilador local gere o arquivo transitório `project-schema-metadata.json` na raiz da pasta `gestor/` local do projeto, que será zipado e implantado via `deploy-project-v2.sh` no servidor. O endpoint de recuperação da API e o CLI de banco do servidor lerão este arquivo para identificar e exportar as tabelas dinâmicas do projeto.

## Slices

| # | Escopo | Arquivos | Validação mínima |
| --- | --- | --- | --- |
| 1 | Compilador gera `project-schema-metadata.json` na raiz local de `gestor/` | `atualizacao-dados-recursos.php` | `php -l` + run `Update => Core` |
| 2 | deploy-project-v2.sh empacota o arquivo no deploy | `deploy-project-v2.sh` | Bash script review |
| 3 | API e CLI de banco do servidor lêem project-schema-metadata.json no dump | `api.php`, `recuperacao-banco-de-dados.php` | `php -l` |
| 4 | Atualização de documentação pt-br e en (se aplicável) | `CONN2FLOW-SISTEMA-RECURSOS.md` | Doc review |

## Notas de execução

- **Estrutura do project-schema-metadata.json**: O compilador deve gerar um JSON formatado na raiz de `gestor/` contendo as tabelas do projeto. Ex:
  ```json
  {
      "tabelas": {
          "menus": { ... },
          "publisher_highlights": { ... }
      }
  }
  ```
- **Merge de Tabelas no Servidor**: No servidor, o `recuperacao-banco-de-dados.php` e a função `api_project_recover()` na API lerão esse arquivo e adicionarão as tabelas encontradas à lista de tabelas a exportar caso o cliente faça a requisição padrão.
