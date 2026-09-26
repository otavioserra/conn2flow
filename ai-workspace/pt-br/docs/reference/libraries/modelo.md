---
title: "Biblioteca modelo.php"
label: "Modelos HTML"
description: "Substituição de variáveis e manipulação de blocos delimitados em templates HTML — a base de toda a montagem de telas do Gestor."
section: reference
order: 20
sources:
  - gestor/bibliotecas/modelo.php
verified_at: c267f123
---

# Biblioteca `modelo.php`

`modelo.php` é a biblioteca de templates do Gestor. Tudo o que o Conn2Flow desenha no servidor passa por ela: páginas, layouts, componentes e respostas de widgets são **strings HTML** em que o código troca marcadores por valores e recorta, repete ou remove blocos. Não há engine de templates, AST nem escape automático; são funções puras de string.

A biblioteca é carregada no bootstrap do Gestor e registra a própria versão em `$_GESTOR['biblioteca-modelo']` (atualmente `1.1.0`).

> [!WARNING]
> Nenhuma função escapa HTML. Todo valor vindo do usuário ou do banco precisa ser tratado antes (por exemplo, com `htmlspecialchars()`), senão vira XSS.

## Os dois tipos de marcador

| Marcador | Formato | Usado para |
|---|---|---|
| Variável | Qualquer string literal, por convenção `#nome#` ou `@[[grupo#nome]]@` | Um valor pontual |
| Bloco (célula) | Par de comentários `<!-- nome < -->` … `<!-- nome > -->` | Um trecho a extrair, repetir, trocar ou remover |

As funções não impõem formato. Elas procuram exatamente a string recebida. A convenção dos comentários de bloco é seguida pelos módulos do core e pelos widgets.

## Substituir variáveis

| Função | Ocorrências | Maiúsculas/minúsculas |
|---|---|---|
| `modelo_var_troca($modelo, $var, $valor)` | só a **primeira** | diferencia |
| `modelo_var_troca_fim($modelo, $var, $valor)` | só a **última** | ignora |
| `modelo_var_troca_tudo($modelo, $var, $valor)` | **todas** | ignora |
| `modelo_var_in($modelo, $var, $valor)` | insere **antes** da primeira, mantendo a variável | diferencia |

Quando a variável não é encontrada, o modelo volta inalterado; não há erro nem aviso.

`modelo_var_troca()` e `modelo_var_troca_tudo()` também aceitam um array associativo no lugar de `$var`. Cada **chave é usada literalmente** como marcador; o `#` não é acrescentado, apesar do que diz o docblock da função:

```php
$html = modelo_var_troca_tudo($html, [
    '#titulo#' => $titulo,
    '#url#'    => $url,
]);
```

`modelo_var_troca_tudo()` usa `str_ireplace()` de propósito. A versão antiga com `preg_replace()` tratava `$19` no valor como referência de grupo e corrompia preços como `$19.97`.

## Trabalhar com blocos

| Função | Resultado |
|---|---|
| `modelo_tag_val($modelo, $in, $out)` | Devolve o **conteúdo** entre as tags (ou `''`) |
| `modelo_tag_in($modelo, $in, $out, $valor)` | Troca o bloco **inteiro, com as tags**, por `$valor` |
| `modelo_tag_troca_val($modelo, $in, $out, $valor)` | Troca só o **conteúdo**, preservando as tags |
| `modelo_tag_del($modelo, $in, $out)` | Remove o bloco inteiro, com as tags |

> [!IMPORTANT]
> As quatro funções atuam **só na primeira ocorrência**, e a tag de fechamento também é procurada a partir do início do texto. Com dois blocos de mesmo nome, `modelo_tag_del()` remove só o primeiro. Com uma tag de fechamento antes da de abertura, o recorte sai errado. Use nomes de bloco únicos por template.

## O padrão de repetição ("célula")

É assim que os módulos do core montam listas no servidor: extraem o bloco como modelo, deixam um marcador no lugar e inserem cada linha antes do marcador.

```php
$cel_nome = 'linha';
// 1. Guarda o modelo e deixa um marcador no lugar do bloco.
$cel = modelo_tag_val($_GESTOR['pagina'], '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
$_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'], '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->', '<!-- '.$cel_nome.' -->');

// 2. Para cada registro, preenche uma cópia e a insere antes do marcador (a ordem se mantém).
foreach ($registros as $r) {
    $linha = modelo_var_troca_tudo($cel, '#nome#', htmlspecialchars($r['nome']));
    $_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'], '<!-- '.$cel_nome.' -->', $linha);
}

// 3. Remove o marcador.
$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '<!-- '.$cel_nome.' -->', '');
```

O mesmo par de funções resolve blocos condicionais: `modelo_tag_del()` apaga a seção quando a condição é falsa. É o que faz, por exemplo, o módulo `publisher` com as mensagens `templates-todos-linkados-msg`.

## Funções legadas

- `modelo_abrir($modelo_local)` lê um arquivo e remove o bloco de desenvolvimento `<!--!#del#(#!-->` … `<!--!#del#)#!-->`. Não tem chamadores no core: templates vivem no **banco** e em `resources/`, não em arquivos lidos em runtime. Também só remove o primeiro bloco.
- `modelo_input_in($modelo, $in, $out, $valor)` chama `paginaTrocaVarValor()`, que **não existe** no core. Chamá-la causa erro fatal. Não tem chamadores; não use.

## Veja também

- [Módulo menus](../modules/menus.md): um widget que usa blocos `<!-- item < -->` com regras próprias.
- [Como escrever documentação](../../guides/documentation.md)

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/modelo.php` por `c2f docs:extract` — 10 funções. Não edite dentro deste bloco.

- `modelo_input_in(string $modelo, string $name_input_in, string $name_input_out, string $valor): string` — [linha 33](../../../../../gestor/bibliotecas/modelo.php#L33)
  Substitui variáveis de input em um modelo HTML.
  Parâmetros:
  - `$modelo`: O template HTML onde as substituições serão feitas.
  - `$name_input_in`: Nome do placeholder de entrada (ex: 'campo').
  - `$name_input_out`: Nome do campo de saída que substituirá o placeholder.
  - `$valor`: Valor a ser atribuído ao campo.
  Retorno: Retorna o modelo com as variáveis substituídas.
- `modelo_var_troca(string $modelo, string|array $var, string $valor = null): string` — [linha 57](../../../../../gestor/bibliotecas/modelo.php#L57)
  Substitui a primeira ocorrência de uma variável em um modelo.
  Parâmetros:
  - `$modelo`: O template onde a substituição será feita.
  - `$var`: A variável/placeholder a ser substituída, ou array de variáveis.
  - `$valor`: O valor que substituirá a variável (ignorado se $var for array).
  Retorno: Retorna o modelo com a primeira ocorrência da variável substituída.
- `modelo_var_troca_fim(string $modelo, string $var, string $valor): string` — [linha 101](../../../../../gestor/bibliotecas/modelo.php#L101)
  Substitui a última ocorrência de uma variável em um modelo.
  Parâmetros:
  - `$modelo`: O template onde a substituição será feita.
  - `$var`: A variável/placeholder a ser substituída.
  - `$valor`: O valor que substituirá a variável.
  Retorno: Retorna o modelo com a última ocorrência da variável substituída.
- `modelo_var_troca_tudo(string $modelo, string|array $var, string $valor = null): string` — [linha 137](../../../../../gestor/bibliotecas/modelo.php#L137)
  Substitui todas as ocorrências de uma variável em um modelo.
  Parâmetros:
  - `$modelo`: O template onde as substituições serão feitas.
  - `$var`: A variável/placeholder a ser substituída, ou array de variáveis.
  - `$valor`: O valor que substituirá todas as ocorrências da variável (ignorado se $var for array).
  Retorno: Retorna o modelo com todas as ocorrências da variável substituídas.
- `modelo_var_in(string $modelo, string $var, string $valor): string` — [linha 165](../../../../../gestor/bibliotecas/modelo.php#L165)
  Insere um valor antes da variável mantendo a variável no modelo.
  Parâmetros:
  - `$modelo`: O template onde a inserção será feita.
  - `$var`: A variável/placeholder de referência.
  - `$valor`: O valor a ser inserido antes da variável.
  Retorno: Retorna o modelo com o valor inserido antes da variável.
- `modelo_tag_val(string $modelo, string $tag_in, string $tag_out): string` — [linha 199](../../../../../gestor/bibliotecas/modelo.php#L199)
  Extrai o conteúdo entre duas tags em um modelo.
  Parâmetros:
  - `$modelo`: O template de onde o conteúdo será extraído.
  - `$tag_in`: A tag de abertura.
  - `$tag_out`: A tag de fechamento.
  Retorno: Retorna o conteúdo entre as tags ou string vazia se não encontrado.
- `modelo_tag_in(string $modelo, string $tag_in, string $tag_out, string $valor): string` — [linha 231](../../../../../gestor/bibliotecas/modelo.php#L231)
  Substitui um bloco delimitado por tags incluindo as próprias tags.
  Parâmetros:
  - `$modelo`: O template onde a substituição será feita.
  - `$tag_in`: A tag de abertura do bloco.
  - `$tag_out`: A tag de fechamento do bloco.
  - `$valor`: O valor que substituirá todo o bloco (tags + conteúdo).
  Retorno: Retorna o modelo com o bloco substituído pelo valor.
- `modelo_tag_del(string $modelo, string $tag_in, string $tag_out): string` — [linha 267](../../../../../gestor/bibliotecas/modelo.php#L267)
  Remove um bloco delimitado por tags incluindo as próprias tags.
  Parâmetros:
  - `$modelo`: O template de onde o bloco será removido.
  - `$tag_in`: A tag de abertura do bloco a ser removido.
  - `$tag_out`: A tag de fechamento do bloco a ser removido.
  Retorno: Retorna o modelo sem o bloco especificado.
- `modelo_tag_troca_val(string $modelo, string $tag_in, string $tag_out, string $valor): string` — [linha 305](../../../../../gestor/bibliotecas/modelo.php#L305)
  Substitui apenas o conteúdo entre as tags, preservando as tags.
  Parâmetros:
  - `$modelo`: O template onde a substituição será feita.
  - `$tag_in`: A tag de abertura (será preservada).
  - `$tag_out`: A tag de fechamento (será preservada).
  - `$valor`: O novo conteúdo que ficará entre as tags.
  Retorno: Retorna o modelo com o conteúdo entre as tags substituído.
- `modelo_abrir(string $modelo_local): string` — [linha 340](../../../../../gestor/bibliotecas/modelo.php#L340)
  Carrega um arquivo de template e remove blocos de exclusão.
  Parâmetros:
  - `$modelo_local`: Caminho do arquivo de template a ser carregado.
  Retorno: Retorna o conteúdo do template com os blocos de exclusão removidos.

<!-- c2f:extract:end -->
