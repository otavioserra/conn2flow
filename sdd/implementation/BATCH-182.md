# BATCH-182: Contrato de Documentação, `docs:audit`, `docs:extract` e Piloto Bilíngue

Execução da [req-177](../human-requests/req-177.md). FEAT-014, fase 1.

## Atividades

- [x] **Contrato:** taxonomia `guides/ concepts/ reference/{libraries,modules,api,cli,hooks}/ whats-new/`, com pastas e chaves em inglês e idênticas nos dois idiomas. O frontmatter `title/description/section/order/visibility/module/sources/verified_at` está normatizado na req e documentado em `ai-workspace/*/docs/guides/documentation.md`.
- [x] **`cli/src/Support/Docs/`:**
  - `Frontmatter`: subconjunto de YAML, sem dependência;
  - `PhpFunctionExtractor`: tokenizer que pula corpos de classe e de função e aceita funções dentro de `if (!function_exists())`;
  - `LibraryReference`: bloco `c2f:extract`, em lista, porque union types quebram tabela Markdown;
  - `DocsTree`;
  - `DocsAuditor`: git injetável para testes.
- [x] **`c2f docs:audit`** (`--limit`, `--json`, `--strict`, `--source`) e **`c2f docs:extract`** (`<lib>|--all`, `--check`, `--create`), registrados em `Application.php`.
- [x] **Piloto (lido do código, pt-br e en):**
  - `index.md`;
  - `guides/documentation.md`;
  - `reference/libraries/modelo.md`;
  - `reference/modules/menus.md`.
- [x] PHPUnit: `tests/Unit/PHP/DocsToolingReq177Test.php`, com 8 testes e 34 asserções.

## Achados do código registrados nas docs

1. `modelo_input_in()` chama `paginaTrocaVarValor()`, que **não existe** no core. Seria erro fatal, mas não há chamadores. `modelo_abrir()` também está sem chamadores.
2. A forma de array de `modelo_var_troca()`/`modelo_var_troca_tudo()` usa a chave **literalmente**. O docblock diz "sem #", o que está errado.
3. Todas as funções de bloco de `modelo.php` atuam só na primeira ocorrência, e a tag de fechamento é buscada desde o início do texto.
4. `hooks.php` e `modulo-distribuido.php` têm métodos de classe que o extrator (funções globais) não cobre. Fica para a onda que documentar essas bibliotecas.

## Validação

- `php cli/c2f.php docs:audit --json`: as 8 docs piloto com score 0 e 0 erros. Os 72 avisos restantes são lacunas de cobertura (44 bibliotecas e 28 módulos sem doc). Há 125/126 docs legadas.
- `php cli/c2f.php docs:extract --all --check`: exit 0.
- Contagem do extrator igual ao `grep` nas 44 bibliotecas, exceto as duas com classe (esperado).
- `vendor/bin/phpunit --filter DocsToolingReq177Test`: 8/8. As 2 *deprecations* do PHPUnit são preexistentes (`ProjectSshDeployReq034Test`, `TwoFactorTest`).
