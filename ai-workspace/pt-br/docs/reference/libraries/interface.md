---
title: "Biblioteca interface.php"
label: "Interface de CRUD"
description: "O motor de CRUD dos módulos administrativos: ciclo iniciar/finalizar por opção, listagem com DataTables, formulários, validação, histórico, backups de campo, alertas e as variantes Tailwind dos componentes."
section: reference
order: 12
sources:
  - gestor/bibliotecas/interface.php
  - gestor/bibliotecas/seguranca.php
verified_at: dd893291
---

# Biblioteca `interface.php`

A `interface.php` é o **motor de CRUD** do painel. Um módulo administrativo não monta telas de listagem, edição e exclusão à mão: ele diz o que quer (tabela, colunas, campos, validações, botões), e a biblioteca desenha a tela a partir de componentes (`interface-listar`, `interface-formulario-edicao`…), grava histórico, trata os AJAX padrão e dispara os hooks.

Peça a biblioteca no JSON do módulo (`"bibliotecas": ["interface", …]`).

## O ciclo de um módulo

Todo controlador de módulo segue o mesmo esqueleto:

```php
function meu_modulo_start(){
    global $_GESTOR;
    gestor_incluir_bibliotecas();

    if($_GESTOR['ajax']){
        interface_ajax_iniciar();
        switch($_GESTOR['ajax-opcao']){ /* AJAX próprios do módulo */ }
        interface_ajax_finalizar();          // AJAX padrão: listar, histórico, backup, verificar-campo
    } else {
        meu_modulo_interfaces_padroes();     // preenche $_GESTOR['interface'][<opcao>]
        interface_iniciar();
        switch($_GESTOR['opcao']){
            case 'adicionar': meu_modulo_adicionar(); break;
            case 'editar':    meu_modulo_editar(); break;
        }
        interface_finalizar();
    }
}
```

- **`interface_iniciar()`** roda antes do código do módulo. Ele lê `$_GESTOR['opcao']` (e, se houver, `$_GESTOR['interface-opcao']`), chama o `interface_<opcao>_iniciar()` correspondente com os parâmetros de `$_GESTOR['interface'][<opcao>]['iniciar']` e liga as bandeiras que o módulo testa:
  - `$_GESTOR['adicionar-banco']` quando o formulário de inclusão foi enviado (`_gestor-adicionar`);
  - `$_GESTOR['atualizar-banco']` quando o de edição foi enviado (`_gestor-atualizar`);
  - `$_GESTOR['modulo-registro-id']` com o id do registro (`?id=` em GET, ou `_gestor-registro-id`). Sem id nas opções que precisam dele, redireciona para a raiz do módulo.
- **`interface_finalizar()`** roda depois. Chama o `interface_<opcao>_finalizar()` com `$_GESTOR['interface'][<opcao>]['finalizar']`, que **desenha a tela** em `$_GESTOR['pagina']`, e depois imprime o alerta pendente e renderiza os componentes marcados.
- Com `$_GESTOR['interface-nao-aplicar']`, os dois não fazem nada.

### Opções

| `opcao` / `interface-opcao` | O que o finalizador faz |
|---|---|
| `listar` | Tabela paginada (DataTables) com busca, ordenação, ações por linha e modal de exclusão |
| `adicionar`, `clonar` | Formulário de inclusão (`interface-formulario-inclusao`). `clonar` exige `id` e parte do registro existente |
| `editar` | Formulário de edição (`interface-formulario-edicao`, com variante Tailwind), metadados e histórico |
| `visualizar` | Tela somente leitura (`interface-formulario-visualizacao`) |
| `status` | Troca `status` do registro (`?id=…&status=A|I`), incrementa `versao`, registra no histórico |
| `excluir` | **Exclusão lógica**: `status='D'`, `versao+1`, histórico e redirecionamento |
| `config` | Tela de configuração do módulo, sem registro (`interface-formulario-configuracoes`) |
| `alteracoes`, `simples`, `adicionar-incomum`, `editar-incomum` | Só via `interface-opcao`: variações para telas que não seguem o CRUD padrão |

Os tratadores de cada opção:

| Opção | Início | Fim |
|---|---|---|
| `listar` | `interface_listar_iniciar()` (vazio) | `interface_listar_finalizar()` |
| `adicionar` | `interface_adicionar_iniciar()` | `interface_adicionar_finalizar()` |
| `clonar` | `interface_clonar_iniciar()` (aceita `forcarId`) | `interface_adicionar_finalizar()` |
| `editar` | `interface_editar_iniciar()` (aceita `forcarId`) | `interface_editar_finalizar()` |
| `visualizar` | `interface_visualizar_iniciar()` | `interface_visualizar_finalizar()` |
| `status` | `interface_status_iniciar()` (exige `id` e `status` em GET) | `interface_status_finalizar()` |
| `excluir` | `interface_excluir_iniciar()` (exige `id` em GET) | `interface_excluir_finalizar()` |
| `config` | `interface_config_iniciar()` | `interface_config_finalizar()` |
| `alteracoes` | `interface_alteracoes_iniciar()` (usa `modulo-registro-padrao-id` sem `id`) | `interface_alteracoes_finalizar()` |
| `simples` | `interface_simples_iniciar()` | `interface_simples_finalizar()` |
| `adicionar-incomum` | `interface_adicionar_incomum_iniciar()` | `interface_adicionar_incomum_finalizar()` |
| `editar-incomum` | `interface_editar_incomum_iniciar()` | `interface_editar_incomum_finalizar()` |

### Hooks disparados

Para o módulo `X` e a opção `O` (e também para a `interface-opcao`):
- `hook_do_action('X', 'O.pre-banco')` em todo POST, antes do código do módulo;
- `O.parametros` em GET, antes do finalizador;
- `O.pagina` em GET, depois do finalizador;
- `excluir.banco` (com o id) após a exclusão, e `status.banco` (com id e novo status) após a troca de status.

Veja a doc de hooks para registrar ouvintes.

## Parâmetros dos finalizadores

Os finalizadores de formulário (`adicionar`, `editar`, `visualizar`, `config`, `alteracoes`, `simples`, `*-incomum`) aceitam, conforme o caso:

| Parâmetro | Efeito |
|---|---|
| `formulario` | `['validacao' => [...], 'campos' => [...], 'opcao' => …]`: validação no cliente e campos gerados (abaixo) |
| `botoes` | Botões do cabeçalho: `[id => ['url','rotulo','tooltip','icon','cor', 'callback'?]]` |
| `botoes_rodape` | Botões extras ao pé do formulário, mesmo formato |
| `sem_botao_padrao` | Remove o botão de enviar padrão |
| `metaDados` | Lista `[['titulo' => …, 'dado' => …]]` exibida ao lado do formulário |
| `removerNaoAlterarId` / `removerBotaoEditar` | Tiram o "não alterar id" e o botão de edição |
| `variaveisTrocarDepois` | `['chave' => 'valor']` trocados como `#chave#` no fim da montagem |
| `campoTitulo` / `forcarSemID` | `visualizar`: campo usado no título, ou dispensa de registro |
| `banco`, `historico`, `callbackFunction` | `status`/`excluir`: outra tabela, sem histórico, e função chamada após a gravação |

Os formulários trocam automaticamente todos os `#form-…#` do HTML pelas variáveis do módulo cujo id contém `form` (`gestor_variaveis(['conjunto' => true, 'padrao' => 'form'])`).

## Listagem

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
            Array('id' => 'nome', 'nome' => 'Nome', 'ordenar' => 'asc'),
            Array('id' => 'data_modificacao', 'nome' => 'Modificação', 'formatar' => 'dataHora', 'nao_procurar' => true),
        ),
    ),
    'opcoes' => Array(
        'editar' => Array('url' => 'editar/', 'tooltip' => '…', 'icon' => 'edit', 'cor' => 'basic blue'),
        'excluir' => Array('opcao' => 'excluir', 'tooltip' => '…', 'icon' => 'trash', 'cor' => 'basic red'),
    ),
    'botoes' => Array( /* botão "Adicionar" etc. */ ),
);
```

- `interface_listar_finalizar()` desenha o layout `interface-listar` e o modal `interface-delecao-modal`, e inclui o DataTables. `interface_listar_tabela()` monta a primeira página no servidor e publica a configuração em `javascript-vars.interface.lista`.
- A **coluna de ações é a primeira** (req-147) e usa uma chave própria (`INTERFACE_COLUNA_ACOES`), para o id dos botões nunca vir de uma coluna formatada.
- Colunas: `ordenar` (`asc`/`desc`), `nao_ordenar`, `nao_procurar`, `nao_visivel`, `className` e `formatar` (veja *Formatação*).
- Registros com `status='D'` nunca aparecem. A página atual, o total e o tamanho da página ficam numa **variável de sessão por usuário** (`<modulo>-<opcao>-interface-<usuario>`), que as próximas chamadas AJAX usam.
- A paginação, a busca e a ordenação seguintes vêm por AJAX (`ajax-opcao=listar`) em `interface_ajax_listar()` → `interface_listar_ajax()`. A busca faz `UCASE(coluna) LIKE UCASE('%termo%')` em cada coluna pesquisável e em `columnsExtraSearch` (por padrão, `id`).

## Formulários

### Campos gerados (`formulario.campos`)

`interface_formulario_campos()` insere cada campo no marcador `<span>#<id>#</span>` da página, conforme o `tipo`:

| `tipo` | Gera |
|---|---|
| `select` | `<select>` Fomantic a partir de `tabela` (`nome`, `campo`, `id_numerico` ou `id`, `where`, `id_selecionado`/`valor_selecionado`) ou de `dados` (`[['texto','valor','icone'?]]`). Opções: `menu`, `procurar`, `limpar`, `multiple`, `fluid`, `disabled`, `placeholder` e ícones |
| `imagepick` / `imagepick-hosts` | Seletor de imagem do gerenciador de arquivos (`id_arquivos`/`id_hosts_arquivos`) |
| `templates-hosts` | Seletor de template por `categoria_id` (`template_id`, `template_tipo` `gestor`/`hosts`) |

### Validação no navegador (`formulario.validacao`)

`interface_formulario_validacao()` publica regras para o validador do Fomantic/Tailwind. Cada item é `['regra', 'campo', 'label', 'identificador'?]`, e as regras são:
- `texto-obrigatorio` (3 a 255 caracteres) e `texto-obrigatorio-verificar-campo` (além disso, consulta se o valor já existe);
- `selecao-obrigatorio`, `nao-vazio`, `maior-ou-igual-a-zero`;
- `email`, `email-comparacao`, `email-comparacao-verificar-campo`;
- `senha`, `senha-comparacao`, `dominio`;
- `regexPermited` e `regexNecessary`, com `regrasExtra` para regex próprias.

`removerRegra` tira regras padrão.

### Validação no servidor

`interface_validacao_campos_obrigatorios(['campos' => [...], 'redirect' => …])` confere o `$_REQUEST` antes de gravar. Na primeira falha, grava um alerta e **redireciona** (para `redirect` ou recarrega a URL), encerrando a requisição.

| Regra | Confere |
|---|---|
| `texto-obrigatorio` | Tamanho entre `min` (padrão 3) e `max` (padrão 255), **em bytes** (`strlen`) |
| `selecao-obrigatorio` | Campo preenchido |
| `email-obrigatorio` | Regex própria de e-mail |

> [!WARNING]
> A regex de `email-obrigatorio` não aceita e-mails que **começam com número** (`^[^0-9]`), com **letras maiúsculas** (sem `/i`) ou com `+`, como `1ana@x.com`, `Ana@x.com` e `ana+loja@x.com`. E `texto-obrigatorio` conta bytes: `max` 255 aceita bem menos caracteres acentuados.

- `interface_verificar_campos(['campo', 'valor', 'language'?])` diz se já existe outro registro ativo com aquele valor. Na edição, ignora o próprio registro, e pode olhar outra tabela via `verificarCamposOutraTabela` no JSON do módulo. É o que o AJAX `verificar-campo` (`interface_ajax_verificar_campo()`) usa. O docblock da função descreve outros parâmetros; os reais são estes.

## Histórico

- `interface_historico_incluir(['alteracoes' => [['campo','alteracao','alteracao_txt','valor_antes','valor_depois','tabela','filtro']], …])` grava na tabela `historico` quem mudou o quê, ligado ao módulo e ao registro. Aceita `deletar` (conta a versão seguinte), `sem_id`, `versao`, `tabela` alternativa e ids manuais (`id_numerico_manual`, `id_usuarios_manual`, `id_hosts_manual`, `modulo_id`).
- `interface_historico(['id','modulo','pagina','sem_id'?])` desenha o histórico paginado na tela de edição. `interface_ajax_historico_mais_resultados()` traz as páginas seguintes.

Os CRUDs usam o par `banco_select_campos_antes_iniciar()`/`banco_select_campos_antes()` para saber o "antes" (veja [banco.php](banco.md)).

## Backups de campo

Para campos longos (HTML, CSS), cada versão pode ser guardada e restaurada:
- `interface_backup_campo_incluir(['campo','id_numerico','versao','valor','modulo'?,'maxCopias'?])` guarda a versão e apaga as mais antigas além de `maxCopias`;
- `interface_backup_campo_select([...])` desenha o dropdown de versões;
- `interface_ajax_backup_campo()` devolve o valor escolhido.

## Alertas

`interface_alerta(['msg' => …])` agenda uma mensagem para a tela. Com `redirect`, ela sobrevive ao próximo redirecionamento (fica na sessão). O `interface_finalizar()` chama `interface_alerta(['imprimir' => true])`, que publica o alerta em `javascript-vars.interface.alert` e inclui o `modal-alerta`.

## Formatação de dados

`interface_formatar_dado(['dado' => …, 'formato' => …])` é usada nas colunas `formatar` da listagem. Os formatos são:
- `data` e `dataHora`: `DD/MM/AAAA` e `DD/MM/AAAA HHhMM`;
- `dinheiroReais` e `dinheiroUSD`;
- `telefone`;
- `outraTabela`: troca o id pelo nome de outra tabela, via `interface_trocar_valor_outra_tabela()`;
- `outroConjunto` e `outroArray`: troca por um mapa (`interface_trocar_valor_outro_conjunto()` e `interface_trocar_valor_outro_array()`);
- `encapsular`: põe o dado dentro de um texto, via `interface_encapsular_valor()`.

`formato` também pode ser uma lista, aplicada em sequência, com `valor_senao_existe` e `valor_substituir_por_rotulo`.

As funções auxiliares de formatação:
- `interface_data_hora_from_datetime_to_text($dt, $formato = false)` usa por padrão o formato `D/ME/A HhMI` (`D`, `ME`, `A`, `H`, `MI` e `S` são trocados pelas partes da data);
- `interface_data_from_datetime_to_text($dt)` devolve `DD/MM/AAAA`;
- `interface_formatar_telefone($tel)` formata números brasileiros com e sem `+55` e o padrão norte-americano `+1`; o resto sai só com `+` na frente.

## Componentes e assets

- `interface_componentes_incluir(['componente' => [...]])` marca componentes (modais de carregamento, deleção e alerta…), e `interface_componentes()` os renderiza no fim da página, na variante certa.
- `interface_componente_variante($id)` devolve `<id>-tailwind` numa requisição **só Tailwind**; `interface_componente_canonico($id)` tira o sufixo. Assim o mesmo módulo serve Fomantic e Tailwind sem mudar código (req-118).
- `interface_assets_incluir()` inclui `interface/interface-tailwind.js` (Tailwind puro) ou `interface/interface.css` + `interface/interface.js` (Fomantic/híbrido).
- `interface_botoes_cabecalho()` e `interface_botoes_rodape()` desenham os botões a partir de `botoes`/`botoes_rodape`.
- `interface_modulo_variavel_valor(['variavel' => …])` lê um campo do registro atual do módulo (usado para compor o título "Nome - …").

## Segurança e defeitos conhecidos

> [!CAUTION]
> **SQL injection na busca da listagem.** `interface_listar_ajax()` monta o `WHERE`/`ORDER BY` com o termo de busca (`search[value]`), os nomes de coluna (`columns[i][data]`) e `columnsExtraSearch` vindos do `$_REQUEST`, **sem escape nem lista de permissão**. Qualquer usuário com acesso a uma listagem consegue ler outras tabelas. Correção pendente (ver backlog de segurança).

> [!CAUTION]
> **`excluir` e `status` agem por GET** (`?opcao=excluir&id=…`), e a validação CSRF do core só cobre POST/PUT/PATCH/DELETE. Com o cookie `SameSite=Lax`, um link aberto por um administrador logado pode excluir ou desativar registros. Correção pendente.

> [!WARNING]
> `interface_verificar_campos()` põe o **nome da coluna** no SQL protegido só por `banco_escape_field()`, que não impede expressões sem aspas. O valor também chega **escapado duas vezes** pelo AJAX `verificar-campo`, então valores com apóstrofo nunca batem.

Outros defeitos: `interface_editar_finalizar()` e `interface_alteracoes_finalizar()` passam `'id' => $id` ao histórico sem definir `$id` (*warning* de variável indefinida). Sem efeito no resultado, porque `interface_historico()` ignora esse parâmetro e filtra pelo `id_numerico` do registro atual.

## Veja também

- [Biblioteca gestor.php](gestor.md), [Biblioteca banco.php](banco.md), [Bibliotecas do Gestor](index.md)

## Funções (referência gerada)

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/interface.php` por `c2f docs:extract` — 58 funções. Não edite dentro deste bloco.

- `interface_data_hora_from_datetime_to_text(string $data_hora, string|false $format = false): string` — [linha 38](../../../../../gestor/bibliotecas/interface.php#L38)
- `interface_data_from_datetime_to_text(string $data_hora): string` — [linha 91](../../../../../gestor/bibliotecas/interface.php#L91)
- `interface_trocar_valor_outra_tabela(array|false $params = false): string|array` — [linha 122](../../../../../gestor/bibliotecas/interface.php#L122)
- `interface_trocar_valor_outro_conjunto(array|false $params = false): string` — [linha 260](../../../../../gestor/bibliotecas/interface.php#L260)
- `interface_trocar_valor_outro_array(array|false $params = false): string` — [linha 296](../../../../../gestor/bibliotecas/interface.php#L296)
- `interface_encapsular_valor(array|false $params = false): string` — [linha 331](../../../../../gestor/bibliotecas/interface.php#L331)
- `interface_formatar_telefone(string $telefone): string` — [linha 356](../../../../../gestor/bibliotecas/interface.php#L356)
- `interface_formatar_dado(array|false $params = false): string` — [linha 437](../../../../../gestor/bibliotecas/interface.php#L437)
- `interface_alerta(array|false $params = false): void|string` — [linha 548](../../../../../gestor/bibliotecas/interface.php#L548)
- `interface_historico_incluir(array|false $params = false): void` — [linha 639](../../../../../gestor/bibliotecas/interface.php#L639)
- `interface_historico(array|false $params = false): void` — [linha 766](../../../../../gestor/bibliotecas/interface.php#L766)
- `interface_assets_incluir(): void` — [linha 1181](../../../../../gestor/bibliotecas/interface.php#L1181)
- `interface_componente_variante(string $id, string|null $modo = null): string` — [linha 1218](../../../../../gestor/bibliotecas/interface.php#L1218)
- `interface_componente_canonico(string $id): string` — [linha 1239](../../../../../gestor/bibliotecas/interface.php#L1239)
- `interface_componentes_incluir(array|false $params = false): void` — [linha 1258](../../../../../gestor/bibliotecas/interface.php#L1258)
- `interface_componentes(array|false $params = false): void` — [linha 1300](../../../../../gestor/bibliotecas/interface.php#L1300)
- `interface_formulario_campos(array|false $params = false): void` — [linha 1409](../../../../../gestor/bibliotecas/interface.php#L1409)
- `interface_formulario_validacao(array|false $params = false): void` — [linha 2260](../../../../../gestor/bibliotecas/interface.php#L2260)
- `interface_validacao_campos_obrigatorios(array|false $params = false): void` — [linha 2728](../../../../../gestor/bibliotecas/interface.php#L2728)
- `interface_modulo_variavel_valor(array|false $params = false): mixed` — [linha 2829](../../../../../gestor/bibliotecas/interface.php#L2829)
- `interface_backup_campo_incluir(array|false $params = false): void` — [linha 2914](../../../../../gestor/bibliotecas/interface.php#L2914)
- `interface_backup_campo_select(array|false $params = false): void` — [linha 3006](../../../../../gestor/bibliotecas/interface.php#L3006)
- `interface_verificar_campos(array|false $params = false): array` — [linha 3129](../../../../../gestor/bibliotecas/interface.php#L3129)
- `interface_botoes_cabecalho(array|false $params = false): void` — [linha 3196](../../../../../gestor/bibliotecas/interface.php#L3196)
- `interface_botoes_rodape(array|false $params = false): string` — [linha 3243](../../../../../gestor/bibliotecas/interface.php#L3243)
- `interface_ajax_backup_campo(array|false $params = false): void` — [linha 3295](../../../../../gestor/bibliotecas/interface.php#L3295)
- `interface_ajax_historico_mais_resultados(): void` — [linha 3405](../../../../../gestor/bibliotecas/interface.php#L3405)
- `interface_ajax_listar(): void` — [linha 3432](../../../../../gestor/bibliotecas/interface.php#L3432)
- `interface_ajax_verificar_campo(): void` — [linha 3451](../../../../../gestor/bibliotecas/interface.php#L3451)
- `interface_excluir_iniciar(array|false $params = false): void` — [linha 3490](../../../../../gestor/bibliotecas/interface.php#L3490)
- `interface_excluir_finalizar(array|false $params = false): void` — [linha 3521](../../../../../gestor/bibliotecas/interface.php#L3521)
- `interface_status_iniciar(array|false $params = false): void` — [linha 3637](../../../../../gestor/bibliotecas/interface.php#L3637)
- `interface_status_finalizar(array|false $params = false): void` — [linha 3672](../../../../../gestor/bibliotecas/interface.php#L3672)
- `interface_adicionar_iniciar($params = false)` — [linha 3764](../../../../../gestor/bibliotecas/interface.php#L3764)
- `interface_clonar_iniciar($params = false)` — [linha 3774](../../../../../gestor/bibliotecas/interface.php#L3774)
- `interface_adicionar_finalizar($params = false)` — [linha 3802](../../../../../gestor/bibliotecas/interface.php#L3802)
- `interface_adicionar_incomum_iniciar($params = false)` — [linha 3920](../../../../../gestor/bibliotecas/interface.php#L3920)
- `interface_adicionar_incomum_finalizar($params = false)` — [linha 3930](../../../../../gestor/bibliotecas/interface.php#L3930)
- `interface_editar_incomum_iniciar($params = false)` — [linha 4019](../../../../../gestor/bibliotecas/interface.php#L4019)
- `interface_editar_incomum_finalizar($params = false)` — [linha 4051](../../../../../gestor/bibliotecas/interface.php#L4051)
- `interface_editar_iniciar($params = false)` — [linha 4218](../../../../../gestor/bibliotecas/interface.php#L4218)
- `interface_editar_finalizar($params = false)` — [linha 4250](../../../../../gestor/bibliotecas/interface.php#L4250)
- `interface_visualizar_iniciar($params = false)` — [linha 4436](../../../../../gestor/bibliotecas/interface.php#L4436)
- `interface_visualizar_finalizar($params = false)` — [linha 4464](../../../../../gestor/bibliotecas/interface.php#L4464)
- `interface_config_iniciar($params = false)` — [linha 4586](../../../../../gestor/bibliotecas/interface.php#L4586)
- `interface_config_finalizar($params = false)` — [linha 4600](../../../../../gestor/bibliotecas/interface.php#L4600)
- `interface_alteracoes_iniciar($params = false)` — [linha 4710](../../../../../gestor/bibliotecas/interface.php#L4710)
- `interface_alteracoes_finalizar($params = false)` — [linha 4736](../../../../../gestor/bibliotecas/interface.php#L4736)
- `interface_simples_iniciar($params = false)` — [linha 4902](../../../../../gestor/bibliotecas/interface.php#L4902)
- `interface_simples_finalizar($params = false)` — [linha 4916](../../../../../gestor/bibliotecas/interface.php#L4916)
- `interface_listar_ajax($params = false)` — [linha 5011](../../../../../gestor/bibliotecas/interface.php#L5011)
- `interface_listar_tabela($params = false)` — [linha 5194](../../../../../gestor/bibliotecas/interface.php#L5194)
- `interface_listar_iniciar($params = false)` — [linha 5499](../../../../../gestor/bibliotecas/interface.php#L5499)
- `interface_listar_finalizar($params = false)` — [linha 5506](../../../../../gestor/bibliotecas/interface.php#L5506)
- `interface_ajax_iniciar($params = false)` — [linha 5600](../../../../../gestor/bibliotecas/interface.php#L5600)
- `interface_ajax_finalizar($params = false)` — [linha 5607](../../../../../gestor/bibliotecas/interface.php#L5607)
- `interface_iniciar($params = false)` — [linha 5640](../../../../../gestor/bibliotecas/interface.php#L5640)
- `interface_finalizar($params = false)` — [linha 5707](../../../../../gestor/bibliotecas/interface.php#L5707)

<!-- c2f:extract:end -->
