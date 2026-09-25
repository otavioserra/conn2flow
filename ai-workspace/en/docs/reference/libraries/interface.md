---
title: "interface.php library"
description: "The CRUD engine of the admin modules: the start/finish cycle per option, DataTables listing, forms, validation, history, field backups, alerts and the Tailwind component variants."
section: reference
order: 12
sources:
  - gestor/bibliotecas/interface.php
  - gestor/bibliotecas/seguranca.php
verified_at: dd893291
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
- Subsequent pagination, search and sorting come via AJAX (`ajax-opcao=listar`) in `interface_ajax_listar()` → `interface_listar_ajax()`. The search runs `UCASE(column) LIKE UCASE('%term%')` on each searchable column and on `columnsExtraSearch` (`id` by default).

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
- `interface_botoes_cabecalho()` and `interface_botoes_rodape()` draw the buttons from `botoes`/`botoes_rodape`.
- `interface_modulo_variavel_valor(['variavel' => …])` reads a field of the module's current record (used to build the "Name - …" title).

## Security and known defects

> [!CAUTION]
> **SQL injection in the listing search.** `interface_listar_ajax()` builds the `WHERE`/`ORDER BY` from the search term (`search[value]`), the column names (`columns[i][data]`) and `columnsExtraSearch` coming from `$_REQUEST`, **without escaping or an allow-list**. Any user with access to a listing can read other tables. Fix pending (see the security backlog).

> [!CAUTION]
> **`excluir` and `status` act on GET** (`?opcao=excluir&id=…`), and the core CSRF check only covers POST/PUT/PATCH/DELETE. With the `SameSite=Lax` cookie, a link opened by a logged-in administrator can delete or deactivate records. Fix pending.

> [!WARNING]
> `interface_verificar_campos()` puts the **column name** into SQL protected only by `banco_escape_field()`, which does not stop quote-free expressions. The value also arrives **escaped twice** through the `verificar-campo` AJAX, so values with an apostrophe never match.

Other defects: `interface_editar_finalizar()` and `interface_alteracoes_finalizar()` pass `'id' => $id` to the history without defining `$id` (undefined variable warning). It has no effect on the result, because `interface_historico()` ignores that parameter and filters by the current record's `id_numerico`.

## See also

- [gestor.php library](gestor.md), [banco.php library](banco.md), [Gestor libraries](index.md)

## Functions (generated reference)

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/interface.php` by `c2f docs:extract` — 58 functions. Do not edit inside this block.

- `interface_data_hora_from_datetime_to_text(string $data_hora, string|false $format = false): string` — [line 38](../../../../../gestor/bibliotecas/interface.php#L38)
- `interface_data_from_datetime_to_text(string $data_hora): string` — [line 91](../../../../../gestor/bibliotecas/interface.php#L91)
- `interface_trocar_valor_outra_tabela(array|false $params = false): string|array` — [line 122](../../../../../gestor/bibliotecas/interface.php#L122)
- `interface_trocar_valor_outro_conjunto(array|false $params = false): string` — [line 260](../../../../../gestor/bibliotecas/interface.php#L260)
- `interface_trocar_valor_outro_array(array|false $params = false): string` — [line 296](../../../../../gestor/bibliotecas/interface.php#L296)
- `interface_encapsular_valor(array|false $params = false): string` — [line 331](../../../../../gestor/bibliotecas/interface.php#L331)
- `interface_formatar_telefone(string $telefone): string` — [line 356](../../../../../gestor/bibliotecas/interface.php#L356)
- `interface_formatar_dado(array|false $params = false): string` — [line 437](../../../../../gestor/bibliotecas/interface.php#L437)
- `interface_alerta(array|false $params = false): void|string` — [line 548](../../../../../gestor/bibliotecas/interface.php#L548)
- `interface_historico_incluir(array|false $params = false): void` — [line 639](../../../../../gestor/bibliotecas/interface.php#L639)
- `interface_historico(array|false $params = false): void` — [line 766](../../../../../gestor/bibliotecas/interface.php#L766)
- `interface_assets_incluir(): void` — [line 1181](../../../../../gestor/bibliotecas/interface.php#L1181)
- `interface_componente_variante(string $id, string|null $modo = null): string` — [line 1218](../../../../../gestor/bibliotecas/interface.php#L1218)
- `interface_componente_canonico(string $id): string` — [line 1239](../../../../../gestor/bibliotecas/interface.php#L1239)
- `interface_componentes_incluir(array|false $params = false): void` — [line 1258](../../../../../gestor/bibliotecas/interface.php#L1258)
- `interface_componentes(array|false $params = false): void` — [line 1300](../../../../../gestor/bibliotecas/interface.php#L1300)
- `interface_formulario_campos(array|false $params = false): void` — [line 1409](../../../../../gestor/bibliotecas/interface.php#L1409)
- `interface_formulario_validacao(array|false $params = false): void` — [line 2260](../../../../../gestor/bibliotecas/interface.php#L2260)
- `interface_validacao_campos_obrigatorios(array|false $params = false): void` — [line 2728](../../../../../gestor/bibliotecas/interface.php#L2728)
- `interface_modulo_variavel_valor(array|false $params = false): mixed` — [line 2829](../../../../../gestor/bibliotecas/interface.php#L2829)
- `interface_backup_campo_incluir(array|false $params = false): void` — [line 2914](../../../../../gestor/bibliotecas/interface.php#L2914)
- `interface_backup_campo_select(array|false $params = false): void` — [line 3006](../../../../../gestor/bibliotecas/interface.php#L3006)
- `interface_verificar_campos(array|false $params = false): array` — [line 3129](../../../../../gestor/bibliotecas/interface.php#L3129)
- `interface_botoes_cabecalho(array|false $params = false): void` — [line 3196](../../../../../gestor/bibliotecas/interface.php#L3196)
- `interface_botoes_rodape(array|false $params = false): string` — [line 3243](../../../../../gestor/bibliotecas/interface.php#L3243)
- `interface_ajax_backup_campo(array|false $params = false): void` — [line 3295](../../../../../gestor/bibliotecas/interface.php#L3295)
- `interface_ajax_historico_mais_resultados(): void` — [line 3405](../../../../../gestor/bibliotecas/interface.php#L3405)
- `interface_ajax_listar(): void` — [line 3432](../../../../../gestor/bibliotecas/interface.php#L3432)
- `interface_ajax_verificar_campo(): void` — [line 3451](../../../../../gestor/bibliotecas/interface.php#L3451)
- `interface_excluir_iniciar(array|false $params = false): void` — [line 3490](../../../../../gestor/bibliotecas/interface.php#L3490)
- `interface_excluir_finalizar(array|false $params = false): void` — [line 3521](../../../../../gestor/bibliotecas/interface.php#L3521)
- `interface_status_iniciar(array|false $params = false): void` — [line 3637](../../../../../gestor/bibliotecas/interface.php#L3637)
- `interface_status_finalizar(array|false $params = false): void` — [line 3672](../../../../../gestor/bibliotecas/interface.php#L3672)
- `interface_adicionar_iniciar($params = false)` — [line 3764](../../../../../gestor/bibliotecas/interface.php#L3764)
- `interface_clonar_iniciar($params = false)` — [line 3774](../../../../../gestor/bibliotecas/interface.php#L3774)
- `interface_adicionar_finalizar($params = false)` — [line 3802](../../../../../gestor/bibliotecas/interface.php#L3802)
- `interface_adicionar_incomum_iniciar($params = false)` — [line 3920](../../../../../gestor/bibliotecas/interface.php#L3920)
- `interface_adicionar_incomum_finalizar($params = false)` — [line 3930](../../../../../gestor/bibliotecas/interface.php#L3930)
- `interface_editar_incomum_iniciar($params = false)` — [line 4019](../../../../../gestor/bibliotecas/interface.php#L4019)
- `interface_editar_incomum_finalizar($params = false)` — [line 4051](../../../../../gestor/bibliotecas/interface.php#L4051)
- `interface_editar_iniciar($params = false)` — [line 4218](../../../../../gestor/bibliotecas/interface.php#L4218)
- `interface_editar_finalizar($params = false)` — [line 4250](../../../../../gestor/bibliotecas/interface.php#L4250)
- `interface_visualizar_iniciar($params = false)` — [line 4436](../../../../../gestor/bibliotecas/interface.php#L4436)
- `interface_visualizar_finalizar($params = false)` — [line 4464](../../../../../gestor/bibliotecas/interface.php#L4464)
- `interface_config_iniciar($params = false)` — [line 4586](../../../../../gestor/bibliotecas/interface.php#L4586)
- `interface_config_finalizar($params = false)` — [line 4600](../../../../../gestor/bibliotecas/interface.php#L4600)
- `interface_alteracoes_iniciar($params = false)` — [line 4710](../../../../../gestor/bibliotecas/interface.php#L4710)
- `interface_alteracoes_finalizar($params = false)` — [line 4736](../../../../../gestor/bibliotecas/interface.php#L4736)
- `interface_simples_iniciar($params = false)` — [line 4902](../../../../../gestor/bibliotecas/interface.php#L4902)
- `interface_simples_finalizar($params = false)` — [line 4916](../../../../../gestor/bibliotecas/interface.php#L4916)
- `interface_listar_ajax($params = false)` — [line 5011](../../../../../gestor/bibliotecas/interface.php#L5011)
- `interface_listar_tabela($params = false)` — [line 5194](../../../../../gestor/bibliotecas/interface.php#L5194)
- `interface_listar_iniciar($params = false)` — [line 5499](../../../../../gestor/bibliotecas/interface.php#L5499)
- `interface_listar_finalizar($params = false)` — [line 5506](../../../../../gestor/bibliotecas/interface.php#L5506)
- `interface_ajax_iniciar($params = false)` — [line 5600](../../../../../gestor/bibliotecas/interface.php#L5600)
- `interface_ajax_finalizar($params = false)` — [line 5607](../../../../../gestor/bibliotecas/interface.php#L5607)
- `interface_iniciar($params = false)` — [line 5640](../../../../../gestor/bibliotecas/interface.php#L5640)
- `interface_finalizar($params = false)` — [line 5707](../../../../../gestor/bibliotecas/interface.php#L5707)

<!-- c2f:extract:end -->
