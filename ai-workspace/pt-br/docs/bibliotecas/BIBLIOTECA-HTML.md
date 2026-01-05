# Biblioteca: html.php

> 🏗️ Geração e manipulação de elementos HTML

## Visão Geral

A biblioteca `html.php` fornece funções utilitárias para construir, manipular e formatar elementos HTML de forma programática, facilitando a geração dinâmica de interfaces.

**Localização**: `gestor/bibliotecas/html.php`  
**Total de Funções**: 8

## Dependências

- **Bibliotecas**: Nenhuma (standalone)
- **Variáveis Globais**: Nenhuma

---

## Funções Principais

### html_iniciar()

Inicia construção de um elemento HTML com tag de abertura.

**Assinatura:**
```php
function html_iniciar($params = false)
```

**Parâmetros (Array Associativo):**
- `tag` (string) - **Obrigatório** - Nome da tag HTML
- `atributos` (array) - **Opcional** - Atributos do elemento

**Retorno:**
- (string) - Tag de abertura HTML

**Exemplo de Uso:**
```php
// Tag simples
echo html_iniciar(Array('tag' => 'div'));
// Output: <div>

// Com atributos
echo html_iniciar(Array(
    'tag' => 'div',
    'atributos' => Array(
        'class' => 'container',
        'id' => 'main',
        'data-role' => 'wrapper'
    )
));
// Output: <div class="container" id="main" data-role="wrapper">
```

---

### html_finalizar()

Finaliza elemento HTML com tag de fechamento.

**Assinatura:**
```php
function html_finalizar($params = false)
```

**Parâmetros (Array Associativo):**
- `tag` (string) - **Obrigatório** - Nome da tag HTML

**Retorno:**
- (string) - Tag de fechamento HTML

**Exemplo de Uso:**
```php
echo html_iniciar(Array('tag' => 'div'));
echo "Conteúdo";
echo html_finalizar(Array('tag' => 'div'));
// Output: <div>Conteúdo</div>
```

---

### html_elemento()

Cria elemento HTML completo (abertura + conteúdo + fechamento).

**Assinatura:**
```php
function html_elemento($params = false)
```

**Parâmetros (Array Associativo):**
- `tag` (string) - **Obrigatório** - Nome da tag
- `conteudo` (string) - **Opcional** - Conteúdo interno
- `atributos` (array) - **Opcional** - Atributos

**Retorno:**
- (string) - Elemento HTML completo

**Exemplo de Uso:**
```php
// Elemento simples
echo html_elemento(Array(
    'tag' => 'p',
    'conteudo' => 'Texto do parágrafo'
));
// Output: <p>Texto do parágrafo</p>

// Com atributos
echo html_elemento(Array(
    'tag' => 'button',
    'conteudo' => 'Clique Aqui',
    'atributos' => Array(
        'class' => 'btn btn-primary',
        'type' => 'submit',
        'onclick' => 'handleClick()'
    )
));
// Output: <button class="btn btn-primary" type="submit" onclick="handleClick()">Clique Aqui</button>
```

---

### html_atributo()

Gera string de atributos HTML a partir de array.

**Assinatura:**
```php
function html_atributo($params = false)
```

**Parâmetros (Array Associativo):**
- `atributos` (array) - **Obrigatório** - Array de atributos

**Retorno:**
- (string) - String de atributos formatada

**Exemplo de Uso:**
```php
$attrs = html_atributo(Array(
    'atributos' => Array(
        'class' => 'form-control',
        'id' => 'email',
        'required' => 'required',
        'placeholder' => 'Digite seu email'
    )
));

echo "<input type='text' $attrs>";
// Output: <input type='text' class="form-control" id="email" required="required" placeholder="Digite seu email">
```

---

### html_valor()

Extrai valor de atributo de um elemento HTML.

**Assinatura:**
```php
function html_valor($params = false)
```

**Parâmetros (Array Associativo):**
- `html` (string) - **Obrigatório** - String HTML
- `atributo` (string) - **Obrigatório** - Nome do atributo

**Retorno:**
- (string) - Valor do atributo ou vazio

**Exemplo de Uso:**
```php
$html = '<input type="text" name="username" value="john_doe">';

$valor = html_valor(Array(
    'html' => $html,
    'atributo' => 'value'
));

echo $valor;  // john_doe
```

---

### html_adicionar_classe()

Adiciona classe CSS a elemento HTML.

**Assinatura:**
```php
function html_adicionar_classe($params = false)
```

**Parâmetros (Array Associativo):**
- `html` (string) - **Obrigatório** - String HTML
- `classe` (string) - **Obrigatório** - Classe a adicionar

**Retorno:**
- (string) - HTML com classe adicionada

**Exemplo de Uso:**
```php
$html = '<div class="container">Conteúdo</div>';

$html = html_adicionar_classe(Array(
    'html' => $html,
    'classe' => 'active'
));

echo $html;
// Output: <div class="container active">Conteúdo</div>
```

---

### html_consulta()

Consulta/extrai elementos HTML usando seletores.

**Assinatura:**
```php
function html_consulta($params = false)
```

**Parâmetros (Array Associativo):**
- `html` (string) - **Obrigatório** - String HTML
- `seletor` (string) - **Obrigatório** - Seletor CSS

**Retorno:**
- (array) - Elementos encontrados

**Exemplo de Uso:**
```php
$html = '<div><p class="text">Parágrafo 1</p><p class="text">Parágrafo 2</p></div>';

$paragrafos = html_consulta(Array(
    'html' => $html,
    'seletor' => 'p.text'
));

// Retorna array com os elementos <p>
```

---

### html_beautify()

Formata/identifica HTML para melhor legibilidade.

**Assinatura:**
```php
function html_beautify($html)
```

**Parâmetros:**
- `$html` (string) - **Obrigatório** - HTML não formatado

**Retorno:**
- (string) - HTML formatado e identado

**Exemplo de Uso:**
```php
$html = '<div><p>Texto</p><ul><li>Item 1</li><li>Item 2</li></ul></div>';

$formatado = html_beautify($html);

echo $formatado;
/* Output:
<div>
    <p>Texto</p>
    <ul>
        <li>Item 1</li>
        <li>Item 2</li>
    </ul>
</div>
*/
```

---

## Casos de Uso Comuns

### 1. Construtor de Formulários

```php
function gerar_formulario($campos) {
    $html = html_iniciar(Array(
        'tag' => 'form',
        'atributos' => Array(
            'method' => 'post',
            'action' => '/submit',
            'class' => 'form-horizontal'
        )
    ));
    
    foreach ($campos as $campo) {
        $html .= html_elemento(Array(
            'tag' => 'div',
            'atributos' => Array('class' => 'form-group'),
            'conteudo' => 
                html_elemento(Array(
                    'tag' => 'label',
                    'conteudo' => $campo['label']
                )) .
                html_iniciar(Array(
                    'tag' => 'input',
                    'atributos' => Array(
                        'type' => $campo['tipo'],
                        'name' => $campo['nome'],
                        'class' => 'form-control',
                        'required' => $campo['obrigatorio'] ? 'required' : null
                    )
                ))
        ));
    }
    
    $html .= html_finalizar(Array('tag' => 'form'));
    
    return $html;
}
```

### 2. Gerador de Tabelas

```php
function gerar_tabela($dados, $colunas) {
    $html = html_iniciar(Array(
        'tag' => 'table',
        'atributos' => Array('class' => 'table table-striped')
    ));
    
    // Cabeçalho
    $html .= html_iniciar(Array('tag' => 'thead'));
    $html .= html_iniciar(Array('tag' => 'tr'));
    foreach ($colunas as $coluna) {
        $html .= html_elemento(Array(
            'tag' => 'th',
            'conteudo' => $coluna['titulo']
        ));
    }
    $html .= html_finalizar(Array('tag' => 'tr'));
    $html .= html_finalizar(Array('tag' => 'thead'));
    
    // Corpo
    $html .= html_iniciar(Array('tag' => 'tbody'));
    foreach ($dados as $linha) {
        $html .= html_iniciar(Array('tag' => 'tr'));
        foreach ($colunas as $coluna) {
            $html .= html_elemento(Array(
                'tag' => 'td',
                'conteudo' => $linha[$coluna['campo']]
            ));
        }
        $html .= html_finalizar(Array('tag' => 'tr'));
    }
    $html .= html_finalizar(Array('tag' => 'tbody'));
    
    $html .= html_finalizar(Array('tag' => 'table'));
    
    return $html;
}
```

### 3. Cards Responsivos

```php
function gerar_card($titulo, $descricao, $imagem = null) {
    $conteudo = '';
    
    if ($imagem) {
        $conteudo .= html_iniciar(Array(
            'tag' => 'img',
            'atributos' => Array(
                'src' => $imagem,
                'class' => 'card-img-top',
                'alt' => $titulo
            )
        ));
    }
    
    $conteudo .= html_elemento(Array(
        'tag' => 'div',
        'atributos' => Array('class' => 'card-body'),
        'conteudo' => 
            html_elemento(Array(
                'tag' => 'h5',
                'atributos' => Array('class' => 'card-title'),
                'conteudo' => $titulo
            )) .
            html_elemento(Array(
                'tag' => 'p',
                'atributos' => Array('class' => 'card-text'),
                'conteudo' => $descricao
            ))
    ));
    
    return html_elemento(Array(
        'tag' => 'div',
        'atributos' => Array('class' => 'card'),
        'conteudo' => $conteudo
    ));
}
```

### 4. Breadcrumbs Dinâmicos

```php
function gerar_breadcrumb($caminho) {
    $items = '';
    
    foreach ($caminho as $index => $item) {
        $ativo = ($index === count($caminho) - 1);
        
        $classes = 'breadcrumb-item';
        if ($ativo) {
            $classes .= ' active';
        }
        
        $conteudo = $ativo ? $item['titulo'] : 
            html_elemento(Array(
                'tag' => 'a',
                'atributos' => Array('href' => $item['url']),
                'conteudo' => $item['titulo']
            ));
        
        $items .= html_elemento(Array(
            'tag' => 'li',
            'atributos' => Array('class' => $classes),
            'conteudo' => $conteudo
        ));
    }
    
    return html_elemento(Array(
        'tag' => 'nav',
        'conteudo' => html_elemento(Array(
            'tag' => 'ol',
            'atributos' => Array('class' => 'breadcrumb'),
            'conteudo' => $items
        ))
    ));
}
```

---

## Padrões e Melhores Práticas

### Escape de Conteúdo

```php
// ✅ BOM - Escapar conteúdo do usuário
$nome = htmlspecialchars($_POST['nome']);
echo html_elemento(Array(
    'tag' => 'p',
    'conteudo' => $nome
));

// ❌ EVITAR - Injeção XSS
echo html_elemento(Array(
    'tag' => 'p',
    'conteudo' => $_POST['nome']  // Perigoso!
));
```

### Reutilização

```php
// ✅ Criar funções auxiliares
function botao($texto, $tipo = 'button') {
    return html_elemento(Array(
        'tag' => 'button',
        'atributos' => Array(
            'type' => $tipo,
            'class' => 'btn btn-primary'
        ),
        'conteudo' => $texto
    ));
}
```

---

## Veja Também

- [BIBLIOTECA-INTERFACE.md](./BIBLIOTECA-INTERFACE.md) - Componentes de UI
- [BIBLIOTECA-FORMULARIO.md](./BIBLIOTECA-FORMULARIO.md) - Forms

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
