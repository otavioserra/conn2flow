# BATCH-184: Migração do Acervo de Documentação — Onda 1 (fundação) e seguintes do primeiro agente

Execução da [req-179](../human-requests/req-179.md). Os módulos estão com o segundo agente: [req-180](../human-requests/req-180.md) / [BATCH-185](BATCH-185.md).

## Progresso

| Doc (pt-br + en) | Legado removido | Achados do código |
|---|---|---|
| `reference/libraries/index.md` | — | O registro `bibliotecas-dados` tem `api-cliente` e `cpanel` sem arquivo (fatal se pedidos). `gestor_incluir_bibliotecas()` não valida nomes. |
| `reference/libraries/variaveis.md` | `BIBLIOTECA-VARIAVEIS` / `LIBRARY-VARIABLES` | Biblioteca sem chamadores; nenhuma variável `_sistema` nos dados; SQL sem escape de `grupo`/`id`; sem filtro de idioma; `atualizar` não atualiza o cache. A doc antiga a apresentava como sistema de configuração, com casos de uso inventados. |
| `reference/libraries/pagina.md` | `BIBLIOTECA-PAGINA` / `LIBRARY-PAGE` | Só `pagina_celula()` tem uso (`perfil-usuario`). O `strtolower()` de mascarar/desmascarar atua no padrão, não no nome. |
| `reference/libraries/banco.md` | `BIBLIOTECA-BANCO` / `LIBRARY-DATABASE` / `BANCO-V2-DOCS` | A `banco-v2` foi removida da linha 2.x (req-108), e os `@deprecated` são resíduo. `'unico' => false` ainda devolve uma linha só. `banco_retirar_acentos()` gera maiúsculas (`migraCOes`). `banco_identificador()` faz DELETE físico de registros `status='D'`. `banco_delete_varios()` dá TypeError. 22 funções sem chamadores. |
| `reference/libraries/gestor.md` | `BIBLIOTECA-GESTOR` / `LIBRARY-MANAGER` | `gestor_variaveis_alterar()` sem escape do valor; `gestor_variaveis_globais()` ignora o módulo; `gestor_layout()` sem escape e sem filtro de status; `gestor_componente()` sem filtro de status; sessões limpas em 1 de cada 51 requisições; `existe('0')` é true. |

| `guides/installation.md` (antecipado a pedido do Humano) | `CONN2FLOW-INSTALADOR-DETALHADO`, `CONN2FLOW-ADAPTACAO-POS-INSTALACAO` (+ en) | A doc antiga descrevia `checkSystemRequirements()`/`runMigrations()`/`runSeeds()`/`install()`, que **não existem**. O instalador não valida a versão do PHP nem as extensões. `releases/latest` do GitHub aponta para o `gestor-v*` (as duas séries usam `make_latest: true`), então o link "sempre o mais recente" usa a API filtrando `instalador-v*`: JS no template `docs-article` + comando testado. O fallback do instalador é fixo em `gestor-v2.10.1`. `CAPTCHA_PROVIDER` para Turnstile é `cloudflare-turnstile`. `getGestorPath()` tem `. DIRECTORY_SEPARATOR + 'gestor'` (TypeError, só no ramo sem `install_path`). O `PHP85-INSTALL-GUIDE` (en) fica para `guides/development-environment` (onda 4). |

| `reference/libraries/interface.md` | `BIBLIOTECA-INTERFACE` / `LIBRARY-INTERFACE` | **Segurança (req-181):** SQL injection em `interface_listar_ajax()` (busca, colunas e ordenação vindas do `$_REQUEST`); excluir/status por GET sem CSRF; nome de coluna controlável em `verificar-campo`. A regex de `email-obrigatorio` rejeita e-mail que começa com dígito, com maiúsculas ou com `+`. `texto-obrigatorio` conta bytes. `$id` indefinido em `editar`/`alteracoes_finalizar` (inócuo). O docblock de `interface_verificar_campos` estava errado. |

## Ferramental ajustado no caminho

- `docs:audit` não trata `reference/libraries/index.md` como biblioteca.
- O extrator ignora `@param tipo $params['chave']` (chave de array), que antes sobrescrevia o tipo real do parâmetro.

## Validação

- `docs:audit --json`: 0 erros. Os avisos restantes são só `missing:` (cobertura pendente).
- PHPUnit das ferramentas de docs: 12/12.
