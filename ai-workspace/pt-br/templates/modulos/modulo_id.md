# Template de Módulo (`modulo_id`)

Este diretório é o MODELO OFICIAL de como estruturar um módulo dentro do Gestor. NÃO é um módulo funcional de negócio; serve como referência para criação, leitura e padronização.

---
## Visão Geral
Um módulo é composto por:
1. **Arquivo de configuração JSON** (`modulo_id.json`): define tabela, páginas (valor HTML e CSS de uma página), componentes (HTML e CSS curtos que se repetem como um alerta, ou pedaços de uma página), variáveis (valores dinâmicos de informações que mudam numa página ou componente), bibliotecas e opcionalmente dados de seleção (selectDados*) para dropdowns e campos de escolha.
    - Exemplo de Registro de Página:
   ```json
    {
        "name": "Nome Livre", // Nome da página, pode ser qualquer um, é só referencial
        "id": "modulo-id-opcao", // Identificador único em relação a todas as páginas.
        "layout": "layout-id", // Identificador do layout principal que será usado. Referência `gestor\resources\pt-br\layouts.json`. Para o painel administrativo: `layout-administrativo-do-gestor` para uma página comum: `layout-pagina-sem-permissao`.
        "path": "modulo-id/opcao/", // Roteamento da página em relação a raiz do sistema.
        "type": "system|page", // Pode ser tipo sistema (módulos e alguma operação administrativa) ou página (página comum sem vinculação a módulos).
        "option": "opcao", // Opção que será vinculada à página. Usada no módulo para lidar com a lógica da página do módulo em específico.
        "root": true, // Se é ou não a página raiz do módulo.
        "version": "1.0"
    }
   ```
    - Exemplo de Registro de Componente:
   ```json
    {
        "name": "Nome Livre", // Nome do componente, pode ser qualquer um, é só referencial
        "id": "identificador-unico-do-componente-dentro-do-modulo", // Identificador do mesmo para poder acessar o componente.
        "version": "1.0"
    }
   ```
    - Exemplo de Registro de Variável:
   ```json
    {
        "id": "identificador-unico-da-variavel-dentro-do-modulo", // Identificador do mesmo para poder acessar a variável.
        "value": "valor da variável", // Valor da variável em si. É do tipo MEDIUMTEXT, ou seja, pode usar bastante espaço.
        "type": "string" // Tipo da variável.
    }
   ```
   Tipos de Variável:
   ```php
    'camposTipos' => Array(
		Array(	'texto' => 'String',				'valor' => 'string',			),
		Array(	'texto' => 'Texto',					'valor' => 'text',				),
		Array(	'texto' => 'Booleano',				'valor' => 'bool',				),
		Array(	'texto' => 'Número',				'valor' => 'number',			),
		Array(	'texto' => 'Quantidade',			'valor' => 'quantidade',		),
		Array(	'texto' => 'Dinheiro',				'valor' => 'dinheiro',			),
		Array(	'texto' => 'CSS',					'valor' => 'css',				),
		Array(	'texto' => 'JS',					'valor' => 'js',				),
		Array(	'texto' => 'HTML',					'valor' => 'html',				),
		Array(	'texto' => 'TinyMCE',				'valor' => 'tinymce',			),
		Array(	'texto' => 'Datas Multiplas',		'valor' => 'datas-multiplas',	),
		Array(	'texto' => 'Data',					'valor' => 'data',				),
		Array(	'texto' => 'Data e Hora',			'valor' => 'data-hora',			),
	),
   ```
2. **Arquivo PHP** (`modulo_id.php`): controla rotas (opções), CRUD, validações, histórico, backup, interface e AJAX.
3. **Arquivo JS** (`modulo_id.js`): lógica de interface dinâmica, chamadas AJAX, inicialização frontend e funcionalidades específicas como CodeMirror, modais, sistemas de preview, etc.
4. **Diretório `resources/<lingua>/`** com:
   - `pages/` → páginas HTML/CSS do módulo.
   - `components/` → componentes reutilizáveis.
   - `layouts/` → (opcional) layouts específicos do módulo.

Bibliotecas essenciais: `banco`, `gestor`, `modelo` são carregadas implicitamente; adicionais devem ser listadas em `modulo_id.json`.

---
## 1. Arquivo de Configuração (`modulo_id.json`)
Neste arquivo faz-se o mapeamento dos dados de recursos. Mas os arquivos físicos .HTML e .CSS estão na pasta `resources/<lingua>/`.
### Campos principais:
- `versao`: versão semântica do módulo.
- `bibliotecas`: lista de bibliotecas adicionais (apelidos definidos em `gestor/config.php`).
- `tabela`: mapeamento da tabela primária:
  - `nome`, `id` (campo identificador textual), `id_numerico`, `status`, `versao`, `data_criacao`, `data_modificacao`.
- `selectDados*` (opcional): arrays de dados para dropdowns e seleções, nomeados conforme necessidade do módulo (ex: `selectDadosTipo`, `selectDadosStatus`, `selectDadosFrameworkCSS`, etc.).

#### Estrutura de selectDados*
Os arrays `selectDados*` seguem um padrão específico:
```json
{
  "selectDadosFrameworkCSS": [
    {
      "texto": "Fomantic UI",
      "valor": "fomantic-ui"
    },
    {
      "texto": "TailwindCSS", 
      "valor": "tailwindcss"
    }
  ],
  "selectDadosStatus": [
    {
      "texto": "Ativo",
      "valor": "A"
    },
    {
      "texto": "Inativo",
      "valor": "I"
    }
  ]
}
```

#### Uso em Formulários
```php
// Campo select usando selectDados*
Array(
    'tipo' => 'select',
    'id' => 'framework-css',
    'nome' => 'framework_css',
    'valor_selecionado' => $framework_css, // valor atual para edição
    'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
    'dados' => $modulo['selectDadosFrameworkCSS'], // referência ao array
),
```
- `resources`:
  - `<lingua>` → ex: `pt-br`.
    - `pages`: lista de páginas do módulo:
      - `name` (livre), `id` (identificador interno único), `layout` (layout global existente), `path` (rota pública com `/` final), `type` (`system` ou `page`), `option` (valor de `opcao` que o PHP usa no switch), `root` (boolean se rota padrão), `version`.
    - `components`: coleção de componentes reutilizáveis (cada um com `id` e `version`).
    - `variables`: variáveis consumidas via `gestor_variaveis`:
      - `id`, `value`, `type` (ex: `string`, `int`, `json`, `html`, `markdown`).

Regras:
- `id` de página vira parte da referência de arquivos: `resources/pt-br/pages/<id>/<id>.html` e opcional `.css`.
- `components` seguem estrutura: `resources/pt-br/components/<id>/<id>.html|css`.
- `layout` aponta para layout já existente ou customizado em `layouts`.

---
## 2. Arquivo PHP (`modulo_id.php`)
Responsabilidades:
- Carregar config JSON: `$_GESTOR['modulo#'.$_GESTOR['modulo-id']]`.
- Definir `$_GESTOR['modulo-id']`.
- Incluir bibliotecas declaradas (via `gestor_incluir_bibliotecas()`).
- Implementar interfaces padrão (listar, adicionar, editar) seguindo padrões de:
  - Validação: `interface_validacao_campos_obrigatorios`.
  - Identificador único: `banco_identificador`.
  - Verificação de unicidade adicional: `interface_verificar_campos`.
  - Insert: `banco_insert_name`.
  - Update + histórico: `banco_select_campos_antes_iniciar`, `interface_historico_incluir`.
  - Incremento de versão: campo `versao` via `versao = versao + 1`.
  - Botões de cabeçalho configurados em `$_GESTOR['interface']['editar']['finalizar']['botoes']`.
  - Lista configurada via `$_GESTOR['interface']['listar']['finalizar']` com:
    - `banco` (nome, campos, id, status)
    - `tabela.colunas` (campos, formatações, ordenação, pesquisa)
    - `opcoes` (editar, status toggle, excluir)
    - `botoes` (adicionar etc.)

Fluxo típico (não-AJAX):
1. `modulo_id_start()` → detecta se é AJAX ou interface.
2. Para interface: `interface_iniciar()` → switch em `$_GESTOR['opcao']` → chama função (ex: `modulo_id_adicionar`).
3. Função monta `$_GESTOR['interface'][<acao>]['finalizar']` e manipula `$_GESTOR['pagina']` com substituições (`modelo_var_troca_tudo`).
4. `interface_finalizar()` gera HTML final.

Fluxo AJAX:
1. `ajax=sim` + `ajaxOpcao` no POST.
2. `interface_ajax_iniciar()`.
3. Switch em `$_GESTOR['ajax-opcao']`.
4. Função define `$_GESTOR['ajax-json']`.
5. `interface_ajax_finalizar()` serializa JSON.

Funções chave usadas (referência sucinta):
- `gestor_variaveis()` → pega variáveis do módulo ou globais.
- `interface_formatar_dado()` → formatação de campos (dataHora, outraTabela, etc.).
- `gestor_pagina_javascript_incluir()` → inclui `<modulo>.js`.
- `gestor_redirecionar()` / `_raiz()` → navegação.
- `interface_alerta()` → feedback ao usuário.
- `interface_modulo_variavel_valor()` → acesso a valores da tabela após seleção.

Histórico & Backup:
- `interface_historico_incluir()` registra alterações campo a campo se `banco_select_campos_antes_iniciar()` foi chamado.
- `interface_backup_campo_incluir()` armazena valor de campos-chave (opcional) antes da modificação.

Status & Exclusão:
- Padrão: exclusão lógica (`status='D'`).
- Toggle status via botões `status` / `ativar` / `desativar` configurados.

Segurança & Boas Práticas:
- Sempre escapar valores que forem ser adicionados no banco de dados para evitar SQL injection: `banco_escape_field($_REQUEST['campo'])`.
- Validar campos obrigatórios também no PHP (não confiar só em JS).
- Nunca confiar em `id` vindo do cliente sem conferir existência e status != 'D'.

---
## 3. Arquivo JavaScript (`modulo_id.js`)
Responsabilidades:
- Inicialização de componentes (dropdowns, formulários, widgets).
- Configuração de bibliotecas externas (CodeMirror, modais, etc.).
- Validação assíncrona (AJAX) complementar.
- Funcionalidades específicas do módulo (sistemas de preview, editores, etc.).
- Requisições AJAX seguindo padrão:
```js
$.ajax({
  type: 'POST',
  url: gestor.raiz + gestor.moduloCaminho + '/',
  data: { opcao: gestor.moduloOpcao, ajax: 'sim', ajaxOpcao: 'opcao', params: {} },
  dataType: 'json',
  beforeSend: function(){ $.carregar_abrir(); },
  success: function(dados){ if(dados.status==='ok'){ /* ... */ } $.carregar_fechar(); },
  error: function(err){ /* tratar */ $.carregar_fechar(); }
});
```
- Deve chamar funções internas de inicialização (ex: `exemploChamadas();`).
- Evitar lógica pesada inline no HTML (tudo vai aqui).

### Inclusão de Recursos Externos
Quando necessário usar bibliotecas como CodeMirror, TinyMCE, etc., incluir no PHP:
```php
// CSS
$_GESTOR['css'][] = '<link rel="stylesheet" href="'.$_GESTOR['url-raiz'].'biblioteca/arquivo.css" />';
// JavaScript  
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'biblioteca/arquivo.js"></script>';
```

#### CodeMirror para Editores de Código
Para módulos que editam HTML, CSS ou JavaScript:
```php
// ===== Inclusão do CodeMirror
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/theme/tomorrow-night-bright.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.css" />';
$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.css" />';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/lib/codemirror.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/selection/active-line.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/dialog/dialog.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/searchcursor.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/search.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/scroll/annotatescrollbar.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/matchesonscrollbar.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/search/jump-to-line.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/edit/matchbrackets.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/addon/display/fullscreen.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/xml/xml.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/css/css.js"></script>';
$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'codemirror-5.59.1/mode/htmlmixed/htmlmixed.js"></script>';

// ===== Inclusão Módulo JS (sempre após bibliotecas externas)
gestor_pagina_javascript_incluir();
```

### Suporte a Múltiplos Frameworks CSS

#### Campo framework_css
Desde a versão v1.15.0, o sistema suporta múltiplos frameworks CSS. Para módulos que gerenciam recursos visuais (layouts, páginas, componentes):

```php
// No formulário de adição/edição
Array(
    'tipo' => 'select',
    'id' => 'framework-css',
    'nome' => 'framework_css',
    'valor_selecionado' => 'fomantic-ui', // padrão
    'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
    'dados' => $modulo['selectDadosFrameworkCSS'],
),
```

#### Configuração no JSON
```json
{
  "selectDadosFrameworkCSS": [
    {
      "texto": "Fomantic UI",
      "valor": "fomantic-ui"
    },
    {
      "texto": "TailwindCSS",
      "valor": "tailwindcss"
    }
  ]
}
```

#### Validação de Framework
```php
// Validação obrigatória para módulos visuais
Array(
    'regra' => 'selecao-obrigatorio',
    'campo' => 'framework_css',
    'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
    'identificador' => 'framework_css',
)
```

#### Persistência no Banco
```php
// Incluir o campo na persistência
$campo_nome = "framework_css"; $post_nome = $campo_nome; 
if($_REQUEST[$post_nome]) $campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
```

---
## 4. Estrutura de Resources
```
resources/
  pt-br/
    pages/
      modulo-id-opcao/
        modulo-id-opcao.html
        modulo-id-opcao.css (opcional)
    components/
      identificador-unico-do-componente-dentro-do-modulo/
        identificador-unico-do-componente-dentro-do-modulo.html
        identificador-unico-do-componente-dentro-do-modulo.css (opcional)
    layouts/ (opcional)
```
Observações:
- O conteúdo HTML nunca fica direto no PHP, apenas referências/substituições de placeholders via `modelo_var_troca_tudo`.
- Variáveis de texto ficam no JSON (`variables`).

### Uso de Componentes Avançados
Para módulos que usam modais, editores ou sistemas de preview:

#### Sistema de Modais (Padrão Correto)
```php
// ===== ORDEM CRÍTICA: Modal ANTES do CodeMirror
$_GESTOR['dependencias']['assets']['final'] = Array(
    // 1. Modal component (PRIMEIRO na ordem)
    'components/modal-preview-' . $_GESTOR['modulo-id'] . '.php',
    
    // 2. CodeMirror e bibliotecas externas (DEPOIS do modal)
    'assets/codemirror/lib/codemirror.js',
    'assets/codemirror/mode/xml/xml.js',
    'assets/codemirror/mode/css/css.js',
    'assets/codemirror/mode/javascript/javascript.js',
    'assets/codemirror/mode/htmlmixed/htmlmixed.js',
    
    // 3. Scripts customizados (POR ÚLTIMO)
    'assets/modulos/' . $_GESTOR['modulo-id'] . '/script.js'
);

// ===== CHAMADA CORRETA: SEM parâmetro 'variaveis'
$modalComponente = gestor_componente('modal-preview');

// ===== SUBSTITUIÇÃO DE VARIÁVEIS: Individual com modelo_var_troca
$modalComponente = modelo_var_troca($modalComponente,'#title#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'modal-title-preview')));
$modalComponente = modelo_var_troca($modalComponente,'#desktop#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'modal-desktop-preview')));
$modalComponente = modelo_var_troca($modalComponente,'#tablet#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'modal-tablet-preview')));
$modalComponente = modelo_var_troca($modalComponente,'#mobile#',gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'modal-mobile-preview')));
$modalComponente = modelo_var_troca($modalComponente,'#button-tooltip#',gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-title')));
$modalComponente = modelo_var_troca($modalComponente,'#button-value#',gestor_variaveis(Array('modulo' => 'interface','id' => 'form-button-value')));

// ===== EXIBIÇÃO DO COMPONENTE
echo $modalComponente;
```

**⚠️ IMPORTANTE - Ordem de Inclusão:**
O modal deve ser incluído **ANTES** das inclusões do CodeMirror ou outras bibliotecas externas para garantir que os elementos estejam disponíveis quando os scripts são carregados.

#### ❌ Padrão Incorreto (NÃO usar)
```php
// ERRO 1: parâmetro 'variaveis' não existe no gestor_componente
$modalComponente = gestor_componente(Array(
    'modulo' => 'modulo',
    'componente' => 'modal',
    'variaveis' => Array(  // ❌ ERRO: este parâmetro não existe
        'titulo' => 'Título'
    )
));

// ERRO 2: Modal incluído DEPOIS do CodeMirror (ordem incorreta)
$_GESTOR['dependencias']['assets']['final'] = Array(
    'assets/codemirror/lib/codemirror.js',  // ❌ ERRO: CodeMirror primeiro
    'components/modal-preview.php'          // ❌ ERRO: Modal depois
);

// ERRO 3: tentativa de uso de substitua_variaveis ao invés de modelo_var_troca
$modalComponente = substitua_variaveis($modalComponente, Array(  // ❌ ERRO: função inexistente
    '#title#' => 'valor'
));
```

**Problemas Identificados:**
- ❌ `gestor_componente()` NÃO aceita parâmetro 'variaveis'
- ❌ Modal incluído após CodeMirror quebra a inicialização
- ❌ Função `substitua_variaveis()` não existe - use `modelo_var_troca()`
    )
));
```

#### Sistema de Preview TailwindCSS
Para módulos que editam HTML/CSS (layouts, páginas, componentes):
```php
// ===== IMPLEMENTAR EM AMBAS AS FUNÇÕES: adicionar E editar

function modulo_adicionar() {
    // ... lógica de adicionar
    
    // ===== ORDEM CRÍTICA: Modal ANTES do CodeMirror
    $_GESTOR['dependencias']['assets']['final'] = Array(
        // 1. Modal PRIMEIRO
        'components/modal-preview-' . $_GESTOR['modulo-id'] . '.php',
        
        // 2. CodeMirror DEPOIS
        'assets/codemirror/lib/codemirror.js',
        'assets/codemirror/mode/xml/xml.js',
        'assets/codemirror/mode/css/css.js',
        'assets/codemirror/mode/javascript/javascript.js',
        'assets/codemirror/mode/htmlmixed/htmlmixed.js',
        
        // 3. Scripts customizados POR ÚLTIMO
        'assets/modulos/' . $_GESTOR['modulo-id'] . '/script.js'
    );
    
    // ===== CHAMADA CORRETA DO MODAL (sem 'variaveis')
    $modalPreview = gestor_componente('modal-preview');
    
    // ===== SUBSTITUIÇÕES INDIVIDUAIS
    $modalPreview = modelo_var_troca($modalPreview, '#modal-id#', 'modal-preview-' . $_GESTOR['modulo-id']);
    $modalPreview = modelo_var_troca($modalPreview, '#titulo#', gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'], 'id' => 'modal-preview-title')));
    
    echo $modalPreview;
}

function modulo_editar($id) {
    // ... lógica de editar
    
    // ===== MESMO PADRÃO para função editar
    $_GESTOR['dependencias']['assets']['final'] = Array(
        'components/modal-preview-' . $_GESTOR['modulo-id'] . '.php',
        'assets/codemirror/lib/codemirror.js',
        // ... demais assets na mesma ordem
    );
    
    $modalPreview = gestor_componente('modal-preview');
    $modalPreview = modelo_var_troca($modalPreview, '#modal-id#', 'modal-preview-' . $_GESTOR['modulo-id']);
    echo $modalPreview;
}
```

**Botão de Preview Personalizado:**
```php
// Na configuração do formulário
'botoes_rodape' => [
    'previsualizar' => [
        'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-button-preview')),
        'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-preview')),
        'icon' => 'plus circle',
        'cor' => 'positive',
        'callback' => 'previsualizar', // função JavaScript no script.js
    ],
],
```

### Funcionalidades Avançadas

#### Sistema de Endpoint Preview
Para módulos com preview, criar arquivo `preview.php`:
```php
// controladores/modulos/admin-layouts/preview.php
<?php
require_once '../../../gestor.php';

$nome = $_POST['nome'] ?? '';
$html = $_POST['html'] ?? '';
$css = $_POST['css'] ?? '';
$framework_css = $_POST['framework_css'] ?? 'fomantic';

// Configurar framework CSS
$cssFramework = '';
if ($framework_css === 'tailwindcss') {
    $cssFramework = '<script src="https://cdn.tailwindcss.com"></script>';
} else {
    $cssFramework = '<link rel="stylesheet" href="' . $_GESTOR['url-raiz'] . 'assets/fomantic/semantic.min.css">';
}

// Estrutura de preview
$preview = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Preview: $nome</title>
    $cssFramework
    <style>$css</style>
</head>
<body>
    $html
</body>
</html>";

echo $preview;
?>
```

#### JavaScript de Preview (script.js)
```javascript
function previsualizar() {
    // Obtém dados do formulário
    var formData = new FormData($('#form-' + moduloAtual)[0]);
    
    // Chama endpoint de preview
    $.ajax({
        url: 'controladores/modulos/' + moduloAtual + '/preview.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Exibe no iframe do modal
            $('#preview-frame').attr('src', 'data:text/html;charset=utf-8,' + encodeURIComponent(response));
            $('#modal-preview-' + moduloAtual).modal('show');
        },
        error: function() {
            alert('Erro ao gerar preview');
        }
    });
}
```

### Padrões de Formulário Aprendidos
```php
// Interface de adicionar com botões personalizados (ex: sistema de preview)
$_GESTOR['interface']['adicionar']['finalizar'] = Array(
    'sem_botao_padrao' => true, // remove botão padrão de salvar
    'botoes_rodape' => [
        'previsualizar' => [
            'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-button-preview')),
            'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-preview')),
            'icon' => 'plus circle',
            'cor' => 'positive',
            'callback' => 'previsualizar', // função JavaScript
        ],
    ],
    'formulario' => Array(
        'validacao' => Array(
            Array(
                'regra' => 'texto-obrigatorio',
                'campo' => 'nome',
                'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
            ),
            Array(
                'regra' => 'selecao-obrigatorio',
                'campo' => 'framework_css',
                'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-framework-css-label')),
                'identificador' => 'framework_css',
            )
        ),
        'campos' => Array(
            // definição de campos do formulário
        ),
    )
);

// Interface de editar (similar ao adicionar)
$_GESTOR['interface']['editar']['finalizar'] = Array(
    'sem_botao_padrao' => true,
    'botoes_rodape' => [
        'previsualizar' => [
            'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-button-preview')),
            'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-preview')),
            'icon' => 'plus circle',
            'cor' => 'positive',
            'callback' => 'previsualizar',
        ],
    ],
    'id' => $id,
    'metaDados' => $metaDados,
    'banco' => Array(
        'nome' => $modulo['tabela']['nome'],
        'id' => $modulo['tabela']['id'],
        'status' => $modulo['tabela']['status'],
    ),
    'botoes' => Array(
        'adicionar' => Array(...),
        'status' => Array(...),
        'excluir' => Array(...),
    ),
    'formulario' => Array(
        // mesmo padrão da interface adicionar
    )
);
```

---
## 5. Convenções de Identificadores
- Módulo: usar `kebab-case` (ex: `admin-atualizacoes`).
- Páginas: `modulo-opcao` (ex: `admin-atualizacoes-detalhe`).
- Variáveis: `contexto-descricao` (ex: `updates-title`).
- Componentes: granular e autoexplicativo (ex: `registro-status-badge`).
- Campos de tabela: seguir snake_case se legado já usar; manter consistência.

---
## 6. Ciclo de Vida CRUD
Adicionar:
1. Usuário acessa `/modulo-id/adicionar/`.
2. JS valida client-side (opcional) + PHP valida server-side.
3. Calcula `id` único.
4. Insere registro e redireciona para `/editar/`.

Editar:
1. Carrega registro existente.
2. Guarda estado anterior (`banco_select_campos_antes_iniciar`).
3. Compara campos -> build `$alteracoes`.
4. Atualiza registro (incrementa versão).
5. Histórico + backup.
6. Redireciona com novo `id` se renomeado.

Listar:
1. Define configuração de tabela.
2. Sistema monta HTML automaticamente.
3. Ações por registro conforme `opcoes`.

---
## 7. AJAX Padrão
Requisição envia: `opcao`, `ajax=sim`, `ajaxOpcao`, `params{}`.
Resposta: `$_GESTOR['ajax-json']` → serializado em JSON.

---
## 8. Formatação de Campos (`interface_formatar_dado`)
Formatos comuns:
- `dataHora`, `data`, `moeda`, `outraTabela`, `outroConjunto`.
- `outraTabela` exige:
```php
'formatar' => [
  'id' => 'outraTabela',
  'tabela' => [ 'nome' => 'modulos', 'campo_trocar' => 'nome', 'campo_referencia' => 'id' ],
  'valor_senao_existe' => '<span class="ui info text">N/A</span>'
]
```

---
## 9. Histórico
- Registra campo antes/depois quando há alteração.
- Depende de chamada prévia a `banco_select_campos_antes_iniciar` + uso de `banco_select_campos_antes` por campo.
- Armazena tipo (alteração, criação, exclusão lógica etc.).

---
## 10. Backup de Campos
- Usado para registrar valores críticos (ex: templates, layouts) antes de alteração destrutiva.
- Funções: `interface_backup_campo_incluir` e seleção via `interface_backup_campo_select`.

## 11. Validação e Segurança

### Validação de Campos
Sempre implementar validação em duas camadas:

**No JavaScript (validação prévia):**
```js
function validarFormulario(){
    if(!$('#campo-obrigatorio').val()){
        alert('Campo obrigatório não preenchido');
        $('#campo-obrigatorio').focus();
        return false;
    }
    return true;
}
```

**No PHP (validação definitiva):**
```php
// Validação de campos obrigatórios
interface_validacao_campos_obrigatorios(Array(
    'nome',
    'campo_obrigatorio'
));

// Verificação de unicidade
$existe = interface_verificar_campos(Array(
    'tabela' => $modulo['tabela']['nome'],
    'campo' => 'nome',
    'valor' => banco_escape_field($_REQUEST['nome']),
    'exceto-id' => $id // Para edição
));
```

### Segurança
- **Sempre escapar valores** para o banco: `banco_escape_field($_REQUEST['campo'])`
- **Nunca confiar apenas na validação JavaScript** - sempre revalidar no PHP
- **Verificar existência e status** antes de editar: `status != 'D'`
- **Controlar permissões** de acesso às opções do módulo

---
## 12. Boas Práticas de Evolução
- Incrementar `versao` no JSON ao alterar estrutura (páginas/variáveis/componentes) que impacte cache.
- Evitar lógica duplicada entre módulos; extrair para biblioteca reutilizável.
- Manter documentação (`modulo_id.md`) atualizada a cada novo padrão.

---
## 13. Roteamento: Caminho de Página vs `?opcao=`

O roteador (`gestor_roteador`) possui regras importantes:

1. Se a requisição contém `?opcao=alguma-coisa`, o core após incluir o módulo e executar a lógica chamará `gestor_redirecionar_raiz()` e NÃO entregará a página HTML dessa requisição. Use esse formato para ações que não precisam renderizar imediatamente (efeitos colaterais / processamento / mudança de estado seguido de redirect).
2. Para exibir uma página HTML diretamente, crie um registro em `resources.<lingua>.pages[]` no JSON do módulo com um `path` (terminado em `/`). O roteador localizará a página pelo caminho (sem usar `?opcao`) e carregará seu HTML + módulo na mesma resposta.
3. O campo `option` do registro de página ainda é passado para o módulo (`$_GESTOR['opcao']`), permitindo switch interno, mas sem disparar o redirecionamento porque o acesso foi por caminho e não por `?opcao=`.
4. Links internos que devem mostrar conteúdo devem apontar para o `path` (ex: `detalhe/`) e parâmetros extras (ex: `detalhe/?log=abc.log`). Evite `?opcao=detalhe` nesse caso ou perderá o HTML.
5. AJAX (`ajax=sim`) nunca sofre esse redirect automático; a resposta JSON é retornada conforme `$_GESTOR['ajax-json']`.
6. Boas práticas: separar claramente URLs de ação (`?opcao=`) de URLs navegacionais (paths). Facilita caching, logs e SEO.

Exemplo: No módulo `admin-atualizacoes`, links foram ajustados de `?opcao=detalhe-atualizacao&log=...` para `detalhe/?log=...` para permitir exibição do HTML da página detalhe.

---
## 14. Passos para Criar um Novo Módulo
1. Copiar pasta `modulo_id` com novo nome (ex: `admin-relatorios`).
2. Ajustar `modulo_novo.json` (tabela, pages, variables, bibliotecas específicas).
3. Renomear funções em `modulo_novo.php` (prefixo consistente: `modulo_novo_`).
4. Ajustar JS (`modulo_novo.js`) removendo exemplos não usados.
5. Criar registro em `ModulosData.json` + permissão em `UsuariosPerfisModulosData.json`.
6. Implementar páginas e componentes em `resources/`.
7. Testar CRUD básico + AJAX.
8. Registrar versão inicial.

---
## 14. Checklist de Qualidade
- [ ] JSON válido e alinhado à tabela real.
- [ ] Todas as funções PHP com prefixo correto.
- [ ] `gestor_incluir_bibliotecas()` chamado no `start`.
- [ ] Placeholders substituídos via `modelo_var_troca_tudo`.
- [ ] Validação server-side implementada.
- [ ] Histórico ativado (se relevante).
- [ ] Backup usado (se necessário).
- [ ] JS sem código morto de exemplo antes de produção.
- [ ] Variáveis de interface no JSON (nada hardcoded em HTML final).

---
## 15. Glossário Rápido
- `opcao`: rota lógica tratada no PHP.
- `ajax-opcao`: subrota para chamadas assíncronas.
- `modulo-id`: identificador global do módulo.
- `$_GESTOR['pagina']`: buffer HTML final antes de renderização.
- `gestor_variaveis`: resolve variáveis por módulo/idioma.

---
## 16. Próximos Aperfeiçoamentos (Template)
- Adicionar exemplos de `components` reais.
- Adicionar exemplo de `layout` customizado.
- Criar seção avançada sobre paginação e filtros dinâmicos.
- Incluir padrão de testes automatizados (futuro).

---
## 17. Referência Rápida de Funções Importantes
| Função | Uso |
| ------ | --- |
| gestor_incluir_bibliotecas | Carrega bibliotecas declaradas no JSON |
| gestor_pagina_javascript_incluir | Inclui JS do módulo |
| interface_validacao_campos_obrigatorios | Validação de campos obrigatórios |
| banco_identificador | Gera identificador único textual |
| banco_insert_name | Insert usando vetor de campos | 
| banco_update | Update direto |
| interface_historico_incluir | Log de alterações |
| interface_backup_campo_incluir | Backup de campos específicos |
| interface_formatar_dado | Formatação de apresentação |
| interface_alerta | Mensagem e redirect |
| modelo_var_troca_tudo | Substituição de placeholders |

---
## 18. Observações Finais
Este template deve ser mantido enxuto, didático e atualizado. Toda nova funcionalidade transversal que se repetir em múltiplos módulos deve virar biblioteca reutilizável e apenas referenciada aqui.

> Atualize esta documentação sempre que alterar o fluxo ou adicionar um novo padrão.

---
## 19. Padrões Atualizados (v1.15.0+)

### ✅ Sistema de Modais Correto
- **Modal ANTES do CodeMirror** na ordem de dependências
- **gestor_componente() SEM parâmetro 'variaveis'**
- **modelo_var_troca() individual** para cada substituição de variável
- Padrão implementado em admin-layouts e admin-componentes

### ✅ Sistema de Preview TailwindCSS
- Modal de pré-visualização para módulos visuais
- Endpoint preview.php para geração de HTML
- Suporte multi-framework (TailwindCSS/FomanticUI)
- Botões customizados com callbacks JavaScript

### ✅ Ordem de Inclusão Crítica
```
1. Modal Components (primeiro)
2. CodeMirror Libraries (depois) 
3. Custom Scripts (último)
```

### ✅ Campo framework_css
- Suporte nativo desde v1.15.0
- Selectbox TailwindCSS vs FomanticUI
- Validação obrigatória para módulos visuais

**Base de Conhecimento:** admin-paginas, admin-layouts, admin-componentes
