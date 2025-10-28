# Biblioteca: modelo.php

> 📝 Motor de templates e substituição de variáveis

## Visão Geral

A biblioteca `modelo.php` fornece funções para manipular templates HTML, substituir variáveis, extrair e inserir conteúdo usando tags especiais. Sistema fundamental para geração dinâmica de páginas.

**Localização**: `gestor/bibliotecas/modelo.php`  
**Total de Funções**: 10

## Dependências

- **Bibliotecas**: Nenhuma (standalone)
- **Variáveis Globais**: Nenhuma

---

## Conceitos

### Variáveis
Placeholders no formato `[[nome-variavel]]` substituídos por valores.

### Tags
Delimitadores especiais para blocos de conteúdo:
- `<!-- tag < -->conteúdo<!-- tag > -->` - Bloco normal
- `<!-- [[variavel]] -->` - Ponto de inserção

---

## Funções Principais

### modelo_var_troca()

Substitui primeira ocorrência de variável por valor.

**Assinatura:**
```php
function modelo_var_troca($modelo, $var, $valor)
```

**Parâmetros:**
- `$modelo` (string) - Template HTML
- `$var` (string) - Variável a substituir
- `$valor` (string) - Valor de substituição

**Retorno:**
- (string) - Template com variável substituída

**Exemplo de Uso:**
```php
$template = "Olá [[nome]], bem-vindo ao [[site]]!";

$template = modelo_var_troca($template, '[[nome]]', 'João');
// "Olá João, bem-vindo ao [[site]]!"

$template = modelo_var_troca($template, '[[site]]', 'Conn2Flow');
// "Olá João, bem-vindo ao Conn2Flow!"
```

---

### modelo_var_troca_tudo()

Substitui todas as ocorrências de variável.

**Assinatura:**
```php
function modelo_var_troca_tudo($modelo, $var, $valor)
```

**Exemplo de Uso:**
```php
$template = "[[produto]] custa R$ 10. Compre [[produto]] agora!";

$result = modelo_var_troca_tudo($template, '[[produto]]', 'Notebook');
// "Notebook custa R$ 10. Compre Notebook agora!"
```

---

### modelo_var_in()

Insere valor em ponto de inserção marcado.

**Assinatura:**
```php
function modelo_var_in($modelo, $var, $valor)
```

**Exemplo de Uso:**
```php
$template = "
<div>
    <!-- conteudo -->
</div>";

$result = modelo_var_in($template, '<!-- conteudo -->', '<p>Texto inserido</p>');
/*
<div>
    <p>Texto inserido</p>
</div>
*/
```

---

### modelo_tag_val()

Extrai conteúdo entre tags.

**Assinatura:**
```php
function modelo_tag_val($modelo, $tag_in, $tag_out)
```

**Parâmetros:**
- `$modelo` (string) - Template HTML
- `$tag_in` (string) - Tag de abertura
- `$tag_out` (string) - Tag de fechamento

**Retorno:**
- (string) - Conteúdo entre as tags

**Exemplo de Uso:**
```php
$template = "
<div>
    <!-- item < -->
    <li>[[titulo]]</li>
    <!-- item > -->
</div>";

$item_template = modelo_tag_val($template, '<!-- item < -->', '<!-- item > -->');
// "<li>[[titulo]]</li>"
```

---

### modelo_tag_in()

Substitui conteúdo entre tags.

**Assinatura:**
```php
function modelo_tag_in($modelo, $tag_in, $tag_out, $valor)
```

**Exemplo de Uso:**
```php
$template = "
<!-- menu < -->
<nav>Menu padrão</nav>
<!-- menu > -->";

$result = modelo_tag_in($template, '<!-- menu < -->', '<!-- menu > -->', 
    '<nav><a href="/">Home</a></nav>');
// Substitui menu padrão pelo novo
```

---

### modelo_tag_del()

Remove bloco entre tags.

**Assinatura:**
```php
function modelo_tag_del($modelo, $tag_in, $tag_out)
```

**Exemplo de Uso:**
```php
$template = "
<div>Conteúdo principal</div>
<!-- debug < -->
<div>Info de debug</div>
<!-- debug > -->";

$result = modelo_tag_del($template, '<!-- debug < -->', '<!-- debug > -->');
// Remove bloco de debug completamente
```

---

### modelo_tag_troca_val()

Extrai e substitui conteúdo entre tags.

**Assinatura:**
```php
function modelo_tag_troca_val($modelo, $tag_in, $tag_out, $valor)
```

**Exemplo de Uso:**
```php
// Similar a tag_in, mas retorna o valor antigo
$old_content = modelo_tag_troca_val($template, '<!-- block < -->', 
    '<!-- block > -->', $new_content);
```

---

### modelo_input_in()

Substitui valor de input HTML.

**Assinatura:**
```php
function modelo_input_in($modelo, $name_input_in, $name_input_out, $valor)
```

**Parâmetros:**
- `$modelo` (string) - Template HTML
- `$name_input_in` (string) - Nome do input início
- `$name_input_out` (string) - Nome do input fim
- `$valor` (string) - Novo valor

**Exemplo de Uso:**
```php
$form = '<input type="text" name="email" value="">';

$result = modelo_input_in($form, 'name="email"', '>', 'user@example.com');
// '<input type="text" name="email" value="user@example.com">'
```

---

### modelo_var_troca_fim()

Substitui variável do fim para o início.

**Assinatura:**
```php
function modelo_var_troca_fim($modelo, $var, $valor)
```

---

### modelo_abrir()

Carrega template de arquivo.

**Assinatura:**
```php
function modelo_abrir($modelo_local)
```

**Parâmetros:**
- `$modelo_local` (string) - Caminho do arquivo

**Retorno:**
- (string) - Conteúdo do template

**Exemplo de Uso:**
```php
$template = modelo_abrir('/templates/email.html');
$template = modelo_var_troca_tudo($template, '[[nome]]', 'João');
```

---

## Casos de Uso Comuns

### 1. Sistema de Templates de Email

```php
function enviar_email_boas_vindas($usuario) {
    // Carregar template
    $template = modelo_abrir('/templates/boas-vindas.html');
    
    // Substituir variáveis
    $template = modelo_var_troca_tudo($template, '[[nome]]', $usuario['nome']);
    $template = modelo_var_troca_tudo($template, '[[email]]', $usuario['email']);
    $template = modelo_var_troca_tudo($template, '[[data]]', date('d/m/Y'));
    
    // Enviar
    comunicacao_email(Array(
        'destinatarios' => Array(Array('email' => $usuario['email'])),
        'mensagem' => Array(
            'assunto' => 'Bem-vindo!',
            'html' => $template
        )
    ));
}
```

### 2. Listagem com Template de Item

```php
function listar_produtos() {
    // Template da página
    $pagina = modelo_abrir('/templates/produtos.html');
    
    // Extrair template do item
    $item_template = modelo_tag_val($pagina, '<!-- item < -->', '<!-- item > -->');
    
    // Remover template original
    $pagina = modelo_tag_in($pagina, '<!-- item < -->', '<!-- item > -->', '<!-- itens -->');
    
    // Buscar produtos
    $produtos = banco_select(Array(
        'campos' => Array('nome', 'preco', 'imagem'),
        'tabela' => 'produtos'
    ));
    
    // Gerar HTML dos itens
    $html_itens = '';
    foreach ($produtos as $produto) {
        $item = $item_template;
        $item = modelo_var_troca_tudo($item, '[[nome]]', $produto['nome']);
        $item = modelo_var_troca_tudo($item, '[[preco]]', $produto['preco']);
        $item = modelo_var_troca_tudo($item, '[[imagem]]', $produto['imagem']);
        $html_itens .= $item;
    }
    
    // Inserir itens na página
    $pagina = modelo_var_in($pagina, '<!-- itens -->', $html_itens);
    
    echo $pagina;
}
```

### 3. Conteúdo Condicional

```php
function renderizar_perfil($usuario) {
    $template = modelo_abrir('/templates/perfil.html');
    
    // Mostrar/ocultar blocos condicionalmente
    if ($usuario['is_premium']) {
        // Manter bloco premium
        $premium_content = modelo_tag_val($template, '<!-- premium < -->', '<!-- premium > -->');
        $template = modelo_tag_in($template, '<!-- premium < -->', '<!-- premium > -->', $premium_content);
        
        // Remover bloco free
        $template = modelo_tag_del($template, '<!-- free < -->', '<!-- free > -->');
    } else {
        // Remover bloco premium
        $template = modelo_tag_del($template, '<!-- premium < -->', '<!-- premium > -->');
        
        // Manter bloco free
        $free_content = modelo_tag_val($template, '<!-- free < -->', '<!-- free > -->');
        $template = modelo_tag_in($template, '<!-- free < -->', '<!-- free > -->', $free_content);
    }
    
    // Substituir variáveis do usuário
    $template = modelo_var_troca_tudo($template, '[[nome]]', $usuario['nome']);
    
    echo $template;
}
```

### 4. Gerador de Relatórios

```php
function gerar_relatorio_vendas($periodo) {
    $template = modelo_abrir('/templates/relatorio.html');
    
    // Dados do relatório
    $vendas = buscar_vendas($periodo);
    $total = array_sum(array_column($vendas, 'valor'));
    
    // Cabeçalho
    $template = modelo_var_troca_tudo($template, '[[periodo]]', $periodo);
    $template = modelo_var_troca_tudo($template, '[[total]]', number_format($total, 2));
    $template = modelo_var_troca_tudo($template, '[[data-geracao]]', date('d/m/Y H:i'));
    
    // Itens
    $linha_template = modelo_tag_val($template, '<!-- linha < -->', '<!-- linha > -->');
    $template = modelo_tag_in($template, '<!-- linha < -->', '<!-- linha > -->', '<!-- linhas -->');
    
    $html_linhas = '';
    foreach ($vendas as $venda) {
        $linha = $linha_template;
        $linha = modelo_var_troca_tudo($linha, '[[data]]', $venda['data']);
        $linha = modelo_var_troca_tudo($linha, '[[cliente]]', $venda['cliente']);
        $linha = modelo_var_troca_tudo($linha, '[[valor]]', number_format($venda['valor'], 2));
        $html_linhas .= $linha;
    }
    
    $template = modelo_var_in($template, '<!-- linhas -->', $html_linhas);
    
    return $template;
}
```

---

## Padrões e Melhores Práticas

### Nomenclatura de Variáveis

```php
// ✅ BOM - Descritivo e consistente
[[nome-usuario]]
[[data-cadastro]]
[[total-pedido]]

// ❌ EVITAR - Ambíguo
[[n]]
[[d]]
[[t]]
```

### Organização de Tags

```php
// ✅ BOM - Tags claras e bem definidas
<!-- header < -->
<header>...</header>
<!-- header > -->

<!-- item < -->
<div class="item">...</div>
<!-- item > -->

// ❌ EVITAR - Tags genéricas
<!-- a < -->
...
<!-- a > -->
```

---

## Veja Também

- [BIBLIOTECA-PAGINA.md](./BIBLIOTECA-PAGINA.md) - Manipulação de páginas
- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Componentes

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
