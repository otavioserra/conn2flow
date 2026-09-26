---
title: "html.php library"
label: "HTML (DOM)"
description: "Editing an HTML snippet with DOMDocument: open it, change attributes, text and classes by CSS class name, and return the HTML."
section: reference
order: 180
sources:
  - gestor/bibliotecas/html.php
  - gestor/bibliotecas/configuracao.php
verified_at: 4c6d01f0
---

# `html.php` library

A DOM editing session over HTML, kept in the `$_HTML['dom']` global: it opens a snippet, changes elements selected **by CSS class** and returns the HTML. Today only `configuracao.php` uses it, to hide elements (the `escondido` class) on configuration screens; the rest of the Gestor works with strings through [modelo.php](modelo.md).

```php
gestor_incluir_biblioteca('html');

html_iniciar(['valor' => $snippet]);         // or ['gestor' => true] to use $_GESTOR['pagina']
html_adicionar_classe(['consulta' => 'field', 'classe' => 'required']);
html_atributo(['opcao' => 'mudar', 'consulta' => 'field', 'atributo' => 'data-id', 'valor' => '7']);
$snippet = html_finalizar();                 // or ['gestor' => true] to write into $_GESTOR['pagina']
```

| Function | |
|---|---|
| `html_iniciar(['valor' => … \| 'gestor' => true])` | Loads the HTML into a `DOMDocument` |
| `html_atributo(['opcao' => 'valor'\|'mudar', 'consulta', 'atributo', 'valor'])` | Reads the attribute of the **first** element with the class, or writes it on **all** of them |
| `html_valor(['opcao' => 'mudar', 'consulta', 'valor'])` | Replaces the text (`nodeValue`) of every element with the class |
| `html_adicionar_classe(['consulta', 'classe'])` | Adds a class |
| `html_elemento(['opcao' => 'excluir', 'consulta'])` | Removes every element with the class |
| `html_consulta(['valor' => '//xpath'])` | Free XPath query; returns a `DOMNodeList` |
| `html_finalizar(['gestor' => true])` | Serializes, strips the `<html><body>` the DOM adds and releases the session |
| `html_beautify($html)` | Indents with the **tidy** extension; returns a `tidy` object |

`consulta` is always **a class name** (not a CSS selector) and goes into the XPath unescaped.

> [!CAUTION]
> `html_finalizar()` applies `html_entity_decode()` to the whole document. Escaped text becomes HTML again: `&lt;script&gt;` comes out as `<script>`. Never pass a snippet containing user data through this library.

Other side effects:
- `html_adicionar_classe()` reads the `class` of the first element and writes it, with the new class, to **every** matching element: the other elements' own classes are replaced.
- `html_iniciar()` uses `mb_convert_encoding(…, 'HTML-ENTITIES')`, deprecated in PHP 8.2 (it raises a deprecation notice).
- `DOMDocument` repairs the HTML: it closes tags, moves elements and wraps loose text. The output may not match the input byte for byte.
- `html_beautify()` applies `stripslashes()` first, which deletes backslashes from the content. It has no callers.

## Functions

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/html.php` by `c2f docs:extract` — 8 functions. Do not edit inside this block.

- `html_iniciar(array|false $params = false): void` — [line 35](../../../../../gestor/bibliotecas/html.php#L35)
- `html_finalizar(array|false $params = false): string|void` — [line 70](../../../../../gestor/bibliotecas/html.php#L70)
- `html_consulta(array|false $params = false): DOMNodeList` — [line 113](../../../../../gestor/bibliotecas/html.php#L113)
- `html_atributo(array|false $params = false): string|void` — [line 145](../../../../../gestor/bibliotecas/html.php#L145)
- `html_valor(array|false $params = false): void` — [line 202](../../../../../gestor/bibliotecas/html.php#L202)
- `html_adicionar_classe(array|false $params = false): void` — [line 246](../../../../../gestor/bibliotecas/html.php#L246)
- `html_elemento(array|false $params = false): void` — [line 287](../../../../../gestor/bibliotecas/html.php#L287)
- `html_beautify(string $html): tidy` — [line 325](../../../../../gestor/bibliotecas/html.php#L325)

<!-- c2f:extract:end -->
