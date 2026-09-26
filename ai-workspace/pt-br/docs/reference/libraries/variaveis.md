---
title: "Biblioteca variaveis.php"
label: "Variáveis (legado)"
description: "Leitura e gravação de variáveis de sistema (módulo _sistema) na tabela variaveis — biblioteca sem uso no core, distinta do sistema de textos gestor_variaveis()."
section: reference
order: 100
sources:
  - gestor/bibliotecas/variaveis.php
  - gestor/config.php
  - gestor/gestor.php
verified_at: a6e51e29
---

# Biblioteca `variaveis.php`

A `variaveis.php` lê e grava **variáveis de sistema**: registros da tabela `variaveis` com `modulo = '_sistema'`, organizados por `grupo`.

> [!IMPORTANT]
> Não confunda com o sistema de textos e rótulos do Gestor. Os textos de interface (`form-name-label`, mensagens, alertas) são lidos por `gestor_variaveis()`, que está em `gestor.php` e já vem carregada. É ela que os módulos usam. A `variaveis.php` é outra coisa e hoje **não tem chamadores no core**, e os dados do core (`gestor/db/data/VariaveisData.json`) não trazem nenhuma variável `_sistema`.

A biblioteca é registrada como `variaveis` em `$_GESTOR['bibliotecas-dados']`. O único ponto do core que a inclui é o seletor de idioma de `gestor.php`, e mesmo ali as funções usadas são as de `gestor_variaveis()`: a inclusão é desnecessária.

## Funções

- `variaveis_sistema($grupo, $id = false)` carrega **todas** as variáveis do grupo numa única consulta e as guarda no cache global `$_VARIAVEIS_SISTEMA[$grupo]`. Com `$id`, devolve o valor (ou `null` se não existir). Sem `$id`, devolve o array `id => valor` do grupo (vazio se não houver nada).
- `variaveis_sistema_incluir($grupo, $id, $valor, $tipo = 'string')` cria a variável só se ainda não existir (não atualiza a existente). Um `$valor` `null` é gravado como `NULL`.
- `variaveis_sistema_atualizar($grupo, $id, $valor)` faz o `UPDATE` do valor (`null` grava `NULL`).

## Cuidados

> [!WARNING]
> `$grupo` e `$id` entram **sem escape** nas três funções (só o valor de `variaveis_sistema_atualizar()` passa por `banco_escape_field()`, e o de `variaveis_sistema_incluir()` pelo `banco_insert_name_campo()`). Nunca passe dado vindo do usuário nesses parâmetros.

- As consultas **não filtram por `language`**. Se a mesma variável existir em mais de um idioma, `variaveis_sistema()` fica com o último registro lido.
- `variaveis_sistema_atualizar()` **não atualiza o cache**: na mesma requisição, `variaveis_sistema()` continua devolvendo o valor antigo. A função declara `global $_VARIAVEIS_id`, que não é usado.

## Veja também

- [Bibliotecas do Gestor](index.md): registro e carregamento.

## Funções (referência gerada)

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/variaveis.php` por `c2f docs:extract` — 3 funções. Não edite dentro deste bloco.

- `variaveis_sistema(string $grupo, string|false $id = false): string|array|null` — [linha 41](../../../../../gestor/bibliotecas/variaveis.php#L41)
  Retorna variável(is) do sistema.
  Parâmetros:
  - `$grupo`: Grupo da variável (obrigatório).
  - `$id`: ID específico da variável (opcional).
  Retorno: Se $id fornecido, retorna o valor da variável específica.
- `variaveis_sistema_incluir(string $grupo, string $id, string $valor, string $tipo = 'string'): void` — [linha 88](../../../../../gestor/bibliotecas/variaveis.php#L88)
  Inclui uma nova variável do sistema.
  Parâmetros:
  - `$grupo`: Grupo da variável (obrigatório).
  - `$id`: ID da variável (obrigatório).
  - `$valor`: Valor que será incluído (obrigatório).
  - `$tipo`: Tipo da variável (opcional, padrão: 'string').
- `variaveis_sistema_atualizar(string $grupo, string $id, string $valor): void` — [linha 132](../../../../../gestor/bibliotecas/variaveis.php#L132)
  Atualiza o valor de uma variável do sistema.
  Parâmetros:
  - `$grupo`: Grupo da variável (obrigatório).
  - `$id`: ID da variável (obrigatório).
  - `$valor`: Novo valor que será atribuído (obrigatório).

<!-- c2f:extract:end -->
