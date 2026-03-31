# Biblioteca: widgets.php

> ��� Sistema modular de widgets dinâmicos

## Visão Geral

A biblioteca `widgets.php` fornece um sistema para renderizar **componentes dinâmicos (widgets) diretamente no HTML das páginas**. Ela atua como uma ponte entre o conteúdo estático e a lógica de back-end dos módulos, permitindo que funções PHP específicas de um módulo sejam chamadas diretamente a partir de marcações HTML especiais.

O fluxo é: o `gestor.php` varre o HTML da página em busca do marcador `@[[widgets#...]]@` e encaminha a string interna para `widgets_get()`, que cuida de analisar o formato, incluir o arquivo `.widget.php` do módulo e executar a função solicitada, devolvendo o HTML resultante para substituir o marcador.

**Localização**: `gestor/bibliotecas/widgets.php`  
**Versão**: 2.0.0  
**Total de Funções**: 1 principal

## Dependências

- **Variáveis Globais**: `$_GESTOR`
- **Contexto**: Carregada pelo `gestor.php` sob demanda via `gestor_incluir_biblioteca('widgets')`

## Variáveis Globais

```php
$_GESTOR['biblioteca-widgets'] = Array(
    'versao' => '2.0.0',
);

// Registro de widgets que precisam de resposta AJAX (preenchido durante processamento)
$_GESTOR['widgetsToAjax'] = 'modulo->funcao({...})<#;>outro-widget->funcao({...})';
```

---

## Estrutura e Funcionamento

O sistema de widgets opera por meio de marcadores especiais inseridos no HTML das páginas. O gestor principal (`gestor.php`) localiza esses marcadores e usa `widgets_get()` para processá-los.

### Sintaxe HTML

O marcador deve seguir o formato:

```html
@[[widgets#MODULO_ID->FUNCAO(JSON_PARAMS)]]@
```

Onde:
- `MODULO_ID`: O ID do módulo que contém a lógica do widget.
- `FUNCAO`: O nome da função PHP a ser chamada.
- `JSON_PARAMS`: Uma string JSON válida contendo os parâmetros a serem passados à função.

**Exemplo:**
```html
@[[widgets#meu-modulo->renderizar_lista({"limite": 5, "ordem": "desc"})]]@
```

### Processamento no gestor.php

O `gestor.php` detecta marcadores com o seguinte padrão:

```php
// @[[widgets#MODULO_ID->FUNCAO(JSON_PARAMS)]]@  (novo formato modular)
// @[[widgets#nome-simples]]@                    (compatibilidade retroativa)
$pattern = "/".preg_quote($open)."widgets#(.+?)".preg_quote($close)."/i";
preg_match_all($pattern, $_GESTOR['pagina'], $matchesWidgets);

foreach($matchesWidgets[1] as $match){
    $widget = widgets_get(Array('id' => $match));
    // ... substitui o marcador pelo HTML retornado
}
```

### Suporte a AJAX

Quando um widget é processado durante uma requisição normal (não-AJAX), o sistema registra automaticamente seu identificador em `$_GESTOR['widgetsToAjax']`. Nas requisições AJAX subsequentes, o `gestor.php` chama `gestor_pagina_widgets_ajax()` que reutiliza `widgets_get()` — mas desta vez chamando a função `_ajax` correspondente (ex: `renderizar_lista_ajax`).

---

## Funções Principais

### widgets_get()

Processa e renderiza um widget completo por ID.

**Assinatura:**
```php
function widgets_get($params = false)
```

**Parâmetros (Array Associativo):**
- `id` (string) — **Obrigatório** — Identificador único do widget no formato `MODULO_ID->FUNCAO(JSON_PARAMS)` ou nome simples para compatibilidade.

**Retorno:**
- (string) — HTML processado e completo do widget, ou string vazia se não encontrado/sem resultado.

**Fluxo interno detalhado:**

```
widgets_get(['id' => 'meu-modulo->renderizar_lista({"limite": 5})'])
  │
  ├─ 1. preg_match extrai: module="meu-modulo", func="renderizar_lista", json='{"limite": 5}'
  │
  ├─ 2. json_decode converte para array PHP: ['limite' => 5]
  │
  ├─ 3. require_once: gestor/modulos/meu-modulo/meu-modulo.widget.php
  │
  ├─ 4. Verifica se é AJAX:
  │       ├─ SIM: chama renderizar_lista_ajax(['limite' => 5])
  │       └─ NÃO: registra em $_GESTOR['widgetsToAjax'], chama renderizar_lista(['limite' => 5])
  │
  └─ 5. Retorna o HTML resultante (ou '' se função não existir)
```

**Exemplo de uso (interno pelo gestor.php):**
```php
$widget_html = widgets_get(Array(
    'id' => 'meu-modulo->renderizar_lista({"limite": 5})'
));
```

---

## Como Criar um Widget em um Módulo

Para disponibilizar um widget a partir de um módulo, siga os passos abaixo:

### 1. Criar o arquivo do widget

No diretório do módulo (`gestor/modulos/seu-modulo/`), crie um arquivo chamado `seu-modulo.widget.php`.

### 2. Definir a função

Dentro desse arquivo, defina a função que será chamada pelo marcador HTML. A função deve aceitar um array de parâmetros e retornar uma string HTML.

**Exemplo (`gestor/modulos/meu-modulo/meu-modulo.widget.php`):**

```php
<?php

function renderizar_lista($params = array()) {
    $limite = isset($params['limite']) ? (int)$params['limite'] : 10;
    $ordem  = isset($params['ordem'])  ? $params['ordem']       : 'asc';

    $itens = banco_select(Array(
        'tabela' => 'meus_itens',
        'campos' => Array('id', 'titulo', 'descricao'),
        'extra'  => "WHERE status = 'A' ORDER BY titulo " . strtoupper($ordem) . " LIMIT " . $limite,
    ));

    if (empty($itens)) {
        return '<p>Nenhum item encontrado.</p>';
    }

    $html = '<ul class="meu-modulo-lista">';
    foreach ($itens as $item) {
        $html .= '<li>' . htmlspecialchars($item['titulo']) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}
```

### 3. Usar na página HTML

Insira o marcador na página onde deseja renderizar o widget:

```html
@[[widgets#meu-modulo->renderizar_lista({"limite": 5, "ordem": "desc"})]]@
```

### 4. Função AJAX (opcional)

Se o widget precisar responder a requisições AJAX sem recarregar a página, crie uma função com o sufixo `_ajax`:

```php
function renderizar_lista_ajax($params = array()) {
    // Processar e retornar dados JSON ou HTML parcial
    // ATENÇÃO: Não deve retornar nada em caso de sucesso (ecoa diretamente)
    // Deve retornar string com mensagem de erro apenas em caso de falha
    
    $dados = [/* ... */];
    echo json_encode($dados);
    exit;
}
```

---

## Compatibilidade com Versões Anteriores

A `widgets_get()` mantém suporte ao formato simples (legado):

```html
@[[widgets#nome-simples-widget]]@
```

Neste caso, se o ID não corresponder ao padrão `MODULO->FUNCAO(...)`, o bloco modular não é ativado e a função retorna uma string vazia. Este comportamento pode ser expandido futuramente para buscar widgets legados em banco de dados ou arquivos de recursos.

---

## Padrões e Melhores Práticas

### Segurança nos Parâmetros

```php
// ✅ BOM — sempre sanitizar parâmetros recebidos via JSON
function meu_widget($params = array()) {
    $id = isset($params['id']) ? (int)$params['id'] : 0;
    $termo = isset($params['q']) ? banco_escape_field($params['q']) : '';
    // ...
}

// ❌ EVITAR — usar parâmetros diretamente em queries
function meu_widget_inseguro($params = array()) {
    $id = $params['id']; // sem sanitização!
    $result = banco_select(['extra' => "WHERE id = $id"]); // SQL injection!
}
```

### Retorno Consistente

```php
// ✅ BOM — sempre retornar string (nunca null ou false)
function meu_widget($params = array()) {
    if (empty($params)) return '';
    // ...
    return $html;
}
```

### Nomenclatura

- O nome da função deve ser único em todo o projeto.
- Recomendado: usar prefixo com ID do módulo — ex: `catalogo_widget_lista()`.
- A versão AJAX deve ter o mesmo nome + sufixo `_ajax` — ex: `catalogo_widget_lista_ajax()`.

---

## Veja Também

- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) — Componentes e variáveis do sistema
- [BIBLIOTECA-INTERFACE.md](./BIBLIOTECA-INTERFACE.md) — Operações CRUD de módulos
- [BIBLIOTECA-MODELO.md](./BIBLIOTECA-MODELO.md) — Template e substituição de variáveis
- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) — Operações de banco de dados

---

**Última Atualização**: Março 2026  
**Versão da Documentação**: 2.0.0  
**Mantenedor**: Equipe Conn2Flow
