---
title: "Biblioteca html.php"
description: "Manipulação de um trecho HTML com DOMDocument: abrir, alterar atributos, texto e classes por nome de classe CSS, e devolver o HTML."
section: reference
order: 180
sources:
  - gestor/bibliotecas/html.php
  - gestor/bibliotecas/configuracao.php
verified_at: 4c6d01f0
---

# Biblioteca `html.php`

Uma sessão de edição de HTML por DOM, guardada na global `$_HTML['dom']`: abre um trecho, altera elementos selecionados **por classe CSS** e devolve o HTML. Hoje só a `configuracao.php` a usa, para esconder elementos (classe `escondido`) nas telas de configuração; o resto do Gestor trabalha com strings pela [modelo.php](modelo.md).

```php
gestor_incluir_biblioteca('html');

html_iniciar(['valor' => $trecho]);          // ou ['gestor' => true] para usar $_GESTOR['pagina']
html_adicionar_classe(['consulta' => 'campo', 'classe' => 'obrigatorio']);
html_atributo(['opcao' => 'mudar', 'consulta' => 'campo', 'atributo' => 'data-id', 'valor' => '7']);
$trecho = html_finalizar();                  // ou ['gestor' => true] para gravar em $_GESTOR['pagina']
```

| Função | |
|---|---|
| `html_iniciar(['valor' => … \| 'gestor' => true])` | Carrega o HTML num `DOMDocument` |
| `html_atributo(['opcao' => 'valor'\|'mudar', 'consulta', 'atributo', 'valor'])` | Lê o atributo do **primeiro** elemento com a classe, ou grava em **todos** |
| `html_valor(['opcao' => 'mudar', 'consulta', 'valor'])` | Troca o texto (`nodeValue`) de todos os elementos com a classe |
| `html_adicionar_classe(['consulta', 'classe'])` | Acrescenta uma classe |
| `html_elemento(['opcao' => 'excluir', 'consulta'])` | Remove todos os elementos com a classe |
| `html_consulta(['valor' => '//xpath'])` | Consulta XPath livre; devolve `DOMNodeList` |
| `html_finalizar(['gestor' => true])` | Serializa, tira o `<html><body>` que o DOM acrescenta e libera a sessão |
| `html_beautify($html)` | Indenta com a extensão **tidy**; devolve um objeto `tidy` |

`consulta` é sempre **um nome de classe** (não um seletor CSS) e entra no XPath sem escape.

> [!CAUTION]
> `html_finalizar()` aplica `html_entity_decode()` ao documento inteiro. Um texto escapado volta a ser HTML: `&lt;script&gt;` sai como `<script>`. Nunca passe por esta biblioteca um trecho que contenha dados do usuário.

Outros efeitos colaterais:
- `html_adicionar_classe()` lê o `class` do primeiro elemento e o grava, com a classe nova, em **todos** os que casam: as classes próprias dos demais são substituídas.
- `html_iniciar()` usa `mb_convert_encoding(…, 'HTML-ENTITIES')`, obsoleto no PHP 8.2 (gera aviso de *deprecation*).
- O `DOMDocument` corrige o HTML: fecha tags, move elementos e envolve texto solto. O que sai pode não ser byte a byte o que entrou.
- `html_beautify()` aplica `stripslashes()` antes, o que apaga barras invertidas do conteúdo. Não tem chamadores.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/html.php` por `c2f docs:extract` — 8 funções. Não edite dentro deste bloco.

- `html_iniciar(array|false $params = false): void` — [linha 35](../../../../../gestor/bibliotecas/html.php#L35)
- `html_finalizar(array|false $params = false): string|void` — [linha 70](../../../../../gestor/bibliotecas/html.php#L70)
- `html_consulta(array|false $params = false): DOMNodeList` — [linha 113](../../../../../gestor/bibliotecas/html.php#L113)
- `html_atributo(array|false $params = false): string|void` — [linha 145](../../../../../gestor/bibliotecas/html.php#L145)
- `html_valor(array|false $params = false): void` — [linha 202](../../../../../gestor/bibliotecas/html.php#L202)
- `html_adicionar_classe(array|false $params = false): void` — [linha 246](../../../../../gestor/bibliotecas/html.php#L246)
- `html_elemento(array|false $params = false): void` — [linha 287](../../../../../gestor/bibliotecas/html.php#L287)
- `html_beautify(string $html): tidy` — [linha 325](../../../../../gestor/bibliotecas/html.php#L325)

<!-- c2f:extract:end -->
