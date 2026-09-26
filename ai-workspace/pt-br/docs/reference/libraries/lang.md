---
title: "Biblioteca lang.php"
label: "Traduções do CLI"
description: "Dicionários JSON e __t() para as mensagens dos scripts de linha de comando (atualizador, compilador, plugins). Não é a tradução do site."
section: reference
order: 210
sources:
  - gestor/bibliotecas/lang.php
  - gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php
verified_at: c267f123
---

# Biblioteca `lang.php`

A `lang.php` traduz as **mensagens dos scripts internos**: o atualizador de banco, o compilador de recursos, o instalador de plugins. Ela não participa da tradução do site nem do painel, que usa as [variáveis de texto](gestor.md) (`gestor_variaveis()`) e a pasta `resources/<idioma>/`.

Não está no registro de bibliotecas: os scripts a incluem com `require_once`.

## Como funciona

- Ao ser incluída, define `$GLOBALS['lang']` (padrão `pt-br`) e carrega `$GLOBALS['dicionario']` com `carregar_dicionario()`.
- `carregar_dicionario($lang = 'pt-br', $base = '')` lê `gestor/bibliotecas<base>/<lang>.json`. **Esse arquivo não existe na pasta `bibliotecas/`**, então o dicionário padrão é sempre vazio.
- `set_lang($lang)` troca o idioma e recarrega o dicionário (vazio, pelo mesmo motivo).
- `__t($chave, $trocas = [])` devolve a tradução ou, sem ela, **a própria chave**. Cada troca substitui `{nome}` e `:nome`.

Os dicionários de verdade ficam ao lado de cada script, que os mescla depois do `set_lang()`:

```php
set_lang('pt-br');
$arquivo = __DIR__ . '/lang/' . $GLOBALS['lang'] . '.json';
if (is_file($arquivo)) {
    $GLOBALS['dicionario'] = array_merge($GLOBALS['dicionario'], json_decode(file_get_contents($arquivo), true));
}
echo __t('_compare_summary', ['tabela' => 'paginas', 'ins' => 3]);
```

Pastas existentes: `controladores/atualizacoes/lang/`, `controladores/plugins/lang/` e `controladores/agents/arquitetura/lang/`.

## Armadilhas

- O compilador de recursos (`atualizacao-dados-recursos.php`) chama `set_lang('pt-br')` mas **não mescla** a sua pasta `lang/`: as mensagens dele saem como a chave crua (`_map_file_not_found`).
- A troca `:nome` também atinge prefixos: com `['id' => 5]`, `:identificador` vira `5entificador`. Prefira `{nome}`.
- As funções são declaradas dentro de `if (!function_exists(…))`: se outro código já definiu `__t()`, a versão desta biblioteca é ignorada.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/lang.php` por `c2f docs:extract` — 3 funções. Não edite dentro deste bloco.

- `carregar_dicionario($lang = 'pt-br', $base = '')` — [linha 25](../../../../../gestor/bibliotecas/lang.php#L25)
- `__t($key, $replacements = [])` — [linha 68](../../../../../gestor/bibliotecas/lang.php#L68)
- `set_lang($lang)` — [linha 92](../../../../../gestor/bibliotecas/lang.php#L92)

<!-- c2f:extract:end -->
