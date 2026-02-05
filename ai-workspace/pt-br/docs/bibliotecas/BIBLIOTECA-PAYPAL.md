# 🚧 BIBLIOTECA PAYPAL V2.0.0 (EM CONSTRUÇÃO)

> **⚠️ AVISO: VERSÃO PRÉ-ALPHA**
> 
> Esta biblioteca está atualmente em **desenvolvimento ativo (versão pré-alpha)**.
> Embora a maior parte da funcionalidade esteja implementada, ela ainda não foi amplamente testada em produção.
> O uso deve ser feito com cautela e estritamente em ambiente SANDBOX até a validação completa.
> APIs e assinaturas de funções podem sofrer alterações sem aviso prévio.

## 📋 Visão Geral

A biblioteca PayPal v2.0.0 para o Conn2Flow CMS é uma reescrita completa da integração com o PayPal, focada nas APIs REST mais recentes.

**Principais Recursos:**
- **Autenticação OAuth 2.0** (Client Credentials)
- **Orders API v2** (Checkout, Pedidos Únicos)
- **Payments API v2** (Capturas, Reembolsos)
- **Subscriptions API v1** (Planos, Assinaturas, Cobrança Recorrente)
- **Invoicing API v2** (Faturas, Notas Fiscais)
- **Purgatory/Disputes** (Gestão de Disputas)
- **Payouts** (Pagamentos em Massa)
- **Reporting** (Transações e Saldos)
- **Webhooks** (Validação e Processamento Automático)

---

## ⚙️ Configuração

A biblioteca utiliza variáveis de ambiente (`.env`) ou a configuração global `$_CONFIG` do sistema.

### Variáveis de Ambiente (.env)

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `PAYPAL_MODE` | Ambiente de execução (`sandbox` ou `live`) | `sandbox` |
| `PAYPAL_CLIENT_ID` | Client ID da App PayPal | `S3uC1i3nt1D...` |
| `PAYPAL_SECRET` | Secret Key da App PayPal | `S3uS3cr3t...` |
| `PAYPAL_WEBHOOK_ID` | ID do Webhook para validação | `123456...` |
| `PAYPAL_CURRENCY` | Moeda padrão (opcional) | `BRL` |

### Array Global `$_CONFIG`

```php
$_CONFIG['paypal'] = Array(
    'mode' => 'sandbox', // ou 'live'
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

## 📚 Referência de Funções

### 🔑 Autenticação e Core
| Função | Descrição |
|--------|-----------|
| `paypal_autenticar()` | Obtém ou renova o token de acesso OAuth 2.0 |
| `paypal_requisicao($params)` | Wrapper para fazer chamadas diretas à API REST |
| `paypal_obter_url_api()` | Retorna a URL base (Sandbox ou Live) |
| `paypal_info()` | Retorna metadados da biblioteca |

### 🛒 Orders API v2 (Pedidos)
| Função | Descrição |
|--------|-----------|
| `paypal_criar_pedido($params)` | Cria um pedido de pagamento (Checkout) |
| `paypal_capturar_pedido($params)` | Captura o valor de um pedido aprovado |
| `paypal_consultar_pedido($params)` | Consulta o status e detalhes de um pedido |

### 💳 Payments API v2 (Pagamentos e Reembolsos)
| Função | Descrição |
|--------|-----------|
| `paypal_reembolsar($params)` | Realiza estorno total ou parcial de uma captura |
| `paypal_consultar_reembolso($params)` | Consulta o status de um estorno |

### 📅 Subscriptions API (Assinaturas)

**Planos e Produtos:**
| Função | Descrição |
|--------|-----------|
| `paypal_criar_produto($params)` | Cria um produto (necessário para planos) |
| `paypal_criar_plano($params)` | Cria um plano de cobrança recorrente |
| `paypal_listar_planos($params)` | Lista planos criados |
| `paypal_consultar_plano($params)` | Detalhes de um plano |
| `paypal_ativar_plano($params)` | Ativa um plano |
| `paypal_desativar_plano($params)` | Desativa um plano |
| `paypal_atualizar_precos_plano($params)` | Altera o preço de um plano existente |

**Gestão de Assinaturas:**
| Função | Descrição |
|--------|-----------|
| `paypal_criar_assinatura($params)` | Inicia uma nova assinatura |
| `paypal_consultar_assinatura($params)` | Consulta status de uma assinatura |
| `paypal_suspender_assinatura($params)` | Suspende temporariamente uma assinatura |
| `paypal_cancelar_assinatura($params)` | Cancela definitivamente uma assinatura |
| `paypal_ativar_assinatura($params)` | Reativa uma assinatura suspensa |
| `paypal_capturar_assinatura($params)` | Cobra um valor avulso na assinatura |
| `paypal_listar_transacoes_assinatura($params)` | Histórico de pagamentos da assinatura |

### 📄 Invoicing API v2 (Faturas)
| Função | Descrição |
|--------|-----------|
| `paypal_criar_fatura($params)` | Cria um rascunho de fatura |
| `paypal_enviar_fatura($params)` | Envia a fatura por e-mail para o cliente |
| `paypal_listar_faturas($params)` | Lista faturas emitidas |
| `paypal_consultar_fatura($params)` | Detalhes de uma fatura |
| `paypal_cancelar_fatura($params)` | Cancela uma fatura enviada |
| `paypal_lembrete_fatura($params)` | Envia lembrete de pagamento |
| `paypal_registrar_pagamento_fatura($params)` | Marca fatura como paga externamente |
| `paypal_gerar_qrcode_fatura($params)` | Gera QR Code para pagamento |
| `paypal_gerar_numero_fatura()` | Gera próximo número sequencial |

### 💸 Payouts API v1 (Pagamentos em Massa)
| Função | Descrição |
|--------|-----------|
| `paypal_criar_payout($params)` | Envia pagamentos para múltiplos recebedores |
| `paypal_consultar_payout($params)` | Status de um lote de pagamentos |
| `paypal_consultar_item_payout($params)` | Status de um pagamento individual do lote |
| `paypal_cancelar_item_payout($params)` | Cancela um pagamento não reclamado |

### ⚖️ Disputes API (Disputas)
| Função | Descrição |
|--------|-----------|
| `paypal_listar_disputas($params)` | Lista disputas abertas contra a conta |
| `paypal_consultar_disputa($params)` | Detalhes de uma disputa específica |
| `paypal_aceitar_disputa($params)` | Aceita a disputa e reembolsa o cliente |
| `paypal_contestar_disputa($params)` | Envia evidências para contestar |
| `paypal_mensagem_disputa($params)` | Envia mensagem no chat da disputa |
| `paypal_escalar_disputa($params)` | Escala para o time do PayPal decidir |

### 📈 Reporting & Transações
| Função | Descrição |
|--------|-----------|
| `paypal_listar_transacoes($params)` | Busca transações no histórico da conta |
| `paypal_consultar_saldo($params)` | Consulta saldo atual nas diversas moedas |

### 🔗 Utilities
| Função | Descrição |
|--------|-----------|
| `paypal_gerar_link_pagamento($params)` | Atalho para criar pedido e retornar link |
| `paypal_verificar_link_pagamento($params)` | Verifica se um link gerado foi pago |
| `paypal_validar_webhook($params)` | Verifica assinatura criptográfica do webhook |
| `paypal_processar_webhook($params)` | Processa o JSON do evento recebido |
| `paypal_traduzir_status($params)` | Traduz status EN -> PT-BR |
| `paypal_formatar_valor($params)` | Formata moeda com símbolo |

---

## 📝 Exemplos de Uso

### Criar Pedido Simples
```php
$pedido = paypal_criar_pedido(Array(
    'valor' => 150.00,
    'descricao' => 'Compra na Loja Virtual',
    'url_retorno' => 'https://seusite.com/retorno',
    'url_cancelamento' => 'https://seusite.com/cancelado'
));

if($pedido) {
    // Redireciona para o PayPal
    header("Location: " . $pedido['approve_url']);
}
```

### Criar Assinatura
```php
// 1. Criar Plano (uma vez) e obter $plan_id
// 2. Criar Assinatura
$assinatura = paypal_criar_assinatura(Array(
    'plan_id' => 'P-123456789...',
    'assinante' => Array(
        'nome' => 'João',
        'sobrenome' => 'Silva',
        'email' => 'joao@email.com'
    )
));

if($assinatura) {
    header("Location: " . $assinatura['approve_url']);
}
```

### Validar Webhook
```php
// No endpoint de webhook
$headers = getallheaders();
$body = file_get_contents('php://input');

if(paypal_validar_webhook(Array('headers' => $headers, 'body' => $body))) {
    $evento = paypal_processar_webhook(Array('body' => $body));
    
    if($evento['event_type'] == 'PAYMENT.CAPTURE.COMPLETED') {
        // Liberar pedido
    }
}
```
