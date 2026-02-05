# 🚧 PAYPAL LIBRARY V2.0.0 (UNDER CONSTRUCTION)

> **⚠️ WARNING: PRE-ALPHA VERSION**
> 
> This library is currently under **active development (pre-alpha version)**.
> Although most functionality is implemented, it has not yet been widely tested in production.
> Usage should be done with caution and strictly in SANDBOX environment until full validation.
> APIs and function signatures may change without prior notice.

## 📋 Overview

The PayPal v2.0.0 library for Conn2Flow CMS is a complete rewrite of the PayPal integration, focused on the latest REST APIs.

**Key Features:**
- **OAuth 2.0 Authentication** (Client Credentials)
- **Orders API v2** (Checkout, Single Orders)
- **Payments API v2** (Captures, Refunds)
- **Subscriptions API v1** (Plans, Subscriptions, Recurring Billing)
- **Invoicing API v2** (Invoices)
- **Purgatory/Disputes** (Dispute Management)
- **Payouts** (Mass Payments)
- **Reporting** (Transactions and Balances)
- **Webhooks** (Validation and Automatic Processing)

---

## ⚙️ Configuration

The library uses environment variables (`.env`) or the system's global `$_CONFIG`.

### Environment Variables (.env)

| Variable | Description | Example |
|----------|-------------|---------|
| `PAYPAL_MODE` | Execution environment (`sandbox` or `live`) | `sandbox` |
| `PAYPAL_CLIENT_ID` | PayPal App Client ID | `S3uC1i3nt1D...` |
| `PAYPAL_SECRET` | PayPal App Secret Key | `S3uS3cr3t...` |
| `PAYPAL_WEBHOOK_ID` | Webhook ID for validation | `123456...` |
| `PAYPAL_CURRENCY` | Default currency (optional) | `BRL` |

### Global Array `$_CONFIG`

```php
$_CONFIG['paypal'] = Array(
    'mode' => 'sandbox', // or 'live'
    'currency' => 'BRL',
    'webhook_id' => '...',
    'sandbox' => Array(
        'client_id' => '...',
        'client_secret' => '...'
    ),
    'live' => Array(
        'client_id' => '...',
        'client_secret' => '...'
    )
);
```

---

## 📚 Function Reference

### 🔑 Authentication and Core
| Function | Description |
|----------|-------------|
| `paypal_autenticar()` | Obtains or renews OAuth 2.0 access token |
| `paypal_requisicao($params)` | Wrapper for making direct REST API calls |
| `paypal_obter_url_api()` | Returns base URL (Sandbox or Live) |
| `paypal_info()` | Returns library metadata |

### 🛒 Orders API v2 (Orders)
| Function | Description |
|----------|-------------|
| `paypal_criar_pedido($params)` | Creates a payment order (Checkout) |
| `paypal_capturar_pedido($params)` | Captures the amount of an approved order |
| `paypal_consultar_pedido($params)` | Checks status and details of an order |

### 💳 Payments API v2 (Payments and Refunds)
| Function | Description |
|----------|-------------|
| `paypal_reembolsar($params)` | Performs full or partial refund of a capture |
| `paypal_consultar_reembolso($params)` | Checks status of a refund |

### 📅 Subscriptions API (Subscriptions)

**Plans and Products:**
| Function | Description |
|----------|-------------|
| `paypal_criar_produto($params)` | Creates a product (required for plans) |
| `paypal_criar_plano($params)` | Creates a recurring billing plan |
| `paypal_listar_planos($params)` | Lists created plans |
| `paypal_consultar_plano($params)` | Details of a plan |
| `paypal_ativar_plano($params)` | Activates a plan |
| `paypal_desativar_plano($params)` | Deactivates a plan |
| `paypal_atualizar_precos_plano($params)` | Changes price of an existing plan |

**Subscription Management:**
| Function | Description |
|----------|-------------|
| `paypal_criar_assinatura($params)` | Starts a new subscription |
| `paypal_consultar_assinatura($params)` | Checks subscription status |
| `paypal_suspender_assinatura($params)` | Temporarily suspends a subscription |
| `paypal_cancelar_assinatura($params)` | Permanently cancels a subscription |
| `paypal_ativar_assinatura($params)` | Reactivates a suspended subscription |
| `paypal_capturar_assinatura($params)` | Charges a one-time amount on subscription |
| `paypal_listar_transacoes_assinatura($params)` | Subscription payment history |

### 📄 Invoicing API v2 (Invoices)
| Function | Description |
|----------|-------------|
| `paypal_criar_fatura($params)` | Creates an invoice draft |
| `paypal_enviar_fatura($params)` | Sends invoice by email to customer |
| `paypal_listar_faturas($params)` | Lists issued invoices |
| `paypal_consultar_fatura($params)` | Details of an invoice |
| `paypal_cancelar_fatura($params)` | Cancels a sent invoice |
| `paypal_lembrete_fatura($params)` | Sends payment reminder |
| `paypal_registrar_pagamento_fatura($params)` | Marks invoice as paid externally |
| `paypal_gerar_qrcode_fatura($params)` | Generates QR Code for payment |
| `paypal_gerar_numero_fatura()` | Generates next sequential number |

### 💸 Payouts API v1 (Mass Payments)
| Function | Description |
|----------|-------------|
| `paypal_criar_payout($params)` | Sends payments to multiple receivers |
| `paypal_consultar_payout($params)` | Status of a payment batch |
| `paypal_consultar_item_payout($params)` | Status of an individual batch item |
| `paypal_cancelar_item_payout($params)` | Cancels an unclaimed payment |

### ⚖️ Disputes API (Disputes)
| Function | Description |
|----------|-------------|
| `paypal_listar_disputas($params)` | Lists open disputes against account |
| `paypal_consultar_disputa($params)` | Details of a specific dispute |
| `paypal_aceitar_disputa($params)` | Accepts dispute and refunds customer |
| `paypal_contestar_disputa($params)` | Sends evidence to contest |
| `paypal_mensagem_disputa($params)` | Sends message in dispute chat |
| `paypal_escalar_disputa($params)` | Escalates for PayPal team decision |

### 📈 Reporting & Transactions
| Function | Description |
|----------|-------------|
| `paypal_listar_transacoes($params)` | Fetches transactions from account history |
| `paypal_consultar_saldo($params)` | Checks current balance in various currencies |

### 🔗 Utilities
| Function | Description |
|----------|-------------|
| `paypal_gerar_link_pagamento($params)` | Shortcut to create order and return link |
| `paypal_verificar_link_pagamento($params)` | Verifica se um link gerado foi pago |
| `paypal_validar_webhook($params)` | Verifies webhook cryptographic signature |
| `paypal_processar_webhook($params)` | Processes received JSON event |
| `paypal_traduzir_status($params)` | Translates status EN -> PT-BR |
| `paypal_formatar_valor($params)` | Formats currency with symbol |

---

## 📝 Usage Examples

### Create Simple Order
```php
$pedido = paypal_criar_pedido(Array(
    'valor' => 150.00,
    'descricao' => 'Online Store Purchase',
    'url_retorno' => 'https://yoursite.com/return',
    'url_cancelamento' => 'https://yoursite.com/cancel'
));

if($pedido) {
    // Redirects to PayPal
    header("Location: " . $pedido['approve_url']);
}
```

### Create Subscription
```php
// 1. Create Plan (once) and get $plan_id
// 2. Create Subscription
$assinatura = paypal_criar_assinatura(Array(
    'plan_id' => 'P-123456789...',
    'assinante' => Array(
        'nome' => 'John',
        'sobrenome' => 'Doe',
        'email' => 'john@email.com'
    )
));

if($assinatura) {
    header("Location: " . $assinatura['approve_url']);
}
```

### Validate Webhook
```php
// In webhook endpoint
$headers = getallheaders();
$body = file_get_contents('php://input');

if(paypal_validar_webhook(Array('headers' => $headers, 'body' => $body))) {
    $evento = paypal_processar_webhook(Array('body' => $body));
    
    if($evento['event_type'] == 'PAYMENT.CAPTURE.COMPLETED') {
        // Release order
    }
}
```
