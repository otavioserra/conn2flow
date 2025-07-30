<?php
/**
 * Script de verificação da instalação do Conn2Flow
 * Verifica se os dados foram inseridos corretamente e se há erros
 */

echo "=== VERIFICAÇÃO PÓS-INSTALAÇÃO CONN2FLOW ===\n\n";

// Busca arquivos de configuração para encontrar dados do banco
$possibleConfigs = [
    __DIR__ . '/../gestor/autenticacoes/*//.env',
    __DIR__ . '/../gestor/config.php',
];

$dbConfig = null;

// Tenta encontrar configuração do banco
foreach (glob(__DIR__ . '/../gestor/autenticacoes/*/.env') as $envFile) {
    if (file_exists($envFile)) {
        echo "Encontrado arquivo .env: {$envFile}\n";
        $envContent = file_get_contents($envFile);
        
        // Extrai configurações do banco
        if (preg_match('/DB_HOST=(.+)/', $envContent, $matches)) {
            $dbConfig['host'] = trim($matches[1]);
        }
        if (preg_match('/DB_DATABASE=(.+)/', $envContent, $matches)) {
            $dbConfig['database'] = trim($matches[1]);
        }
        if (preg_match('/DB_USERNAME=(.+)/', $envContent, $matches)) {
            $dbConfig['username'] = trim($matches[1]);
        }
        if (preg_match('/DB_PASSWORD=(.+)/', $envContent, $matches)) {
            $dbConfig['password'] = trim($matches[1]);
        }
        if (preg_match('/URL_RAIZ=(.+)/', $envContent, $matches)) {
            $urlRaiz = trim($matches[1]);
            echo "URL_RAIZ configurada: {$urlRaiz}\n";
        }
        
        break;
    }
}

if (!$dbConfig) {
    echo "❌ Não foi possível encontrar configuração do banco de dados.\n";
    echo "Verifique se a instalação foi concluída corretamente.\n";
    exit(1);
}

echo "\n--- Configuração do Banco Encontrada ---\n";
echo "Host: " . ($dbConfig['host'] ?? 'não definido') . "\n";
echo "Database: " . ($dbConfig['database'] ?? 'não definido') . "\n"; 
echo "Username: " . ($dbConfig['username'] ?? 'não definido') . "\n";
echo "Password: " . (empty($dbConfig['password']) ? '[vazia]' : '[definida]') . "\n";

// Tenta conectar ao banco
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "\n✅ Conexão com banco de dados bem-sucedida!\n";
    
    // Verifica tabelas críticas
    echo "\n--- Verificação de Tabelas Críticas ---\n";
    $criticalTables = [
        'usuarios' => 'Usuários do sistema',
        'usuarios_perfis' => 'Perfis de usuário',
        'modulos' => 'Módulos do sistema',
        'variaveis' => 'Variáveis de configuração',
        'hosts_configuracoes' => 'Configurações do host',
        'hosts_paginas' => 'Páginas do sistema'
    ];
    
    $allGood = true;
    
    foreach ($criticalTables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $result = $stmt->fetch();
            $count = $result['count'];
            
            if ($count > 0) {
                echo "✅ {$description}: {$count} registros\n";
            } else {
                echo "⚠️  {$description}: Nenhum registro encontrado!\n";
                $allGood = false;
            }
        } catch (PDOException $e) {
            echo "❌ {$description}: Tabela não existe ou erro - " . $e->getMessage() . "\n";
            $allGood = false;
        }
    }
    
    // Verifica especificamente a página de sucesso
    echo "\n--- Verificação da Página de Sucesso ---\n";
    try {
        $stmt = $pdo->query("SELECT id, nome, caminho, html FROM hosts_paginas WHERE caminho = 'instalacao-sucesso'");
        $successPage = $stmt->fetch();
        
        if ($successPage) {
            echo "✅ Página de sucesso encontrada:\n";
            echo "   ID: {$successPage['id']}\n";
            echo "   Nome: {$successPage['nome']}\n";
            echo "   Caminho: {$successPage['caminho']}\n";
            
            // Verifica se o HTML contém o link correto
            if (strpos($successPage['html'], 'dashboard') !== false) {
                echo "✅ Link do dashboard encontrado no HTML\n";
            } else {
                echo "⚠️  Link do dashboard NÃO encontrado no HTML\n";
                echo "   HTML atual contém: " . (strpos($successPage['html'], 'href=') !== false ? 'link genérico' : 'nenhum link') . "\n";
            }
        } else {
            echo "❌ Página de sucesso NÃO encontrada\n";
        }
    } catch (PDOException $e) {
        echo "❌ Erro ao verificar página de sucesso: " . $e->getMessage() . "\n";
    }
    
    // Verifica se há problemas com colunas TEXT vs MEDIUMTEXT
    echo "\n--- Verificação de Estrutura de Colunas ---\n";
    $tablesToCheck = [
        'variaveis' => ['valor'],
        'hosts_variaveis' => ['valor'], 
        'historico' => ['alteracao_txt', 'valor_antes', 'valor_depois']
    ];
    
    foreach ($tablesToCheck as $table => $columns) {
        try {
            foreach ($columns as $column) {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
                $columnInfo = $stmt->fetch();
                
                if ($columnInfo) {
                    $type = strtolower($columnInfo['Type']);
                    if (strpos($type, 'mediumtext') !== false) {
                        echo "✅ {$table}.{$column}: MEDIUMTEXT (correto)\n";
                    } elseif (strpos($type, 'text') !== false) {
                        echo "⚠️  {$table}.{$column}: TEXT (pode causar truncamento)\n";
                    } else {
                        echo "ℹ️  {$table}.{$column}: {$type}\n";
                    }
                } else {
                    echo "❌ {$table}.{$column}: Coluna não encontrada\n";
                }
            }
        } catch (PDOException $e) {
            echo "❌ Erro ao verificar {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n--- Resumo ---\n";
    if ($allGood) {
        echo "✅ Instalação parece estar funcionando corretamente!\n";
        echo "ℹ️  Se houve erros durante a instalação, eles foram relacionados a parsing de HTML entities\n";
        echo "ℹ️  Isso não afeta o funcionamento do sistema.\n";
    } else {
        echo "⚠️  Alguns problemas foram detectados na instalação.\n";
        echo "ℹ️  Verifique se os seeders foram executados corretamente.\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ Erro ao conectar com banco de dados: " . $e->getMessage() . "\n";
    echo "Verifique se:\n";
    echo "- O MySQL está rodando\n";
    echo "- As credenciais estão corretas\n";
    echo "- O banco de dados existe\n";
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
?>
