# Library: comunicacao.php

> 📧 Sending emails and print management

## Overview

The `comunicacao.php` library provides functions for sending emails via SMTP using PHPMailer and managing page printing. It supports rich HTML, attachments, embedded images, and multi-tenant configuration.

**Location**: `gestor/bibliotecas/comunicacao.php`  
**Version**: 1.1.0  
**Total Functions**: 2  
**Base Library**: PHPMailer

## Dependencies

- **External Library**: PHPMailer (included)
  - Location: `gestor/bibliotecas/PHPMailer/`
- **Libraries**: gestor.php, modelo.php, configuracao.php (optional)
- **Global Variables**: `$_GESTOR`, `$_CONFIG`, `$_CRON`

## Global Variables

```php
$_GESTOR['biblioteca-comunicacao'] = Array(
    'versao' => '1.1.0',
);

// Email configuration
$_CONFIG['email'] = Array(
    'ativo' => true,
    'server' => Array(
        'host' => 'smtp.example.com',
        'user' => 'user@example.com',
        'pass' => 'password',
        'port' => 587,
        'secure' => true  // SSL
    ),
    'sender' => Array(
        'from' => 'noreply@example.com',
        'fromName' => 'System',
        'replyTo' => 'contact@example.com',
        'replyToName' => 'Support'
    )
);
```

---

## Main Functions

### comunicacao_email()

Sends HTML email via SMTP with support for attachments and embedded images.

**Signature:**
```php
function comunicacao_email($params = false)
```

**Parameters (Associative Array):**

**Server Configuration:**
- `servidor` (array) - **Optional** - Custom SMTP settings
  - `debug` (bool) - Enable debug
  - `hospedeiro` (string) - SMTP Host
  - `usuario` (string) - SMTP User
  - `senha` (string) - SMTP Password
  - `porta` (int) - SMTP Port (587 or 465)
  - `seguro` (bool) - Use SSL/TLS

**Sender Configuration:**
- `remetente` (array) - **Optional** - Sender data
  - `de` (string) - Source email
  - `deNome` (string) - Sender name
  - `responderPara` (string) - Reply-to email
  - `responderParaNome` (string) - Reply-to name

**Recipients:**
- `destinatarios` (array) - **Optional** - List of recipients
  - `email` (string) - Recipient email
  - `nome` (string) - Recipient name
  - `tipo` (string) - 'normal', 'cc' or 'bcc'

**Message:**
- `mensagem` (array) - **Optional** - Email content
  - `assunto` (string) - Email subject
  - `html` (string) - HTML body
  - `htmlLayoutID` (string) - Layout component ID
  - `htmlTitulo` (string) - HTML page title
  - `htmlVariaveis` (array) - Variables for substitution
  - `htmlAssinaturaAutomatica` (bool) - Include signature
  - `imagens` (array) - Embedded images
  - `anexos` (array) - Email attachments

**Multi-tenancy:**
- `id_hosts` (int) - **Optional** - Specific host ID
- `hostPersonalizacao` (bool) - **Optional** - Use host config

**Test:**
- `EMAIL_TESTS` (bool) - **Optional** - Use test settings
- `EMAIL_DEBUG`, `EMAIL_HOST`, `EMAIL_USER`, etc. - Test configs

**Return:**
- (bool) - true if sent successfully, false otherwise

**Basic Usage Example:**
```php
// Simple email
$sent = comunicacao_email(Array(
    'destinatarios' => Array(
        Array(
            'email' => 'client@example.com',
            'nome' => 'John Doe'
        )
    ),
    'mensagem' => Array(
        'assunto' => 'Welcome to the System',
        'html' => '<h1>Hello John!</h1><p>Welcome to our system.</p>'
    )
));

if ($sent) {
    echo "Email sent successfully!";
}
```

**Example with Layout:**
```php
// Email with custom layout
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'client@example.com', 'nome' => 'Client')
    ),
    'mensagem' => Array(
        'assunto' => 'Order Confirmation #123',
        'htmlLayoutID' => 'email-order-confirmation',
        'htmlVariaveis' => Array(
            Array('variavel' => '[[order-number]]', 'valor' => '123'),
            Array('variavel' => '[[total]]', 'valor' => '$ 150.00'),
            Array('variavel' => '[[date]]', 'valor' => date('Y-m-d'))
        ),
        'htmlAssinaturaAutomatica' => true
    )
));
```

**Example with Attachments:**
```php
// Email with PDF attachment
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'client@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Your Monthly Report',
        'html' => '<p>Please find the report attached.</p>',
        'anexos' => Array(
            Array(
                'caminho' => '/tmp/report.pdf',
                'nome' => 'Report-January-2025.pdf',
                'tmpCaminho' => '/tmp/report.pdf'  // Will be deleted after sending
            )
        )
    )
));
```

**Example with Embedded Images:**
```php
// Email with embedded logo
comunicacao_email(Array(
    'destinatarios' => Array(
        Array('email' => 'client@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Newsletter',
        'html' => '<img src="cid:logo"><p>Newsletter content</p>',
        'imagens' => Array(
            Array(
                'caminho' => '/var/www/images/logo.png',
                'cid' => 'logo',
                'nome' => 'logo.png'
            )
        )
    )
));
```

**Example with Multiple Recipients:**
```php
// Email to multiple recipients with CC and BCC
comunicacao_email(Array(
    'destinatarios' => Array(
        Array(
            'email' => 'main@example.com',
            'nome' => 'Main Recipient',
            'tipo' => 'normal'
        ),
        Array(
            'email' => 'copy@example.com',
            'nome' => 'Copy',
            'tipo' => 'cc'
        ),
        Array(
            'email' => 'admin@example.com',
            'tipo' => 'bcc'  // Blind carbon copy
        )
    ),
    'mensagem' => Array(
        'assunto' => 'Important Notification',
        'html' => '<p>Message for everyone.</p>'
    )
));
```

**Example with Custom Configuration:**
```php
// Use different SMTP server
comunicacao_email(Array(
    'servidor' => Array(
        'hospedeiro' => 'smtp.gmail.com',
        'usuario' => 'myapp@gmail.com',
        'senha' => 'app_password',
        'porta' => 587,
        'seguro' => true
    ),
    'remetente' => Array(
        'de' => 'noreply@myapp.com',
        'deNome' => 'My App',
        'responderPara' => 'support@myapp.com',
        'responderParaNome' => 'Support Team'
    ),
    'destinatarios' => Array(
        Array('email' => 'client@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Test',
        'html' => '<p>Test email</p>'
    )
));
```

**Behavior:**
- Uses 'layout-emails' automatically if it exists
- Supports system global variables ([[variable]])
- Applies inline CSS for compatibility
- Deletes temporary files after sending
- Logs errors if debug is active

**Notes:**
- Requires `$_CONFIG['email']['ativo'] = true`
- Uses PHPMailer for SMTP sending
- Supports UTF-8 by default
- Email layout is optional but recommended

---

### comunicacao_impressao()

Prepares data for page printing.

**Signature:**
```php
function comunicacao_impressao($params = false)
```

**Parameters (Associative Array):**
- `pagina` (string) - **Required** - Page to be printed
- `titulo` (string) - **Optional** - Print title

**Return:**
- (void) - Stores data in session

**Usage Example:**
```php
// Prepare order printing
comunicacao_impressao(Array(
    'pagina' => '/orders/print/123',
    'titulo' => 'Order #123'
));

// Redirect to print page
gestor_redirecionar('/print');

// On print page, retrieve:
$print = gestor_sessao_variavel('impressao');
// Array(
//     'pagina' => '/orders/print/123',
//     'titulo' => 'Order #123'
// )
```

**Behavior:**
- Stores data in `$_SESSION` via `gestor_sessao_variavel()`
- Used in conjunction with dedicated print page
- Allows passing context to print window

---

## Common Use Cases

### 1. Registration Confirmation

```php
function send_confirmation_email($user) {
    $token = generate_confirmation_token($user['id']);
    $link = host_url(Array('opcao' => 'full')) . "confirm/$token";
    
    return comunicacao_email(Array(
        'destinatarios' => Array(
            Array(
                'email' => $user['email'],
                'nome' => $user['nome']
            )
        ),
        'mensagem' => Array(
            'assunto' => 'Confirm your registration',
            'htmlLayoutID' => 'email-confirmation',
            'htmlVariaveis' => Array(
                Array('variavel' => '[[name]]', 'valor' => $user['nome']),
                Array('variavel' => '[[link]]', 'valor' => $link)
            ),
            'htmlAssinaturaAutomatica' => true
        )
    ));
}
```

### 2. Password Recovery

```php
function send_password_recovery($email) {
    $user = find_user_by_email($email);
    
    if (!$user) {
        return false;
    }
    
    $token = generate_recovery_token($user['id']);
    $link = host_url(Array('opcao' => 'full')) . "reset-password/$token";
    
    return comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $email, 'nome' => $user['nome'])
        ),
        'mensagem' => Array(
            'assunto' => 'Password Recovery',
            'html' => "
                <h2>Hello {$user['nome']},</h2>
                <p>Click the link below to reset your password:</p>
                <p><a href='$link'>Reset Password</a></p>
                <p>The link expires in 1 hour.</p>
            "
        )
    ));
}
```

### 3. Order Notification

```php
function notify_order_confirmed($order) {
    // Generate order PDF
    $pdf_path = generate_order_pdf($order['id']);
    
    // Send to client
    $sent_client = comunicacao_email(Array(
        'destinatarios' => Array(
            Array(
                'email' => $order['client_email'],
                'nome' => $order['client_name']
            )
        ),
        'mensagem' => Array(
            'assunto' => "Order #{$order['number']} Confirmed",
            'htmlLayoutID' => 'email-order-confirmed',
            'htmlVariaveis' => Array(
                Array('variavel' => '[[number]]', 'valor' => $order['number']),
                Array('variavel' => '[[total]]', 'valor' => $order['total']),
                Array('variavel' => '[[items]]', 'valor' => generate_items_html($order))
            ),
            'anexos' => Array(
                Array(
                    'caminho' => $pdf_path,
                    'nome' => "Order-{$order['number']}.pdf",
                    'tmpCaminho' => $pdf_path
                )
            ),
            'htmlAssinaturaAutomatica' => true
        )
    ));
    
    // Notify admin with BCC
    comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $order['client_email'], 'tipo' => 'normal'),
            Array('email' => 'admin@store.com', 'tipo' => 'bcc')
        ),
        'mensagem' => Array(
            'assunto' => "New Order #{$order['number']}",
            'html' => "<p>New order received!</p>"
        )
    ));
    
    return $sent_client;
}
```

### 4. Mass Newsletter

```php
function send_newsletter($template_id, $recipients) {
    $success = 0;
    $failures = 0;
    
    foreach ($recipients as $recipient) {
        $sent = comunicacao_email(Array(
            'destinatarios' => Array(
                Array(
                    'email' => $recipient['email'],
                    'nome' => $recipient['nome']
                )
            ),
            'mensagem' => Array(
                'assunto' => 'Newsletter - ' . date('F Y'),
                'htmlLayoutID' => $template_id,
                'htmlVariaveis' => Array(
                    Array('variavel' => '[[name]]', 'valor' => $recipient['nome']),
                    Array('variavel' => '[[unsubscribe-link]]', 
                          'valor' => generate_unsubscribe_link($recipient['id']))
                ),
                'htmlAssinaturaAutomatica' => true
            )
        ));
        
        if ($sent) {
            $success++;
            
            // Log sending
            banco_insert_name(Array(
                Array('recipient_id', $recipient['id']),
                Array('template_id', $template_id),
                Array('sent_at', 'NOW()', true, false)
            ), 'newsletter_sends');
        } else {
            $failures++;
        }
        
        // Delay to avoid overload
        usleep(100000); // 0.1 second
    }
    
    return Array('success' => $success, 'failures' => $failures);
}
```

### 5. Email with QR Code

```php
function send_ticket($sale) {
    // Generate QR Code
    require_once 'phpqrcode/qrlib.php';
    
    $qr_file = sys_get_temp_dir() . '/qr-' . $sale['id'] . '.png';
    QRcode::png($sale['validation_code'], $qr_file, QR_ECLEVEL_L, 10);
    
    return comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $sale['client_email'])
        ),
        'mensagem' => Array(
            'assunto' => 'Your Ticket',
            'html' => "
                <h2>Ticket - {$sale['event_name']}</h2>
                <p>Present this QR Code at the entrance:</p>
                <p><img src='cid:qrcode' width='300'></p>
                <p>Code: {$sale['validation_code']}</p>
            ",
            'imagens' => Array(
                Array(
                    'caminho' => $qr_file,
                    'cid' => 'qrcode',
                    'nome' => 'qrcode.png',
                    'imagemTmpCaminho' => $qr_file  // Delete after sending
                )
            )
        )
    ));
}
```

### 6. Invoice Printing

```php
function prepare_invoice_print($order_id) {
    comunicacao_impressao(Array(
        'pagina' => "/admin/orders/invoice/$order_id",
        'titulo' => "Invoice - Order #$order_id"
    ));
    
    // Redirect to print page
    gestor_redirecionar('/admin/print');
}

// On page /admin/print
function display_print() {
    $data = gestor_sessao_variavel('impressao');
    
    if ($data) {
        // Include page to be printed
        include($data['pagina']);
        
        // JavaScript to print automatically
        echo "<script>window.print();</script>";
        
        // Clear session
        gestor_sessao_variavel_del('impressao');
    }
}
```

---

## Multi-Tenant Configuration

### Per Host

```php
// Configure specific email for each host
function configure_host_email($id_hosts) {
    gestor_incluir_biblioteca('configuracao');
    
    // Save host settings
    configuracao_hosts_variaveis_salvar(Array(
        'id_hosts' => $id_hosts,
        'modulo' => 'comunicacao-configuracoes',
        'variaveis' => Array(
            'email-personalizado-ativo' => '1',
            'servidor-host' => 'smtp.customhost.com',
            'servidor-usuario' => 'noreply@host.com',
            'servidor-senha' => 'password',
            'servidor-porta' => '587',
            'remetente-de' => 'contact@host.com',
            'remetente-de-nome' => 'Custom Store'
        )
    ));
}

// Send email using host configuration
comunicacao_email(Array(
    'id_hosts' => 5,
    'hostPersonalizacao' => true,
    'destinatarios' => Array(
        Array('email' => 'client@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Custom Email',
        'html' => '<p>Email with host config</p>'
    )
));
```

---

## Debug and Testing

### Debug Mode

```php
// Enable debug to see sending details
$sent = comunicacao_email(Array(
    'servidor' => Array(
        'debug' => true  // Enables SMTP::DEBUG_SERVER
    ),
    'destinatarios' => Array(
        Array('email' => 'test@example.com')
    ),
    'mensagem' => Array(
        'assunto' => 'Debug Test',
        'html' => '<p>Test</p>'
    )
));

// Errors will be logged in history via log_debugar()
```

### Development Testing

```php
// Use test settings without changing $_CONFIG
$EMAIL_TESTS = true;
$EMAIL_DEBUG = true;
$EMAIL_HOST = 'smtp.mailtrap.io';
$EMAIL_USER = 'test_user';
$EMAIL_PASS = 'test_pass';
$EMAIL_PORT = 2525;
$EMAIL_FROM = 'test@localhost';
$EMAIL_FROM_NAME = 'Test Environment';

comunicacao_email(Array(
    'EMAIL_TESTS' => $EMAIL_TESTS,
    'EMAIL_DEBUG' => $EMAIL_DEBUG,
    'EMAIL_HOST' => $EMAIL_HOST,
    'EMAIL_USER' => $EMAIL_USER,
    'EMAIL_PASS' => $EMAIL_PASS,
    'EMAIL_PORT' => $EMAIL_PORT,
    'EMAIL_FROM' => $EMAIL_FROM,
    'EMAIL_FROM_NAME' => $EMAIL_FROM_NAME,
    'destinatarios' => Array(
        Array('email' => 'dev@localhost')
    ),
    'mensagem' => Array(
        'assunto' => 'Test Email',
        'html' => '<h1>Test</h1>'
    )
));
```

---

## Patterns and Best Practices

### Email Validation

```php
// ✅ Validate email before sending
function send_with_validation($email, $message) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    return comunicacao_email(Array(
        'destinatarios' => Array(Array('email' => $email)),
        'mensagem' => $message
    ));
}
```

### Error Handling

```php
// ✅ Always check return
$sent = comunicacao_email($params);

if (!$sent) {
    error_log("Failed to send email to: " . $params['destinatarios'][0]['email']);
    // Try resending or notify admin
}
```

### Email Queues

```php
// ✅ For large volumes, use queue
function add_email_to_queue($params) {
    banco_insert_name(Array(
        Array('params', serialize($params)),
        Array('status', 'pending'),
        Array('attempts', 0),
        Array('created_at', 'NOW()', true, false)
    ), 'email_queue');
}

// Process queue in cron
function process_email_queue() {
    $emails = banco_select(Array(
        'campos' => '*',
        'tabela' => 'email_queue',
        'extra' => "WHERE status='pending' LIMIT 10"
    ));
    
    foreach ($emails as $email) {
        $params = unserialize($email['params']);
        $sent = comunicacao_email($params);
        
        if ($sent) {
            banco_update("status='sent'", 'email_queue', "WHERE id='{$email['id']}'");
        } else {
            $attempts = $email['attempts'] + 1;
            $status = $attempts >= 3 ? 'failed' : 'pending';
            banco_update("attempts=$attempts, status='$status'", 
                        'email_queue', "WHERE id='{$email['id']}'");
        }
    }
}
```

---

## Limitations and Considerations

### Performance

- Synchronous sending can be slow
- For high volumes, use asynchronous queue
- Consider SMTP server limits

### Attachment Size

- PHPMailer default limit: no restriction, but SMTP may have
- Consider compressing large files
- Use storage services + links for very large files

### HTML

- Not all clients support advanced CSS
- Use tables for layout (compatibility)
- Test in various email clients

### Security

- Do not send credentials via email
- Use HTTPS for links
- Always validate incoming emails

---

## See Also

- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer) - Official documentation
- [LIBRARY-MANAGER.md](./LIBRARY-MANAGER.md) - Layouts and components
- [LIBRARY-PDF.md](./LIBRARY-PDF.md) - PDF attachment generation
- [LIBRARY-HOST.md](./LIBRARY-HOST.md) - Multi-tenancy

---

**Last Update**: October 2025  
**Documentation Version**: 1.0.0  
**Maintainer**: Conn2Flow Team
