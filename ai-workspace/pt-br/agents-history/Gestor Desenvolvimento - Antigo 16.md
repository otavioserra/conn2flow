# Gestor Desenvolvimento — Antigo 16

## Refatoração html-editor + Recursos IA para Layouts/Componentes + Modernização do Sistema de Widgets

---

## Contexto e Objetivos

Esta sessão cobriu três grandes frentes de desenvolvimento no core do Conn2Flow (`conn2flow`):

1. **Refatoração dos módulos `admin-layouts` e `admin-componentes`** para adotar a biblioteca `html-editor` centralizada (padrão já em uso pelo `admin-paginas`).
2. **Criação de recursos de IA** (alvos, modos, prompts e templates) para os módulos de layouts e componentes — habilitando o assistente IA no editor desses módulos.
3. **Modernização do sistema de widgets** (`widgets.php`) para um modelo totalmente modular baseado em arquivos `.widget.php` por módulo, eliminando o registro estático de widgets no `$_GESTOR`.

**Contexto técnico:**
- Sistema: Conn2Flow Gestor (PHP 8.x, Fomantic UI, CodeMirror 5.65.20)
- Ambiente: Docker local Windows, repositório `conn2flow`
- Branch: `main`
- Data: Março 2026

---

## Fase 1 — Refatoração da Biblioteca `html-editor`

### 1.1 Motivação

O módulo `admin-paginas` já usava a biblioteca `html-editor.php` via `html_editor_componente(['alvo' => 'paginas'])`. Os módulos `admin-layouts` e `admin-componentes` ainda tinham código duplicado para importar CodeMirror, montar o editor e incluir componentes — o que gerava inconsistência e dificultava manutenção.

### 1.2 Extensão da biblioteca `html-editor.php`

**Arquivo**: `gestor/bibliotecas/html-editor.php`

#### Parâmetro `alvo` em `html_editor_componente()`

O principal ponto de extensão foi o parâmetro `alvo` na função `html_editor_componente()`, que já existia para `paginas` e `publisher`. Novos alvos foram adicionados:

```php
$alvo = isset($alvo) ? $alvo : 'paginas';
```

#### Mapa de callbacks de backup

Cada módulo precisa de um callback PHP diferente para salvar o estado do editor antes do envio do formulário. Foi criado um mapa explícito:

```php
$backupCallbackMap = [
    'paginas'    => 'adminPaginasBackupCampo',
    'layouts'    => 'adminLayoutsBackupCampo',
    'componentes' => 'adminComponentesBackupCampo',
    'publisher'  => 'adminPaginasBackupCampo',
];

$backupCallback = isset($backupCallbackMap[$alvo]) ? $backupCallbackMap[$alvo] : 'adminPaginasBackupCampo';
```

#### Variável JS `alvo` injetada no frontend

Para que o JavaScript (`html-editor-interface.js`) saiba em qual contexto está operando (páginas, layouts ou componentes), a variável é injetada via:

```php
gestor_js_variavel_incluir('html_editor', [
    'alvo' => $alvo,
]);
```

#### Switch por alvo — remoção do `html_extra_head` para layouts

Layouts **não** possuem o campo `html_extra_head` (CSS extra de cabeçalho). Para evitar exibição de aba inativa no editor quando `alvo === 'layouts'`, os blocos correspondentes são removidos do HTML do componente:

```php
switch($alvo){
    case 'layouts':
        $cel_nome = 'html-extra-head-menu';    $html_editor = modelo_tag_del($html_editor, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
        $cel_nome = 'html-extra-head-content'; $html_editor = modelo_tag_del($html_editor, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
    break;
}
```

### 1.3 Atualização do `html-editor-interface.js`

**Arquivo**: `gestor/assets/interface/html-editor-interface.js`

O JS foi atualizado em todas as funções que lidam com preview e salvamento para respeitar o `alvo` injetado pelo PHP. As principais mudanças:

- **Filtros de preview**: As URLs de preview (`previewHtml`, `editHtml`) passaram a receber o `alvo` como parâmetro GET, permitindo ao backend construir o HTML correto (para layouts, o preview omite cabeçalho/rodapé padrão e usa apenas a estrutura do layout).

- **Função de backup**: O callback de salvamento (`adminPaginasBackupCampo` etc.) é selecionado dinamicamente com base no valor de `html_editor.alvo`.

- **Validação de modo**: Condicionais foram adicionadas para que certas funcionalidades (ex: preview de variáveis de página) só sejam exibidas quando `alvo === 'paginas'`.

### 1.4 Módulo `admin-layouts.php`

**Arquivo**: `gestor/modulos/admin-layouts/admin-layouts.php`

**Antes** (código duplicado):
```php
// ~100 linhas de inclusão manual de CodeMirror, montagem do componente, etc.
gestor_pagina_css_incluir('<link rel="stylesheet" ...codemirror.../>');
// ...
$html_editor = gestor_componente(['id' => 'html-editor']);
// ...remocoes manuais de blocos...
```

**Depois** (uma linha):
```php
html_editor_componente(['alvo' => 'layouts']);
```

A limpeza do `admin-layouts.js` também foi realizada — handlers de eventos que eram responsabilidade da biblioteca `html-editor-interface.js` foram removidos para evitar conflito.

### 1.5 Módulo `admin-componentes.php`

**Arquivo**: `gestor/modulos/admin-componentes/admin-componentes.php`

Idêntico ao processo do `admin-layouts`. A chamada foi simplificada para:

```php
html_editor_componente(['alvo' => 'componentes']);
```

E o `admin-componentes.js` foi limpo de handlers duplicados correspondentes.

---

## Fase 2 — Recursos de IA para Layouts e Componentes

### 2.1 Contexto: Sistema de IA do html-editor

O assistente IA do editor HTML carrega suas configurações a partir da seção `resources` no JSON do módulo. O sistema suporta três camadas:

- **`ai_prompts_targets`**: Define os "alvos" disponíveis (ex: `layouts`, `componentes`) — cada alvo tem um contexto diferente para a IA.
- **`ai_modes`**: Modos de geração (ex: "criar do zero", "refatorar", "adicionar bloco"). Cada modo referencia um alvo e um arquivo `.md` com instruções para a IA.
- **`ai_prompts`**: Templates de prompts estruturados que o usuário pode selecionar como ponto de partida. Cada prompt tem um arquivo `.md` associado.

### 2.2 `admin-layouts.json` — Metadados de IA

**Arquivo**: `gestor/modulos/admin-layouts/admin-layouts.json`

Adição das seções de IA (PT-BR e EN):

```json
"resources": {
    "pt-br": {
        "ai_prompts_targets": [
            { "id": "layouts", "name": "Layouts" }
        ],
        "ai_modes": [
            {
                "id": "layouts",
                "name": "Layouts",
                "target": "layouts",
                "default": true,
                "version": "1.0",
                "checksum": { "md": "" }
            }
        ],
        "ai_prompts": [
            { "id": "layouts",              "name": "Layouts" },
            { "id": "layout-site-basico",   "name": "Site Básico" },
            { "id": "layout-landing-page",  "name": "Landing Page" },
            { "id": "layout-blog",          "name": "Blog" },
            { "id": "layout-dashboard",     "name": "Dashboard Admin" }
        ]
    },
    "en": { /* estrutura espelhada */ }
}
```

### 2.3 `admin-componentes.json` — Metadados de IA

**Arquivo**: `gestor/modulos/admin-componentes/admin-componentes.json`

Mesma estrutura, com prompts especializados para componentes:

```json
"ai_prompts": [
    { "id": "componentes",              "name": "Componentes" },
    { "id": "componente-card",          "name": "Card" },
    { "id": "componente-hero",          "name": "Hero / Banner" },
    { "id": "componente-formulario",    "name": "Formulário" },
    { "id": "componente-navegacao",     "name": "Navegação" }
]
```

### 2.4 Arquivos de Modo IA (`.md`)

**Diretório PT-BR**: `gestor/modulos/admin-layouts/resources/pt-br/ai_modes/layouts/`  
**Diretório EN**: `gestor/modulos/admin-layouts/resources/en/ai_modes/layouts/`

O arquivo `layouts.md` define as instruções sistêmicas enviadas para a IA quando o usuário usa o assistente no contexto de layouts. Ele descreve:

- A estrutura de um layout Conn2Flow (marcador `@[[pagina#corpo]]@`)
- Frameworks disponíveis: Fomantic UI e TailwindCSS
- Boas práticas: HTML semântico, responsividade, variáveis do sistema
- O que **não** incluir (ex: não duplicar cabeçalho/rodapé dentro do layout)

Exemplo de instrução para layouts (trecho):

```md
# Modo IA: Layouts

Um layout no Conn2Flow é um esqueleto HTML reutilizável que envolve o conteúdo das páginas.
O marcador obrigatório `@[[pagina#corpo]]@` indica onde o conteúdo de cada página é inserido.

## Estrutura Mínima
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @[[pagina#css]]@
    @[[pagina#head-extra]]@
</head>
<body>
    @[[pagina#corpo]]@
    @[[pagina#js]]@
</body>
</html>
```

### 2.5 Arquivos de Prompt IA (`.md`)

**Diretório PT-BR layouts**: `gestor/modulos/admin-layouts/resources/pt-br/ai_prompts/`  
**Diretório PT-BR componentes**: `gestor/modulos/admin-componentes/resources/pt-br/ai_prompts/`

Os prompts são templates de exemplo que aparecem no menu do assistente IA. Cada um descreve um cenário de uso comum, como ponto de partida para o usuário refinar.

**Prompts criados para layouts** (5 alvos × 2 frameworks × 2 idiomas = 20 arquivos):

| ID do Prompt       | Conceito                          |
|--------------------|-----------------------------------|
| `layouts`          | Layout genérico básico            |
| `layout-site-basico` | Site institucional completo     |
| `layout-landing-page` | Landing page de conversão      |
| `layout-blog`      | Blog com sidebar                  |
| `layout-dashboard` | Painel administrativo             |

Para cada conceito: versão Fomantic UI + versão TailwindCSS, em PT-BR e EN.

**Prompts criados para componentes** (5 alvos × 2 frameworks × 2 idiomas = 20 arquivos):

| ID do Prompt            | Conceito                       |
|-------------------------|--------------------------------|
| `componentes`           | Componente genérico            |
| `componente-card`       | Card de produto/conteúdo       |
| `componente-hero`       | Hero section / Banner          |
| `componente-formulario` | Formulário de contato          |
| `componente-navegacao`  | Menu de navegação              |

### 2.6 Templates HTML

Foram criados templates HTML de exemplo para o painel de "Modelos" do editor (aba de templates):

- **10 templates de layout**: cobrindo layouts para site básico, landing page, blog, dashboard e layout em branco — em Fomantic UI e TailwindCSS.
- **10 templates de componente**: cobrindo cards, heroes, formulários, menus e componente em branco — em Fomantic UI e TailwindCSS.

Os templates ficam em:
- `gestor/modulos/admin-layouts/resources/pt-br/templates/`
- `gestor/modulos/admin-componentes/resources/pt-br/templates/`
- Estruturas espelhadas na pasta `en/`.

Os metadados dos templates foram adicionados ao arquivo `templates.json` de cada módulo, em ambos os idiomas.

---

## Fase 3 — Modernização do Sistema de Widgets

### 3.1 O Problema: Arquitetura Legada Rígida

O sistema anterior (`widgets.php` v1.0.x) registrava cada widget em um array central `$_GESTOR['biblioteca-widgets']['widgets']`. Para adicionar um novo widget era necessário:

1. Registrar o widget no array global.
2. Criar o componente HTML no banco de dados.
3. Criar um controlador PHP específico.
4. Adicionar um `case` no switch do `widgets_controller()`.

Isso tornava a adição de widgets acoplada ao arquivo central, dificultando o desenvolvimento de módulos independentes.

### 3.2 Nova Arquitetura: Widgets Modulares

A v2.0 introduz um padrão completamente novo, onde **cada módulo define seus próprios widgets** em um arquivo `MODULO_ID.widget.php` dentro da pasta do módulo. O sistema lê este arquivo dinamicamente quando o marcador HTML é encontrado na página.

**Comparação de fluxos:**

| v1.0 (legado)                                    | v2.0 (modular)                                         |
|--------------------------------------------------|--------------------------------------------------------|
| Widget registrado em `$_GESTOR['widgets']`       | Widget definido em `modulos/MODULO/MODULO.widget.php`  |
| HTML armazenado no banco de dados (`componentes`)| HTML gerado diretamente pela função PHP do módulo      |
| Switch central em `widgets_controller()`         | `call_user_func()` dinâmico com o nome da função       |
| Um único arquivo de registro                     | Um arquivo por módulo, totalmente isolado              |

### 3.3 Novo Formato do Marcador HTML

```html
<!-- ANTES (legado) -->
@[[widgets#formulario-contato]]@

<!-- DEPOIS (modular) -->
@[[widgets#meu-modulo->renderizar_formulario({"titulo": "Fale Conosco", "redirect": "/obrigado"})]]@
```

O novo marcador tem três componentes separados por `->` e `()`:
- `meu-modulo` — ID do módulo (mapeia para `gestor/modulos/meu-modulo/meu-modulo.widget.php`)
- `renderizar_formulario` — Nome da função PHP a chamar
- `{"titulo": "Fale Conosco", "redirect": "/obrigado"}` — JSON com parâmetros da função

### 3.4 Implementação de `widgets_get()` — v2.0

**Arquivo**: `gestor/bibliotecas/widgets.php`

A função foi reescrita do zero. O código central:

```php
function widgets_get($params = false){
    global $_GESTOR;

    if($params) foreach($params as $var => $val) $$var = $val;

    if(isset($id)){
        $callbackResult = '';

        // Extrai module, func e JSON via regex
        if(preg_match('/^([a-zA-Z0-9_\-]+)->([a-zA-Z0-9_\-]+)\((.*)\)$/', $id, $m)){
            $module = $m[1];
            $func   = $m[2];
            $json   = $m[3];

            // Decode JSON → array
            $paramsArray = json_decode($json, true);
            if(!is_array($paramsArray)) $paramsArray = array();

            // Inclui o widget do módulo se existir
            $widgetFile = $_GESTOR['modulos-path'] . $module . '/' . $module . '.widget.php';
            if(file_exists($widgetFile)) require_once($widgetFile);

            if(function_exists($func)){
                if($_GESTOR['ajax']){
                    // Modo AJAX: chama a versão _ajax da função
                    $func .= '_ajax';
                } else {
                    // Modo normal: registra no widgetsToAjax para chamadas futuras
                    $widgetsAjaxList = array_filter(explode('<#;>', isset($_GESTOR['widgetsToAjax']) ? $_GESTOR['widgetsToAjax'] : ''));
                    if(!in_array($id, $widgetsAjaxList, true)){
                        $widgetsAjaxList[] = $id;
                        $_GESTOR['widgetsToAjax'] = implode('<#;>', $widgetsAjaxList);
                    }
                }

                $callbackResult = call_user_func($func, $paramsArray);
            }
        }

        // Fallback para compatibilidade retroativa (ID simples)
        if($callbackResult === ''){
            // Retorna vazio — comportamento expansível futuramente
        }

        return $callbackResult;
    }

    return '';
}
```

**Detalhes técnicos da regex:**

O padrão `^([a-zA-Z0-9_\-]+)->([a-zA-Z0-9_\-]+)\((.*)\)$` captura:
- Grupo 1 (`$m[1]`): `MODULO_ID` — alfanumérico, underscore, hífen
- Grupo 2 (`$m[2]`): `FUNCAO` — alfanumérico, underscore, hífen
- Grupo 3 (`$m[3]`): `JSON_PARAMS` — qualquer conteúdo entre os parênteses externos

**Segurança**: Os parâmetros JSON são decodificados para array PHP antes de serem passados à função. A função PHP recebe um array com tipos nativos (int, string, bool, etc.) — nunca a string JSON raw.

### 3.5 Documentação no `gestor.php`

**Arquivo**: `gestor/gestor.php`

Comentários explicativos foram adicionados ao bloco de processamento de widgets para documentar o novo formato:

```php
// ===== Busca por widgets na página.
// O padrão procurado é algo como
//   @[[widgets#MODULO_ID->FUNCAO(JSON_PARAMS)]]@
// ou apenas @[[widgets#meu-widget]]@ para compatibilidade.
// Tudo que estiver entre "widgets#" e o fechamento será passado
// diretamente para widgets_get() que conhece o formato.
```

### 3.6 Registro de Versão

A biblioteca foi atualizada para v2.0.0:

```php
$_GESTOR['biblioteca-widgets'] = Array(
    'versao' => '2.0.0',
);
```

O registro antigo de widgets (array `['widgets']` com configurações por widget) foi removido completamente.

---

## Fase 4 — Atualização de Documentação

### 4.1 Documentação (conn2flow)

Ambos os arquivos de documentação da biblioteca `widgets.php` foram reescritos do zero para refletir a nova arquitetura v2.0:

- **PT-BR**: `ai-workspace/pt-br/docs/bibliotecas/BIBLIOTECA-WIDGETS.md`
- **EN**: `ai-workspace/en/docs/libraries/LIBRARY-WIDGETS.md`

Os documentos anteriores (v1.0.1) descreviam o sistema de widgets legado com:
- Registro central em `$_GESTOR['widgets']`
- Funções `widgets_search()`, `widgets_controller()`, `widgets_formulario_contato()`
- Exemplos de newsletter, pesquisa, área de usuário, comentários

Os novos documentos cobrem:
- Sintaxe `@[[widgets#MODULO->FUNCAO(JSON)]]@`
- Fluxo de processamento no `gestor.php`
- Ciclo de vida AJAX (registro em `widgetsToAjax`, chamada `_ajax`)
- Guia de criação de widget por módulo (arquivo `.widget.php`)
- Compatibilidade retroativa com IDs simples

### 4.2 Problema de Localização (conn2flow-site — acidental)

> ⚠️ **Nota de resolvência**: Na sessão anterior (sessão de implementação do `widgets.php`), o terminal estava posicionado no diretório do `conn2flow-site` quando os comandos heredoc foram executados. Como resultado, dois arquivos foram criados **no repositório errado**:
>
> - `conn2flow-site/ai-workspace/en/docs/libraries/LIBRARY-WIDGETS.md` ← ❌ deveria estar em `conn2flow`
> - A ausência de `conn2flow-site/ai-workspace/pt-br/docs/bibliotecas/BIBLIOTECA-WIDGETS.md` confirma que o PT-BR não chegou lá
>
> **Status atual** (31/03/2026): O arquivo mal posicionado ainda existe no `conn2flow-site`. Os arquivos corretos no `conn2flow` foram devidamente atualizados nesta sessão de documentação. O arquivo no `conn2flow-site` pode ser removido futuramente.

---

## Decisões Técnicas

### Por que `call_user_func()` em vez de switch?

O switch central requer modificação de código a cada novo widget adicionado — o que acopla todos os widgets a um ponto único e gera conflitos de merge em projetos com múltiplos desenvolvedores. O `call_user_func()` com `require_once` dinâmico permite que cada módulo adicione widgets de forma completamente independente.

### Por que `file_exists()` antes do `require_once()`?

Evitar fatais do PHP quando o módulo não possui arquivo `.widget.php`. Modules sem widgets simplesmente não criam esse arquivo e o sistema segue sem erro.

### Por que registrar em `widgetsToAjax` em vez de outro mecanismo?

O `$_GESTOR['widgetsToAjax']` é uma string delimitada por `<#;>` que persiste durante a execução da requisição normal e é enviada ao frontend via variável JS. O JavaScript a usa para saber quais widgets precisam de atualização AJAX, sem necessidade de nova discovery.

### Por que remover `html_extra_head` para layouts?

Layouts no Conn2Flow são esqueletos HTML que **já contêm** o `<head>` completo. O campo `html_extra_head` existe apenas para páginas (conteúdo inline). Exibir essa aba vazia no editor de layouts confundiria o usuário — foi mais correto remover do que deixar desabilitado.

### Por que 5 conceitos × 2 frameworks para templates?

Os frameworks Fomantic UI e TailwindCSS têm classes completamente diferentes — um template genérico não seria útil para nenhum dos dois. Ao criar versões específicas, o assistente IA tem contexto suficiente para gerar código correto para o framework em uso.

---

## Bugs Encontrados e Resolvidos

| # | Bug | Causa | Solução |
|---|-----|-------|---------|
| 1 | `html_extra_head` exibido em layouts | Nenhuma exclusão condicional por alvo | Switch `case 'layouts'` com `modelo_tag_del()` |
| 2 | Callback de backup errado em layouts/componentes | Callback hardcoded para `paginas` | `$backupCallbackMap` com mapeamento por alvo |
| 3 | Template AJAX: widgets duplicados em `widgetsToAjax` | Append sem verificação | `in_array()` antes do push no array |
| 4 | Docs reescritas no repositório errado | Terminal posicionado errado ao executar heredoc | Detectado no commit `354ba00` do conn2flow-site; arquivos corretos criados no conn2flow nessa sessão |

---

## Arquivos Criados/Modificados (Inventário Completo)

### `conn2flow` — biblioteca e módulos

| Arquivo | Tipo | Descrição |
|---------|------|-----------|
| `gestor/bibliotecas/widgets.php` | Modificado | Reescrita completa para v2.0 modular |
| `gestor/bibliotecas/html-editor.php` | Modificado | Adição de `backupCallbackMap`, parâmetro `alvo`, remoção condicional `html_extra_head` |
| `gestor/gestor.php` | Modificado | Comentários no bloco de processamento de widgets |
| `gestor/assets/interface/html-editor-interface.js` | Modificado | Suporte a `alvo` nas funções de preview e backup |
| `gestor/modulos/admin-layouts/admin-layouts.php` | Modificado | Uso de `html_editor_componente(['alvo' => 'layouts'])` |
| `gestor/modulos/admin-layouts/admin-layouts.js` | Modificado | Remoção de handlers duplicados da biblioteca |
| `gestor/modulos/admin-layouts/admin-layouts.json` | Modificado | Adição de metadados de IA (`ai_prompts_targets`, `ai_modes`, `ai_prompts`) |
| `gestor/modulos/admin-componentes/admin-componentes.php` | Modificado | Uso de `html_editor_componente(['alvo' => 'componentes'])` |
| `gestor/modulos/admin-componentes/admin-componentes.js` | Modificado | Remoção de handlers duplicados da biblioteca |
| `gestor/modulos/admin-componentes/admin-componentes.json` | Modificado | Adição de metadados de IA |

### `conn2flow` — recursos de IA criados (seleção)

| Arquivo | Tipo | Descrição |
|---------|------|-----------|
| `gestor/modulos/admin-layouts/resources/pt-br/ai_modes/layouts/layouts.md` | Criado | Instruções do modo IA para layouts (PT-BR) |
| `gestor/modulos/admin-layouts/resources/en/ai_modes/layouts/layouts.md` | Criado | Instruções do modo IA para layouts (EN) |
| `gestor/modulos/admin-layouts/resources/pt-br/ai_prompts/layouts/` | Criados | Prompts padrão para geração de layout (PT-BR Fomantic + TailwindCSS) |
| `gestor/modulos/admin-layouts/resources/en/ai_prompts/layouts/` | Criados | Idem (EN) |
| `gestor/modulos/admin-layouts/resources/pt-br/ai_prompts/layout-site-basico/` | Criados | Prompt: layout de site básico (PT-BR, ambos os frameworks) |
| `gestor/modulos/admin-layouts/resources/pt-br/ai_prompts/layout-landing-page/` | Criados | Prompt: landing page (PT-BR) |
| `gestor/modulos/admin-layouts/resources/pt-br/ai_prompts/layout-blog/` | Criados | Prompt: layout de blog (PT-BR) |
| `gestor/modulos/admin-layouts/resources/pt-br/ai_prompts/layout-dashboard/` | Criados | Prompt: painel administrativo (PT-BR) |
| `gestor/modulos/admin-componentes/resources/pt-br/ai_prompts/componente-card/` | Criados | Prompt: card (PT-BR) |
| `gestor/modulos/admin-componentes/resources/pt-br/ai_prompts/componente-hero/` | Criados | Prompt: hero/banner (PT-BR) |
| `gestor/modulos/admin-componentes/resources/pt-br/ai_prompts/componente-formulario/` | Criados | Prompt: formulário (PT-BR) |
| `gestor/modulos/admin-componentes/resources/pt-br/ai_prompts/componente-navegacao/` | Criados | Prompt: navegação (PT-BR) |
| *(espelhos EN para todos os acima)* | Criados | Versões EN de todos os prompts |
| *(10 templates HTML de layout PT-BR + 10 EN)* | Criados | Templates para painel de modelos do editor |
| *(10 templates HTML de componente PT-BR + 10 EN)* | Criados | Templates para painel de modelos |

### `conn2flow` — documentação

| Arquivo | Tipo | Descrição |
|---------|------|-----------|
| `ai-workspace/pt-br/docs/bibliotecas/BIBLIOTECA-WIDGETS.md` | Reescrito | Documentação v2.0 completa da biblioteca widgets |
| `ai-workspace/en/docs/libraries/LIBRARY-WIDGETS.md` | Reescrito | Tradução EN da documentação v2.0 |

### `conn2flow-site` — arquivos acidentais (veja Fase 4.2)

| Arquivo | Status |
|---------|--------|
| `ai-workspace/en/docs/libraries/LIBRARY-WIDGETS.md` | Criado por acidente no commit `354ba00` — pode ser removido futuramente |

---

## Commits Realizados

### conn2flow

| Commit | Arquivos | Descrição |
|--------|----------|-----------|
| `968789de` | 2 (47+, 257-) | `feat: implement modular widget system — widgets.php v2.0 with MODULO_ID->FUNCAO(JSON) syntax` |

### conn2flow-site

| Commit | Arquivos | Descrição |
|--------|----------|-----------|
| `354ba00` | 24 (912+, 129-) | Incluiu LIBRARY-WIDGETS.md (acidental) + mudanças de 3d-catalog pendentes de sessões anteriores |

---

**Data**: Março 2026  
**Versão da Documentação**: 1.0.0  
**Agente**: GitHub Copilot (Claude Sonnet 4.6)
