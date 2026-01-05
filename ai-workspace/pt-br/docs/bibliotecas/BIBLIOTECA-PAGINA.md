# Biblioteca: pagina.php

> 📄 Manipulação de células e variáveis de página

## Visão Geral

A biblioteca `pagina.php` fornece funções para manipular células (blocos de conteúdo) e variáveis em páginas HTML, permitindo substituição dinâmica de conteúdo e gerenciamento de templates.

**Localização**: `gestor/bibliotecas/pagina.php`  
**Versão**: 1.0.0  
**Total de Funções**: 7

## Dependências

- **Bibliotecas**: modelo.php
- **Variáveis Globais**: `$_GESTOR`

## Variáveis Globais

```php
$_GESTOR['biblioteca-pagina'] = Array(
    'versao' => '1.0.0',
);

// Página atual sendo processada
$_GESTOR['pagina'] // HTML da página

// Delimitadores de variáveis globais
$_GESTOR['variavel-global'] = Array(
    'open' => '[[',
    'close' => ']]',
    'openText' => '@[[',
    'closeText' => ']]@'
);
```

---

## Conceitos

### Células
Blocos de HTML delimitados por comentários especiais:
- `<!-- nome < -->conteúdo<!-- nome > -->` - Célula normal
- `<!-- nome [[conteúdo]] nome -->` - Célula com comentário

### Variáveis Globais
Placeholders no formato `[[variavel-nome]]` substituídos por valores dinâmicos.

---

## Funções Principais

### pagina_celula()

Extrai e marca célula da página para processamento.

**Assinatura:**
```php
function pagina_celula($nome, $comentario = false, $apagar = false)
```

**Parâmetros:**
- `$nome` (string) - **Obrigatório** - Nome da célula
- `$comentario` (bool) - **Opcional** - Usar formato com comentário
- `$apagar` (bool) - **Opcional** - Remover célula da página

**Retorno:**
- (string) - Conteúdo da célula

**Exemplo de Uso:**
```php
// HTML da página
$_GESTOR['pagina'] = '
    <!-- header < -->
    <header>Cabeçalho</header>
    <!-- header > -->
    <main>Conteúdo principal</main>
';

// Extrair célula
$header = pagina_celula('header');
// Retorna: "<header>Cabeçalho</header>"

// Página agora tem: <!-- header --><main>...</main>

// Com flag apagar
$header = pagina_celula('header', false, true);
// Página agora tem: <main>...</main> (célula removida)
```

---

### pagina_celula_trocar_variavel_valor()

Substitui variável em uma célula específica.

**Assinatura:**
```php
function pagina_celula_trocar_variavel_valor($celula, $variavel, $valor, $variavelEspecifica = false)
```

**Parâmetros:**
- `$celula` (string) - **Obrigatório** - HTML da célula
- `$variavel` (string) - **Obrigatório** - Nome da variável
- `$valor` (string) - **Obrigatório** - Valor de substituição
- `$variavelEspecifica` (bool) - **Opcional** - Usar variável sem delimitadores

**Exemplo de Uso:**
```php
$celula = '<h1>[[titulo]]</h1><p>[[descricao]]</p>';

// Substituir com delimitadores automáticos
$celula = pagina_celula_trocar_variavel_valor($celula, 'titulo', 'Meu Título');
// Resultado: "<h1>Meu Título</h1><p>[[descricao]]</p>"

// Substituir múltiplas variáveis
$celula = pagina_celula_trocar_variavel_valor($celula, 'descricao', 'Texto descritivo');
// Resultado: "<h1>Meu Título</h1><p>Texto descritivo</p>"
```

---

### pagina_celula_incluir()

Insere conteúdo de célula de volta na página.

**Assinatura:**
```php
function pagina_celula_incluir($celula, $valor)
```

**Parâmetros:**
- `$celula` (string) - **Obrigatório** - Nome da célula
- `$valor` (string) - **Obrigatório** - Conteúdo a inserir

**Exemplo de Uso:**
```php
// Página tem: <!-- menu --><main>...</main>

// Inserir menu processado
$menu_html = '<nav><a href="/">Home</a></nav>';
pagina_celula_incluir('menu', $menu_html);

// Página agora: <nav><a href="/">Home</a></nav><main>...</main>
```

---

### pagina_trocar_variavel_valor()

Substitui variável diretamente na página global.

**Assinatura:**
```php
function pagina_trocar_variavel_valor($variavel, $valor, $variavelEspecifica = false)
```

**Exemplo de Uso:**
```php
// Página tem: <title>[[titulo-site]]</title>

pagina_trocar_variavel_valor('titulo-site', 'Meu Site');

// Página agora: <title>Meu Site</title>
```

---

### pagina_trocar_variavel()

Substitui variável em código arbitrário.

**Assinatura:**
```php
function pagina_trocar_variavel($params = false)
```

**Parâmetros (Array Associativo):**
- `codigo` (string) - **Obrigatório** - HTML com variável
- `variavel` (string) - **Obrigatório** - Nome da variável
- `valor` (string) - **Obrigatório** - Valor de substituição

**Exemplo de Uso:**
```php
$template = '<div class="[[classe]]">[[conteudo]]</div>';

$html = pagina_trocar_variavel(Array(
    'codigo' => $template,
    'variavel' => 'classe',
    'valor' => 'destaque'
));

$html = pagina_trocar_variavel(Array(
    'codigo' => $html,
    'variavel' => 'conteudo',
    'valor' => 'Texto importante'
));

// Resultado: <div class="destaque">Texto importante</div>
```

---

### pagina_variaveis_globais_mascarar()

Mascara variáveis globais para armazenamento em banco.

**Assinatura:**
```php
function pagina_variaveis_globais_mascarar($params = false)
```

**Parâmetros (Array Associativo):**
- `valor` (string) - **Obrigatório** - HTML com variáveis

**Retorno:**
- (string) - HTML mascarado

**Exemplo de Uso:**
```php
// Converter para formato de banco
$html = '<h1>[[titulo]]</h1>';
$mascarado = pagina_variaveis_globais_mascarar(Array(
    'valor' => $html
));
// Retorna: "<h1>@[[titulo]]@</h1>"

// Salvar no banco
banco_update_campo('conteudo', $mascarado);
```

---

### pagina_variaveis_globais_desmascarar()

Desmascara variáveis globais vindas do banco.

**Assinatura:**
```php
function pagina_variaveis_globais_desmascarar($params = false)
```

**Exemplo de Uso:**
```php
// Recuperar do banco
$dados = banco_select(Array(
    'campos' => Array('conteudo'),
    'tabela' => 'paginas',
    'extra' => "WHERE id='123'",
    'unico' => true
));

// Desmascarar
$html = pagina_variaveis_globais_desmascarar(Array(
    'valor' => $dados['conteudo']
));
// Converte "@[[variavel]]@" de volta para "[[variavel]]"
```

---

## Casos de Uso Comuns

### 1. Sistema de Template

```php
function renderizar_pagina($template_id, $dados) {
    // Carregar template
    $template = banco_select(Array(
        'campos' => Array('html'),
        'tabela' => 'templates',
        'extra' => "WHERE id='$template_id'",
        'unico' => true
    ));
    
    $_GESTOR['pagina'] = $template['html'];
    
    // Substituir variáveis
    foreach ($dados as $variavel => $valor) {
        pagina_trocar_variavel_valor($variavel, $valor);
    }
    
    return $_GESTOR['pagina'];
}

// Uso
$html = renderizar_pagina('produto-detalhe', Array(
    'nome-produto' => 'Notebook',
    'preco' => 'R$ 2.500,00',
    'descricao' => 'Notebook de alta performance'
));
```

### 2. Processamento de Células Condicionais

```php
function processar_pagina_produto($produto) {
    // Extrair células
    $cel_desconto = pagina_celula('desconto');
    $cel_estoque = pagina_celula('sem-estoque');
    
    // Mostrar desconto apenas se houver
    if ($produto['desconto'] > 0) {
        $cel_desconto = pagina_celula_trocar_variavel_valor(
            $cel_desconto, 
            'percentual', 
            $produto['desconto'] . '%'
        );
        pagina_celula_incluir('desconto', $cel_desconto);
    } else {
        // Remover célula de desconto
        pagina_celula('desconto', false, true);
    }
    
    // Mostrar aviso se sem estoque
    if ($produto['estoque'] == 0) {
        pagina_celula_incluir('sem-estoque', $cel_estoque);
    } else {
        pagina_celula('sem-estoque', false, true);
    }
}
```

### 3. Editor de Conteúdo

```php
function salvar_conteudo_editor($pagina_id, $conteudo) {
    // Mascarar variáveis antes de salvar
    $conteudo_mascarado = pagina_variaveis_globais_mascarar(Array(
        'valor' => $conteudo
    ));
    
    banco_update(
        "conteudo='" . banco_escape_field($conteudo_mascarado) . "'",
        'paginas',
        "WHERE id='$pagina_id'"
    );
}

function carregar_conteudo_editor($pagina_id) {
    $pagina = banco_select(Array(
        'campos' => Array('conteudo'),
        'tabela' => 'paginas',
        'extra' => "WHERE id='$pagina_id'",
        'unico' => true
    ));
    
    // Desmascarar para edição
    return pagina_variaveis_globais_desmascarar(Array(
        'valor' => $pagina['conteudo']
    ));
}
```

### 4. Listagem com Template de Item

```php
function listar_produtos() {
    // Template da página tem célula de item
    $cel_item = pagina_celula('item');
    
    $produtos = banco_select(Array(
        'campos' => Array('nome', 'preco', 'imagem'),
        'tabela' => 'produtos'
    ));
    
    $html_itens = '';
    
    foreach ($produtos as $produto) {
        $item = $cel_item;
        $item = pagina_celula_trocar_variavel_valor($item, 'nome', $produto['nome']);
        $item = pagina_celula_trocar_variavel_valor($item, 'preco', $produto['preco']);
        $item = pagina_celula_trocar_variavel_valor($item, 'imagem', $produto['imagem']);
        
        $html_itens .= $item;
    }
    
    pagina_celula_incluir('item', $html_itens);
}
```

---

## Padrões e Melhores Práticas

### Nomenclatura de Células

```php
// ✅ BOM - Nomes descritivos
<!-- header < -->...<!-- header > -->
<!-- menu-principal < -->...<!-- menu-principal > -->
<!-- conteudo-dinamico < -->...<!-- conteudo-dinamico > -->

// ❌ EVITAR - Nomes genéricos
<!-- div1 < -->...<!-- div1 > -->
<!-- bloco < -->...<!-- bloco > -->
```

### Ordem de Processamento

```php
// ✅ Processar células antes de variáveis
$celula = pagina_celula('produto');
$celula = pagina_celula_trocar_variavel_valor($celula, 'nome', $nome);
pagina_celula_incluir('produto', $celula);

pagina_trocar_variavel_valor('titulo-pagina', 'Lista de Produtos');
```

### Mascaramento Consistente

```php
// ✅ Sempre mascarar ao salvar
$html_salvar = pagina_variaveis_globais_mascarar(Array('valor' => $html));

// ✅ Sempre desmascarar ao carregar
$html_editar = pagina_variaveis_globais_desmascarar(Array('valor' => $html_bd));
```

---

## Limitações e Considerações

### Performance

- Operações de string podem ser lentas com HTML grande
- Cache resultados quando possível

### Aninhamento

- Células não devem estar aninhadas
- Uma célula por bloco de conteúdo

### Encoding

- Cuidado com caracteres especiais em variáveis
- Use `htmlspecialchars()` quando necessário

---

## Veja Também

- [BIBLIOTECA-MODELO.md](./BIBLIOTECA-MODELO.md) - Funções de template
- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Componentes e layouts

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
