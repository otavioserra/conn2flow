---
title: "Biblioteca pagina.php"
label: "Página"
description: "Atalhos sobre $_GESTOR['pagina']: recortar células, trocar variáveis globais e mascarar marcadores para o banco."
section: reference
order: 100
sources:
  - gestor/bibliotecas/pagina.php
  - gestor/config.php
verified_at: a6e51e29
---

# Biblioteca `pagina.php`

A `pagina.php` reúne atalhos sobre `$_GESTOR['pagina']`, o HTML da página que o módulo está montando, e sobre os marcadores de variável global. Todas as funções são finas camadas sobre a [biblioteca modelo.php](modelo.md).

Ela não vem carregada. Peça `pagina` no JSON do módulo (fazem isso `admin-paginas`, `pages-index` e `publisher-pages`) ou use `gestor_incluir_biblioteca('pagina')`.

## Marcadores de variável global

Os marcadores são definidos em `gestor/config.php`:

| Chave de `$_GESTOR['variavel-global']` | Valor | Uso |
|---|---|---|
| `open` / `close` | `@[[` / `]]@` | Forma **gravada no banco** e resolvida em runtime |
| `openText` / `closeText` | `[[` / `]]` | Forma **digitada** pelo usuário nos editores |

## Células da página

- `pagina_celula($nome, $comentario = false, $apagar = false)` recorta o bloco `<!-- nome < -->…<!-- nome > -->` de `$_GESTOR['pagina']` e devolve o conteúdo. No lugar, deixa o marcador `<!-- nome -->` (para reinserir com `pagina_celula_incluir()`) ou nada, se `$apagar` for `true`. Com `$comentario = true`, o formato do bloco passa a ser `<!-- nome [[ … ]] nome -->` e o marcador deixado é `<!-- [[nome]] -->`.
- `pagina_celula_incluir($celula, $valor)` insere `$valor` **antes** do marcador `<!-- celula -->`, mantendo o marcador, o que permite chamá-la em loop para repetir linhas.

> [!NOTE]
> É o mesmo padrão "célula" de `modelo_tag_val()` + `modelo_tag_in()` + `modelo_var_in()`, com as mesmas limitações: só a primeira ocorrência do bloco é tratada. Hoje o único usuário no core é o `perfil-usuario`, que chama `pagina_celula($nome, false, true)` para **remover** os blocos `bloqueado-mensagem` e `formulario` conforme o acesso.

## Trocar variáveis globais

- `pagina_trocar_variavel_valor($variavel, $valor, $variavelEspecifica = false)` troca, em toda a `$_GESTOR['pagina']`, `@[[variavel]]@` por `$valor`. Com `$variavelEspecifica = true`, procura a string literal `$variavel`, sem acrescentar os marcadores.
- `pagina_celula_trocar_variavel_valor($celula, $variavel, $valor, $variavelEspecifica = false)` faz o mesmo numa string de célula e devolve o resultado (valor `null` vira vazio).
- `pagina_trocar_variavel(['codigo' => …, 'variavel' => …, 'valor' => …])` faz o mesmo em qualquer string. Devolve `null` se faltar algum dos três parâmetros.

As três usam `modelo_var_troca_tudo()`: trocam **todas** as ocorrências, sem diferenciar maiúsculas de minúsculas. `pagina_trocar_variavel_valor()` não faz nada se `$valor` for `null`.

## Mascarar para o banco

- `pagina_variaveis_globais_mascarar(['valor' => …])` converte `[[x]]` em `@[[x]]@`: é o que acontece quando um editor salva conteúdo digitado pelo usuário.
- `pagina_variaveis_globais_desmascarar(['valor' => …])` faz o caminho inverso, `@[[x]]@` → `[[x]]`, para exibir no editor.

> [!WARNING]
> As duas funções aplicam `strtolower()` ao **padrão de substituição** (`"@[[$1]]@"`), e não ao nome capturado. O nome da variável **mantém a caixa original**, apesar do que o código dá a entender. Os módulos que mascaram por conta própria (por exemplo `publisher` e `publisher-pages`) usam a mesma expressão e têm o mesmo comportamento.

Nenhuma das funções de variável tem chamadores no core hoje. Os módulos fazem a mesma coisa diretamente com `modelo_var_troca_tudo()` e `preg_replace()`.

## Veja também

- [Biblioteca modelo.php](modelo.md)
- [Bibliotecas do Gestor](index.md)

## Funções (referência gerada)

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/pagina.php` por `c2f docs:extract` — 7 funções. Não edite dentro deste bloco.

- `pagina_celula(string $nome, bool $comentario = false, bool $apagar = false): string` — [linha 39](../../../../../gestor/bibliotecas/pagina.php#L39)
  Extrai e processa célula de conteúdo da página.
  Parâmetros:
  - `$nome`: Nome da célula a extrair (obrigatório).
  - `$comentario`: Se true, usa formato [[ ]], senão usa < > (padrão: false).
  - `$apagar`: Se true, remove célula completamente, senão deixa marcador (padrão: false).
  Retorno: Conteúdo da célula ou string vazia.
- `pagina_celula_trocar_variavel_valor(string $celula, string $variavel, string $valor, bool $variavelEspecifica = false): string` — [linha 75](../../../../../gestor/bibliotecas/pagina.php#L75)
  Substitui variável por valor em célula específica.
  Parâmetros:
  - `$celula`: Conteúdo da célula (obrigatório).
  - `$variavel`: Nome da variável (obrigatório).
  - `$valor`: Valor para substituir (opcional).
  - `$variavelEspecifica`: Se true, usa variável literal sem marcadores (padrão: false).
  Retorno: Célula com variável substituída.
- `pagina_celula_incluir(string $celula, string $valor): void` — [linha 106](../../../../../gestor/bibliotecas/pagina.php#L106)
  Inclui célula de conteúdo na página.
  Parâmetros:
  - `$celula`: Nome da célula (obrigatório).
  - `$valor`: Valor a inserir (obrigatório).
- `pagina_trocar_variavel_valor(string $variavel, string $valor, bool $variavelEspecifica = false): void` — [linha 128](../../../../../gestor/bibliotecas/pagina.php#L128)
  Substitui variável por valor na página.
  Parâmetros:
  - `$variavel`: Nome da variável (obrigatório).
  - `$valor`: Valor para substituir (obrigatório).
  - `$variavelEspecifica`: Se true, usa variável literal sem marcadores (padrão: false).
- `pagina_trocar_variavel(array|false $params = false): string|null` — [linha 160](../../../../../gestor/bibliotecas/pagina.php#L160)
  Substitui variável por valor em código arbitrário.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['codigo']`: Código com variáveis (obrigatório).
  - `$params['variavel']`: Nome da variável (obrigatório).
  - `$params['valor']`: Valor para substituir (obrigatório).
  Retorno: Código com variável substituída ou null.
- `pagina_variaveis_globais_mascarar(array|false $params = false): string` — [linha 189](../../../../../gestor/bibliotecas/pagina.php#L189)
  Mascara variáveis globais para armazenamento em banco.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['valor']`: Valor a mascarar (obrigatório).
  Retorno: Valor mascarado ou string vazia.
- `pagina_variaveis_globais_desmascarar(array|false $params = false): string` — [linha 223](../../../../../gestor/bibliotecas/pagina.php#L223)
  Desmascara variáveis globais vindas do banco.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['valor']`: Valor a desmascarar (obrigatório).
  Retorno: Valor desmascarado ou string vazia.

<!-- c2f:extract:end -->
