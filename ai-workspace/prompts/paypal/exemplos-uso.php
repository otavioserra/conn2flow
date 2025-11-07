<?php
/**
 * Exemplos de Uso - Biblioteca PayPal REST API
 *
 * Este arquivo contém exemplos práticos de como usar a biblioteca PayPal
 * integrada ao Conn2Flow CMS.
 *
 * @package Conn2Flow
 * @subpackage Exemplos
 */

// ===== EXEMPLO 1: Configuração no config.php ou em módulo personalizado

/*
// Adicionar ao config.php ou em arquivo de configuração do seu módulo:

$_CONFIG['paypal'] = Array(
    'mode' => getenv('PAYPAL_MODE') ?: 'sandbox',
    'sandbox' => Array(
        'client_id' => getenv('PAYPAL_CLIENT_ID_SANDBOX'),
        'client_secret' => getenv('PAYPAL_CLIENT_SECRET_SANDBOX'),
    ),
    'live' => Array(
        'client_id' => getenv('PAYPAL_CLIENT_ID_LIVE'),
        'client_secret' => getenv('PAYPAL_CLIENT_SECRET_LIVE'),
    ),
    'currency' => getenv('PAYPAL_CURRENCY') ?: 'BRL',
    'webhook_id' => getenv('PAYPAL_WEBHOOK_ID'),
);
*/

// ===== EXEMPLO 2: Autenticação Simples

function exemplo_autenticacao(){
    // Obter token OAuth 2.0
    $token = paypal_autenticar();
    
    if($token){
        echo "✅ Autenticação bem-sucedida!\n";
        echo "Access Token: " . substr($token['access_token'], 0, 20) . "...\n";
        echo "Expira em: " . $token['expires_in'] . " segundos\n";
        echo "Expira às: " . date('Y-m-d H:i:s', $token['expires_at']) . "\n";
        return $token;
    } else {
        echo "❌ Erro na autenticação\n";
        return false;
    }
}

// ===== EXEMPLO 3: Criar Pedido Simples

function exemplo_criar_pedido_simples(){
    // Criar pedido de R$ 150,00
    $pedido = paypal_criar_pedido(Array(
        'valor' => 150.00,
        'moeda' => 'BRL',
        'descricao' => 'Compra de Produto Digital',
        'referencia' => 'PEDIDO-' . time()
    ));
    
    if($pedido){
        echo "✅ Pedido criado com sucesso!\n";
        echo "Order ID: " . $pedido['id'] . "\n";
        echo "Status: " . $pedido['status'] . "\n";
        echo "\n🔗 URL para aprovação:\n";
        echo $pedido['approve_url'] . "\n";
        return $pedido;
    } else {
        echo "❌ Erro ao criar pedido\n";
        return false;
    }
}

// ===== EXEMPLO 4: Criar Pedido com Itens Detalhados

function exemplo_criar_pedido_com_itens(){
    $pedido = paypal_criar_pedido(Array(
        'valor' => 250.00,
        'moeda' => 'BRL',
        'descricao' => 'Compra no Site',
        'referencia' => 'PEDIDO-MULT-' . time(),
        'itens' => Array(
            Array(
                'nome' => 'Produto Premium',
                'quantidade' => 1,
                'preco' => 150.00
            ),
            Array(
                'nome' => 'Produto Básico',
                'quantidade' => 2,
                'preco' => 50.00
            )
        ),
        'url_retorno' => 'https://seusite.com/pagamento/sucesso',
        'url_cancelamento' => 'https://seusite.com/pagamento/cancelado'
    ));
    
    if($pedido){
        echo "✅ Pedido com itens criado!\n";
        echo "Order ID: " . $pedido['id'] . "\n";
        echo "Total de itens: 3\n";
        echo "URL de aprovação: " . $pedido['approve_url'] . "\n";
        return $pedido;
    } else {
        echo "❌ Erro ao criar pedido\n";
        return false;
    }
}

// ===== EXEMPLO 5: Capturar Pagamento após Aprovação

function exemplo_capturar_pagamento($order_id){
    // Após usuário aprovar o pagamento, capturar
    $captura = paypal_capturar_pedido(Array(
        'order_id' => $order_id
    ));
    
    if($captura){
        echo "✅ Pagamento capturado com sucesso!\n";
        echo "Capture ID: " . $captura['id'] . "\n";
        echo "Status: " . $captura['status'] . "\n";
        
        // Verificar se foi completado
        if($captura['status'] === 'COMPLETED'){
            echo "💰 Pagamento COMPLETADO - Liberar produto/serviço\n";
        }
        
        return $captura;
    } else {
        echo "❌ Erro ao capturar pagamento\n";
        return false;
    }
}

// ===== EXEMPLO 6: Fluxo Completo de Pagamento

function exemplo_fluxo_completo(){
    echo "=== FLUXO COMPLETO DE PAGAMENTO ===\n\n";
    
    // Passo 1: Autenticar
    echo "Passo 1: Autenticando...\n";
    $token = paypal_autenticar();
    if(!$token){
        echo "❌ Falha na autenticação\n";
        return false;
    }
    echo "✅ Autenticado\n\n";
    
    // Passo 2: Criar pedido
    echo "Passo 2: Criando pedido...\n";
    $pedido = paypal_criar_pedido(Array(
        'valor' => 99.90,
        'moeda' => 'BRL',
        'descricao' => 'Assinatura Premium - 1 Mês',
        'referencia' => 'SUB-' . time()
    ));
    
    if(!$pedido){
        echo "❌ Falha ao criar pedido\n";
        return false;
    }
    echo "✅ Pedido criado: " . $pedido['id'] . "\n";
    echo "🔗 Redirecionar usuário para: " . $pedido['approve_url'] . "\n\n";
    
    // Passo 3: Usuário aprova (simulado - na prática, PayPal redireciona de volta)
    echo "Passo 3: Aguardando aprovação do usuário...\n";
    echo "⏳ (Usuário deve clicar no link e aprovar o pagamento)\n\n";
    
    // Passo 4: Após aprovação, capturar
    echo "Passo 4: Capturando pagamento...\n";
    echo "ℹ️ Use a função exemplo_capturar_pagamento('" . $pedido['id'] . "') após aprovação\n\n";
    
    return Array(
        'order_id' => $pedido['id'],
        'approve_url' => $pedido['approve_url']
    );
}

// ===== EXEMPLO 7: Consultar Status de Pedido

function exemplo_consultar_pedido($order_id){
    $pedido = paypal_consultar_pedido(Array(
        'order_id' => $order_id
    ));
    
    if($pedido){
        echo "✅ Pedido consultado:\n";
        echo "Order ID: " . $pedido['id'] . "\n";
        echo "Status: " . $pedido['status'] . "\n";
        echo "Criado em: " . $pedido['create_time'] . "\n";
        
        if(isset($pedido['purchase_units'][0]['amount'])){
            $amount = $pedido['purchase_units'][0]['amount'];
            echo "Valor: " . $amount['currency_code'] . " " . $amount['value'] . "\n";
        }
        
        return $pedido;
    } else {
        echo "❌ Erro ao consultar pedido\n";
        return false;
    }
}

// ===== EXEMPLO 8: Processar Reembolso Total

function exemplo_reembolso_total($capture_id){
    // Reembolsar valor total
    $reembolso = paypal_reembolsar(Array(
        'capture_id' => $capture_id,
        'nota' => 'Cliente solicitou cancelamento'
    ));
    
    if($reembolso){
        echo "✅ Reembolso processado!\n";
        echo "Refund ID: " . $reembolso['id'] . "\n";
        echo "Status: " . $reembolso['status'] . "\n";
        return $reembolso;
    } else {
        echo "❌ Erro ao processar reembolso\n";
        return false;
    }
}

// ===== EXEMPLO 9: Processar Reembolso Parcial

function exemplo_reembolso_parcial($capture_id){
    // Reembolsar apenas R$ 50,00 de um pagamento maior
    $reembolso = paypal_reembolsar(Array(
        'capture_id' => $capture_id,
        'valor' => 50.00,
        'moeda' => 'BRL',
        'nota' => 'Reembolso parcial - produto com defeito'
    ));
    
    if($reembolso){
        echo "✅ Reembolso parcial processado!\n";
        echo "Refund ID: " . $reembolso['id'] . "\n";
        echo "Valor reembolsado: R$ 50,00\n";
        return $reembolso;
    } else {
        echo "❌ Erro ao processar reembolso\n";
        return false;
    }
}

// ===== EXEMPLO 10: Consultar Reembolso

function exemplo_consultar_reembolso($refund_id){
    $reembolso = paypal_consultar_reembolso(Array(
        'refund_id' => $refund_id
    ));
    
    if($reembolso){
        echo "✅ Reembolso consultado:\n";
        echo "Refund ID: " . $reembolso['id'] . "\n";
        echo "Status: " . $reembolso['status'] . "\n";
        
        if(isset($reembolso['amount'])){
            echo "Valor: " . $reembolso['amount']['currency_code'] . " " . $reembolso['amount']['value'] . "\n";
        }
        
        return $reembolso;
    } else {
        echo "❌ Erro ao consultar reembolso\n";
        return false;
    }
}

// ===== EXEMPLO 11: Validar e Processar Webhook

function exemplo_processar_webhook(){
    // Simular recebimento de webhook (na prática, vem do PayPal via POST)
    
    // Obter headers do webhook
    $headers = Array(
        'PAYPAL-TRANSMISSION-ID' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? null,
        'PAYPAL-TRANSMISSION-TIME' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? null,
        'PAYPAL-TRANSMISSION-SIG' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? null,
        'PAYPAL-CERT-URL' => $_SERVER['HTTP_PAYPAL_CERT_URL'] ?? null,
        'PAYPAL-AUTH-ALGO' => $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? null
    );
    
    // Obter body do webhook
    $body = file_get_contents('php://input');
    
    // Passo 1: Validar webhook
    $valido = paypal_validar_webhook(Array(
        'headers' => $headers,
        'body' => $body
    ));
    
    if(!$valido){
        echo "❌ Webhook inválido ou não autêntico\n";
        http_response_code(400);
        return false;
    }
    
    echo "✅ Webhook validado\n";
    
    // Passo 2: Processar evento
    $evento = paypal_processar_webhook(Array(
        'body' => $body,
        'callback' => function($event){
            // Processar baseado no tipo de evento
            switch($event['event_type']){
                case 'PAYMENT.CAPTURE.COMPLETED':
                    echo "💰 Pagamento completado!\n";
                    // Atualizar status do pedido no banco de dados
                    break;
                
                case 'PAYMENT.CAPTURE.DENIED':
                    echo "❌ Pagamento negado\n";
                    // Notificar cliente
                    break;
                
                case 'PAYMENT.CAPTURE.REFUNDED':
                    echo "↩️ Reembolso processado\n";
                    // Atualizar status do pedido
                    break;
                
                default:
                    echo "ℹ️ Evento: " . $event['event_type'] . "\n";
            }
        }
    ));
    
    if($evento){
        echo "✅ Webhook processado: " . $evento['event_type'] . "\n";
        http_response_code(200);
        return true;
    } else {
        echo "❌ Erro ao processar webhook\n";
        http_response_code(500);
        return false;
    }
}

// ===== EXEMPLO 12: Uso em Módulo do Conn2Flow

/*
// Em um módulo do Conn2Flow (ex: modulos/pagamentos/paypal-checkout.php)

// Incluir biblioteca
require_once $_GESTOR['bibliotecas-path'] . 'paypal.php';

// Criar pedido ao exibir página de checkout
if(isset($_GET['criar_pedido'])){
    $valor_carrinho = 299.90; // Obtido do carrinho do usuário
    
    $pedido = paypal_criar_pedido(Array(
        'valor' => $valor_carrinho,
        'moeda' => 'BRL',
        'descricao' => 'Compra no site',
        'referencia' => 'PEDIDO-' . $_SESSION['id_usuarios'] . '-' . time(),
        'url_retorno' => 'https://seusite.com/modulos/pagamentos/paypal-retorno.php',
        'url_cancelamento' => 'https://seusite.com/carrinho'
    ));
    
    if($pedido){
        // Salvar order_id na sessão ou banco
        $_SESSION['paypal_order_id'] = $pedido['id'];
        
        // Redirecionar para PayPal
        header('Location: ' . $pedido['approve_url']);
        exit;
    }
}

// Processar retorno do PayPal (arquivo paypal-retorno.php)
if(isset($_GET['token']) && isset($_SESSION['paypal_order_id'])){
    $order_id = $_SESSION['paypal_order_id'];
    
    // Capturar pagamento
    $captura = paypal_capturar_pedido(Array(
        'order_id' => $order_id
    ));
    
    if($captura && $captura['status'] === 'COMPLETED'){
        // Pagamento completado - liberar produto
        echo "Pagamento aprovado! Obrigado pela compra.";
        
        // Atualizar pedido no banco de dados
        // Enviar email de confirmação
        // Etc...
    } else {
        echo "Erro ao processar pagamento. Tente novamente.";
    }
}
*/

// ===== EXEMPLO 13: Teste Rápido

function exemplo_teste_rapido(){
    echo "=== TESTE RÁPIDO DA BIBLIOTECA PAYPAL ===\n\n";
    
    // Testar autenticação
    echo "1. Testando autenticação...\n";
    $auth = exemplo_autenticacao();
    
    if($auth){
        echo "\n2. Criando pedido de teste...\n";
        $pedido = exemplo_criar_pedido_simples();
        
        if($pedido){
            echo "\n✅ Biblioteca funcionando corretamente!\n";
            echo "\n📝 Próximos passos:\n";
            echo "   1. Acesse a URL de aprovação no navegador\n";
            echo "   2. Faça login com uma conta PayPal Sandbox\n";
            echo "   3. Aprove o pagamento\n";
            echo "   4. Use o Order ID para capturar: exemplo_capturar_pagamento('" . $pedido['id'] . "')\n";
        }
    }
}

// ===== Como Executar os Exemplos

/*
// Para testar, inclua este arquivo em um script PHP e chame as funções:

require_once 'gestor/config.php';
require_once $_GESTOR['bibliotecas-path'] . 'paypal.php';
require_once 'ai-workspace/prompts/paypal/exemplos-uso.php';

// Executar teste rápido
exemplo_teste_rapido();

// Ou executar exemplos específicos:
// exemplo_criar_pedido_simples();
// exemplo_criar_pedido_com_itens();
// exemplo_consultar_pedido('ORDER_ID_AQUI');
// exemplo_capturar_pagamento('ORDER_ID_AQUI');
// exemplo_reembolso_total('CAPTURE_ID_AQUI');
*/

?>
