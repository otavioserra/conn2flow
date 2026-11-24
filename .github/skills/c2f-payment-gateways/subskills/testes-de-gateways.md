# Testes de gateways sem credenciais — receita completa

Subskill de `c2f-payment-gateways`. Leia ao escrever testes unitários, E2E ou simular webhooks de checkout. As proibições críticas também estão na skill-mãe.

## Estratégia

A estratégia de testes de checkout é baseada em **dublês/mocks** das funções da biblioteca do gateway.

## 8.1 Padrão de Dublê (Test Double)

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

## 8.2 Testes E2E com Cartões de Teste

- Stripe: usar cartões de teste (`4242 4242 4242 4242`) no modo de teste.
- PayPal: usar contas sandbox do PayPal Developer.
- **Proibição Absoluta**: Credenciais de produção **NUNCA** devem constar em arquivos de teste, variáveis de ambiente de CI, ou logs de execução.

## 8.3 Simulação de Webhooks

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
