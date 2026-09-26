---
title: "configuracao.php library"
label: "Variable configuration"
description: "The text-variable editor of a module (screen and saving) used by the variables and modulos modules, and the per-host variables legacy."
section: reference
order: 300
sources:
  - gestor/bibliotecas/configuracao.php
  - gestor/modulos/variables/variables.php
  - gestor/modulos/modulos/modulos.php
verified_at: 22d2719d
---

# `configuracao.php` library

Builds and saves the **editor of a module's text variables** (the `variaveis` table, read by `gestor_variaveis()`): the screen where each variable shows up with a field matching its type, and the saving of the changes. It is what the `variables` and `modulos` modules show when editing a record.

## Screen: `configuracao_administracao()`

```php
gestor_incluir_biblioteca('configuracao');
configuracao_administracao([
    'modulo' => $id,                                  // module whose variables will be edited
    'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
    'marcador' => '<!-- configuracao-administracao -->',
]);
```

It reads the variables of the module and language, builds the `configuracao-widget` and `configuracao-campos` components and replaces the `marcador` in `$_GESTOR['pagina']` with the editor. Field types live in `$_GESTOR['biblioteca-configuracao']['camposTipos']`: `string`, `text`, `bool`, `number`, `quantidade`, `dinheiro`, `css`, `js`, `html`, `editor-texto`, `datas-multiplas`, `data` and `data-hora`. The old `tinymce` type is read as `editor-texto` (`configuracao_campo_tipo()`).

## Saving: `configuracao_administracao_salvar()`

```php
configuracao_administracao_salvar([
    'modulo' => $id,
    'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
    'tabela' => $modulo['tabela'],   // to bump the record version and write the history
]);
```

It compares the form (`variaveis-total`, and for each index `ref-N`, `id-N`, `grupo-N`, `descricao-N`, `tipo-N` and `valor-N`) with the database:
- an existing variable that changed: updated, and marked `user_modified=1` (deploys then preserve the value, see [resources](../../concepts/resources.md));
- a new variable (with `id`): inserted;
- **a variable that existed and did not come in the form: deleted** from the database;
- if anything changed, it increments the module record version and writes `module-variables` to the history.

> [!WARNING]
> Deleting by absence relies on the form arriving complete. Each variable takes 6 POST fields; with PHP's default limit (`max_input_vars = 1000`), a module with more than ~160 variables gets its POST cut, and the variables left out are **deleted** without warning. For large modules, raise `max_input_vars`.

`modulo` and `linguagemCodigo` go into the SQL unescaped: core callers pass already-escaped values (`modulo-registro-id`).

## Hosts legacy

`configuracao_hosts_variaveis()` (overlays the module variables with values from `hosts_variaveis`) and `configuracao_hosts_salvar()` belong to the multi-host mode. The `hosts_variaveis` table does not exist on current installations and `$_GESTOR['host-id']` is never set ([host.php](host.md)); `configuracao_hosts_salvar()` has no callers and `configuracao_hosts_variaveis()` is only called by [comunicacao.php](comunicacao.md) in a branch that also depends on a host.

The screen hides parts with [html.php](html.md) (`html_adicionar_classe(... 'escondido')`).

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/configuracao.php` by `c2f docs:extract` — 5 functions. Do not edit inside this block.

- `configuracao_campo_tipo(string $tipo): string` — [line 52](../../../../../gestor/bibliotecas/configuracao.php#L52)
  Normaliza o tipo de um campo de configuração (req-144 / BATCH-147).
  Parameters:
  - `$tipo`: Tipo gravado no banco.
  Returns: Tipo canônico.
- `configuracao_administracao_salvar(array|false $params = false): void` — [line 75](../../../../../gestor/bibliotecas/configuracao.php#L75)
  Salva as configurações de administração de um módulo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (obrigatório).
  - `$params['tabela']`: Definições da tabela onde será atualizado o histórico (obrigatório).
- `configuracao_administracao(array|false $params = false): void` — [line 262](../../../../../gestor/bibliotecas/configuracao.php#L262)
  Exibe o widget de administração de configurações de um módulo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['marcador']`: Marcador textual onde será incluído o widget (obrigatório).
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (obrigatório).
- `configuracao_hosts_salvar(array|false $params = false): array` — [line 542](../../../../../gestor/bibliotecas/configuracao.php#L542)
  Salva as configurações de hosts para variáveis de um módulo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (obrigatório).
  - `$params['tabela']`: Definições da tabela onde será atualizado o histórico (obrigatório).
  - `$params['grupos']`: Grupos alvos para filtrar as variáveis (opcional).
  - `$params['plugin']`: Identificador do plugin relacionado (opcional).
  Returns: Array de retorno com informações do processamento.
- `configuracao_hosts_variaveis(array|false $params = false): array` — [line 822](../../../../../gestor/bibliotecas/configuracao.php#L822)
  Retorna as variáveis de configuração de um módulo para um host específico.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (opcional, usa padrão do sistema).
  - `$params['grupos']`: Grupos alvos para filtrar as variáveis (opcional).
  - `$params['id_hosts']`: Identificador do host alvo (opcional, usa host atual).
  Returns: Array de variáveis de configuração com valores mesclados do host.

<!-- c2f:extract:end -->
