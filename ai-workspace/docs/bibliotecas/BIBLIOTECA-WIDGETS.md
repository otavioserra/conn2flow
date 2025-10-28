# Biblioteca: widgets.php

> 🧩 Sistema de widgets reutilizáveis

## Visão Geral

A biblioteca `widgets.php` fornece um sistema para criar e gerenciar widgets - componentes reutilizáveis com funcionalidade própria, CSS e JavaScript isolados. Suporta validação de formulários, integração com reCAPTCHA e controle de acesso.

**Localização**: `gestor/bibliotecas/widgets.php`  
**Versão**: 1.0.1  
**Total de Funções**: 4 (3 principais + 1 controlador específico)

## Dependências

- **Bibliotecas**: gestor.php, modelo.php, formulario.php, autenticacao.php
- **Variáveis Globais**: `$_GESTOR`, `$_CONFIG`
- **JavaScript**: widgets.js, jQuery-Mask-Plugin

## Variáveis Globais

```php
$_GESTOR['biblioteca-widgets'] = Array(
    'versao' => '1.0.1',
    'widgets' => Array(
        'formulario-contato' => Array(
            'versao' => '1.0.2',
            'componenteID' => 'widgets-formulario-contato',
            'jsCaminho' => 'widgets.js',
            'modulosExtras' => 'contatos'
        ),
        // Adicione mais widgets aqui
    ),
);

// Cache de CSS/JS incluídos
$_GESTOR['widgets-css'][$widget_id] = true;
$_GESTOR['widgets-js'][$js_path] = true;
```

---

## Estrutura de Widget

### Configuração

Cada widget é definido em `$_GESTOR['biblioteca-widgets']['widgets']`:

```php
'widget-id' => Array(
    'versao' => '1.0.0',              // Versão do widget
    'componenteID' => 'componente-id', // ID do componente HTML
    'jsCaminho' => 'script.js',        // Arquivo JavaScript
    'modulosExtras' => 'modulo1,modulo2' // Módulos para variáveis
)
```

### Componente HTML

Armazenado na tabela `componentes` do banco de dados com:
- HTML do widget
- CSS isolado
- Variáveis substituíveis

---

## Funções Principais

### widgets_get()

Renderiza e retorna um widget completo.

**Assinatura:**
```php
function widgets_get($params = false)
```

**Parâmetros (Array Associativo):**
- `id` (string) - **Obrigatório** - Identificador único do widget

**Retorno:**
- (string) - HTML renderizado do widget

**Exemplo de Uso:**
```php
// Incluir widget de formulário de contato
$widget_html = widgets_get(Array(
    'id' => 'formulario-contato'
));

echo $widget_html;
```

**Comportamento:**
1. Busca configuração do widget
2. Carrega componente HTML do banco
3. Executa controlador específico (se existir)
4. Inclui CSS uma única vez
5. Inclui JavaScript uma única vez
6. Registra módulos extras para variáveis
7. Retorna HTML processado

**Notas:**
- CSS e JS são incluídos apenas uma vez por página
- Usa cache para evitar duplicação
- Variáveis globais são substituídas automaticamente

---

### widgets_search()

Busca configuração de um widget.

**Assinatura:**
```php
function widgets_search($params = false)
```

**Parâmetros (Array Associativo):**
- `id` (string) - **Obrigatório** - Identificador do widget

**Retorno:**
- (array|null) - Configuração do widget ou null

**Exemplo de Uso:**
```php
$config = widgets_search(Array(
    'id' => 'formulario-contato'
));

if ($config) {
    echo "Versão: " . $config['versao'];
    echo "Componente: " . $config['componenteID'];
}
```

---

### widgets_controller()

Controlador central que despacha para controladores específicos.

**Assinatura:**
```php
function widgets_controller($params = false)
```

**Parâmetros (Array Associativo):**
- `id` (string) - **Obrigatório** - ID do widget
- `html` (string) - **Obrigatório** - HTML do widget

**Retorno:**
- (string) - HTML processado

**Exemplo de Uso:**
```php
// Uso interno pela função widgets_get()
$html = widgets_controller(Array(
    'id' => 'formulario-contato',
    'html' => $widget_html
));
```

**Controladores Disponíveis:**
- `'formulario-contato'` → `widgets_formulario_contato()`

---

### widgets_formulario_contato()

Controlador específico do widget de formulário de contato.

**Assinatura:**
```php
function widgets_formulario_contato($params = false)
```

**Parâmetros (Array Associativo):**
- `html` (string) - **Obrigatório** - HTML do widget

**Retorno:**
- (string) - HTML processado com validações e controles

**Funcionalidades:**
1. **Validação de Formulário**
   - Nome obrigatório
   - Email válido
   - Telefone não vazio
   - Mensagem obrigatória

2. **Controle de Acesso**
   - Verifica rate limiting por IP
   - Mostra mensagem se bloqueado
   - Esconde formulário se bloqueado

3. **reCAPTCHA**
   - Integra Google reCAPTCHA v3
   - Ativa apenas se configurado
   - Bypass para usuários em whitelist

4. **Máscaras de Input**
   - Inclui jQuery Mask Plugin
   - Aplica máscaras automaticamente

**Exemplo de Uso:**
```php
// Incluir widget na página
echo widgets_get(Array('id' => 'formulario-contato'));

// HTML resultante inclui:
// - Formulário com validação
// - reCAPTCHA (se configurado)
// - Máscara de telefone
// - Mensagem de bloqueio (se aplicável)
```

---

## Casos de Uso Comuns

### 1. Criar Novo Widget

```php
// 1. Registrar widget
$_GESTOR['biblioteca-widgets']['widgets']['meu-widget'] = Array(
    'versao' => '1.0.0',
    'componenteID' => 'componente-meu-widget',
    'jsCaminho' => 'meu-widget.js',
    'modulosExtras' => 'meu-modulo'
);

// 2. Criar componente no banco de dados
banco_insert_name(Array(
    Array('id', 'componente-meu-widget'),
    Array('html', '<div class="meu-widget">[[conteudo]]</div>'),
    Array('css', '.meu-widget { padding: 20px; }'),
    Array('language', 'pt')
), 'componentes');

// 3. Criar controlador (opcional)
function widgets_meu_widget($params = false) {
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        // Processar HTML
        $html = str_replace('[[conteudo]]', 'Meu Conteúdo', $html);
        return $html;
    }
    
    return '';
}

// 4. Adicionar ao controller
function widgets_controller($params = false){
    // ... código existente ...
    switch($id){
        case 'formulario-contato': 
            $html = widgets_formulario_contato(Array('html' => $html)); 
            break;
        case 'meu-widget':
            $html = widgets_meu_widget(Array('html' => $html));
            break;
    }
    // ...
}

// 5. Usar na página
echo widgets_get(Array('id' => 'meu-widget'));
```

### 2. Widget de Newsletter

```php
// Registrar
$_GESTOR['biblioteca-widgets']['widgets']['newsletter'] = Array(
    'versao' => '1.0.0',
    'componenteID' => 'widget-newsletter',
    'jsCaminho' => 'newsletter.js'
);

// Controlador
function widgets_newsletter($params = false) {
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        gestor_incluir_biblioteca('formulario');
        
        // Validação
        formulario_validacao(Array(
            'formId' => 'form-newsletter',
            'validacao' => Array(
                Array(
                    'regra' => 'email',
                    'campo' => 'email',
                    'label' => 'Email'
                )
            )
        ));
        
        // Processar submissão
        if (isset($_POST['email'])) {
            $email = $_POST['email'];
            
            // Salvar no banco
            banco_insert_name(Array(
                Array('email', $email),
                Array('data_cadastro', 'NOW()', true, false)
            ), 'newsletter_emails');
            
            // Mostrar mensagem de sucesso
            $html = str_replace('<!-- form < -->', '', $html);
            $html = str_replace('<!-- form > -->', '', $html);
        }
        
        return $html;
    }
    
    return '';
}
```

### 3. Widget de Pesquisa

```php
function widgets_pesquisa($params = false) {
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        // Processar pesquisa
        if (isset($_GET['q'])) {
            $termo = banco_escape_field($_GET['q']);
            
            $resultados = banco_select(Array(
                'campos' => Array('titulo', 'resumo', 'url'),
                'tabela' => 'conteudos',
                'extra' => "WHERE titulo LIKE '%$termo%' OR conteudo LIKE '%$termo%' LIMIT 10"
            ));
            
            $cel_resultado = modelo_tag_val($html, '<!-- resultado < -->', '<!-- resultado > -->');
            $html = modelo_tag_in($html, '<!-- resultado < -->', '<!-- resultado > -->', '<!-- resultados -->');
            
            if ($resultados) {
                $html_resultados = '';
                
                foreach ($resultados as $resultado) {
                    $item = $cel_resultado;
                    $item = str_replace('[[titulo]]', $resultado['titulo'], $item);
                    $item = str_replace('[[resumo]]', $resultado['resumo'], $item);
                    $item = str_replace('[[url]]', $resultado['url'], $item);
                    $html_resultados .= $item;
                }
                
                $html = modelo_var_in($html, '<!-- resultados -->', $html_resultados);
            } else {
                $html = modelo_var_in($html, '<!-- resultados -->', '<p>Nenhum resultado encontrado.</p>');
            }
        }
        
        return $html;
    }
    
    return '';
}
```

### 4. Widget com Autenticação

```php
function widgets_area_usuario($params = false) {
    global $_GESTOR;
    
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        gestor_incluir_biblioteca('autenticacao');
        
        $usuario = gestor_usuario();
        
        if ($usuario) {
            // Usuário logado
            $html = modelo_tag_in($html, '<!-- nao-logado < -->', '<!-- nao-logado > -->', '');
            $html = str_replace('[[nome-usuario]]', $usuario['nome'], $html);
            $html = str_replace('[[email-usuario]]', $usuario['email'], $html);
        } else {
            // Usuário não logado
            $html = modelo_tag_in($html, '<!-- logado < -->', '<!-- logado > -->', '');
        }
        
        return $html;
    }
    
    return '';
}
```

### 5. Widget com Ajax

```php
function widgets_comentarios($params = false) {
    if($params)foreach($params as $var => $val)$$var = $val;
    
    if(isset($html)){
        $pagina_id = $_GET['pagina_id'] ?? null;
        
        if ($pagina_id) {
            // Carregar comentários
            $comentarios = banco_select(Array(
                'campos' => Array('autor', 'comentario', 'data'),
                'tabela' => 'comentarios',
                'extra' => "WHERE pagina_id='$pagina_id' AND aprovado=1 ORDER BY data DESC"
            ));
            
            $cel_comentario = modelo_tag_val($html, '<!-- comentario < -->', '<!-- comentario > -->');
            $html = modelo_tag_in($html, '<!-- comentario < -->', '<!-- comentario > -->', '<!-- lista-comentarios -->');
            
            $html_comentarios = '';
            
            if ($comentarios) {
                foreach ($comentarios as $comentario) {
                    $item = $cel_comentario;
                    $item = str_replace('[[autor]]', htmlspecialchars($comentario['autor']), $item);
                    $item = str_replace('[[comentario]]', htmlspecialchars($comentario['comentario']), $item);
                    $item = str_replace('[[data]]', date('d/m/Y H:i', strtotime($comentario['data'])), $item);
                    $html_comentarios .= $item;
                }
            } else {
                $html_comentarios = '<p>Nenhum comentário ainda. Seja o primeiro!</p>';
            }
            
            $html = modelo_var_in($html, '<!-- lista-comentarios -->', $html_comentarios);
            $html = str_replace('[[pagina-id]]', $pagina_id, $html);
        }
        
        return $html;
    }
    
    return '';
}
```

---

## Integração com reCAPTCHA

### Configuração

```php
// Em configuracao.php ou similar
$_CONFIG['usuario-recaptcha-active'] = true;
$_CONFIG['usuario-recaptcha-site'] = 'sua-chave-site';
$_CONFIG['usuario-recaptcha-secret'] = 'sua-chave-secreta';
```

### Validação no Controlador

```php
function validar_recaptcha($token) {
    global $_CONFIG;
    
    $secret = $_CONFIG['usuario-recaptcha-secret'];
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = Array(
        'secret' => $secret,
        'response' => $token
    );
    
    $options = Array(
        'http' => Array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response, true);
    
    return $result['success'] && $result['score'] >= 0.5;
}
```

---

## Padrões e Melhores Práticas

### Isolamento de CSS

```php
// ✅ BOM - CSS com prefixo específico
.widget-formulario-contato input {
    /* estilos */
}

// ❌ EVITAR - CSS genérico
input {
    /* afeta todos os inputs da página */
}
```

### Versionamento

```php
// ✅ Incrementar versão ao atualizar JavaScript
'versao' => '1.0.1', // CSS/HTML mudou
'versao' => '1.1.0', // JS mudou (cache-busting)
```

### Validação

```php
// ✅ Sempre validar no servidor
// Não confiar apenas em validação JavaScript
```

---

## Limitações e Considerações

### Performance

- Widgets incluem CSS/JS na página
- Múltiplos widgets podem aumentar tamanho da página
- Use minificação em produção

### Cache

- CSS/JS são cacheados por versão
- Incrementar versão força reload
- Browser cache pode causar problemas

### Segurança

- Sempre sanitizar entrada do usuário
- Usar `htmlspecialchars()` em output
- Validar no servidor, não só no cliente

---

## Veja Também

- [BIBLIOTECA-GESTOR.md](./BIBLIOTECA-GESTOR.md) - Componentes
- [BIBLIOTECA-FORMULARIO.md](./BIBLIOTECA-FORMULARIO.md) - Validação
- [BIBLIOTECA-AUTENTICACAO.md](./BIBLIOTECA-AUTENTICACAO.md) - Controle de acesso
- [BIBLIOTECA-MODELO.md](./BIBLIOTECA-MODELO.md) - Templates

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
