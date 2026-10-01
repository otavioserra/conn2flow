# BATCH-214: deploy de projeto depois da req-202/203 (req-206)

Execução da [req-206](../human-requests/req-206.md). Corrige o que a [req-202](../human-requests/req-202.md) e a [req-203](../human-requests/req-203.md) quebravam no deploy de projeto.

**Status**: `complete` (validado no Lab; homologação humana pendente).

## Causas e correções

| Efeito no Lab | Causa | Correção |
|---|---|---|
| Admin com login, e-mail e senha da semente | A req-202 passou `usuarios` de `pk` para `natural_key`. `sincronizarTabela()` só respeitava `insert_only` no ramo de PK | O ramo de chave natural pula o registro que já existe quando a tabela é `insert_only` (`atualizacoes-banco-de-dados.php`) |
| Módulos do projeto `D`, permissões `cloud-*` removidas, `db/data` do projeto reescrito | Compilando um projeto, o compilador lia o `tables_config.json` do core com o `base_dir` do core e gravava as oito tabelas do core no `db/data` do projeto | Em modo projeto, a tabela declarada pelo core é lida das sementes do projeto; sem semente no projeto, é pulada (`DYNAMIC_SKIP_PROJETO_SEM_SEMENTE`) |
| Arquivo do core mais novo ficava no servidor | `rsync -u` decide por data, e o compilador regravava os `*Data.json` mesmo sem mudança | `jsonWrite()` não regrava conteúdo igual; `db/data` é copiado por conteúdo (`rsync -c`) em `synchronize-project.sh` e `sync-core-to-project.sh` |
| Suíte PHPUnit completa morria com `Cannot redeclare dataFileNameFromTable()` | Os testes da req-202/203 carregavam o compilador no processo da suíte; ele declara `main()` e `dataFileNameFromTable()`, como o sincronizador | Testes que carregam o compilador rodam em processo próprio |

## Testes

- `Req206InsertOnlyNaturalKeyTest`: a semente não regrava o administrador que já existe, ainda insere o que falta, e sem `insert_only` a chave natural continua atualizando.
- `Req206ProjectSeedsTest`: projeto sem semente não recebe os dados do core; projeto com semente compila só as próprias; no core as oito tabelas seguem compiladas; escrita de JSON igual não muda a data do arquivo; os dois scripts copiam `db/data` por conteúdo.
- `ProjectSshDeployReq034Test`: o sync do core passa a ter quatro chamadas `rsync`, todas pelo helper protegido.

## Validação

- PHPUnit (Lab, PHP 8.5): 1380 testes, 1 falha — `ProjectSshDeployReq034Test::testBibliotecaDeTransporteExisteEEhSintaticamenteValida`, fim de linha CRLF do checkout Windows lido pelo WSL, anterior a este lote.
- Vitest: 461/461.
- Lab (`conn2flow-site-local`), banco consultado antes e depois:
  - `project:update-all`: saída 0; 68 módulos ativos por idioma, permissões dos perfis `cloud-*` e usuários idênticos; os `*Data.json` das oito tabelas no projeto não foram regravados.
  - `updates-manager-database.sh --tables usuarios --force-all`: `SKIP_UPDATE_INSERT_ONLY tabela=usuarios chave=administrador`, `+0 ~0 =1`.
  - `/`, `/noticias/`, `/docs/` e `/pro/` respondem 200.

## Ocorrência durante a validação

A primeira rodada do pipeline deste lote, feita antes da correção do `insert_only`, sobrescreveu de novo o administrador do Lab. Login e e-mail foram devolvidos a `contact@conn2flow.com`; a senha ficou sendo a da semente, porque o log do sincronizador trunca o hash anterior e não há backup. O sincronizador roda sem `--backup` no pipeline de projeto: vale avaliar ligar o backup de `usuarios` por padrão.

## Pendências

- Homologação humana.
- `main()` e `dataFileNameFromTable()` duplicadas entre compilador, sincronizador e atualizador de plugin: os scripts não rodam juntos em produção, mas a duplicata obriga o isolamento dos testes.
