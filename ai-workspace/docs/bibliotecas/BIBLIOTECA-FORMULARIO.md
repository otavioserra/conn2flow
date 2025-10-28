# Biblioteca: formulario.php

> 📋 Validação e processamento de formulários

## Visão Geral

A biblioteca `formulario.php` fornece funções para validação de formulários com suporte a Google reCAPTCHA v3, validação de campos obrigatórios e integração com JavaScript.

**Localização**: `gestor/bibliotecas/formulario.php`  
**Total de Funções**: 5

## Dependências

- **Bibliotecas**: gestor.php
- **Variáveis Globais**: `$_GESTOR`, `$_CONFIG`
- **JavaScript**: FormValidator.js
- **API Externa**: Google reCAPTCHA v3

---

## Funções Principais

### formulario_incluir_js()

Inclui JavaScript de validação de formulários.

**Assinatura:**
```php
function formulario_incluir_js()
```

**Exemplo de Uso:**
```php
// Incluir script de validação
formulario_incluir_js();
// Adiciona <script src=".../FormValidator.js"></script>
```

---

### formulario_validacao()

Configura regras de validação para um formulário.

**Assinatura:**
```php
function formulario_validacao($params = false)
```

**Parâmetros (Array Associativo):**
- `formId` (string) - **Obrigatório** - ID do formulário
- `validacao` (array) - **Obrigatório** - Array de regras de validação

**Regras de Validação:**
- `texto-obrigatorio` - Campo de texto não vazio
- `nao-vazio` - Campo não vazio
- `email` - Email válido
- `cpf` - CPF válido
- `cnpj` - CNPJ válido
- `telefone` - Telefone válido
- `cep` - CEP válido
- `numero` - Número válido
- `data` - Data válida
- `url` - URL válida

**Exemplo de Uso:**
```php
// Validar formulário de cadastro
formulario_validacao(Array(
    'formId' => 'form-cadastro',
    'validacao' => Array(
        Array(
            'regra' => 'texto-obrigatorio',
            'campo' => 'nome',
            'label' => 'Nome Completo'
        ),
        Array(
            'regra' => 'email',
            'campo' => 'email',
            'label' => 'E-mail'
        ),
        Array(
            'regra' => 'telefone',
            'campo' => 'telefone',
            'label' => 'Telefone'
        ),
        Array(
            'regra' => 'cpf',
            'campo' => 'cpf',
            'label' => 'CPF'
        )
    )
));

// Validar formulário de contato
formulario_validacao(Array(
    'formId' => 'form-contato',
    'validacao' => Array(
        Array(
            'regra' => 'texto-obrigatorio',
            'campo' => 'nome',
            'label' => 'Nome'
        ),
        Array(
            'regra' => 'email',
            'campo' => 'email',
            'label' => 'Email'
        ),
        Array(
            'regra' => 'texto-obrigatorio',
            'campo' => 'mensagem',
            'label' => 'Mensagem'
        )
    )
));
```

---

### formulario_validacao_campos_obrigatorios()

Valida se campos obrigatórios foram preenchidos.

**Assinatura:**
```php
function formulario_validacao_campos_obrigatorios($params = false)
```

**Parâmetros (Array Associativo):**
- `campos` (array) - **Obrigatório** - Lista de campos obrigatórios

**Retorno:**
- (bool) - true se todos preenchidos, false caso contrário

**Exemplo de Uso:**
```php
// Validar no servidor
$valido = formulario_validacao_campos_obrigatorios(Array(
    'campos' => Array('nome', 'email', 'telefone')
));

if (!$valido) {
    echo "Preencha todos os campos obrigatórios!";
    exit;
}

// Processar formulário
processar_cadastro($_POST);
```

---

### formulario_google_recaptcha()

Valida token do Google reCAPTCHA v3.

**Assinatura:**
```php
function formulario_google_recaptcha()
```

**Retorno:**
- (bool) - true se válido, false caso contrário

**Exemplo de Uso:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar reCAPTCHA
    if (!formulario_google_recaptcha()) {
        echo json_encode(Array(
            'erro' => 'Validação reCAPTCHA falhou'
        ));
        exit;
    }
    
    // Processar formulário
    processar_envio($_POST);
}
```

**Configuração:**
```php
// Em configuracao.php
$_CONFIG['usuario-recaptcha-active'] = true;
$_CONFIG['usuario-recaptcha-site'] = 'sua-chave-site';
$_CONFIG['usuario-recaptcha-secret'] = 'sua-chave-secreta';
$_CONFIG['usuario-recaptcha-score'] = 0.5;  // Score mínimo (0-1)
```

---

### formulario_google_recaptcha_tipo()

Retorna tipo/versão do reCAPTCHA configurado.

**Assinatura:**
```php
function formulario_google_recaptcha_tipo()
```

**Retorno:**
- (string) - Tipo do reCAPTCHA ('v3', 'v2', etc.)

**Exemplo de Uso:**
```php
$tipo = formulario_google_recaptcha_tipo();

if ($tipo === 'v3') {
    // Incluir script v3
    echo '<script src="https://www.google.com/recaptcha/api.js?render=' . 
         $_CONFIG['usuario-recaptcha-site'] . '"></script>';
}
```

---

## Casos de Uso Comuns

### 1. Formulário Completo com Validação

```php
// HTML do formulário
?>
<form id="form-cadastro" method="post">
    <input type="text" name="nome" placeholder="Nome Completo">
    <input type="email" name="email" placeholder="E-mail">
    <input type="tel" name="telefone" placeholder="Telefone">
    <input type="text" name="cpf" placeholder="CPF">
    <button type="submit">Cadastrar</button>
</form>

<?php
// Configurar validação
formulario_incluir_js();

formulario_validacao(Array(
    'formId' => 'form-cadastro',
    'validacao' => Array(
        Array('regra' => 'texto-obrigatorio', 'campo' => 'nome', 'label' => 'Nome'),
        Array('regra' => 'email', 'campo' => 'email', 'label' => 'E-mail'),
        Array('regra' => 'telefone', 'campo' => 'telefone', 'label' => 'Telefone'),
        Array('regra' => 'cpf', 'campo' => 'cpf', 'label' => 'CPF')
    )
));

// Processar submissão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (formulario_validacao_campos_obrigatorios(Array(
        'campos' => Array('nome', 'email', 'telefone', 'cpf')
    ))) {
        // Salvar no banco
        banco_insert_name(Array(
            Array('nome', $_POST['nome']),
            Array('email', $_POST['email']),
            Array('telefone', $_POST['telefone']),
            Array('cpf', $_POST['cpf'])
        ), 'clientes');
        
        echo "Cadastro realizado com sucesso!";
    }
}
```

### 2. Formulário com reCAPTCHA v3

```php
// Configurar reCAPTCHA
$_CONFIG['usuario-recaptcha-active'] = true;
$_CONFIG['usuario-recaptcha-site'] = 'chave_site';
$_CONFIG['usuario-recaptcha-secret'] = 'chave_secreta';
$_CONFIG['usuario-recaptcha-score'] = 0.5;

// HTML
?>
<form id="form-contato" method="post">
    <input type="text" name="nome" required>
    <input type="email" name="email" required>
    <textarea name="mensagem" required></textarea>
    <button type="submit">Enviar</button>
</form>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo $_CONFIG['usuario-recaptcha-site']; ?>"></script>
<script>
document.getElementById('form-contato').addEventListener('submit', function(e) {
    e.preventDefault();
    
    grecaptcha.ready(function() {
        grecaptcha.execute('<?php echo $_CONFIG['usuario-recaptcha-site']; ?>', {
            action: 'submit'
        }).then(function(token) {
            // Adicionar token ao formulário
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'recaptcha_token';
            input.value = token;
            e.target.appendChild(input);
            
            // Submeter
            e.target.submit();
        });
    });
});
</script>

<?php
// Processar com validação reCAPTCHA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!formulario_google_recaptcha()) {
        die('reCAPTCHA falhou!');
    }
    
    // Processar formulário
    enviar_email_contato($_POST);
}
```

### 3. Validação AJAX

```php
// endpoint-validar.php
header('Content-Type: application/json');

$campo = $_POST['campo'] ?? '';
$valor = $_POST['valor'] ?? '';

$erros = Array();

switch ($campo) {
    case 'email':
        if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido';
        }
        
        // Verificar se já existe
        $existe = banco_select(Array(
            'campos' => Array('COUNT(*) as total'),
            'tabela' => 'usuarios',
            'extra' => "WHERE email='$valor'",
            'unico' => true
        ));
        
        if ($existe['total'] > 0) {
            $erros[] = 'E-mail já cadastrado';
        }
        break;
        
    case 'cpf':
        if (!validar_cpf($valor)) {
            $erros[] = 'CPF inválido';
        }
        break;
}

echo json_encode(Array(
    'valido' => empty($erros),
    'erros' => $erros
));
```

### 4. Formulário Multi-Etapas

```php
session_start();

$etapa = $_GET['etapa'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salvar dados da etapa atual
    $_SESSION['form_data'][$etapa] = $_POST;
    
    if ($etapa < 3) {
        // Ir para próxima etapa
        header("Location: ?etapa=" . ($etapa + 1));
        exit;
    } else {
        // Validação final
        if (formulario_google_recaptcha()) {
            // Processar todos os dados
            $dados_completos = array_merge(
                $_SESSION['form_data'][1],
                $_SESSION['form_data'][2],
                $_SESSION['form_data'][3]
            );
            
            salvar_cadastro_completo($dados_completos);
            
            // Limpar sessão
            unset($_SESSION['form_data']);
            
            header("Location: /sucesso");
            exit;
        }
    }
}

// Exibir formulário da etapa atual
switch ($etapa) {
    case 1:
        include 'form-etapa-1.php';
        formulario_validacao(Array(
            'formId' => 'form-etapa-1',
            'validacao' => Array(/* regras etapa 1 */)
        ));
        break;
    // ...
}
```

---

## Padrões e Melhores Práticas

### Validação Client + Server

```php
// ✅ BOM - Validar ambos os lados
// Cliente (UX)
formulario_validacao(Array(
    'formId' => 'form',
    'validacao' => Array(/* regras */)
));

// Servidor (Segurança)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!formulario_validacao_campos_obrigatorios(Array(
        'campos' => Array('email', 'senha')
    ))) {
        die('Campos obrigatórios não preenchidos');
    }
}
```

### Mensagens de Erro Claras

```php
// ✅ BOM - Labels descritivas
Array(
    'regra' => 'email',
    'campo' => 'email',
    'label' => 'Endereço de E-mail'  // Clara e específica
)

// ❌ EVITAR - Labels genéricas
Array(
    'regra' => 'email',
    'campo' => 'email',
    'label' => 'Email'  // Muito genérica
)
```

---

## Veja Também

- [BIBLIOTECA-WIDGETS.md](./BIBLIOTECA-WIDGETS.md) - Widgets de formulário
- [BIBLIOTECA-HTML.md](./BIBLIOTECA-HTML.md) - Geração de HTML
- [Google reCAPTCHA v3](https://developers.google.com/recaptcha/docs/v3)

---

**Última Atualização**: Outubro 2025  
**Versão da Documentação**: 1.0.0  
**Mantenedor**: Equipe Conn2Flow
