<?php
/**
 * Teste de detecção de URL_RAIZ
 * Simula diferentes cenários de instalação
 */

// Simula o método detectUrlRaiz() do Installer
function detectUrlRaiz() {
    echo "=== TESTE DE DETECÇÃO DE URL_RAIZ ===\n";
    
    // Debug: log de todas as variáveis relevantes
    $serverVars = [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'não definido',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'não definido',
        'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'não definido',
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'não definido',
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'não definido'
    ];
    
    foreach ($serverVars as $var => $value) {
        echo "Variável {$var}: {$value}\n";
    }
    
    // Método 1: Usar REQUEST_URI se disponível
    if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
        $requestUri = $_SERVER['REQUEST_URI'];
        echo "\nAnalisando REQUEST_URI: {$requestUri}\n";
        
        // Remove query parameters se existirem
        $path = parse_url($requestUri, PHP_URL_PATH);
        echo "Caminho limpo (sem query): {$path}\n";
        
        // Remove o arquivo (index.php, installer.php, etc)
        $dirPath = dirname($path);
        echo "Diretório do caminho: {$dirPath}\n";
        
        // Se estamos em uma subpasta, retorna com barra final
        if ($dirPath !== '/' && !empty($dirPath) && $dirPath !== '.') {
            $urlRaiz = $dirPath . '/';
            echo "✅ Subpasta detectada via REQUEST_URI: {$urlRaiz}\n";
            return $urlRaiz;
        }
    }
    
    // Método 2: Usar SCRIPT_NAME como fallback
    if (isset($_SERVER['SCRIPT_NAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        echo "\nAnalisando SCRIPT_NAME: {$scriptName}\n";
        
        $dirPath = dirname($scriptName);
        echo "Diretório do script: {$dirPath}\n";
        
        if ($dirPath !== '/' && !empty($dirPath) && $dirPath !== '.') {
            $urlRaiz = $dirPath . '/';
            echo "✅ Subpasta detectada via SCRIPT_NAME: {$urlRaiz}\n";
            return $urlRaiz;
        }
    }
    
    // Método 3: Analisar estrutura física de diretórios
    $currentFile = __FILE__;
    echo "\nArquivo atual: {$currentFile}\n";
    
    if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $currentDir = dirname(realpath($currentFile));
        
        echo "Document root: {$documentRoot}\n";
        echo "Diretório atual: {$currentDir}\n";
        
        // Calcula o caminho relativo do instalador em relação ao document root
        if (strpos($currentDir, $documentRoot) === 0) {
            $relativePath = substr($currentDir, strlen($documentRoot));
            $relativePath = str_replace('\\', '/', $relativePath); // Normaliza barras
            
            echo "Caminho relativo calculado: {$relativePath}\n";
            
            if (!empty($relativePath) && $relativePath !== '/') {
                $urlRaiz = $relativePath . '/';
                echo "✅ Subpasta detectada via estrutura física: {$urlRaiz}\n";
                return $urlRaiz;
            }
        }
    }
    
    // Padrão: raiz
    echo "\n❌ Nenhuma subpasta detectada, usando raiz: /\n";
    return '/';
}

// Executa o teste
$urlRaiz = detectUrlRaiz();
echo "\n=== RESULTADO ===\n";
echo "URL_RAIZ detectada: {$urlRaiz}\n";
echo "Dashboard URL seria: {$urlRaiz}dashboard\n";

// Testa diferentes cenários simulados
echo "\n=== TESTES SIMULADOS ===\n";

// Cenário 1: Instalação na raiz
echo "\n--- Cenário 1: Instalação na raiz ---\n";
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$result1 = detectUrlRaiz();
echo "Resultado: {$result1}\n";

// Cenário 2: Instalação em subpasta
echo "\n--- Cenário 2: Instalação em subpasta 'instalador' ---\n";
$_SERVER['REQUEST_URI'] = '/instalador/index.php';
$_SERVER['SCRIPT_NAME'] = '/instalador/index.php';
$result2 = detectUrlRaiz();
echo "Resultado: {$result2}\n";

// Cenário 3: Instalação em subpasta aninhada
echo "\n--- Cenário 3: Instalação em subpasta 'sites/meusite' ---\n";
$_SERVER['REQUEST_URI'] = '/sites/meusite/index.php';
$_SERVER['SCRIPT_NAME'] = '/sites/meusite/index.php';
$result3 = detectUrlRaiz();
echo "Resultado: {$result3}\n";

echo "\n=== FIM DOS TESTES ===\n";
?>
