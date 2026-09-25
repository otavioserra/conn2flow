---
title: "Bibliotecas do Gestor"
description: "Como as bibliotecas de gestor/bibliotecas/ são registradas e carregadas, quais já vêm carregadas e como um módulo pede as suas."
section: reference
order: 1
sources:
  - gestor/config.php
  - gestor/bibliotecas/gestor.php
  - gestor/bibliotecas/interface.php
verified_at: a6e51e29
---

# Bibliotecas do Gestor

As bibliotecas são arquivos PHP procedurais em `gestor/bibliotecas/`, e cada um agrupa funções com o mesmo prefixo (`banco_*`, `modelo_*`, `interface_*`…). Não há autoload nem namespace: elas entram por `require_once`, a partir de um registro central.

## O registro: `$_GESTOR['bibliotecas-dados']`

Em `gestor/config.php`, cada nome lógico aponta para um ou mais arquivos:

```php
$_GESTOR['bibliotecas-dados'] = Array(
    'banco'   => Array('banco.php'),
    'gestor'  => Array('gestor.php'),
    'modelo'  => Array('modelo.php'),
    // ...
    'cron'    => Array('cron.php'),
);
```

É o nome lógico, e não o arquivo, que módulos e funções usam para pedir uma biblioteca.

> [!WARNING]
> O registro tem duas entradas sem arquivo: `api-cliente` (`api-cliente.php`) e `cpanel` (`cpanel.php`). Nenhum dos dois existe em `gestor/bibliotecas/`, e pedir qualquer um deles termina em erro fatal do `require_once`.

## O que já vem carregado

`config.php` carrega sempre `banco`, `gestor`, `modelo` e `hooks` (lista `$_GESTOR['bibliotecas']`). Em qualquer ponto do Gestor, funções como `banco_select()`, `gestor_variaveis()`, `modelo_var_troca()` e `hook_do_action()` estão disponíveis.

## Como um módulo pede bibliotecas

1. O JSON do módulo declara a lista em `bibliotecas`:

```json
{ "versao": "1.0.4", "bibliotecas": ["interface", "html", "html-editor"] }
```

2. O controlador do módulo lê o JSON para `$_GESTOR['modulo#<id>']` e, na função `<modulo>_start()`, chama `gestor_incluir_bibliotecas()`, que faz `require_once` de cada arquivo registrado e marca o nome em `$_GESTOR['bibliotecas-inseridas']`.
3. Em código avulso (widgets, controladores, rotinas), use `gestor_incluir_biblioteca('nome')` ou `gestor_incluir_biblioteca(['a', 'b'])`, que pula o que já foi incluído.

> [!NOTE]
> `gestor_incluir_bibliotecas()` não confere se o nome existe no registro. Uma biblioteca escrita errado no JSON do módulo gera *warning* de índice indefinido e não é carregada. Já `gestor_incluir_biblioteca()` ignora em silêncio nomes sem caminho.

As bibliotecas também podem ter rotinas AJAX próprias: `interface.php` repassa as requisições AJAX de interface a `html_editor_ajax_interface()` quando `html-editor` está entre as bibliotecas do Gestor ou do módulo.

`cron.php` é executado fora do ciclo de módulo e, por isso, inclui suas bibliotecas diretamente em vez de usar `gestor_incluir_bibliotecas()`.

## Referência por biblioteca

Cada biblioteca tem uma página com a lista de funções gerada do código (`c2f docs:extract`) e a explicação do comportamento real. Veja o menu "Referência → Bibliotecas".
