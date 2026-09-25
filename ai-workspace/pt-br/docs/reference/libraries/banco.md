---
title: "Biblioteca banco.php"
description: "A camada de banco de dados do Gestor (MySQLi): conexão, consultas, SELECT estruturado, INSERT/UPDATE acumulados, identificadores únicos e o modo distribuído."
section: reference
order: 10
sources:
  - gestor/bibliotecas/banco.php
  - gestor/config.php
  - gestor/bibliotecas/modulo-distribuido.php
verified_at: a6e51e29
---

# Biblioteca `banco.php`

`banco.php` é a camada de acesso a dados de todo o Gestor. Ela sempre vem carregada (`$_GESTOR['bibliotecas']`) e fala com **MySQL/MariaDB por MySQLi**, montando SQL por concatenação de strings. Não há *prepared statements*: a segurança depende de você escapar cada valor com `banco_escape_field()`.

> [!NOTE]
> O cabeçalho do arquivo e algumas mensagens dizem que ela está *deprecated* em favor de `banco-v2.php`. Isso é **resíduo**. A `banco-v2` (*prepared statements*, sintaxe do PHP 8.5) foi um experimento da linha 3.0.x e foi **removida da linha 2.x** no req-108 (commit `2c9f7a358`). Na versão atual, `banco.php` é a camada oficial e a única disponível.

## Configuração e conexão

A conexão usa `$_BANCO`, preenchido em `gestor/config.php` a partir do `.env`:

| Chave | Variável do `.env` | Padrão |
|---|---|---|
| `tipo` | `DB_CONNECTION` | `mysqli` (o único tipo implementado) |
| `host` | `DB_HOST` | `localhost` |
| `nome` | `DB_DATABASE` | — |
| `usuario` / `senha` | `DB_USERNAME` / `DB_PASSWORD` | — |

- A conexão é **preguiçosa**. `banco_query()` e `banco_escape_field()` chamam `banco_conectar()` na primeira vez; não é preciso conectar à mão.
- `banco_conectar()` liga `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`, abre a conexão e define o charset `utf8`. Se a conexão falhar, **encerra a requisição com `die()`**, imprimindo o erro e a pilha gerada por `banco_erro_debug()` (HTML com `arquivo:linha => função` de cada quadro).
- `banco_fechar_conexao()` fecha e remove `$_BANCO['conexao']`.
- `banco_ping()` testa a conexão com `mysqli_ping()` e, se ela caiu, só incrementa `$_BANCO['RECONECT']`: **não reconecta**.

> [!WARNING]
> O charset da conexão é `utf8` (utf8mb3 no MySQL), que não aceita emojis e outros caracteres de 4 bytes. Veja a skill `c2f-mysql-utf8-emoji-encoding` antes de gravar texto livre de usuário.

## Executar SQL

- `banco_query($query)` executa qualquer SQL. Com `$_BANCO['distribuido']` ativo, **não usa o banco local**: repassa a instrução a `banco_distribuido_query()` (módulos distribuídos, req-005), que devolve um `BancoResultadoRemoto` para SELECT ou `true`/`false` para escrita. Localmente, uma `mysqli_sql_exception` é capturada, registrada com `error_log()` (junto com o SQL) e vira `false`. Nenhum erro chega à tela.
- `banco_sql($sql)` executa e devolve todas as linhas com **índices numéricos e nomes** (`mysqli_fetch_array`), ou `null` sem resultados.
- `banco_sql_names($sql, $campos)` executa e devolve linhas associativas usando os nomes de `$campos` (lista separada por vírgulas, ou `*`).
- `banco_linhas_afetadas()` devolve quantas linhas a última escrita alterou (ou `null` no modo distribuído). Serve para "só a primeira requisição reivindica a ação": `UPDATE … WHERE <ainda não feito>` afeta 1 linha para quem chegou primeiro e 0 para as outras.
- `banco_last_id()` devolve o `AUTO_INCREMENT` do último INSERT (no modo distribuído, o id informado pela instalação remota).

### Lendo um resultado cru

Os wrappers aceitam um `mysqli_result` ou um `BancoResultadoRemoto`:
- `banco_num_rows($result)` devolve 0 para `false`. Evita o `TypeError` do PHP 8.1+ quando a consulta falhou;
- `banco_num_fields($result)`, `banco_field_name($result, $i)` e `banco_fields_names($tabela)`: o último faz `SELECT * … LIMIT 1` e devolve os nomes das colunas, ou `null` se não houver colunas;
- `banco_row($result)` devolve uma linha indexada;
- `banco_row_array($result)` devolve uma linha indexada e associativa;
- `banco_fetch_assoc($result)` devolve uma linha associativa.

## SELECT estruturado: `banco_select()`

É a forma recomendada de consultar:

```php
$paginas = banco_select(Array(
    'tabela' => 'paginas',
    'campos' => Array('id', 'nome', 'caminho'),
    'extra'  => "WHERE status='A' AND language='".banco_escape_field($lang)."' ORDER BY nome",
));
// [ ['id' => 'x', 'nome' => 'X', 'caminho' => 'x/'], ... ]  ou  null
```

- `campos` é um array (unido por vírgula) ou uma string, ou `*`.
- `extra` recebe tudo o que vem depois do `FROM` (JOIN, WHERE, ORDER BY, LIMIT). A tabela pode ter apelido: `'tabela' => 'paginas AS p'`.
- `unico` devolve só a primeira linha como array simples, em vez de uma lista.
- Sem resultado, o retorno é **`null`**, e não array vazio.

> [!IMPORTANT]
> **As chaves do resultado são o texto exato dos campos pedidos.** Com `'campos' => ['p.id', 'p.nome']`, as chaves são `'p.id'` e `'p.nome'` (é o que os widgets de menus fazem). Os nomes vêm de um `explode(',')` dos campos, e não do banco. Expressões com vírgula, como `CONCAT(a,b)`, desalinham o mapeamento. Use `AS` sem vírgula, ou `banco_sql()`.

> [!WARNING]
> O modo de linha única é ativado por `isset($unico)`. Então **`'unico' => false` também devolve uma linha só**. Para receber a lista, omita a chave.

Variantes com assinatura posicional, usadas pelo código legado:
- `banco_select_name($campos, $tabela, $extra)` devolve linhas associativas (valores não numéricos convertidos para string);
- `banco_select_editar($campos, $tabela, $extra)` devolve **só a primeira linha** e grava `$_GESTOR['banco-resultado']` (`true`/`false`).

### Guardar o "antes" de uma edição

O padrão de histórico dos CRUDs:

1. `banco_select_campos_antes_iniciar($campos, $tabela, $extra)` lê o registro e guarda a primeira linha em `$_GESTOR['banco-antes']` (devolve `true`/`false`).
2. `banco_select_campos_antes($campo)` devolve o valor antigo de um campo (ou `null`) para comparar com o `$_REQUEST` e montar o histórico com `interface_historico_incluir()`.

## Escrever

### INSERT

O jeito mais usado é acumular campos e inserir de uma vez:

```php
banco_insert_name_campo('id', $id);                 // escapa e põe aspas
banco_insert_name_campo('versao', '1', true);       // sem aspas (número, NOW(), NULL)
banco_insert_name_campo('html', $html, false, false); // sem escapar (já escapado)
banco_insert_name(banco_insert_name_campos(), 'paginas');
```

- `banco_insert_name_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)` acumula em `$_BANCO['insert-name-campos']`, escapando o valor por padrão.
- `banco_insert_name_campos()` devolve os campos acumulados e **limpa** a lista.
- `banco_insert_name($dados, $tabela)` recebe `[[nome, valor, sem_aspas], …]` e monta `INSERT INTO t (…) VALUES (…)`. **Não escapa**: ou os valores vieram de `banco_insert_name_campo()`, ou você escapa antes (como fazem os módulos com `banco_escape_field($_REQUEST[...])`). Itens sem nome ou sem valor são ignorados.
- `banco_insert_name_varios(['tabela' => …, 'campos' => [['nome' => …, 'valores' => [...], 'sem_aspas_simples' => true], …]])` faz um INSERT de várias linhas, organizado **por coluna**. `null` vira `NULL`. Não escapa.

Funções legadas **sem chamadores no core**, que dependem da ordem física das colunas e não escapam nada:
- `banco_insert($campos, $tabela)`: `VALUES('0', …)`;
- `banco_insert_tudo($campos, $tabela)` e `banco_insert_id($campos, $tabela)`: `VALUES(…)`;
- `banco_insert_varios($campos, $tabela)` e `banco_insert_varios_tudo($campos, $tabela)`: várias linhas. As duas concatenam numa variável não inicializada (*warning* no PHP 8).

### UPDATE

- `banco_update($campos, $tabela, $extra)` executa `UPDATE t SET <campos> <extra>`. `$campos` é a string pronta (`"nome='x', versao=versao+1"`).
- `banco_update_campo($nome, $valor, $sem_aspas_simples = false, $escape_field = true)` acumula em `$_BANCO['update-campos']`, e `banco_update_executar($tabela, $extra = '')` executa e limpa o acumulado. Sem campos acumulados, não faz nada.
- `banco_update_varios($campos, $tabela, $campo_nome, $id_nome)` atualiza muitos registros com `CASE id WHEN … THEN …`, quebrando em várias consultas acima de ~1 MB. Os valores **não são escapados**, e a função concatena em `$sql_fechar` sem inicializá-la. Sem chamadores no core.

### Inserir ou atualizar

`banco_insert_update(['tabela' => ['nome' => …, 'id' => …, 'extra' => …], 'dados' => [...], 'dadosTipo' => [...]])` procura o registro por `dados[<id>]`: se existe faz UPDATE, senão faz INSERT. Com `dadosTipo`, `bool` grava `1`/`NULL` e `int` grava o número sem aspas (vazio vira `NULL`); o resto é escapado e vai com aspas. **Não filtra por idioma**, nem ao procurar nem ao atualizar.

### DELETE

- `banco_delete($tabela, $extra)` executa `DELETE FROM t <extra>`. Sem `WHERE` no `$extra`, apaga a tabela inteira.
- `banco_delete_varios($tabela, $campo_ids, $array_ids)` monta `DELETE … WHERE campo IN (…)`.

> [!CAUTION]
> `banco_delete_varios()` chama `count()` sobre `$campo_ids` antes de saber se é array. Com um nome de campo em string, o PHP 8 lança `TypeError`. Também não escapa os ids. Não tem chamadores no core: não use.

## Identificadores (slugs) únicos

Os CRUDs geram o `id` textual do registro a partir do nome:

```php
$id = banco_identificador(Array(
    'id' => $_REQUEST['nome'],
    'tabela' => Array(
        'nome' => 'paginas', 'campo' => 'id', 'id_nome' => 'id_paginas',
        'where' => "language='".$_GESTOR['linguagem-codigo']."'",
    ),
));
```

1. `banco_identificador()` normaliza o texto com `banco_retirar_acentos()`, corta em ~90 caracteres (por palavra) e, se o texto já termina em `-<número>`, parte desse número.
2. `banco_identificador_unico()` procura `id`, `id-1`, `id-2`… até achar um livre, ignorando registros com `status='D'`. Opções de `tabela`:
   - `id_valor` exclui o próprio registro, na edição;
   - `status` define outra coluna de status;
   - `sem_status` desliga o filtro de status;
   - `where` adiciona uma condição.
   Com `sem_traco`, remove os hífens do resultado.

> [!CAUTION]
> Ao escolher um id livre, `banco_identificador_unico()` **apaga fisicamente** (`DELETE`) o registro soft-deleted (`status='D'`) que tiver o mesmo id, para não bater em índice `UNIQUE`. Um registro "na lixeira" some quando outro nasce com o mesmo nome.

`banco_retirar_acentos($var, $retirar_espaco = true)` troca acentos por ASCII, remove pontuação, troca parênteses e colchetes por hífen e espaços por hífen (opcional), e colapsa hífens.

> [!WARNING]
> `banco_retirar_acentos()` aplica `strtolower()` **antes** de trocar os acentos, e `strtolower()` não afeta letras multibyte. Maiúsculas acentuadas viram ASCII **maiúsculo**: `"MIGRAÇÕES"` resulta em `"migraCOes"`. É daí que vêm ids com letras maiúsculas no meio.

## Utilitários

- `banco_escape_field($field)` escapa com `mysqli_real_escape_string()`, conectando se preciso. Sem conexão MySQLi ativa, **lança `LogicException`** em vez de devolver o valor sem escape.
- `banco_campos_virgulas($campos)` une um array com vírgulas (`''` para vazio).
- `banco_total_rows($tabela, $extra = null)` executa `SELECT count(*)` e devolve o total. Sem chamadores no core.
- `banco_campos_nomes($tabela)` executa `SHOW COLUMNS` e devolve os metadados (`Field`, `Type`, `Null`, `Key`, `Default`, `Extra`) de cada coluna.
- `banco_campo_existe($campo, $tabela)` diz se a coluna existe. Os módulos usam para funcionar antes e depois de uma migration (por exemplo, `css_source_hash`).
- `banco_tabelas_lista()` executa `SHOW TABLES` e devolve os nomes.
- `banco_smartstripslashes($str)` só converte para string. É mantida por compatibilidade; não use em código novo.

## Veja também

- [Bibliotecas do Gestor](index.md)
- Skills `c2f-database-operations`, `c2f-database-testing` e `c2f-mysql-utf8-emoji-encoding`.

## Funções (referência gerada)

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/banco.php` por `c2f docs:extract` — 47 funções. Não edite dentro deste bloco.

- `banco_escape_field(string $field): string` — [linha 30](../../../../../gestor/bibliotecas/banco.php#L30)
- `banco_smartstripslashes(mixed $str): string` — [linha 57](../../../../../gestor/bibliotecas/banco.php#L57)
- `banco_erro_debug(): string` — [linha 69](../../../../../gestor/bibliotecas/banco.php#L69)
- `banco_conectar(): void` — [linha 95](../../../../../gestor/bibliotecas/banco.php#L95)
- `banco_ping(): void` — [linha 121](../../../../../gestor/bibliotecas/banco.php#L121)
- `banco_fechar_conexao(): void` — [linha 142](../../../../../gestor/bibliotecas/banco.php#L142)
- `banco_query(string $query): mysqli_result|bool` — [linha 165](../../../../../gestor/bibliotecas/banco.php#L165)
- `banco_linhas_afetadas(): int|null` — [linha 206](../../../../../gestor/bibliotecas/banco.php#L206)
- `banco_num_rows(mixed $result): int` — [linha 228](../../../../../gestor/bibliotecas/banco.php#L228)
- `banco_num_fields(mysqli_result $result): int` — [linha 255](../../../../../gestor/bibliotecas/banco.php#L255)
- `banco_field_name(mysqli_result $result, int $num_field): string` — [linha 276](../../../../../gestor/bibliotecas/banco.php#L276)
- `banco_fields_names(string $table): array|null` — [linha 299](../../../../../gestor/bibliotecas/banco.php#L299)
- `banco_row(mysqli_result $result): array|null` — [linha 330](../../../../../gestor/bibliotecas/banco.php#L330)
- `banco_row_array(mysqli_result $result): array|null` — [linha 350](../../../../../gestor/bibliotecas/banco.php#L350)
- `banco_fetch_assoc(mysqli_result $result): array|null` — [linha 370](../../../../../gestor/bibliotecas/banco.php#L370)
- `banco_sql(string $sql): array|null` — [linha 391](../../../../../gestor/bibliotecas/banco.php#L391)
- `banco_sql_names(string $sql, string $campos): array|null` — [linha 419](../../../../../gestor/bibliotecas/banco.php#L419)
- `banco_select(array|false $params = false): array|null` — [linha 471](../../../../../gestor/bibliotecas/banco.php#L471)
- `banco_select_name(string $campos, string $tabela, string $extra): array|null` — [linha 542](../../../../../gestor/bibliotecas/banco.php#L542)
- `banco_select_editar(string $campos, string $tabela, string $extra): array|null` — [linha 599](../../../../../gestor/bibliotecas/banco.php#L599)
- `banco_select_campos_antes_iniciar(string $campos, string $tabela, string $extra): bool` — [linha 664](../../../../../gestor/bibliotecas/banco.php#L664)
- `banco_select_campos_antes(string $campo): mixed|null` — [linha 724](../../../../../gestor/bibliotecas/banco.php#L724)
- `banco_update(string $campos, string $tabela, string $extra): void` — [linha 750](../../../../../gestor/bibliotecas/banco.php#L750)
- `banco_update_campo(string $nome, string $valor, bool $sem_aspas_simples = false, bool $escape_field = true): void` — [linha 776](../../../../../gestor/bibliotecas/banco.php#L776)
- `banco_update_executar(string $tabela, string $extra = ''): void` — [linha 806](../../../../../gestor/bibliotecas/banco.php#L806)
- `banco_update_varios(array $campos, string $tabela, string $campo_nome, string $id_nome): void` — [linha 845](../../../../../gestor/bibliotecas/banco.php#L845)
- `banco_insert(string $campos, string $tabela): void` — [linha 882](../../../../../gestor/bibliotecas/banco.php#L882)
- `banco_insert_name(array $dados, string $tabela): void` — [linha 898](../../../../../gestor/bibliotecas/banco.php#L898)
- `banco_insert_name_campo(string $nome, string $valor, bool $sem_aspas_simples = false, bool $escape_field = true): void` — [linha 940](../../../../../gestor/bibliotecas/banco.php#L940)
- `banco_insert_name_campos(): array` — [linha 967](../../../../../gestor/bibliotecas/banco.php#L967)
- `banco_insert_name_varios(array|false $params = false): void` — [linha 994](../../../../../gestor/bibliotecas/banco.php#L994)
- `banco_insert_varios(array $campos, string $tabela): void` — [linha 1064](../../../../../gestor/bibliotecas/banco.php#L1064)
- `banco_insert_varios_tudo(array $campos, string $tabela): void` — [linha 1091](../../../../../gestor/bibliotecas/banco.php#L1091)
- `banco_insert_id(string $campos, string $tabela): void` — [linha 1118](../../../../../gestor/bibliotecas/banco.php#L1118)
- `banco_insert_tudo(string $campos, string $tabela): void` — [linha 1133](../../../../../gestor/bibliotecas/banco.php#L1133)
- `banco_last_id(): int|null` — [linha 1147](../../../../../gestor/bibliotecas/banco.php#L1147)
- `banco_delete(string $tabela, string $extra): void` — [linha 1166](../../../../../gestor/bibliotecas/banco.php#L1166)
- `banco_delete_varios(string $tabela, array|string $campo_ids, array $array_ids): void` — [linha 1183](../../../../../gestor/bibliotecas/banco.php#L1183)
- `banco_campos_virgulas(array $campos): string` — [linha 1222](../../../../../gestor/bibliotecas/banco.php#L1222)
- `banco_total_rows(string $tabela, string|null $extra = null): int` — [linha 1250](../../../../../gestor/bibliotecas/banco.php#L1250)
- `banco_campos_nomes(string $tabela): array` — [linha 1272](../../../../../gestor/bibliotecas/banco.php#L1272)
- `banco_campo_existe(string $campo, string $tabela): bool` — [linha 1304](../../../../../gestor/bibliotecas/banco.php#L1304)
- `banco_retirar_acentos(string $var, bool $retirar_espaco = true): string` — [linha 1332](../../../../../gestor/bibliotecas/banco.php#L1332)
- `banco_identificador_unico(array|false $params = false): string` — [linha 1376](../../../../../gestor/bibliotecas/banco.php#L1376)
- `banco_identificador(array|false $params = false): string` — [linha 1448](../../../../../gestor/bibliotecas/banco.php#L1448)
- `banco_insert_update(array|false $params = false): void` — [linha 1527](../../../../../gestor/bibliotecas/banco.php#L1527)
- `banco_tabelas_lista(): array` — [linha 1628](../../../../../gestor/bibliotecas/banco.php#L1628)

<!-- c2f:extract:end -->
