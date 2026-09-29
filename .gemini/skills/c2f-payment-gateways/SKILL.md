---
name: c2f-payment-gateways
description: "LEIA ANTES de implementar, integrar ou testar gateways de pagamento (Stripe, PayPal, etc.) no Conn2Flow (\"integra o checkout\", \"configura o gateway\", \"testa o pagamento\"). Se não ler: preço no frontend é adulterável, webhook sem idempotência duplica cobranças, SDK pesado carrega sem necessidade, e testes exigem credenciais reais."
user-invocable: true
---

# Gateways de Pagamento Seguros (`c2f-payment-gateways`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Pedido para implementar, configurar, testar ou auditar integração com gateways de pagamento (Stripe, PayPal, PagSeguro, etc.); criar endpoints de checkout; processar webhooks de pagamento; adicionar métodos de pagamento ao e-commerce.
- **SKIP APENAS SE**: Edição de módulos sem relação com transações financeiras ou páginas de produto sem checkout.
- **CONSEQUÊNCIA DE IGNORAR**: Preços adulteráveis pelo cliente, cobranças duplicadas por webhooks reenviados, vazamento de credenciais em logs, SDKs pesados carregados desnecessariamente e testes impossíveis sem chaves de produção.

---

## 1. Autoridade Estrita do Servidor

O preço unitário, a moeda, descontos e taxas são definidos e validados **exclusivamente no backend**. O cliente frontend trafega apenas **identificadores e quantidades**.

### 1.1 Proibição de Preço no Frontend

```php
// ❌ ERRADO — preço vindo do frontend é adulterável
$preco = $_POST['preco'];
$gateway->criarCobranca($preco);

// ✅ CORRETO — preço lido do banco no backend
$produto = banco_select_registro('products', "WHERE id = '$produto_id'");
$preco = (float) $produto['preco'];
$gateway->criarCobranca($preco);
```

### 1.2 Validação Dupla (Frontend + Backend)

- O frontend pode exibir preços para UX, mas **toda transação financeira** deve recalcular valores no servidor a partir da fonte de dados canônica (banco de dados).
- Descontos, cupons e frete devem ser validados no backend antes de criar a intenção de pagamento no gateway.

---

## 2. Tokens de Pedido Criptográficos HMAC

O acesso público a um pedido (ex: página de confirmação, link por e-mail) é protegido por HMAC com salt único por pedido.

### 2.1 Geração do Token

```php
// Gerar salt único por pedido
$salt = bin2hex(random_bytes(16));
banco_update_campo('salt', $salt);
banco_update_executar('orders', "WHERE numero = '$numero_pedido'");

// Gerar token HMAC para URL pública
$token = hash_hmac('sha256', $numero_pedido . '|' . $salt, OPENSSL_PASSWORD);
$url_publica = "/pedido/$numero_pedido?token=$token";
```

### 2.2 Validação do Token

```php
function validar_token_pedido($numero, $token_recebido) {
    $pedido = banco_select_registro('orders', "WHERE numero = '$numero'");
    $token_esperado = hash_hmac('sha256', $numero . '|' . $pedido['salt'], OPENSSL_PASSWORD);
    return hash_equals($token_esperado, $token_recebido);
}
```

> [!IMPORTANT]
> O **salt** é o único segredo armazenado no banco. A constante `OPENSSL_PASSWORD` é a chave HMAC do ambiente e NUNCA deve ser exposta ao frontend. Use `hash_equals()` para comparação timing-safe.

---

## 3. Prova de Posse em Verificações de Estado

O estado do pagamento é lido **diretamente da API oficial do gateway** com prova de posse obrigatória.

### 3.1 Tripla Verificação

Ao verificar o estado de um pagamento (ex: após retorno do checkout, webhook, polling), o sistema DEVE confirmar:

1. **Identificador do pedido nos metadados**: O campo `metadata.order_id` (Stripe) ou `custom_id` (PayPal) corresponde ao pedido interno.
2. **Valor cobrado**: O `amount` retornado pelo gateway corresponde exatamente ao valor calculado no backend.
3. **Moeda**: O `currency` retornado corresponde à moeda do pedido.

```php
// ✅ Prova de posse completa
function verificar_pagamento_gateway($payment_intent_id, $pedido) {
    $pi = $stripe->paymentIntents->retrieve($payment_intent_id);

    // 1. Prova de posse: metadados
    if ($pi->metadata->order_id !== $pedido['id']) {
        throw new Exception('Payment intent não pertence a este pedido');
    }
    // 2. Prova de posse: valor
    if ($pi->amount !== (int)($pedido['total'] * 100)) {
        throw new Exception('Valor divergente');
    }
    // 3. Prova de posse: moeda
    if ($pi->currency !== strtolower($pedido['moeda'])) {
        throw new Exception('Moeda divergente');
    }
    return $pi->status === 'succeeded';
}
```

> [!CAUTION]
> NUNCA confie apenas no `payment_intent_id` ou `order_id` do retorno do checkout. Um atacante pode substituir o ID por um pagamento legítimo de valor inferior. A tripla verificação é **obrigatória**.

---

## 4. Captura Mandatória no Backend

Para gateways que operam em duas fases (autorização + captura), como PayPal, a **captura financeira efetiva** deve ocorrer exclusivamente no servidor.

### 4.1 Padrão PayPal: Autorizar no Cliente, Capturar no Servidor

```javascript
// Frontend — PayPal SDK (apenas AUTORIZA)
paypal.Buttons({
    createOrder: (data, actions) => {
        return fetch('/api/paypal/create-order', { method: 'POST' })
            .then(res => res.json())
            .then(data => data.order_id);
    },
    onApprove: (data, actions) => {
        // ❌ ERRADO: actions.order.capture() captura no frontend
        // ✅ CORRETO: enviar para backend capturar
        return fetch('/api/paypal/capture-order', {
            method: 'POST',
            body: JSON.stringify({ order_id: data.orderID })
        });
    }
}).render('#paypal-button-container');
```

```php
// Backend — captura com prova de posse
function capturar_ordem_paypal($order_id, $pedido) {
    $response = $paypal->captureOrder($order_id);
    // Validar prova de posse (§3) antes de atualizar o pedido
    validar_prova_de_posse($response, $pedido);
    atualizar_status_pedido($pedido['id'], 'pago');
}
```

---

## 5. Webhooks Minimalistas e Idempotentes

O webhook atua primariamente como **sinalizador**, não como processador de regras de negócio.

### 5.1 Fluxo do Webhook

1. **Receber** o evento do gateway.
2. **Validar** a assinatura (Stripe: `Webhook::constructEvent()`; PayPal: verificar certificado).
3. **Identificar** o pedido interno via metadados do evento.
4. **Disparar ressincronização** via API oficial do gateway (§3 — Prova de Posse).
5. **Registrar** o evento com `gateway_event_id` para idempotência.

### 5.2 Idempotência Absoluta

```php
function processar_webhook($event_id, $pedido_id) {
    // Verificar idempotência
    $existente = banco_select_registro('gateway_events',
        "WHERE gateway_event_id = '$event_id'");
    if ($existente) {
        return; // Evento já processado — ignorar silenciosamente
    }

    // Registrar evento ANTES de processar
    banco_insert_registro('gateway_events', [
        'gateway_event_id' => $event_id,
        'pedido_id' => $pedido_id,
        'processado_em' => date('Y-m-d H:i:s')
    ]);

    // Ressincronizar via API oficial (prova de posse)
    verificar_e_atualizar_pagamento($pedido_id);
}
```

> [!WARNING]
> O webhook **não deve conter lógica de negócio complexa**. Ele sinaliza que algo mudou; a lógica de atualização usa a API oficial do gateway para obter o estado real com prova de posse.

### 5.3 Endpoint Público e Seguro

O endpoint de webhook utiliza o padrão `<modulo>.ajax.public.php` (ver `c2f-gestor-functions` §11) para receber requisições sem autenticação de sessão, mas **obrigatoriamente** valida a assinatura criptográfica do gateway.

---

## 6. Carregamento Condicional de SDKs

SDKs de terceiros pesados (Stripe.js ~40 KB, PayPal SDK ~200 KB) **só devem ser carregados** quando o pedido selecionar aquele método de pagamento.

### 6.1 Carregamento Sob Demanda

```javascript
// ✅ CORRETO — carregar SDK apenas quando necessário
function carregarGatewaySDK(gateway) {
    if (gateway === 'stripe' && !window.Stripe) {
        return carregarScript('https://js.stripe.com/v3/');
    }
    if (gateway === 'paypal' && !window.paypal) {
        return carregarScript(`https://www.paypal.com/sdk/js?client-id=${clientId}`);
    }
    return Promise.resolve();
}

// Ao selecionar método de pagamento
seletorGateway.addEventListener('change', async (e) => {
    await carregarGatewaySDK(e.target.value);
    inicializarFormularioPagamento(e.target.value);
});
```

### 6.2 Auditoria de Tráfego de Rede

- Em testes E2E (Playwright), interceptar requisições de rede via `page.on('request')` para certificar que SDKs de gateways **não selecionados** não estão sendo carregados.
- Classes CSS dos containers de gateway não selecionado devem receber `hidden` para evitar renderização do iframe.

---

## 7. Gateway Padrão (Fallback Seguro)

Na ausência de seleção explícita pelo comprador, o sistema assume o **gateway padrão do lojista**.

### 7.1 Configuração do Fallback

```php
// Determinar gateway do pedido
$gateway = $pedido['gateway'] ?? $config_loja['gateway_padrao'] ?? 'stripe';

// Validar que o gateway é suportado
$gateways_ativos = ['stripe', 'paypal']; // Do JSON de configuração
if (!in_array($gateway, $gateways_ativos)) {
    throw new Exception("Gateway '$gateway' não está configurado");
}
```

### 7.2 Regra de Prioridade

1. Gateway explícito selecionado pelo comprador no checkout.
2. Gateway padrão configurado pelo lojista no painel administrativo.
3. Primeiro gateway ativo na lista de gateways configurados.

> [!NOTE]
> O fallback seguro garante que **nenhum pedido fique sem gateway atribuído**, mesmo que o frontend não envie a seleção.

---

## 8. Testes Unitários sem Credenciais

A estratégia de testes de checkout é baseada em **dublês/mocks** das funções da biblioteca do gateway.

### 8.1 Padrão de Dublê (Test Double)

```php
// Biblioteca do gateway com injeção de dependência
class GatewayStripe {
    private $client;

    public function __construct($client = null) {
        $this->client = $client ?? new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    }

    public function criarIntencao($valor, $moeda, $metadata) {
        return $this->client->paymentIntents->create([
            'amount' => $valor,
            'currency' => $moeda,
            'metadata' => $metadata
        ]);
    }
}

// No teste — sem credenciais reais
class GatewayStripeTest extends TestCase {
    public function testCriarIntencao() {
        $mock = $this->createMock(\Stripe\StripeClient::class);
        // Configurar mock para retornar resposta simulada
        $gateway = new GatewayStripe($mock);
        $resultado = $gateway->criarIntencao(1000, 'brl', ['order_id' => '123']);
        $this->assertNotNull($resultado);
    }
}
```

### 8.2 Testes E2E com Cartões de Teste

- Stripe: usar cartões de teste (`4242 4242 4242 4242`) no modo de teste.
- PayPal: usar contas sandbox do PayPal Developer.
- **Proibição Absoluta**: Credenciais de produção **NUNCA** devem constar em arquivos de teste, variáveis de ambiente de CI, ou logs de execução.

### 8.3 Simulação de Webhooks

```bash
# Stripe CLI — enviar webhook de teste localmente
stripe trigger payment_intent.succeeded --override payment_intent:metadata.order_id=pedido-123

# Teste manual — POST direto no endpoint
curl -X POST https://meusite.com/ecommerce/ecommerce.ajax.public.php \
  -H "Content-Type: application/json" \
  -d '{"type":"payment_intent.succeeded","data":{"object":{"id":"pi_test","metadata":{"order_id":"123"}}}}'
```

> [!CAUTION]
> Em ambientes de produção, **sempre valide a assinatura do webhook** (`Stripe-Signature` header). Testes com `curl` direto devem ser restritos a ambientes de desenvolvimento/staging.
