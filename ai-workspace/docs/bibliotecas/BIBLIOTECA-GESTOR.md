# Biblioteca: gestor.php

> 🚀 Motor principal do CMS Conn2Flow

## Visão Geral

A biblioteca `gestor.php` é o coração do sistema Conn2Flow, fornecendo 24 funções essenciais para gerenciamento de conteúdo, componentes, layouts, sessões, usuários e páginas.

**Localização**: `gestor/bibliotecas/gestor.php`  
**Total de Funções**: 24

## Principais Categorias

### Gerenciamento de Bibliotecas
- `gestor_incluir_biblioteca()` - Inclui biblioteca dinamicamente
- `gestor_biblioteca_existe()` - Verifica se biblioteca existe
- `gestor_carregar_bibliotecas()` - Carrega todas as bibliotecas

### Componentes e Layouts
- `gestor_componente()` - Carrega componente do banco
- `gestor_layout()` - Carrega layout de página
- `gestor_template()` - Processa template
- `gestor_renderizar()` - Renderiza conteúdo final

### Sessão e Usuário
- `gestor_sessao_iniciar()` - Inicia sessão
- `gestor_sessao_variavel()` - Get/set variável de sessão
- `gestor_sessao_variavel_del()` - Remove variável de sessão
- `gestor_usuario()` - Retorna dados do usuário logado
- `gestor_usuario_logado()` - Verifica se está logado

### Páginas e Rotas
- `gestor_pagina()` - Carrega página
- `gestor_pagina_atual()` - Retorna página atual
- `gestor_pagina_redirecionar()` - Redireciona para URL
- `gestor_pagina_variaveis_globais()` - Substitui variáveis globais
- `gestor_pagina_javascript_incluir()` - Adiciona JavaScript
- `gestor_pagina_css_incluir()` - Adiciona CSS

### Variáveis e Configuração
- `gestor_variaveis()` - Busca variável do sistema
- `gestor_configuracao()` - Busca configuração
- `gestor_idioma()` - Retorna idioma atual

### Utilitários
- `gestor_url()` - Gera URL do sistema
- `gestor_upload()` - Processa upload de arquivo
- `gestor_cache()` - Sistema de cache

## Exemplos de Uso

### Incluir Biblioteca

```php
// Carregar biblioteca quando necessário
gestor_incluir_biblioteca('banco');
gestor_incluir_biblioteca('email');

// Usar funções da biblioteca
banco_select(/*...*/);
email_enviar(/*...*/);
```

### Carregar Componente

```php
// Componente simples
$menu = gestor_componente(Array(
    'id' => 'menu-principal'
));

echo $menu['html'];

// Componente com CSS
$card = gestor_componente(Array(
    'id' => 'card-produto',
    'return_css' => true
));

gestor_pagina_css_incluir($card['css']);
echo $card['html'];
```

### Gerenciar Sessão

```php
// Iniciar sessão
gestor_sessao_iniciar();

// Salvar na sessão
gestor_sessao_variavel('carrinho', Array(
    'items' => Array(),
    'total' => 0
));

// Recuperar da sessão
$carrinho = gestor_sessao_variavel('carrinho');

// Remover da sessão
gestor_sessao_variavel_del('carrinho');
```

### Usuário Logado

```php
// Verificar se está logado
if (gestor_usuario_logado()) {
    $usuario = gestor_usuario();
    
    echo "Olá, {$usuario['nome']}!";
    echo "Email: {$usuario['email']}";
    echo "Nível: {$usuario['nivel']}";
} else {
    gestor_pagina_redirecionar('/login');
}
```

### Processar Página

```php
// Carregar layout principal
$layout = gestor_layout(Array(
    'id' => 'layout-principal',
    'return_css' => true
));

// Processar variáveis globais
$html = gestor_pagina_variaveis_globais(Array(
    'html' => $layout['html']
));

// Adicionar CSS e JS
gestor_pagina_css_incluir($layout['css']);
gestor_pagina_javascript_incluir('<script src="/app.js"></script>');

// Renderizar
echo gestor_renderizar(Array(
    'html' => $html
));
```

### Upload de Arquivo

```php
if (isset($_FILES['arquivo'])) {
    $resultado = gestor_upload(Array(
        'campo' => 'arquivo',
        'destino' => '/uploads/documentos/',
        'tipos_permitidos' => Array('pdf', 'doc', 'docx'),
        'tamanho_maximo' => 5242880  // 5MB
    ));
    
    if ($resultado['sucesso']) {
        echo "Arquivo salvo: " . $resultado['caminho'];
    } else {
        echo "Erro: " . $resultado['erro'];
    }
}
```

### Cache

```php
// Salvar em cache
gestor_cache(Array(
    'acao' => 'set',
    'chave' => 'produtos_destaque',
    'valor' => $produtos,
    'ttl' => 3600  // 1 hora
));

// Recuperar do cache
$produtos = gestor_cache(Array(
    'acao' => 'get',
    'chave' => 'produtos_destaque'
));

if (!$produtos) {
    // Cache expirou, buscar do banco
    $produtos = banco_select(/*...*/);
    gestor_cache(Array(
        'acao' => 'set',
        'chave' => 'produtos_destaque',
        'valor' => $produtos
    ));
}
```

### Variáveis do Sistema

```php
// Buscar variável de linguagem
$titulo_botao = gestor_variaveis(Array(
    'modulo' => 'interface',
    'id' => 'btn-save'
));

echo "<button>$titulo_botao</button>";

// Buscar configuração
$items_per_page = gestor_configuracao(Array(
    'chave' => 'pagination.items_per_page',
    'padrao' => 20
));
```

## Casos de Uso Comuns

### 1. Página de Blog

```php
// Carregar layout
$layout = gestor_layout(Array('id' => 'blog-layout'));

// Buscar posts
gestor_incluir_biblioteca('banco');
$posts = banco_select(Array(
    'campos' => Array('titulo', 'conteudo', 'data'),
    'tabela' => 'posts',
    'extra' => 'ORDER BY data DESC LIMIT 10'
));

// Renderizar lista de posts
$html = '';
foreach ($posts as $post) {
    $html .= gestor_componente(Array(
        'id' => 'post-item',
        'variaveis' => Array(
            '[[titulo]]' => $post['titulo'],
            '[[conteudo]]' => $post['conteudo'],
            '[[data]]' => $post['data']
        )
    ))['html'];
}

// Inserir no layout
$layout['html'] = str_replace('<!-- posts -->', $html, $layout['html']);

// Processar variáveis globais e renderizar
echo gestor_renderizar(Array(
    'html' => gestor_pagina_variaveis_globais(Array(
        'html' => $layout['html']
    ))
));
```

### 2. Área Administrativa

```php
// Verificar autenticação
if (!gestor_usuario_logado()) {
    gestor_pagina_redirecionar('/admin/login');
}

$usuario = gestor_usuario();

// Verificar permissão
if ($usuario['nivel'] !== 'admin') {
    die('Acesso negado');
}

// Carregar dashboard
$dashboard = gestor_componente(Array(
    'id' => 'admin-dashboard'
));

// Incluir bibliotecas necessárias
gestor_incluir_biblioteca('interface');
gestor_incluir_biblioteca('banco');

// Buscar estatísticas
$stats = Array(
    'usuarios' => banco_count('usuarios'),
    'produtos' => banco_count('produtos'),
    'vendas_hoje' => banco_sum('vendas', 'valor', "WHERE DATE(data)=CURDATE()")
);

// Substituir variáveis
$html = $dashboard['html'];
foreach ($stats as $chave => $valor) {
    $html = str_replace("[[$chave]]", $valor, $html);
}

echo gestor_renderizar(Array('html' => $html));
```

### 3. Sistema Multi-idioma

```php
// Detectar idioma
$idioma = gestor_idioma();

// Carregar textos do idioma
$textos = Array(
    'pt' => Array(
        'welcome' => 'Bem-vindo',
        'logout' => 'Sair'
    ),
    'en' => Array(
        'welcome' => 'Welcome',
        'logout' => 'Logout'
    )
);

// Usar no template
$layout = gestor_layout(Array('id' => 'main'));
$layout['html'] = str_replace('[[welcome]]', $textos[$idioma]['welcome'], $layout['html']);

echo gestor_renderizar(Array('html' => $layout['html']));
```

## Arquitetura

### Fluxo de Requisição

1. `index.php` inicia sessão e carrega gestor
2. `gestor_pagina()` identifica rota
3. `gestor_layout()` carrega layout base
4. `gestor_componente()` carrega componentes
5. `gestor_pagina_variaveis_globais()` substitui variáveis
6. `gestor_renderizar()` gera HTML final

### Estrutura de Dados

```php
$_GESTOR = Array(
    'versao' => '2.3.0',
    'url-raiz' => '/var/www/',
    'host-id' => 1,
    'usuario' => Array(/*...*/),
    'pagina' => '...',
    'idioma' => 'pt',
    'bibliotecas' => Array(/*...*/),
    'javascript' => Array(/*...*/),
    'css' => Array(/*...*/)
);
```

## Padrões e Melhores Práticas

### Carregar Bibliotecas sob Demanda

```php
// ✅ BOM - Carregar apenas quando necessário
if (isset($_POST['email'])) {
    gestor_incluir_biblioteca('comunicacao');
    comunicacao_email(/*...*/);
}

// ❌ EVITAR - Carregar tudo sempre
gestor_incluir_biblioteca('comunicacao');
gestor_incluir_biblioteca('pdf');
gestor_incluir_biblioteca('ftp');
// ... uso mínimo
```

### Cache Inteligente

```php
// ✅ Cache de componentes pesados
$key = "componente_menu_{$idioma}";
$menu = gestor_cache(Array('acao' => 'get', 'chave' => $key));

if (!$menu) {
    $menu = gestor_componente(Array('id' => 'menu'));
    gestor_cache(Array('acao' => 'set', 'chave' => $key, 'valor' => $menu));
}
```

---

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Banco de dados
- [BIBLIOTECA-AUTENTICACAO.md](./BIBLIOTECA-AUTENTICACAO.md) - Autenticação
- [BIBLIOTECA-INTERFACE.md](./BIBLIOTECA-INTERFACE.md) - UI components

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
