<?php
/**
 * Script de Teste de Integração - Biblioteca PayPal
 *
 * Este script verifica se a biblioteca PayPal foi corretamente integrada
 * ao Conn2Flow CMS e se todas as funções estão disponíveis.
 *
 * @package Conn2Flow
 * @subpackage Testes
 */

echo "=== TESTE DE INTEGRAÇÃO - BIBLIOTECA PAYPAL ===\n\n";

// ===== 1. Verificar se biblioteca pode ser carregada

echo "1. Verificando carregamento da biblioteca...\n";

// Simular ambiente mínimo do Conn2Flow
$_GESTOR = Array(
    'bibliotecas-path' => __DIR__ . '/../../../gestor/bibliotecas/'
);

// Tentar carregar biblioteca
$biblioteca_path = $_GESTOR['bibliotecas-path'] . 'paypal.php';

if(!file_exists($biblioteca_path)){
    echo "❌ ERRO: Arquivo da biblioteca não encontrado em: $biblioteca_path\n";
    exit(1);
}

require_once $biblioteca_path;
echo "✅ Biblioteca carregada com sucesso\n\n";

// ===== 2. Verificar versão da biblioteca

echo "2. Verificando versão...\n";
if(isset($_GESTOR['biblioteca-paypal']['versao'])){
    echo "✅ Versão da biblioteca: " . $_GESTOR['biblioteca-paypal']['versao'] . "\n\n";
} else {
    echo "❌ ERRO: Versão não registrada\n";
    exit(1);
}

// ===== 3. Verificar existência de funções

echo "3. Verificando funções implementadas...\n";

$funcoes_obrigatorias = Array(
    'paypal_obter_url_api',
    'paypal_obter_credenciais',
    'paypal_requisicao',
    'paypal_autenticar',
    'paypal_criar_pedido',
    'paypal_capturar_pedido',
    'paypal_consultar_pedido',
    'paypal_reembolsar',
    'paypal_consultar_reembolso',
    'paypal_validar_webhook',
    'paypal_processar_webhook'
);

$funcoes_faltando = Array();

foreach($funcoes_obrigatorias as $funcao){
    if(function_exists($funcao)){
        echo "   ✅ $funcao\n";
    } else {
        echo "   ❌ $funcao - NÃO ENCONTRADA\n";
        $funcoes_faltando[] = $funcao;
    }
}

if(count($funcoes_faltando) > 0){
    echo "\n❌ ERRO: " . count($funcoes_faltando) . " função(ões) não encontrada(s)\n";
    exit(1);
}

echo "\n✅ Todas as " . count($funcoes_obrigatorias) . " funções estão disponíveis\n\n";

// ===== 4. Teste básico de função auxiliar

echo "4. Testando função auxiliar (paypal_obter_url_api)...\n";

// Testar modo sandbox
$_CONFIG = Array(
    'paypal' => Array(
        'mode' => 'sandbox'
    )
);

$url_sandbox = paypal_obter_url_api();
if($url_sandbox === 'https://api-m.sandbox.paypal.com'){
    echo "   ✅ Modo Sandbox: $url_sandbox\n";
} else {
    echo "   ❌ ERRO: URL Sandbox incorreta: $url_sandbox\n";
    exit(1);
}

// Testar modo live
$_CONFIG['paypal']['mode'] = 'live';
$url_live = paypal_obter_url_api();
if($url_live === 'https://api-m.paypal.com'){
    echo "   ✅ Modo Live: $url_live\n";
} else {
    echo "   ❌ ERRO: URL Live incorreta: $url_live\n";
    exit(1);
}

echo "\n✅ Função auxiliar funcionando corretamente\n\n";

// ===== 5. Verificar arquivos de documentação

echo "5. Verificando documentação...\n";

$arquivos_docs = Array(
    'biblioteca-paypal.md' => 'Documentação principal',
    'README.md' => 'Guia rápido',
    'exemplos-uso.php' => 'Exemplos de uso',
    'paypal.env.example' => 'Configuração exemplo'
);

$docs_path = __DIR__ . '/';
$docs_faltando = Array();

foreach($arquivos_docs as $arquivo => $descricao){
    $arquivo_path = $docs_path . $arquivo;
    if(file_exists($arquivo_path)){
        $tamanho = filesize($arquivo_path);
        echo "   ✅ $arquivo ($descricao) - " . number_format($tamanho/1024, 2) . " KB\n";
    } else {
        echo "   ❌ $arquivo - NÃO ENCONTRADO\n";
        $docs_faltando[] = $arquivo;
    }
}

if(count($docs_faltando) > 0){
    echo "\n⚠️  AVISO: " . count($docs_faltando) . " arquivo(s) de documentação não encontrado(s)\n\n";
} else {
    echo "\n✅ Toda documentação está presente\n\n";
}

// ===== RESULTADO FINAL

echo "=== RESULTADO FINAL ===\n\n";

if(count($funcoes_faltando) === 0){
    echo "✅ TESTE DE INTEGRAÇÃO: SUCESSO\n\n";
    echo "A biblioteca PayPal REST API está corretamente integrada ao Conn2Flow!\n\n";
    echo "Próximos passos:\n";
    echo "1. Configure as credenciais no arquivo .env\n";
    echo "2. Teste com credenciais reais do PayPal Sandbox\n";
    echo "3. Consulte ai-workspace/prompts/paypal/biblioteca-paypal.md para documentação completa\n";
    echo "4. Veja exemplos em ai-workspace/prompts/paypal/exemplos-uso.php\n\n";
    exit(0);
} else {
    echo "❌ TESTE DE INTEGRAÇÃO: FALHOU\n\n";
    echo "Corrija os erros acima antes de usar a biblioteca.\n\n";
    exit(1);
}
