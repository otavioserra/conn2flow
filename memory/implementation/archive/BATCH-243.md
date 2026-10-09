# BATCH-243 — Tabelas JSON particionadas

- Requisição: [req-234](../../human-requests/archive/req-234.md)
- Projeto: `conn2flow`
- Raiz de implementação: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req234`
- Branch: `feat/req-234`, derivada de `main` (`3e2ad2e9`)
- Autonomia: `autonomo_monitorado`
- Status: `in-review`
- Data: 2026-10-05

## Escopo e contrato de validação

Implementar armazenamento por registros completos, limite padrão de 80 MiB, manifesto validado antes de consumo e leitura integral antes da sincronização SQL. Preservar arquivos monolíticos para tabelas menores e a ordenação recebida do compilador. Integrar geração, checksums, exportação reversa, recuperação e ZIP, sem alterações de UI.

- [x] Isolar worktree e ler briefing/governança.
- [x] Biblioteca compartilhada, escrita segura e limpeza nas transições.
- [x] Integrações do compilador, sincronizador, recuperação e ZIP.
- [x] PHPUnit: round-trip, corrupção/ausência, manifesto inválido, transições, limites, checksum e sincronização sem retirada entre partes.
- [x] Lint PHP e `git diff --check`.
- [x] Revisão do diff e evidências.
- [x] Commit e push na branch isolada `feat/req-234`.

## Evidências

MCP Hub não exposto nesta sessão; operações locais usaram o terminal do workspace. Implementação e testes restritos ao worktree; nenhum dado gerado, CSS, JS ou metadado de outro lote foi incluído.

### Implementação

- `gestor/bibliotecas/db-data.php`: quatro funções públicas requeridas, helpers de descoberta de arquivos para ZIP, checksum lógico por tabela e ordenação da exportação. Leitura monolítica retrocompatível, incluindo BOM e nomes legados snake_case. Manifesto tem precedência sobre monolítico antigo.
- Cada parte tem até 83.886.080 bytes, incluindo colchetes, vírgulas, espaços e quebras de linha. Nunca divide registros. Um registro isolado maior que o limite é rejeitado antes de substituir a entrega anterior.
- Todas as partes temporárias são gravadas antes da primeira publicação. Escritor e leitores cooperam por `flock` de diretório, em arquivo de lock no diretório temporário do sistema. Escritas/renames conferem retorno e tentam novamente; manifesto é publicado e validado antes da limpeza. Conteúdo igual mantém bytes, timestamp de geração e mtime dos arquivos.
- Compilador relê tabelas fixas/dinâmicas/cron pelo leitor comum, escreve dados pelo gravador e registra `partitioned`/`total_parts` no contrato SQL. A ordenação recebida do compilador é preservada.
- Sincronizadores do core e plugins descobrem tabelas únicas, validam integridade antes das mutações e entregam o conjunto completo à comparação. Checksums mantêm a chave histórica `PascalCaseData.json`, mas consideram todas as partes e ignoram `generated_at`. Backups opcionais e seleção de tabelas no modo reverso também descobrem manifestos.
- Exportação reversa usa o gravador, ordena por chave natural/PK e propaga falhas em vez de produzir ZIP incompleto. Recuperação SQL em CLI descobre tabelas particionadas. API empacota apenas arquivos de tabelas validadas, incluindo manifesto e partes.

### Validação executada (Linux / WSL `Conn2Flow-Lab`, PHP 8.5.10)

Dependências PHPUnit instaladas com `composer install --no-interaction --prefer-dist` no worktree. O Composer instalado emite deprecações próprias no PHP 8.5; instalação terminou com código 0. Nenhuma dependência de aplicação foi alterada.

```text
php vendor/bin/phpunit -c phpunit.xml \
  tests/Unit/PHP/DbDataTest.php \
  tests/Unit/PHP/DbDataCompilerTest.php \
  tests/Unit/PHP/DbDataIntegrationTest.php \
  tests/Unit/PHP/DbDataPluginTest.php \
  tests/Unit/PHP/RecuperacaoDadosRecursosTest.php \
  tests/Unit/PHP/Req202ResourcesSyncTest.php \
  tests/Unit/PHP/Req206InsertOnlyNaturalKeyTest.php \
  tests/Unit/PHP/ForcarAtualizacaoTest.php \
  tests/Unit/PHP/RecursosRetiradaTest.php
```

- Suite focada: **60 testes, 277 assertions**, todos aprovados. As quatro classes novas cobrem 24 cenários, incluindo um dataset real de 82 MiB e o compilador com 84 MiB de HTML.
- SQLite em memória: fotografia SQL antes/depois da comparação, nenhuma retirada falsa, ausência de parte aborta antes de alterações; controle negativo comprova que entregar somente a última parte produziria retirada indevida. Somente descoberta MySQL de tabelas/colunas é adaptada no driver de teste.
- ZIP real: exportação SQL, criação, extração e descompilação preservam seis recursos e seus metadados, inclusive registros além da primeira parte.
- `php -l` dos 12 arquivos PHP alterados/adicionados e `git diff --check`: aprovados.
- Suíte geral inicial: **1.571 testes executados, 14.705 assertions, 3 erros, 2 falhas, 4 skipped**. Baseline isolado de `3e2ad2e9`: **1.549 testes executados, 14.615 assertions, os mesmos 3 erros, 2 falhas e 4 skipped**. A execução inicial geral antecedeu os dois últimos cenários focados. Falhas reproduzidas sem a implementação:
  - `StripeAssinaturaCupomTest`, `StripeAssinaturaItensAvulsosTest`, `StripePaymentIntentInstallmentsTest`: falha no setup (assertion `0 === 1`).
  - `CssRegeneracaoTest::testCssRebuildReconheceCheckoutCoreEInstalacaoPlana`: comparação textual com LF contra checkout CRLF.
  - `ProjectSshDeployReq034Test::testBibliotecaDeTransporteExisteEEhSintaticamenteValida`: Bash rejeita `project-transport.sh` em CRLF.

### Revisão e limites

Revisão própria via `review-current-batch`: corrigidos logs de plugin que ainda referenciavam a antiga lista de arquivos, a interpolação PHP obsoleta no mesmo controlador e a ausência de ordenação explícita no exportador. Não restam findings conhecidos no escopo validado.

Não houve deploy, acesso ao banco principal ou validação HTTP autenticada do endpoint de recuperação. O caminho de ZIP usado pela API foi exercitado com `ZipArchive` real, PDO temporário e recuperação em disco. A validação do compilador usou diretório temporário, sem regenerar os dados da árvore compartilhada (cujo `PaginasData.json` foi alterado por outros lotes). O baseline de `main` usado aqui tem aproximadamente 4 MiB nessa tabela.

Publicação de múltiplos arquivos usa lock cooperativo, não rollback de geração: interrupção após substituir partes pode deixar o manifesto anterior incompatível; o leitor aborta por integridade e exige regeneração, evitando consumo parcial no sincronizador. O gravador recebe a tabela inteira, conforme o contrato; não oferece streaming de consulta SQL.

Pendente somente revisão humana e integração em `main`; a requisição humana e os artefatos normativos foram preservados.
