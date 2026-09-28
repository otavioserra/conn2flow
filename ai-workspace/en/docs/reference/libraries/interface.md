---
title: "interface.php library"
label: "CRUD interface"
description: "The CRUD engine of the admin modules: the start/finish cycle per option, DataTables listing, forms, validation, history, field backups, alerts and the Tailwind component variants."
section: reference
order: 12
sources:
  - gestor/bibliotecas/interface.php
  - gestor/bibliotecas/seguranca.php
verified_at: f3511524
---

# The `interface.php` library

`interface.php` is the admin panel's **CRUD engine**. An admin module does not build listing, editing and deletion screens by hand: it states what it wants (table, columns, fields, validations, buttons), and the library draws the screen from components (`interface-listar`, `interface-formulario-edicao`…), records history, handles the standard AJAX calls and fires the hooks.

Request the library in the module JSON (`"bibliotecas": ["interface", …]`).

## A module's cycle

Every module controller follows the same skeleton:

```php
function my_module_start(){
    global $_GESTOR;
    gestor_incluir_bibliotecas();

    if($_GESTOR['ajax']){
        interface_ajax_iniciar();
        switch($_GESTOR['ajax-opcao']){ /* the module's own AJAX */ }
        interface_ajax_finalizar();          // standard AJAX: list, history, backup, verify-field
    } else {
        my_module_interfaces_padroes();      // fills $_GESTOR['interface'][<option>]
        interface_iniciar();
        switch($_GESTOR['opcao']){
            case 'adicionar': my_module_add(); break;
            case 'editar':    my_module_edit(); break;
        }
        interface_finalizar();
    }
}
```

- **`interface_iniciar()`** runs before the module code. It reads `$_GESTOR['opcao']` (and `$_GESTOR['interface-opcao']` when present), calls the matching `interface_<option>_iniciar()` with the parameters in `$_GESTOR['interface'][<option>]['iniciar']` and sets the flags the module checks:
  - `$_GESTOR['adicionar-banco']` when the add form was submitted (`_gestor-adicionar`);
  - `$_GESTOR['atualizar-banco']` when the edit form was submitted (`_gestor-atualizar`);
  - `$_GESTOR['modulo-registro-id']` with the record id (`?id=` on GET, or `_gestor-registro-id`). Without an id on options that need one, it redirects to the module root.
- **`interface_finalizar()`** runs afterwards. It calls `interface_<option>_finalizar()` with `$_GESTOR['interface'][<option>]['finalizar']`, which **draws the screen** into `$_GESTOR['pagina']`, then prints the pending alert and renders the marked components.
- With `$_GESTOR['interface-nao-aplicar']`, both do nothing.

### Options

| `opcao` / `interface-opcao` | What the finisher does |
|---|---|
| `listar` | Paginated table (DataTables) with search, sorting, per-row actions and a delete modal |
| `adicionar`, `clonar` | Add form (`interface-formulario-inclusao`). `clonar` requires `id` and starts from the existing record |
| `editar` | Edit form (`interface-formulario-edicao`, with a Tailwind variant), metadata and history |
| `visualizar` | Read-only screen (`interface-formulario-visualizacao`) |
| `status` | Changes the record's `status` (`?id=…&status=A|I`), increments `versao`, records history |
| `excluir` | **Soft delete**: `status='D'`, `versao+1`, history and redirect |
| `config` | The module's configuration screen, without a record (`interface-formulario-configuracoes`) |
| `alteracoes`, `simples`, `adicionar-incomum`, `editar-incomum` | Only via `interface-opcao`: variations for screens that do not follow the standard CRUD |

The handlers of each option:

| Option | Start | Finish |
|---|---|---|
| `listar` | `interface_listar_iniciar()` (empty) | `interface_listar_finalizar()` |
| `adicionar` | `interface_adicionar_iniciar()` | `interface_adicionar_finalizar()` |
| `clonar` | `interface_clonar_iniciar()` (accepts `forcarId`) | `interface_adicionar_finalizar()` |
| `editar` | `interface_editar_iniciar()` (accepts `forcarId`) | `interface_editar_finalizar()` |
| `visualizar` | `interface_visualizar_iniciar()` | `interface_visualizar_finalizar()` |
| `status` | `interface_status_iniciar()` (requires `id` and `status` on GET) | `interface_status_finalizar()` |
| `excluir` | `interface_excluir_iniciar()` (requires `id` on GET) | `interface_excluir_finalizar()` |
| `config` | `interface_config_iniciar()` | `interface_config_finalizar()` |
| `alteracoes` | `interface_alteracoes_iniciar()` (falls back to `modulo-registro-padrao-id` without `id`) | `interface_alteracoes_finalizar()` |
| `simples` | `interface_simples_iniciar()` | `interface_simples_finalizar()` |
| `adicionar-incomum` | `interface_adicionar_incomum_iniciar()` | `interface_adicionar_incomum_finalizar()` |
| `editar-incomum` | `interface_editar_incomum_iniciar()` | `interface_editar_incomum_finalizar()` |

### Hooks fired

For module `X` and option `O` (and also for the `interface-opcao`):
- `hook_do_action('X', 'O.pre-banco')` on every POST, before the module code;
- `O.parametros` on GET, before the finisher;
- `O.pagina` on GET, after the finisher;
- `excluir.banco` (with the id) after deletion, and `status.banco` (with id and new status) after a status change.

See the hooks doc to register listeners.

## Finisher parameters

The form finishers (`adicionar`, `editar`, `visualizar`, `config`, `alteracoes`, `simples`, `*-incomum`) accept, depending on the case:

| Parameter | Effect |
|---|---|
| `formulario` | `['validacao' => [...], 'campos' => [...], 'opcao' => …]`: client-side validation and generated fields (below) |
| `botoes` | Header buttons: `[id => ['url','rotulo','tooltip','icon','cor', 'callback'?]]` |
| `botoes_rodape` | Extra buttons at the bottom of the form, same format |
| `sem_botao_padrao` | Removes the default submit button |
| `metaDados` | List `[['titulo' => …, 'dado' => …]]` shown next to the form |
| `removerNaoAlterarId` / `removerBotaoEditar` | Remove the "do not change id" checkbox and the edit button |
| `variaveisTrocarDepois` | `['key' => 'value']` replaced as `#key#` at the end of the build |
| `campoTitulo` / `forcarSemID` | `visualizar`: field used in the title, or no record at all |
| `banco`, `historico`, `callbackFunction` | `status`/`excluir`: another table, no history, and a function called after writing |

Forms automatically replace every `#form-…#` in the HTML with the module variables whose id contains `form` (`gestor_variaveis(['conjunto' => true, 'padrao' => 'form'])`).

## Listing

```php
$_GESTOR['interface']['listar']['finalizar'] = Array(
    'banco' => Array(
        'nome' => 'paginas', 'id' => 'id', 'status' => 'status',
        'campos' => Array('nome', 'caminho', 'data_modificacao'),
        'where' => "language='".$_GESTOR['linguagem-codigo']."'",
    ),
    'tabela' => Array(
        'rodape' => true,
        'colunas' => Array(
            Array('id' => 'nome', 'nome' => 'Name', 'ordenar' => 'asc'),
            Array('id' => 'data_modificacao', 'nome' => 'Modified', 'formatar' => 'dataHora', 'nao_procurar' => true),
        ),
    ),
    'opcoes' => Array(
        'editar' => Array('url' => 'editar/', 'tooltip' => '…', 'icon' => 'edit', 'cor' => 'basic blue'),
        'excluir' => Array('opcao' => 'excluir', 'tooltip' => '…', 'icon' => 'trash', 'cor' => 'basic red'),
    ),
    'botoes' => Array( /* "Add" button etc. */ ),
);
```

- `interface_listar_finalizar()` draws the `interface-listar` layout and the `interface-delecao-modal` modal, and includes DataTables. `interface_listar_tabela()` builds the first page on the server and publishes the configuration in `javascript-vars.interface.lista`.
- The **actions column is the first one** (req-147) and uses its own key (`INTERFACE_COLUNA_ACOES`), so that button ids never come from a formatted column.
- Columns: `ordenar` (`asc`/`desc`), `nao_ordenar`, `nao_procurar`, `nao_visivel`, `className` and `formatar` (see *Formatting*).
- Records with `status='D'` never show up. The current page, total and page size live in a **per-user session variable** (`<module>-<option>-interface-<user>`), which later AJAX calls use.
- Subsequent pagination, search and sorting come via AJAX (`ajax-opcao=listar`) in `interface_ajax_listar()` → `interface_listar_ajax()`. The search runs `UCASE(column) LIKE UCASE('%term%')` on each searchable column and on `columnsExtraSearch` (`id` by default). Columns, sorting and extra columns come **only from the configuration stored in the session** by the server; the `columns` sent by DataTables is used just as an index, a column name must be a plain identifier (`interface_listar_coluna_segura()`) and the term is escaped, with `%` and `_` treated as text (req-189).

## Forms

### Generated fields (`formulario.campos`)

`interface_formulario_campos()` inserts each field into the page's `<span>#<id>#</span>` marker, according to its `tipo`:

| `tipo` | Generates |
|---|---|
| `select` | A Fomantic `<select>` from `tabela` (`nome`, `campo`, `id_numerico` or `id`, `where`, `id_selecionado`/`valor_selecionado`) or from `dados` (`[['texto','valor','icone'?]]`). Options: `menu`, `procurar`, `limpar`, `multiple`, `fluid`, `disabled`, `placeholder` and icons |
| `imagepick` / `imagepick-hosts` | Image picker from the file manager (`id_arquivos`/`id_hosts_arquivos`) |
| `templates-hosts` | Template picker by `categoria_id` (`template_id`, `template_tipo` `gestor`/`hosts`) |

### Browser validation (`formulario.validacao`)

`interface_formulario_validacao()` publishes rules for the Fomantic/Tailwind validator. Each item is `['regra', 'campo', 'label', 'identificador'?]`, and the rules are:
- `texto-obrigatorio` (3 to 255 characters) and `texto-obrigatorio-verificar-campo` (which also checks whether the value already exists);
- `selecao-obrigatorio`, `nao-vazio`, `maior-ou-igual-a-zero`;
- `email`, `email-comparacao`, `email-comparacao-verificar-campo`;
- `senha`, `senha-comparacao`, `dominio`;
- `regexPermited` and `regexNecessary`, with `regrasExtra` for custom regexes.

`removerRegra` drops default rules.

### Server validation

`interface_validacao_campos_obrigatorios(['campos' => [...], 'redirect' => …])` checks `$_REQUEST` before saving. On the first failure it stores an alert and **redirects** (to `redirect`, or reloads the URL), ending the request.

| Rule | Checks |
|---|---|
| `texto-obrigatorio` | Length between `min` (default 3) and `max` (default 255), **in bytes** (`strlen`) |
| `selecao-obrigatorio` | Field is filled in |
| `email-obrigatorio` | Its own e-mail regex |

> [!WARNING]
> The `email-obrigatorio` regex rejects e-mails that **start with a digit** (`^[^0-9]`), contain **uppercase letters** (no `/i`) or contain `+`, such as `1ana@x.com`, `Ana@x.com` and `ana+shop@x.com`. And `texto-obrigatorio` counts bytes: `max` 255 accepts far fewer accented characters.

- `interface_verificar_campos(['campo', 'valor', 'language'?])` tells whether another active record already holds that value. When editing it ignores the record itself, and it can look into another table via `verificarCamposOutraTabela` in the module JSON. This is what the `verificar-campo` AJAX (`interface_ajax_verificar_campo()`) uses. The function's docblock describes other parameters; the real ones are these.

## History

- `interface_historico_incluir(['alteracoes' => [['campo','alteracao','alteracao_txt','valor_antes','valor_depois','tabela','filtro']], …])` stores in the `historico` table who changed what, linked to the module and the record. It accepts `deletar` (counts the next version), `sem_id`, `versao`, an alternative `tabela` and manual ids (`id_numerico_manual`, `id_usuarios_manual`, `id_hosts_manual`, `modulo_id`).
- `interface_historico(['id','modulo','pagina','sem_id'?])` draws the paginated history on the edit screen. `interface_ajax_historico_mais_resultados()` loads the next pages.

CRUDs use the `banco_select_campos_antes_iniciar()`/`banco_select_campos_antes()` pair to know the "before" (see [banco.php](banco.md)).

## Field backups

For long fields (HTML, CSS), each version can be kept and restored:
- `interface_backup_campo_incluir(['campo','id_numerico','versao','valor','modulo'?,'maxCopias'?])` stores the version and deletes the oldest ones beyond `maxCopias`;
- `interface_backup_campo_select([...])` draws the version dropdown;
- `interface_ajax_backup_campo()` returns the chosen value.

## Alerts

`interface_alerta(['msg' => …])` schedules a message for the screen. With `redirect`, it survives the next redirect (it is kept in the session). `interface_finalizar()` calls `interface_alerta(['imprimir' => true])`, which publishes the alert in `javascript-vars.interface.alert` and includes `modal-alerta`.

## Data formatting

`interface_formatar_dado(['dado' => …, 'formato' => …])` is used by the listing's `formatar` columns. The formats are:
- `data` and `dataHora`: `DD/MM/YYYY` and `DD/MM/YYYY HHhMM`;
- `dinheiroReais` and `dinheiroUSD`;
- `telefone`;
- `outraTabela`: replaces the id with a name from another table, through `interface_trocar_valor_outra_tabela()`;
- `outroConjunto` and `outroArray`: replace through a map (`interface_trocar_valor_outro_conjunto()` and `interface_trocar_valor_outro_array()`);
- `encapsular`: wraps the value in a text, through `interface_encapsular_valor()`.

`formato` may also be a list, applied in sequence, with `valor_senao_existe` and `valor_substituir_por_rotulo`.

The formatting helpers:
- `interface_data_hora_from_datetime_to_text($dt, $format = false)` uses the `D/ME/A HhMI` format by default (`D`, `ME`, `A`, `H`, `MI` and `S` are replaced by the date parts);
- `interface_data_from_datetime_to_text($dt)` returns `DD/MM/YYYY`;
- `interface_formatar_telefone($phone)` formats Brazilian numbers with and without `+55` and the North American `+1` pattern; anything else just gets a leading `+`.

## Components and assets

- `interface_componentes_incluir(['componente' => [...]])` marks components (loading, delete and alert modals…), and `interface_componentes()` renders them at the end of the page, in the right variant.
- `interface_componente_variante($id)` returns `<id>-tailwind` on a **Tailwind-only** request; `interface_componente_canonico($id)` strips the suffix. That way the same module serves Fomantic and Tailwind without code changes (req-118).
- `interface_assets_incluir()` includes `interface/interface-tailwind.js` (pure Tailwind) or `interface/interface.css` + `interface/interface.js` (Fomantic/hybrid).
- `interface_botoes_cabecalho()` and `interface_botoes_rodape()` draw the buttons from `botoes`/`botoes_rodape`, through `interface_botoes_html($botoes)`. On a Tailwind-only page they output `<a>`/`<button>` with utilities and a Lucide icon (req-190): `interface_botao_tailwind_classes($cor)` maps the Fomantic color (`blue`, `green`, `red`, `basic …`) to one of four tones, and `interface_botao_tailwind_icone($icone)` maps the icon name (with no match, the button keeps only its label). `excluir` carries the URL in `data-href` and opens the delete modal. The classes live in the `<template data-c2f-botoes>` of the `interface-formulario-*-tailwind` components; a new class in the PHP must be added there too, or it renders unstyled. Add and edit have a Tailwind variant; the listing is still Fomantic.
- `interface_modulo_variavel_valor(['variavel' => …])` reads a field of the module's current record (used to build the "Name - …" title).

## Security and known defects

**Delete and status require the CSRF token.** These two actions run on GET (`?opcao=excluir&id=…`, `?opcao=status&status=I&id=…`), and the global CSRF check only covers POST/PUT/PATCH/DELETE. So `interface_excluir_iniciar()` and `interface_status_iniciar()` call `interface_acao_get_exigir_csrf()`, which requires the session token in the query (`_csrf_token`); without it they show an alert and go back to the module root without changing anything (req-189). Panel links already carry the token: `interface_url_csrf($url)` on buttons rendered by the server and `window.interfaceUrlCsrf(url)` on links built by `interface.js`/`interface-v2.js`. A delete or status link built by hand in a module must go through one of them.

> [!WARNING]
> `interface_verificar_campos()` puts the **column name** into SQL protected only by `banco_escape_field()`, which does not stop quote-free expressions. The value also arrives **escaped twice** through the `verificar-campo` AJAX, so values with an apostrophe never match.

Other defects: `interface_editar_finalizar()` and `interface_alteracoes_finalizar()` pass `'id' => $id` to the history without defining `$id` (undefined variable warning). It has no effect on the result, because `interface_historico()` ignores that parameter and filters by the current record's `id_numerico`.

## See also

- [gestor.php library](gestor.md), [banco.php library](banco.md), [Gestor libraries](index.md)

## Functions (generated reference)

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/interface.php` by `c2f docs:extract` — 64 functions. Do not edit inside this block.

- `interface_data_hora_from_datetime_to_text(string $data_hora, string|false $format = false): string` — [line 38](../../../../../gestor/bibliotecas/interface.php#L38)
  Converte data/hora do formato datetime (YYYY-MM-DD HH:MM:SS) para texto formatado.
  Parameters:
  - `$data_hora`: Data/hora no formato datetime (YYYY-MM-DD HH:MM:SS)
  - `$format`: Formato personalizado usando marcadores ou false para formato padrão
  Returns: Data/hora formatada ou string vazia se não houver data
- `interface_data_from_datetime_to_text(string $data_hora): string` — [line 91](../../../../../gestor/bibliotecas/interface.php#L91)
  Converte data do formato datetime (YYYY-MM-DD) para texto no formato brasileiro (DD/MM/YYYY).
  Parameters:
  - `$data_hora`: Data/hora no formato datetime (YYYY-MM-DD HH:MM:SS)
  Returns: Data formatada (DD/MM/YYYY)
- `interface_trocar_valor_outra_tabela(array|false $params = false): string|array` — [line 122](../../../../../gestor/bibliotecas/interface.php#L122)
  Busca e substitui valores de um campo usando referência de outra tabela.
  Parameters:
  - `$params`: Parâmetros da função:
  - `$params['tabela']`: ['where'] Condição WHERE adicional (opcional)
  - `$params['tabela2']`: Configuração de tabela secundária (opcional)
  - `$params['dado']`: Valor a ser buscado
  - `$params['encapsular']`: Template para encapsular resultado (opcional)
  Returns: Valor trocado, array de valores (se camposExtras) ou resultado encapsulado
- `interface_trocar_valor_outro_conjunto(array|false $params = false): string` — [line 260](../../../../../gestor/bibliotecas/interface.php#L260)
  Troca um valor por outro baseado em um conjunto de mapeamentos.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['dado']`: Dado que será verificado e potencialmente trocado.
  - `$params['conjunto']`: Conjunto de mapeamentos com estrutura [['alvo' => 'valor1', 'troca' => 'novoValor1'], ...].
  Returns: O valor trocado se encontrado no conjunto, ou o dado original caso contrário.
- `interface_trocar_valor_outro_array(array|false $params = false): string` — [line 296](../../../../../gestor/bibliotecas/interface.php#L296)
  Troca um valor por outro baseado em array de valores com campos específicos.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['dado']`: Dado que será verificado e potencialmente trocado.
  - `$params['valores']`: Array de valores onde cada elemento contém os campos de troca e alvo.
  - `$params['campo_troca']`: Nome do campo usado para comparação com o dado.
  - `$params['campo_alvo']`: Nome do campo cujo valor será retornado em caso de correspondência.
  Returns: O valor do campo alvo se encontrado, ou o dado original caso contrário.
- `interface_encapsular_valor(array|false $params = false): string` — [line 331](../../../../../gestor/bibliotecas/interface.php#L331)
  Encapsula um valor dentro de uma cápsula de texto.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['dado']`: Dado que será verificado e potencialmente trocado.
  - `$params['capsula']`: Cápsula de texto onde a variável será substituída.
  - `$params['variavel']`: Variável dentro da cápsula que será substituída pelo dado.
  Returns: O valor encapsulado se encontrado, ou o dado original caso contrário.
- `interface_formatar_telefone(string $telefone): string` — [line 356](../../../../../gestor/bibliotecas/interface.php#L356)
  Formata um número de telefone para exibição.
  Parameters:
  - `$telefone`: Número de telefone (pode conter +, espaços, hífens, parênteses).
  Returns: Telefone formatado ou valor original se não for possível formatar.
- `interface_formatar_dado(array|false $params = false): string` — [line 437](../../../../../gestor/bibliotecas/interface.php#L437)
  Formata um dado de acordo com o formato especificado.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['dado']`: Dado que será formatado.
  - `$params['formato']`: ['valor_senao_existe'] Valor a retornar quando dado está vazio.
  Returns: O dado formatado de acordo com as especificações, ou valor padrão se dado estiver vazio.
- `interface_alerta(array|false $params = false): void|string` — [line 548](../../../../../gestor/bibliotecas/interface.php#L548)
  Gerencia alertas de mensagens para o usuário na interface.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['msg']`: Mensagem a ser exibida como alerta.
  - `$params['redirect']`: Se true, salva o alerta na sessão para exibição após redirecionamento.
  - `$params['imprimir']`: Se true, imprime o HTML do alerta na tela.
  Returns: Retorna HTML do alerta se $imprimir for true, caso contrário não retorna nada.
- `interface_historico_incluir(array|false $params = false): void` — [line 639](../../../../../gestor/bibliotecas/interface.php#L639)
  Inclui registros no histórico de alterações do sistema.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['alteracoes']`: []['tabela'] Tabela para conversão de IDs em nomes textuais.
  - `$params['deletar']`: Se true, incrementa versão para registro de deleção.
  - `$params['id_numerico_manual']`: ID numérico manual do registro.
  - `$params['id_usuarios_manual']`: ID do usuário manual.
  - `$params['id_hosts_manual']`: ID do host manual.
  - `$params['modulo_id']`: ID do módulo a vincular manualmente.
  - `$params['sem_id']`: ['versao'] Versão manual do registro quando sem_id está definido.
  - `$params['tabela']`: ['id'] Se true, usa campo id ao invés de id_numerico.
- `interface_historico(array|false $params = false): void` — [line 766](../../../../../gestor/bibliotecas/interface.php#L766)
  Exibe o histórico de alterações de um registro do sistema.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['id']`: Identificador do registro a consultar o histórico.
  - `$params['modulo']`: Identificador do módulo do registro.
  - `$params['pagina']`: Página onde o histórico será implementado.
  - `$params['sem_id']`: Se true, não filtra por ID no histórico.
  - `$params['moduloVars']`: ['historico']['moduloIdExtra'] Módulo ID extra para trocar labels.
  Returns: Exibe o HTML do histórico diretamente.
- `interface_assets_incluir(): void` — [line 1181](../../../../../gestor/bibliotecas/interface.php#L1181)
  Enfileira o runtime da interface administrativa adequado ao framework da requisição (req-118).
- `interface_componente_variante(string $id, string|null $modo = null): string` — [line 1218](../../../../../gestor/bibliotecas/interface.php#L1218)
  Devolve o id da variante Tailwind de um componente quando a requisição é Tailwind pura.
  Parameters:
  - `$id`: Id canônico do componente.
  - `$modo`: Modo resolvido; quando omitido, usa o da requisição corrente.
  Returns: Id a carregar.
- `interface_componente_canonico(string $id): string` — [line 1239](../../../../../gestor/bibliotecas/interface.php#L1239)
  Reduz o id de um componente à sua forma canônica (sem o sufixo de variante).
  Parameters:
  - `$id`: Id possivelmente sufixado.
  Returns: Id canônico.
- `interface_componentes_incluir(array|false $params = false): void` — [line 1258](../../../../../gestor/bibliotecas/interface.php#L1258)
  Marca componentes para inclusão na interface.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['componente']`: Componente ou array de componentes a incluir
- `interface_componentes(array|false $params = false): void` — [line 1300](../../../../../gestor/bibliotecas/interface.php#L1300)
  Renderiza componentes marcados para inclusão na interface.
  Parameters:
  - `$params`: Parâmetros da função (não utilizado).
- `interface_formulario_campos(array|false $params = false): void` — [line 1409](../../../../../gestor/bibliotecas/interface.php#L1409)
  Gera campos de formulário dinamicamente para a interface.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['pagina']`: Página onde será incluído o campo (opcional).
  - `$params['campos']`: Array de configurações de campos a serem gerados.
- `interface_formulario_validacao(array|false $params = false): void` — [line 2260](../../../../../gestor/bibliotecas/interface.php#L2260)
  Configura validações de formulário usando Semantic UI.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['pagina']`: Página onde aplicar a validação (opcional).
  - `$params['campos']`: Array de campos com suas regras de validação.
- `interface_validacao_campos_obrigatorios(array|false $params = false): void` — [line 2728](../../../../../gestor/bibliotecas/interface.php#L2728)
  Valida campos obrigatórios server-side.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['redirect']`: URL de redirecionamento em caso de erro (opcional).
  - `$params['campos']`: Array de campos a validar com suas regras
- `interface_modulo_variavel_valor(array|false $params = false): mixed` — [line 2829](../../../../../gestor/bibliotecas/interface.php#L2829)
  Obtém valor de variável do registro atual do módulo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['variavel']`: Nome da variável/campo a obter (obrigatório).
  Returns: Valor da variável solicitada.
- `interface_backup_campo_incluir(array|false $params = false): void` — [line 2914](../../../../../gestor/bibliotecas/interface.php#L2914)
  Registra backup de campo no banco de dados.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['campo']`: Nome do campo a fazer backup (obrigatório).
  - `$params['id_numerico']`: ID numérico do registro (obrigatório).
  - `$params['versao']`: Número da versão do backup (obrigatório).
  - `$params['valor']`: Valor do campo a ser guardado (obrigatório).
  - `$params['modulo']`: Nome do módulo (opcional, usa módulo atual se não fornecido).
  - `$params['maxCopias']`: Máximo de cópias a manter (opcional, padrão 20).
- `interface_backup_campo_select(array|false $params = false): void` — [line 3006](../../../../../gestor/bibliotecas/interface.php#L3006)
  Renderiza dropdown de seleção de versões de backup de um campo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['campo']`: Nome do campo no banco de dados (obrigatório).
  - `$params['campo_form']`: Nome do campo no formulário (opcional, usa 'campo' se não fornecido).
  - `$params['callback']`: Nome do evento callback JavaScript para sucesso (obrigatório).
  - `$params['id_numerico']`: Identificador numérico do registro (obrigatório).
  - `$params['modulo']`: Nome do módulo (opcional, usa módulo atual se não fornecido).
  Returns: Renderiza HTML do dropdown diretamente.
- `interface_verificar_campos(array|false $params = false): array` — [line 3129](../../../../../gestor/bibliotecas/interface.php#L3129)
  Verifica alterações em campos comparando valores atuais com valores anteriores.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['campos']`: Lista de campos a verificar (obrigatório).
  - `$params['valores_atuais']`: Valores atuais dos campos (obrigatório).
  - `$params['valores_anteriores']`: Valores anteriores dos campos para comparação (obrigatório).
  Returns: Lista de campos que foram alterados.
- `interface_botoes_cabecalho(array|false $params = false): void` — [line 3196](../../../../../gestor/bibliotecas/interface.php#L3196)
  Renderiza botões de ação no cabeçalho da interface administrativa.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['botoes']`: Array de botões a renderizar (obrigatório).
  Returns: Renderiza HTML dos botões diretamente.
- `interface_botoes_rodape(array|false $params = false): string` — [line 3215](../../../../../gestor/bibliotecas/interface.php#L3215)
  Renderiza botões de ação no rodapé da interface administrativa.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['botoes_rodape']`: Array de botões a renderizar no rodapé (obrigatório).
  Returns: HTML dos botões do rodapé.
- `interface_botoes_html(array $botoes): string` — [line 3234](../../../../../gestor/bibliotecas/interface.php#L3234)
  Monta o HTML de um conjunto de botões (cabeçalho ou rodapé), no framework CSS da página.
  Parameters:
  - `$botoes`: Botões por id: cor, icon, icon2, rotulo, tooltip, url, callback, target.
  Returns: HTML dos botões.
- `interface_botao_tailwind_classes(string $cor): string` — [line 3302](../../../../../gestor/bibliotecas/interface.php#L3302)
  Classes Tailwind de um botão a partir da cor do Fomantic declarada no módulo (`blue`, `basic red`…).
  Parameters:
  - `$cor`: Cor no vocabulário do Fomantic.
  Returns: Classes Tailwind.
- `interface_botao_tailwind_icone(string $icone): string` — [line 3324](../../../../../gestor/bibliotecas/interface.php#L3324)
  Traduz o nome de ícone do Fomantic para o equivalente do Lucide (carregado pelo layout Tailwind).
  Parameters:
  - `$icone`: Nome do ícone no Fomantic.
  Returns: Nome no Lucide ou ''.
- `interface_ajax_backup_campo(array|false $params = false): void` — [line 3379](../../../../../gestor/bibliotecas/interface.php#L3379)
  Processa requisição AJAX para restaurar backup de campo.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['campo']`: Nome do campo (via $_REQUEST ou parâmetro).
  - `$params['id_numerico']`: ID numérico do registro (via $_REQUEST ou parâmetro).
  - `$params['modulo']`: Nome do módulo (opcional, usa módulo atual se não fornecido).
  Returns: Define $_GESTOR['ajax-json'] com o valor do campo.
- `interface_ajax_historico_mais_resultados(): void` — [line 3489](../../../../../gestor/bibliotecas/interface.php#L3489)
  Processa requisição AJAX para carregar mais resultados do histórico.
  Returns: Define $_GESTOR['ajax-json'] com a próxima página do histórico.
- `interface_ajax_listar(): void` — [line 3516](../../../../../gestor/bibliotecas/interface.php#L3516)
  Processa requisição AJAX para listagem dinâmica de registros.
  Returns: Define $_GESTOR['ajax-json'] com o HTML da listagem atualizada.
- `interface_ajax_verificar_campo(): void` — [line 3535](../../../../../gestor/bibliotecas/interface.php#L3535)
  Processa requisição AJAX para verificar existência de valor em campo.
  Returns: Define $_GESTOR['ajax-json'] indicando se campo existe (true/false).
- `interface_acao_get_exigir_csrf()` — [line 3581](../../../../../gestor/bibliotecas/interface.php#L3581)
  req-189 (A2): excluir e status agem por GET (`?opcao=excluir&id=…`), e a validação global de CSRF só cobre POST/PUT/PATCH/DELETE. Com o cookie `SameSite=Lax`, um link aberto por um administrador logado bastava para excluir ou desativar registros. Essas ações passam a exigir o token da sessão na query (`_csrf_token`); os links do painel já saem com ele (`interface_url_csrf()` e `window.interfaceUrlCsrf`). Sem o token: alerta e volta à raiz do módulo, sem alterar nada.
- `interface_url_csrf(string $url): string` — [line 3597](../../../../../gestor/bibliotecas/interface.php#L3597)
  Acrescenta o token CSRF da sessão a links de `opcao=excluir`/`opcao=status` (req-189, A2).
- `interface_excluir_iniciar($params = false)` — [line 3604](../../../../../gestor/bibliotecas/interface.php#L3604)
- `interface_excluir_finalizar(array|false $params = false): void` — [line 3636](../../../../../gestor/bibliotecas/interface.php#L3636)
  Finaliza a interface de exclusão de registro (exclusão lógica).
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['banco']`: Dados da tabela customizada (nome, id, status, where).
  - `$params['historico']`: Se false, desativa inclusão no histórico (padrão: ativa).
  - `$params['callbackFunction']`: Função callback a executar após exclusão.
  Returns: Executa exclusão e redireciona para listagem.
- `interface_status_iniciar(array|false $params = false): void` — [line 3752](../../../../../gestor/bibliotecas/interface.php#L3752)
  Inicializa a interface de alteração de status de registro.
  Parameters:
  - `$params`: Parâmetros da função (não utilizado nesta função).
  Returns: Prepara $_GESTOR para alteração de status ou redireciona.
- `interface_status_finalizar(array|false $params = false): void` — [line 3788](../../../../../gestor/bibliotecas/interface.php#L3788)
  Finaliza a interface de alteração de status de registro.
  Parameters:
  - `$params`: Parâmetros da função.
  - `$params['banco']`: Dados da tabela customizada (nome, id, status, where).
  - `$params['historico']`: Se false, desativa inclusão no histórico (padrão: ativa).
  - `$params['callbackFunction']`: Função callback a executar após alteração.
  Returns: Executa alteração de status e redireciona para listagem.
- `interface_adicionar_iniciar($params = false)` — [line 3880](../../../../../gestor/bibliotecas/interface.php#L3880)
- `interface_clonar_iniciar($params = false)` — [line 3890](../../../../../gestor/bibliotecas/interface.php#L3890)
- `interface_adicionar_finalizar($params = false)` — [line 3918](../../../../../gestor/bibliotecas/interface.php#L3918)
- `interface_adicionar_incomum_iniciar($params = false)` — [line 4036](../../../../../gestor/bibliotecas/interface.php#L4036)
- `interface_adicionar_incomum_finalizar($params = false)` — [line 4046](../../../../../gestor/bibliotecas/interface.php#L4046)
- `interface_editar_incomum_iniciar($params = false)` — [line 4135](../../../../../gestor/bibliotecas/interface.php#L4135)
- `interface_editar_incomum_finalizar($params = false)` — [line 4167](../../../../../gestor/bibliotecas/interface.php#L4167)
- `interface_editar_iniciar($params = false)` — [line 4334](../../../../../gestor/bibliotecas/interface.php#L4334)
- `interface_editar_finalizar($params = false)` — [line 4366](../../../../../gestor/bibliotecas/interface.php#L4366)
- `interface_visualizar_iniciar($params = false)` — [line 4552](../../../../../gestor/bibliotecas/interface.php#L4552)
- `interface_visualizar_finalizar($params = false)` — [line 4580](../../../../../gestor/bibliotecas/interface.php#L4580)
- `interface_config_iniciar($params = false)` — [line 4702](../../../../../gestor/bibliotecas/interface.php#L4702)
- `interface_config_finalizar($params = false)` — [line 4716](../../../../../gestor/bibliotecas/interface.php#L4716)
- `interface_alteracoes_iniciar($params = false)` — [line 4826](../../../../../gestor/bibliotecas/interface.php#L4826)
- `interface_alteracoes_finalizar($params = false)` — [line 4852](../../../../../gestor/bibliotecas/interface.php#L4852)
- `interface_simples_iniciar($params = false)` — [line 5018](../../../../../gestor/bibliotecas/interface.php#L5018)
- `interface_simples_finalizar($params = false)` — [line 5032](../../../../../gestor/bibliotecas/interface.php#L5032)
- `interface_listar_coluna_segura(string $coluna): bool` — [line 5134](../../../../../gestor/bibliotecas/interface.php#L5134)
  Nome de coluna aceito em ORDER BY/WHERE da listagem: identificador simples (`nome`, `t.nome`), nunca a coluna de ações nem expressão. req-189 (A1).
- `interface_listar_ajax($params = false)` — [line 5139](../../../../../gestor/bibliotecas/interface.php#L5139)
- `interface_listar_tabela($params = false)` — [line 5311](../../../../../gestor/bibliotecas/interface.php#L5311)
- `interface_listar_iniciar($params = false)` — [line 5616](../../../../../gestor/bibliotecas/interface.php#L5616)
- `interface_listar_finalizar($params = false)` — [line 5623](../../../../../gestor/bibliotecas/interface.php#L5623)
- `interface_ajax_iniciar($params = false)` — [line 5717](../../../../../gestor/bibliotecas/interface.php#L5717)
- `interface_ajax_finalizar($params = false)` — [line 5724](../../../../../gestor/bibliotecas/interface.php#L5724)
- `interface_iniciar($params = false)` — [line 5757](../../../../../gestor/bibliotecas/interface.php#L5757)
- `interface_finalizar($params = false)` — [line 5824](../../../../../gestor/bibliotecas/interface.php#L5824)

<!-- c2f:extract:end -->
