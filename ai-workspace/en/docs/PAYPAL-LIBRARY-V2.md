# PayPal Library v2.0.0 - Documentation

## Overview

The PayPal library for Conn2Flow CMS provides comprehensive integration with PayPal REST APIs, supporting:

- **Orders API v2** - Checkout and order management
- **Payments API v2** - Capture and refunds
- **Catalog Products API v1** - Product catalog
- **Billing Plans API v1** - Subscription plans
- **Subscriptions API v1** - Recurring subscriptions
- **Invoicing API v2** - Invoice management
- **Payouts API v1** - Batch payments
- **Disputes API v1** - Dispute management
- **Reporting API v1** - Transactions and balance
- **Webhooks** - Real-time notifications

## File Location

```
gestor/bibliotecas/paypal.php
```

## Configuration

### Environment Variables (.env)

```env
# PayPal API Credentials
PAYPAL_CLIENT_ID=your_client_id_here
PAYPAL_SECRET=your_secret_here

# Environment: sandbox or live
PAYPAL_MODE=sandbox

# Default currency (optional, defaults to BRL)
PAYPAL_CURRENCY=BRL

# Webhook ID (for webhook validation)
PAYPAL_WEBHOOK_ID=your_webhook_id_here
```

### $_CONFIG Array

```php
$_CONFIG['paypal'] = Array(
    'client_id' => 'your_client_id',
    'secret' => 'your_secret',
    'mode' => 'sandbox', // or 'live'
    'currency' => 'BRL',
    'webhook_id' => 'your_webhook_id'
);
```

## Complete Function Reference

### Core Functions

| Function | Description |
|----------|-------------|
| `paypal_obter_url_api()` | Gets the API URL based on environment |
| `paypal_obter_credenciais()` | Retrieves API credentials |
| `paypal_requisicao($params)` | Makes HTTP requests to PayPal API |
| `paypal_autenticar()` | Obtains OAuth 2.0 access token |

### Orders API v2

| Function | Description |
|----------|-------------|
| `paypal_criar_pedido($params)` | Creates a new order |
| `paypal_capturar_pedido($params)` | Captures an approved order |
| `paypal_consultar_pedido($params)` | Gets order details |

### Payments API v2

| Function | Description |
|----------|-------------|
| `paypal_reembolsar($params)` | Issues a refund |
| `paypal_consultar_reembolso($params)` | Gets refund details |

### Webhooks

| Function | Description |
|----------|-------------|
| `paypal_validar_webhook($params)` | Validates webhook signature |
| `paypal_processar_webhook($params)` | Processes webhook events |

### Catalog Products API v1

| Function | Description |
|----------|-------------|
| `paypal_criar_produto($params)` | Creates a catalog product |
| `paypal_listar_produtos($params)` | Lists products |
| `paypal_consultar_produto($params)` | Gets product details |
| `paypal_atualizar_produto($params)` | Updates a product |

### Billing Plans API v1

| Function | Description |
|----------|-------------|
| `paypal_criar_plano($params)` | Creates a billing plan |
| `paypal_listar_planos($params)` | Lists plans |
| `paypal_consultar_plano($params)` | Gets plan details |
| `paypal_ativar_plano($params)` | Activates a plan |
| `paypal_desativar_plano($params)` | Deactivates a plan |
| `paypal_atualizar_precos_plano($params)` | Updates plan pricing |

### Subscriptions API v1

| Function | Description |
|----------|-------------|
| `paypal_criar_assinatura($params)` | Creates a subscription |
| `paypal_consultar_assinatura($params)` | Gets subscription details |
| `paypal_suspender_assinatura($params)` | Suspends a subscription |
| `paypal_cancelar_assinatura($params)` | Cancels a subscription |
| `paypal_ativar_assinatura($params)` | Reactivates a subscription |
| `paypal_capturar_assinatura($params)` | Captures authorized payment |
| `paypal_listar_transacoes_assinatura($params)` | Lists subscription transactions |

### Invoicing API v2

| Function | Description |
|----------|-------------|
| `paypal_criar_fatura($params)` | Creates a draft invoice |
| `paypal_listar_faturas($params)` | Lists invoices |
| `paypal_consultar_fatura($params)` | Gets invoice details |
| `paypal_enviar_fatura($params)` | Sends invoice to recipient |
| `paypal_cancelar_fatura($params)` | Cancels a sent invoice |
| `paypal_lembrete_fatura($params)` | Sends payment reminder |
| `paypal_registrar_pagamento_fatura($params)` | Records a payment |
| `paypal_registrar_reembolso_fatura($params)` | Records a refund |
| `paypal_gerar_qrcode_fatura($params)` | Generates QR code |
| `paypal_buscar_faturas($params)` | Searches invoices |
| `paypal_deletar_fatura($params)` | Deletes a draft invoice |
| `paypal_gerar_numero_fatura()` | Generates next invoice number |

### Payouts API v1

| Function | Description |
|----------|-------------|
| `paypal_criar_payout($params)` | Creates a batch payout |
| `paypal_consultar_payout($params)` | Gets payout batch details |
| `paypal_consultar_item_payout($params)` | Gets payout item details |
| `paypal_cancelar_item_payout($params)` | Cancels unclaimed payout item |

### Payment Links

| Function | Description |
|----------|-------------|
| `paypal_gerar_link_pagamento($params)` | Generates a payment link |
| `paypal_verificar_link_pagamento($params)` | Checks payment link status |

### Disputes API v1

| Function | Description |
|----------|-------------|
| `paypal_listar_disputas($params)` | Lists disputes |
| `paypal_consultar_disputa($params)` | Gets dispute details |
| `paypal_aceitar_disputa($params)` | Accepts a claim |
| `paypal_contestar_disputa($params)` | Provides evidence |
| `paypal_mensagem_disputa($params)` | Sends message to buyer |
| `paypal_escalar_disputa($params)` | Escalates dispute to claim |

### Transactions/Reporting API v1

| Function | Description |
|----------|-------------|
| `paypal_listar_transacoes($params)` | Lists account transactions |
| `paypal_consultar_saldo($params)` | Gets account balance |

### Utility Functions

| Function | Description |
|----------|-------------|
| `paypal_formatar_valor($valor, $moeda)` | Formats monetary value |
| `paypal_traduzir_status($status, $tipo)` | Translates status to Portuguese |
| `paypal_assinatura_ativa($subscription_id)` | Checks if subscription is active |
| `paypal_pedido_pago($order_id)` | Checks if order is paid |
| `paypal_fatura_paga($invoice_id)` | Checks if invoice is paid |
| `paypal_calcular_taxa($valor, $tipo)` | Estimates PayPal fees |
| `paypal_gerar_id($prefixo)` | Generates unique transaction ID |
| `paypal_validar_email($email)` | Validates email format |
| `paypal_formatar_data($data, $incluir_hora)` | Formats date for PayPal |
| `paypal_info()` | Gets library information |

## Usage Examples

### Creating an Order (One-time Payment)

```php
// Simple order
$pedido = paypal_criar_pedido(Array(
    'valor' => 99.90,
    'descricao' => 'Premium Plan Purchase',
    'url_retorno' => 'https://example.com/success',
    'url_cancelamento' => 'https://example.com/cancel'
));

if($pedido){
    // Redirect user to PayPal
    header('Location: ' . $pedido['approve_url']);
}
```

### Creating a Subscription

```php
// 1. Create Product
$produto = paypal_criar_produto(Array(
    'nome' => 'Premium Service',
    'descricao' => 'Monthly premium subscription',
    'tipo' => 'SERVICE',
    'categoria' => 'SOFTWARE'
));

// 2. Create Plan
$plano = paypal_criar_plano(Array(
    'product_id' => $produto['id'],
    'nome' => 'Monthly Premium',
    'ciclos' => Array(
        Array(
            'frequencia' => 'MONTH',
            'intervalo' => 1,
            'preco' => 29.90,
            'total_ciclos' => 0 // Unlimited
        )
    )
));

// 3. Create Subscription
$assinatura = paypal_criar_assinatura(Array(
    'plan_id' => $plano['id'],
    'email' => 'customer@example.com',
    'nome' => 'John',
    'sobrenome' => 'Doe',
    'url_retorno' => 'https://example.com/subscription/success',
    'url_cancelamento' => 'https://example.com/subscription/cancel'
));

// 4. Redirect to PayPal
header('Location: ' . $assinatura['approve_url']);
```

### Creating and Sending an Invoice

```php
// Create invoice
$fatura = paypal_criar_fatura(Array(
    'destinatario' => Array(
        'email' => 'customer@example.com',
        'nome' => 'John Doe'
    ),
    'itens' => Array(
        Array(
            'nome' => 'Consulting Service',
            'descricao' => '4 hours of consulting',
            'quantidade' => 4,
            'preco' => 150.00
        )
    ),
    'data_vencimento' => date('Y-m-d', strtotime('+15 days'))
));

// Send to customer
if($fatura){
    paypal_enviar_fatura(Array(
        'invoice_id' => $fatura['id']
    ));
}
```

### Creating a Batch Payout

```php
$payout = paypal_criar_payout(Array(
    'assunto_email' => 'You received a payment!',
    'mensagem_email' => 'Thank you for your service.',
    'itens' => Array(
        Array(
            'destinatario' => 'affiliate1@example.com',
            'valor' => 100.00,
            'nota' => 'January commission'
        ),
        Array(
            'destinatario' => 'affiliate2@example.com',
            'valor' => 75.50,
            'nota' => 'January commission'
        )
    )
));
```

### Processing a Webhook

```php
// In your webhook endpoint
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Validate signature
$valido = paypal_validar_webhook(Array(
    'headers' => $headers,
    'body' => $payload
));

if($valido){
    $evento = paypal_processar_webhook(Array(
        'payload' => $payload
    ));
    
    switch($evento['event_type']){
        case 'PAYMENT.CAPTURE.COMPLETED':
            // Handle successful payment
            break;
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
            // Handle subscription activation
            break;
        case 'CUSTOMER.DISPUTE.CREATED':
            // Handle new dispute
            break;
    }
}
```

### Generating a Payment Link

```php
$link = paypal_gerar_link_pagamento(Array(
    'valor' => 199.90,
    'descricao' => 'Product XYZ',
    'referencia' => 'ORDER-12345'
));

// Share this link with customer
echo $link['link'];
```

## Webhook Events

### Supported Event Types

| Event | Description |
|-------|-------------|
| `PAYMENT.CAPTURE.COMPLETED` | Payment captured successfully |
| `PAYMENT.CAPTURE.DENIED` | Payment denied |
| `PAYMENT.CAPTURE.REFUNDED` | Payment refunded |
| `BILLING.SUBSCRIPTION.CREATED` | Subscription created |
| `BILLING.SUBSCRIPTION.ACTIVATED` | Subscription activated |
| `BILLING.SUBSCRIPTION.SUSPENDED` | Subscription suspended |
| `BILLING.SUBSCRIPTION.CANCELLED` | Subscription cancelled |
| `BILLING.SUBSCRIPTION.PAYMENT.FAILED` | Subscription payment failed |
| `INVOICING.INVOICE.PAID` | Invoice paid |
| `INVOICING.INVOICE.CANCELLED` | Invoice cancelled |
| `CUSTOMER.DISPUTE.CREATED` | New dispute created |
| `CUSTOMER.DISPUTE.RESOLVED` | Dispute resolved |

## Error Handling

All functions return `false` on error and log details using `log_registro()` if available.

```php
$result = paypal_criar_pedido($params);

if($result === false){
    // Check logs for details
    // Handle error appropriately
}
```

## Statistics

- **Total Lines**: 3,934
- **Total Functions**: 54
- **APIs Covered**: 10
- **Version**: 2.0.0
- **Author**: Conn2Flow

## Changelog

### v2.0.0 (2025)
- Added Catalog Products API
- Added Billing Plans API
- Added Subscriptions API
- Added Invoicing API v2
- Added Payouts API v1
- Added Disputes API v1
- Added Transactions/Reporting API
- Added Payment Links functions
- Added 14 utility functions
- Comprehensive PHPDoc documentation

### v1.0.0 (Initial)
- Orders API v2
- Payments API v2
- Basic webhook support
