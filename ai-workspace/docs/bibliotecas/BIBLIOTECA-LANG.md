# Biblioteca: lang.php

> 🌐 Sistema de internacionalização e tradução

## Visão Geral

A biblioteca `lang.php` fornece um sistema completo de internacionalização (i18n) para o Conn2Flow, permitindo tradução de strings através de arquivos JSON de dicionário. O sistema usa funções customizadas ao invés de gettext, oferecendo mais flexibilidade e controle.

**Localização**: `gestor/bibliotecas/lang.php`  
**Versão**: 1.0  
**Autor**: Otavio Serra  
**Data**: 12/08/2025  
**Total de Funções**: 3

## Dependências

- Arquivos JSON de dicionário (ex: `pt-br.json`, `en.json`)
- Funções nativas PHP: `file_exists()`, `file_get_contents()`, `json_decode()`

## Variáveis Globais

```php
$GLOBALS['lang'] = 'pt-br';           // Idioma padrão
$GLOBALS['dicionario'] = array();     // Dicionário carregado
```

## Funções Principais

### carregar_dicionario()

Carrega o dicionário de idiomas a partir de um arquivo JSON.

**Assinatura:**
```php
function carregar_dicionario($lang = 'pt-br', $base = '')
```

**Parâmetros:**
- `$lang` (string) - Opcional - Código do idioma (padrão: 'pt-br')
- `$base` (string) - Opcional - Caminho base relativo ao diretório da biblioteca

**Retorno:**
- (array) - Array associativo com as traduções ou array vazio se arquivo não encontrado

**Estrutura do Arquivo JSON:**
```json
{
    "welcome_message": "Bem-vindo ao sistema",
    "login_button": "Entrar",
    "logout_button": "Sair",
    "error_required_field": "Este campo é obrigatório",
    "success_save": "Dados salvos com sucesso"
}
```

**Exemplo de Uso:**
```php
// Carregar dicionário português
$dict_pt = carregar_dicionario('pt-br');

// Carregar dicionário inglês
$dict_en = carregar_dicionario('en');

// Carregar de subdiretório específico
$dict = carregar_dicionario('pt-br', '/../langs');
```

**Funcionamento Interno:**
```php
// 1. Constrói caminho do arquivo
$caminhoBase = realpath(__DIR__ . $base) . '/';
$caminhoArquivo = $caminhoBase . $lang . '.json';
// Resultado: /path/to/bibliotecas/pt-br.json

// 2. Verifica existência
if (file_exists($caminhoArquivo)) {
    // 3. Lê e decodifica JSON
    $jsonContent = file_get_contents($caminhoArquivo);
    $dicionario = json_decode($jsonContent, true);
}
```

---

### __t()

Traduz uma chave de idioma usando o dicionário customizado com suporte a placeholders.

**Assinatura:**
```php
function __t($key, $replacements = [])
```

**Parâmetros:**
- `$key` (string) - **Obrigatório** - Chave de tradução
- `$replacements` (array) - Opcional - Array associativo com valores para substituir placeholders

**Retorno:**
- (string) - Texto traduzido ou a própria chave se não encontrada

**Formatos de Placeholder Suportados:**
- `{placeholder}` - Formato de chaves
- `:placeholder` - Formato de dois pontos

**Exemplo de Uso:**
```php
// Tradução simples
echo __t('welcome_message');
// Saída: "Bem-vindo ao sistema"

// Com placeholders
echo __t('hello_user', ['name' => 'João']);
// Se dicionário tem: "hello_user": "Olá, {name}!"
// Saída: "Olá, João!"

// Múltiplos placeholders
echo __t('user_stats', [
    'posts' => 5,
    'comments' => 12
]);
// Se dicionário tem: "user_stats": "Você tem {posts} posts e {comments} comentários"
// Saída: "Você tem 5 posts e 12 comentários"

// Chave não encontrada retorna a própria chave
echo __t('nonexistent_key');
// Saída: "nonexistent_key"
```

**Exemplo com Diferentes Formatos de Placeholder:**
```php
// Dicionário: "greeting": "Olá, {name}! Você tem :count mensagens"

echo __t('greeting', [
    'name' => 'Maria',
    'count' => 3
]);
// Saída: "Olá, Maria! Você tem 3 mensagens"
```

---

### set_lang()

Define o idioma a ser usado e recarrega o dicionário correspondente.

**Assinatura:**
```php
function set_lang($lang)
```

**Parâmetros:**
- `$lang` (string) - **Obrigatório** - Código do idioma (ex: 'en', 'pt-br', 'es')

**Retorno:**
- (void) - Sem retorno, atualiza variáveis globais

**Efeitos Colaterais:**
- Atualiza `$GLOBALS['lang']` com o novo idioma
- Recarrega `$GLOBALS['dicionario']` com o novo dicionário

**Exemplo de Uso:**
```php
// Mudar para inglês
set_lang('en');
echo __t('welcome_message');
// Saída: "Welcome to the system"

// Mudar para português
set_lang('pt-br');
echo __t('welcome_message');
// Saída: "Bem-vindo ao sistema"

// Mudar para espanhol
set_lang('es');
echo __t('welcome_message');
// Saída: "Bienvenido al sistema"
```

**Uso com Preferências do Usuário:**
```php
// Detectar idioma do navegador
$browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$supported_langs = ['pt', 'en', 'es'];

if(in_array($browser_lang, $supported_langs)) {
    set_lang($browser_lang);
} else {
    set_lang('en'); // Fallback para inglês
}
```

---

## Casos de Uso Comuns

### 1. Sistema de Login Multilíngue
```php
// Permitir usuário escolher idioma
if(isset($_POST['language'])) {
    set_lang($_POST['language']);
    $_SESSION['user_language'] = $_POST['language'];
}

// HTML do formulário
?>
<form method="post">
    <h2><?php echo __t('login_title'); ?></h2>
    
    <label><?php echo __t('username'); ?>:</label>
    <input type="text" name="username" placeholder="<?php echo __t('username_placeholder'); ?>">
    
    <label><?php echo __t('password'); ?>:</label>
    <input type="password" name="password">
    
    <button type="submit"><?php echo __t('login_button'); ?></button>
    
    <select name="language" onchange="this.form.submit()">
        <option value="pt-br">Português</option>
        <option value="en">English</option>
        <option value="es">Español</option>
    </select>
</form>
<?php
```

### 2. Mensagens de Validação Dinâmicas
```php
// Validação de formulário com mensagens traduzidas
function validar_formulario($dados) {
    $erros = [];
    
    if(empty($dados['nome'])) {
        $erros[] = __t('error_name_required');
    }
    
    if(empty($dados['email'])) {
        $erros[] = __t('error_email_required');
    } elseif(!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = __t('error_email_invalid');
    }
    
    if(strlen($dados['senha']) < 8) {
        $erros[] = __t('error_password_too_short', ['min' => 8]);
    }
    
    return $erros;
}

// Dicionário pt-br.json:
// "error_password_too_short": "A senha deve ter no mínimo {min} caracteres"
```

### 3. Notificações do Sistema
```php
// Sistema de notificações
function notificar_usuario($tipo, $dados) {
    switch($tipo) {
        case 'novo_comentario':
            $mensagem = __t('notification_new_comment', [
                'user' => $dados['autor'],
                'post' => $dados['titulo_post']
            ]);
            break;
            
        case 'post_aprovado':
            $mensagem = __t('notification_post_approved', [
                'title' => $dados['titulo']
            ]);
            break;
            
        case 'nova_mensagem':
            $mensagem = __t('notification_new_message', [
                'from' => $dados['remetente'],
                'count' => $dados['quantidade']
            ]);
            break;
    }
    
    return $mensagem;
}

// Dicionário:
// "notification_new_comment": "{user} comentou em '{post}'"
// "notification_post_approved": "Seu post '{title}' foi aprovado!"
// "notification_new_message": "Você tem {count} novas mensagens de {from}"
```

### 4. Interface Administrativa
```php
// Painel administrativo multilíngue
class PainelAdmin {
    
    public function renderMenu() {
        $menu = [
            'dashboard' => __t('menu_dashboard'),
            'users' => __t('menu_users'),
            'posts' => __t('menu_posts'),
            'settings' => __t('menu_settings'),
            'logout' => __t('menu_logout')
        ];
        
        foreach($menu as $key => $label) {
            echo '<a href="admin.php?page=' . $key . '">' . $label . '</a>';
        }
    }
    
    public function renderStats($stats) {
        echo '<div class="stats">';
        echo '<div>' . __t('total_users', ['count' => $stats['users']]) . '</div>';
        echo '<div>' . __t('total_posts', ['count' => $stats['posts']]) . '</div>';
        echo '<div>' . __t('total_comments', ['count' => $stats['comments']]) . '</div>';
        echo '</div>';
    }
}
```

### 5. Emails Multilíngues
```php
// Enviar email no idioma do usuário
function enviar_email_boas_vindas($usuario) {
    // Definir idioma do usuário
    set_lang($usuario['idioma_preferido']);
    
    $assunto = __t('email_welcome_subject');
    $corpo = __t('email_welcome_body', [
        'name' => $usuario['nome'],
        'site' => 'Conn2Flow'
    ]);
    
    enviar_email($usuario['email'], $assunto, $corpo);
    
    // Restaurar idioma padrão do sistema
    set_lang('pt-br');
}
```

## Estrutura de Dicionários

### Organização Recomendada

```json
{
    "_comment": "Geral",
    "app_name": "Conn2Flow",
    "welcome": "Bem-vindo",
    
    "_comment": "Autenticação",
    "login_title": "Entrar no Sistema",
    "logout": "Sair",
    "username": "Nome de usuário",
    "password": "Senha",
    
    "_comment": "Validação",
    "error_required": "Campo obrigatório",
    "error_invalid_email": "Email inválido",
    "error_password_min": "Senha deve ter no mínimo {min} caracteres",
    
    "_comment": "Sucesso",
    "success_save": "Dados salvos com sucesso",
    "success_delete": "Item excluído",
    
    "_comment": "Interface",
    "button_save": "Salvar",
    "button_cancel": "Cancelar",
    "button_delete": "Excluir"
}
```

### Convenções de Nomenclatura

| Prefixo | Uso | Exemplo |
|---------|-----|---------|
| `error_` | Mensagens de erro | `error_not_found` |
| `success_` | Mensagens de sucesso | `success_created` |
| `button_` | Textos de botões | `button_submit` |
| `label_` | Labels de formulário | `label_email` |
| `title_` | Títulos de página/seção | `title_dashboard` |
| `menu_` | Itens de menu | `menu_settings` |
| `notification_` | Notificações | `notification_new_message` |
| `email_` | Templates de email | `email_reset_password` |

## Comparação com Gettext

### Por que Não Usar Gettext?

| Aspecto | Sistema Custom (__t) | Gettext |
|---------|---------------------|---------|
| **Formato** | JSON (fácil edição) | .po/.mo (compilado) |
| **Setup** | Simples, sem configuração | Requer extensão PHP e compilação |
| **Performance** | Leitura de JSON | Mais rápido (compilado) |
| **Flexibilidade** | Total controle | Mais rígido |
| **Pluralização** | Manual | Automática |
| **Fallback** | Retorna a chave | Pode retornar vazio |

### Vantagens do Sistema Custom

1. **Simplicidade**: Não requer extensão PHP ou ferramentas externas
2. **Portabilidade**: Funciona em qualquer ambiente PHP
3. **Edição Fácil**: Arquivos JSON editáveis em qualquer editor
4. **Versionamento**: Fácil track de mudanças em Git
5. **Debugging**: Retorna a chave se tradução não encontrada

## Padrões e Melhores Práticas

### 1. Organização de Arquivos
```
gestor/bibliotecas/
├── lang.php
├── langs/
│   ├── pt-br.json
│   ├── en.json
│   ├── es.json
│   └── fr.json
```

### 2. Cache de Dicionários
```php
// Para melhor performance, considere cache
function carregar_dicionario_com_cache($lang) {
    $cache_key = 'dict_' . $lang;
    
    if(isset($_SESSION[$cache_key])) {
        return $_SESSION[$cache_key];
    }
    
    $dict = carregar_dicionario($lang);
    $_SESSION[$cache_key] = $dict;
    
    return $dict;
}
```

### 3. Detecção Automática de Idioma
```php
// Detectar e definir idioma automaticamente
function detectar_idioma_usuario() {
    // 1. Verificar preferência salva
    if(isset($_SESSION['user_lang'])) {
        return $_SESSION['user_lang'];
    }
    
    // 2. Verificar cookie
    if(isset($_COOKIE['preferred_lang'])) {
        return $_COOKIE['preferred_lang'];
    }
    
    // 3. Detectar do navegador
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    $supported = ['pt', 'en', 'es', 'fr'];
    
    if(in_array($browser_lang, $supported)) {
        return $browser_lang;
    }
    
    // 4. Fallback padrão
    return 'pt-br';
}

// Aplicar no início da aplicação
set_lang(detectar_idioma_usuario());
```

### 4. Validação de Traduções
```php
// Script para verificar chaves faltantes
function validar_traducoes() {
    $idiomas = ['pt-br', 'en', 'es'];
    $base = carregar_dicionario('pt-br'); // Idioma de referência
    
    foreach($idiomas as $lang) {
        if($lang === 'pt-br') continue;
        
        $dict = carregar_dicionario($lang);
        $faltando = array_diff_key($base, $dict);
        
        if(!empty($faltando)) {
            echo "Idioma $lang - Chaves faltando: " . count($faltando) . "\n";
            print_r(array_keys($faltando));
        }
    }
}
```

## Limitações e Considerações

### 1. Pluralização
O sistema não tem suporte automático a pluralização. Soluções:

```php
// Solução manual com múltiplas chaves
"item_singular": "{count} item"
"item_plural": "{count} itens"

// Uso:
$key = ($count == 1) ? 'item_singular' : 'item_plural';
echo __t($key, ['count' => $count]);
```

### 2. Contexto
Não há suporte nativo para contexto (mesma chave, traduções diferentes):

```php
// Solução: usar chaves diferentes
"button_save_new": "Criar"
"button_save_edit": "Atualizar"
```

### 3. Performance
Para sistemas grandes, considere:
- Cache de dicionários em memória/arquivo
- Lazy loading de partes do dicionário
- Compilação de dicionários para PHP arrays

## Veja Também

- [BIBLIOTECA-VARIAVEIS.md](./BIBLIOTECA-VARIAVEIS.md) - Gerenciamento de variáveis do sistema
- [BIBLIOTECA-USUARIO.md](./BIBLIOTECA-USUARIO.md) - Preferências de usuário
- [Documentação Gettext](https://www.gnu.org/software/gettext/) - Alternativa tradicional

---

**Última Atualização**: Outubro 2025  
**Documentado por**: Equipe Conn2Flow
