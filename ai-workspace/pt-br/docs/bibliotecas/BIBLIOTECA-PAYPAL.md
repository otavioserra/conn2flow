# Biblioteca PayPal v2.0.0 - Documentação

## Visão Geral

A biblioteca PayPal para o Conn2Flow CMS fornece integração completa com as APIs REST do PayPal, suportando:

- **Orders API v2** - Checkout e gerenciamento de pedidos
- **Payments API v2** - Captura e reembolsos
- **Catalog Products API v1** - Catálogo de produtos
- **Billing Plans API v1** - Planos de assinatura
- **Subscriptions API v1** - Assinaturas recorrentes
- **Invoicing API v2** - Gerenciamento de faturas
- **Payouts API v1** - Pagamentos em lote
- **Disputes API v1** - Gerenciamento de disputas
- **Reporting API v1** - Transações e saldo
- **Webhooks** - Notificações em tempo real

## Localização do Arquivo

```
gestor/bibliotecas/paypal.php
```

## Configuração

### Variáveis de Ambiente (.env)

```env
# Credenciais da API PayPal
PAYPAL_CLIENT_ID=seu_client_id_aqui
PAYPAL_SECRET=seu_secret_aqui

# Ambiente: sandbox ou live
PAYPAL_MODE=sandbox

# Moeda padrão (opcional, padrão é BRL)
PAYPAL_CURRENCY=BRL

# ID do Webhook (para validação de webhooks)
PAYPAL_WEBHOOK_ID=seu_webhook_id_aqui
```

### Array $_CONFIG

```php
$_CONFIG['paypal'] = Array(
    'client_id' => 'seu_client_id',
    'secret' => 'seu_secret',
    'mode' => 'sandbox', // ou 'live'
    'currency' => 'BRL',
    'webhook_id' => 'seu_webhook_id'
);
```

## Referência Completa de Funções

### Funções Core

| Função | Descrição |
|--------|-----------|
| `paypal_obter_url_api()` | Obtém a URL da API baseada no ambiente |
| `paypal_obter_credenciais()` | Recupera as credenciais da API |
| `paypal_requisicao($params)` | Faz requisições HTTP para a API PayPal |
| `paypal_autenticar()` | Obtém token de acesso OAuth 2.0 |

### Orders API v2

| Função | Descrição |
|--------|-----------|
| `paypal_criar_pedido($params)` | Cria um novo pedido |
| `paypal_capturar_pedido($params)` | Captura um pedido aprovado |
| `paypal_consultar_pedido($params)` | Obtém detalhes do pedido |

### Payments API v2

| Função | Descrição |
|--------|-----------|
| `paypal_reembolsar($params)` | Emite um reembolso |
| `paypal_consultar_reembolso($params)` | Obtém detalhes do reembolso |

### Webhooks

| Função | Descrição |
|--------|-----------|
| `paypal_validar_webhook($params)` | Valida assinatura do webhook |
| `paypal_processar_webhook($params)` | Processa eventos de webhook |

### Catalog Products API v1

| Função | Descrição |
|--------|-----------|
| `paypal_criar_produto($params)` | Cria um produto no catálogo |
| `paypal_listar_produtos($params)` | Lista produtos |
| `paypal_consultar_produto($params)` | Obtém detalhes do produto |
| `paypal_atualizar_produto($params)` | Atualiza um produto |

### Billing Plans API v1

| Função | Descrição |
|--------|-----------|
| `paypal_criar_plano($params)` | Cria um plano de assinatura |
| `paypal_listar_planos($params)` | Lista planos |
| `paypal_consultar_plano($params)` | Obtém detalhes do plano |
| `paypal_ativar_plano($params)` | Ativa um plano |
| `paypal_desativar_plano($params)` | Desativa um plano |
| `paypal_atualizar_precos_plano($params)` | Atualiza preços do plano |

### Subscriptions API v1

| Função | Descrição |
|--------|-----------|
| `paypal_criar_assinatura($params)` | Cria uma assinatura |
| `paypal_consultar_assinatura($params)` | Obtém detalhes da assinatura |
| `paypal_suspender_assinatura($params)` | Suspende uma assinatura |
| `paypal_cancelar_assinatura($params)` | Cancela uma assinatura |
| `paypal_ativar_assinatura($params)` | Reativa uma assinatura |
| `paypal_capturar_assinatura($params)` | Captura pagamento autorizado |
| `paypal_listar_transacoes_assinatura($params)` | Lista transações da assinatura |

### Invoicing API v2

| Função | Descrição |
|--------|-----------|
| `paypal_criar_fatura($params)` | Cria uma fatura rascunho |
| `paypal_listar_faturas($params)` | Lista faturas |
| `paypal_consultar_fatura($params)` | Obtém detalhes da fatura |
| `paypal_enviar_fatura($params)` | Envia fatura ao destinatário |
| `paypal_cancelar_fatura($params)` | Cancela uma fatura enviada |
| `paypal_lembrete_fatura($params)` | Envia lembrete de pagamento |
| `paypal_registrar_pagamento_fatura($params)` | Registra um pagamento |
| `paypal_registrar_reembolso_fatura($params)` | Registra um reembolso |
| `paypal_gerar_qrcode_fatura($params)` | Gera QR code |
| `paypal_buscar_faturas($params)` | Busca faturas |
| `paypal_deletar_fatura($params)` | Deleta fatura rascunho |
| `paypal_gerar_numero_fatura()` | Gera próximo número de fatura |

### Payouts API v1

| Função | Descrição |
|--------|-----------|
| `paypal_criar_payout($params)` | Cria pagamento em lote |
| `paypal_consultar_payout($params)` | Obtém detalhes do lote |
| `paypal_consultar_item_payout($params)` | Obtém detalhes de um item |
| `paypal_cancelar_item_payout($params)` | Cancela item não reclamado |

### Links de Pagamento

| Função | Descrição |
|--------|-----------|
| `paypal_gerar_link_pagamento($params)` | Gera um link de pagamento |
| `paypal_verificar_link_pagamento($params)` | Verifica status do link |

### Disputes API v1

| Função | Descrição |
|--------|-----------|
| `paypal_listar_disputas($params)` | Lista disputas |
| `paypal_consultar_disputa($params)` | Obtém detalhes da disputa |
| `paypal_aceitar_disputa($params)` | Aceita uma reclamação |
| `paypal_contestar_disputa($params)` | Fornece evidências |
| `paypal_mensagem_disputa($params)` | Envia mensagem ao comprador |
| `paypal_escalar_disputa($params)` | Escala disputa para reclamação |

### Transactions/Reporting API v1

| Função | Descrição |
|--------|-----------|
| `paypal_listar_transacoes($params)` | Lista transações da conta |
| `paypal_consultar_saldo($params)` | Obtém saldo da conta |

### Funções Utilitárias

| Função | Descrição |
|--------|-----------|
| `paypal_formatar_valor($valor, $moeda)` | Formata valor monetário |
| `paypal_traduzir_status($status, $tipo)` | Traduz status para português |
| `paypal_assinatura_ativa($subscription_id)` | Verifica se assinatura está ativa |
| `paypal_pedido_pago($order_id)` | Verifica se pedido foi pago |
| `paypal_fatura_paga($invoice_id)` | Verifica se fatura foi paga |
| `paypal_calcular_taxa($valor, $tipo)` | Estima taxas do PayPal |
| `paypal_gerar_id($prefixo)` | Gera ID único de transação |
| `paypal_validar_email($email)` | Valida formato de email |
| `paypal_formatar_data($data, $incluir_hora)` | Formata data para PayPal |
| `paypal_info()` | Obtém informações da biblioteca |

## Exemplos de Uso

### Criando um Pedido (Pagamento Único)

```php
// Pedido simples
$pedido = paypal_criar_pedido(Array(
    'valor' => 99.90,
    'descricao' => 'Compra do Plano Premium',
    'url_retorno' => 'https://example.com/sucesso',
    'url_cancelamento' => 'https://example.com/cancelado'
));

if($pedido){
    // Redirecionar usuário para o PayPal
    header('Location: ' . $pedido['approve_url']);
}
```

### Criando uma Assinatura

```php
// 1. Criar Produto
$produto = paypal_criar_produto(Array(
    'nome' => 'Serviço Premium',
    'descricao' => 'Assinatura mensal premium',
    'tipo' => 'SERVICE',
    'categoria' => 'SOFTWARE'
));

// 2. Criar Plano
$plano = paypal_criar_plano(Array(
    'product_id' => $produto['id'],
    'nome' => 'Premium Mensal',
    'ciclos' => Array(
        Array(
            'frequencia' => 'MONTH',
            'intervalo' => 1,
            'preco' => 29.90,
            'total_ciclos' => 0 // Ilimitado
        )
    )
));

// 3. Criar Assinatura
$assinatura = paypal_criar_assinatura(Array(
    'plan_id' => $plano['id'],
    'email' => 'cliente@example.com',
    'nome' => 'João',
    'sobrenome' => 'Silva',
    'url_retorno' => 'https://example.com/assinatura/sucesso',
    'url_cancelamento' => 'https://example.com/assinatura/cancelado'
));

// 4. Redirecionar para PayPal
header('Location: ' . $assinatura['approve_url']);
```

### Criando e Enviando uma Fatura

```php
// Criar fatura
$fatura = paypal_criar_fatura(Array(
    'destinatario' => Array(
        'email' => 'cliente@example.com',
        'nome' => 'João Silva'
    ),
    'itens' => Array(
        Array(
            'nome' => 'Serviço de Consultoria',
            'descricao' => '4 horas de consultoria',
            'quantidade' => 4,
            'preco' => 150.00
        )
    ),
    'data_vencimento' => date('Y-m-d', strtotime('+15 days'))
));

// Enviar para o cliente
if($fatura){
    paypal_enviar_fatura(Array(
        'invoice_id' => $fatura['id']
    ));
}
```

### Criando Pagamento em Lote

```php
$payout = paypal_criar_payout(Array(
    'assunto_email' => 'Você recebeu um pagamento!',
    'mensagem_email' => 'Obrigado pelo seu serviço.',
    'itens' => Array(
        Array(
            'destinatario' => 'afiliado1@example.com',
            'valor' => 100.00,
            'nota' => 'Comissão de janeiro'
        ),
        Array(
            'destinatario' => 'afiliado2@example.com',
            'valor' => 75.50,
            'nota' => 'Comissão de janeiro'
        )
    )
));
```

### Processando um Webhook

```php
// No seu endpoint de webhook
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Validar assinatura
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
            // Tratar pagamento bem-sucedido
            break;
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
            // Tratar ativação de assinatura
            break;
        case 'CUSTOMER.DISPUTE.CREATED':
            // Tratar nova disputa
            break;
    }
}
```

### Gerando um Link de Pagamento

```php
$link = paypal_gerar_link_pagamento(Array(
    'valor' => 199.90,
    'descricao' => 'Produto XYZ',
    'referencia' => 'PEDIDO-12345'
));

// Compartilhar este link com o cliente
echo $link['link'];
```

## Eventos de Webhook

### Tipos de Eventos Suportados

| Evento | Descrição |
|--------|-----------|
| `PAYMENT.CAPTURE.COMPLETED` | Pagamento capturado com sucesso |
| `PAYMENT.CAPTURE.DENIED` | Pagamento negado |
| `PAYMENT.CAPTURE.REFUNDED` | Pagamento reembolsado |
| `BILLING.SUBSCRIPTION.CREATED` | Assinatura criada |
| `BILLING.SUBSCRIPTION.ACTIVATED` | Assinatura ativada |
| `BILLING.SUBSCRIPTION.SUSPENDED` | Assinatura suspensa |
| `BILLING.SUBSCRIPTION.CANCELLED` | Assinatura cancelada |
| `BILLING.SUBSCRIPTION.PAYMENT.FAILED` | Pagamento de assinatura falhou |
| `INVOICING.INVOICE.PAID` | Fatura paga |
| `INVOICING.INVOICE.CANCELLED` | Fatura cancelada |
| `CUSTOMER.DISPUTE.CREATED` | Nova disputa criada |
| `CUSTOMER.DISPUTE.RESOLVED` | Disputa resolvida |

## Plataforma de Gateways

A biblioteca PayPal se integra com a **Plataforma de Gateways** do Conn2Flow para receber webhooks e retornos de pagamento.

### Endpoints Disponíveis

| Endpoint | Descrição |
|----------|-----------|
| `/_gateways/paypal/webhook` | Recebe webhooks do PayPal |
| `/_gateways/paypal/return` | Processa retorno de pagamento aprovado |
| `/_gateways/paypal/cancel` | Processa cancelamento de pagamento |

### Configuração no Painel PayPal

1. Acesse o [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/)
2. Vá em **My Apps & Credentials**
3. Selecione sua aplicação
4. Em **Webhooks**, clique em **Add Webhook**
5. Configure a URL: `https://seu-dominio.com/_gateways/paypal/webhook`
6. Selecione os eventos desejados
7. Copie o **Webhook ID** para sua configuração

### Integração com Módulos Privados

A plataforma de gateways dispara automaticamente hooks para módulos que implementam a função `{modulo_id}_plataforma_gateways()`.

```php
/**
 * Função de hook para eventos de gateway no módulo
 * 
 * @param array $params Parâmetros do evento
 *   - gateway: string - Identificador do gateway (paypal, stripe, etc)
 *   - action: string - Ação (webhook, return, cancel)
 *   - data: array - Dados do evento
 * @return array|null Resultado do processamento
 */
function meu_modulo_plataforma_gateways($params = Array()) {
    $gateway = $params['gateway'];
    $action = $params['action'];
    $data = $params['data'];
    
    // Processar apenas eventos do PayPal
    if ($gateway !== 'paypal') {
        return null;
    }
    
    switch ($action) {
        case 'webhook':
            // Processar webhook
            $event_type = $data['event_type'];
            
            if ($event_type === 'PAYMENT.CAPTURE.COMPLETED') {
                // Atualizar pedido como pago
            }
            
            return Array('processed' => true);
            
        case 'return':
            // Processar retorno de pagamento
            $token = $data['token'];
            
            // Capturar pagamento e redirecionar
            return Array(
                'processed' => true,
                'redirect_url' => '/checkout/sucesso/?pedido=' . $token,
            );
            
        case 'cancel':
            // Processar cancelamento
            return Array(
                'processed' => true,
                'redirect_url' => '/checkout/cancelado/',
            );
    }
    
    return null;
}
```

### Acesso Direto a Módulos

Também é possível rotear requisições diretamente para um módulo específico:

```
/_gateways/{modulo_id}/{endpoint}
```

Exemplo: `/_gateways/meu-modulo/notificacao`

## Tratamento de Erros

Todas as funções retornam `false` em caso de erro e registram detalhes usando `log_registro()` se disponível.

```php
$resultado = paypal_criar_pedido($params);

if($resultado === false){
    // Verificar logs para detalhes
    // Tratar erro apropriadamente
}
```

## Segurança

### Validação de Webhooks

A biblioteca valida automaticamente a assinatura dos webhooks do PayPal usando:

1. **Verificação de IP** - IPs autorizados do PayPal
2. **Validação de Assinatura** - Criptografia SHA256
3. **Rate Limiting** - Limite de 60 requisições/minuto por IP
4. **Logs Detalhados** - Registro de todas as operações

### Boas Práticas

1. **Sempre use HTTPS** em produção
2. **Valide todos os webhooks** antes de processar
3. **Armazene logs** de todas as transações
4. **Use ambiente sandbox** para testes
5. **Proteja suas credenciais** em variáveis de ambiente

## Estatísticas

- **Total de Linhas**: 3.934
- **Total de Funções**: 54
- **APIs Cobertas**: 10
- **Versão**: 2.0.0
- **Autor**: Conn2Flow

## Changelog

### v2.0.0 (2025)
- Adicionada Catalog Products API
- Adicionada Billing Plans API
- Adicionada Subscriptions API
- Adicionada Invoicing API v2
- Adicionada Payouts API v1
- Adicionada Disputes API v1
- Adicionada Transactions/Reporting API
- Adicionadas funções de Links de Pagamento
- Adicionadas 14 funções utilitárias
- Documentação PHPDoc completa
- Integração com Plataforma de Gateways

### v1.0.0 (Inicial)
- Orders API v2
- Payments API v2
- Suporte básico a webhooks

---

**Veja também:**
- [Biblioteca de Comunicação](BIBLIOTECA-COMUNICACAO.md) - Para requisições HTTP
- [Biblioteca de Log](BIBLIOTECA-LOG.md) - Para registro de logs
- [Módulos Detalhado](../CONN2FLOW-MODULOS-DETALHADO.md) - Para criar módulos de integração
