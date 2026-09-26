---
title: "Biblioteca configuracao.php"
label: "Configuração de variáveis"
description: "O editor de variáveis de texto de um módulo (tela e gravação) usado pelos módulos variables e modulos, e o legado de variáveis por host."
section: reference
order: 300
sources:
  - gestor/bibliotecas/configuracao.php
  - gestor/modulos/variables/variables.php
  - gestor/modulos/modulos/modulos.php
verified_at: 22d2719d
---

# Biblioteca `configuracao.php`

Monta e grava o **editor das variáveis de texto de um módulo** (a tabela `variaveis`, lida por `gestor_variaveis()`): a tela em que cada variável aparece com um campo conforme o tipo, e a gravação das mudanças. É o que os módulos `variables` e `modulos` mostram na edição de um registro.

## Tela: `configuracao_administracao()`

```php
gestor_incluir_biblioteca('configuracao');
configuracao_administracao([
    'modulo' => $id,                                  // módulo cujas variáveis serão editadas
    'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
    'marcador' => '<!-- configuracao-administracao -->',
]);
```

Lê as variáveis do módulo e do idioma, monta os componentes `configuracao-widget` e `configuracao-campos` e troca o `marcador` em `$_GESTOR['pagina']` pelo editor. Os tipos de campo ficam em `$_GESTOR['biblioteca-configuracao']['camposTipos']`: `string`, `text`, `bool`, `number`, `quantidade`, `dinheiro`, `css`, `js`, `html`, `editor-texto`, `datas-multiplas`, `data` e `data-hora`. O tipo antigo `tinymce` é lido como `editor-texto` (`configuracao_campo_tipo()`).

## Gravação: `configuracao_administracao_salvar()`

```php
configuracao_administracao_salvar([
    'modulo' => $id,
    'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
    'tabela' => $modulo['tabela'],   // para subir a versão do registro e gravar o histórico
]);
```

Compara o formulário (`variaveis-total`, e para cada índice `ref-N`, `id-N`, `grupo-N`, `descricao-N`, `tipo-N` e `valor-N`) com o banco:
- variável existente com mudança: atualiza, e marca `user_modified=1` (o deploy passa a preservar o valor, veja [recursos](../../concepts/resources.md));
- variável nova (com `id`): insere;
- **variável que existia e não veio no formulário: é apagada** do banco;
- se algo mudou, incrementa a versão do registro do módulo e grava `module-variables` no histórico.

> [!WARNING]
> A exclusão por ausência depende de o formulário chegar inteiro. Cada variável ocupa 6 campos no POST; com o limite padrão do PHP (`max_input_vars = 1000`), um módulo com mais de ~160 variáveis tem o POST cortado, e as variáveis que ficaram de fora são **apagadas** sem aviso. Em módulos grandes, aumente `max_input_vars`.

`modulo` e `linguagemCodigo` entram no SQL sem escape: os chamadores do core passam valores já escapados (`modulo-registro-id`).

## Legado de hosts

`configuracao_hosts_variaveis()` (sobrepõe às variáveis do módulo os valores de `hosts_variaveis`) e `configuracao_hosts_salvar()` são do modo multi-host. A tabela `hosts_variaveis` não existe nas instalações atuais e `$_GESTOR['host-id']` nunca é definido ([host.php](host.md)); `configuracao_hosts_salvar()` não tem chamadores e `configuracao_hosts_variaveis()` só é chamada pela [comunicacao.php](comunicacao.md) num ramo que também depende de host.

A tela esconde partes com a [html.php](html.md) (`html_adicionar_classe(... 'escondido')`).

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/configuracao.php` por `c2f docs:extract` — 5 funções. Não edite dentro deste bloco.

- `configuracao_campo_tipo(string $tipo): string` — [linha 52](../../../../../gestor/bibliotecas/configuracao.php#L52)
  Normaliza o tipo de um campo de configuração (req-144 / BATCH-147).
  Parâmetros:
  - `$tipo`: Tipo gravado no banco.
  Retorno: Tipo canônico.
- `configuracao_administracao_salvar(array|false $params = false): void` — [linha 75](../../../../../gestor/bibliotecas/configuracao.php#L75)
  Salva as configurações de administração de um módulo.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (obrigatório).
  - `$params['tabela']`: Definições da tabela onde será atualizado o histórico (obrigatório).
- `configuracao_administracao(array|false $params = false): void` — [linha 262](../../../../../gestor/bibliotecas/configuracao.php#L262)
  Exibe o widget de administração de configurações de um módulo.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['marcador']`: Marcador textual onde será incluído o widget (obrigatório).
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (obrigatório).
- `configuracao_hosts_salvar(array|false $params = false): array` — [linha 542](../../../../../gestor/bibliotecas/configuracao.php#L542)
  Salva as configurações de hosts para variáveis de um módulo.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (obrigatório).
  - `$params['tabela']`: Definições da tabela onde será atualizado o histórico (obrigatório).
  - `$params['grupos']`: Grupos alvos para filtrar as variáveis (opcional).
  - `$params['plugin']`: Identificador do plugin relacionado (opcional).
  Retorno: Array de retorno com informações do processamento.
- `configuracao_hosts_variaveis(array|false $params = false): array` — [linha 822](../../../../../gestor/bibliotecas/configuracao.php#L822)
  Retorna as variáveis de configuração de um módulo para um host específico.
  Parâmetros:
  - `$params`: Parâmetros da função.
  - `$params['modulo']`: Módulo alvo para filtrar as variáveis (obrigatório).
  - `$params['linguagemCodigo']`: Linguagem das variáveis (opcional, usa padrão do sistema).
  - `$params['grupos']`: Grupos alvos para filtrar as variáveis (opcional).
  - `$params['id_hosts']`: Identificador do host alvo (opcional, usa host atual).
  Retorno: Array de variáveis de configuração com valores mesclados do host.

<!-- c2f:extract:end -->
