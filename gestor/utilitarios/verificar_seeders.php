<?php
/**
 * Script para verificar se os seeders estão corretos
 * Verifica se os dados estão carregados nas tabelas
 */

echo "VERIFICAÇÃO DE SEEDERS\n";
echo "=====================================\n\n";

$problemas = [];
$corretos = [];

// Lista de verificações de seeders
$verificacoes = [
    'categorias' => 'SELECT COUNT(*) as total FROM categorias',
    'usuarios' => 'SELECT COUNT(*) as total FROM usuarios',
    'modulos' => 'SELECT COUNT(*) as total FROM modulos',
    'componentes' => 'SELECT COUNT(*) as total FROM componentes',
    'templates' => 'SELECT COUNT(*) as total FROM templates',
    'layouts' => 'SELECT COUNT(*) as total FROM layouts',
    'plugins' => 'SELECT COUNT(*) as total FROM plugins',
    'paginas' => 'SELECT COUNT(*) as total FROM paginas',
    'variaveis' => 'SELECT COUNT(*) as total FROM variaveis',
    'modulos_grupos' => 'SELECT COUNT(*) as total FROM modulos_grupos',
    'modulos_operacoes' => 'SELECT COUNT(*) as total FROM modulos_operacoes',
    'usuarios_perfis' => 'SELECT COUNT(*) as total FROM usuarios_perfis',
    'usuarios_perfis_modulos' => 'SELECT COUNT(*) as total FROM usuarios_perfis_modulos',
    'hosts_configuracoes' => 'SELECT COUNT(*) as total FROM hosts_configuracoes'
];

foreach ($verificacoes as $tabela => $query) {
    $comando = "docker-compose exec mysql mysql -u root -proot123 conn2flow -e \"$query;\" 2>nul";
    $output = shell_exec($comando);
    
    // Parse do output
    $lines = explode("\n", trim($output));
    $total_registros = 0;
    
    if (count($lines) >= 2) {
        $data_line = trim($lines[1]);
        if (is_numeric($data_line)) {
            $total_registros = (int)$data_line;
        }
    }
    
    if ($total_registros > 0) {
        echo "✓ $tabela: $total_registros registros\n";
        $corretos[] = $tabela;
    } else {
        echo "❌ $tabela: Nenhum registro encontrado\n";
        $problemas[] = "$tabela: Tabela vazia";
    }
}

echo "\n=====================================\n";
echo "RESUMO:\n";
echo "- Total de seeders verificados: " . count($verificacoes) . "\n";
echo "- Seeders corretos: " . count($corretos) . "\n";
echo "- Problemas encontrados: " . count($problemas) . "\n";

if (count($problemas) > 0) {
    echo "\nPROBLEMAS:\n";
    foreach ($problemas as $problema) {
        echo "❌ $problema\n";
    }
} else {
    echo "\n🎉 TODOS OS SEEDERS ESTÃO CORRETOS!\n";
}

echo "\nSEEDERS VERIFICADOS:\n";
foreach ($corretos as $seeder) {
    echo "✓ $seeder\n";
}

echo "\n=====================================\n";
echo "VERIFICAÇÃO DETALHADA DE DADOS IMPORTANTES:\n";

// Verificações específicas
$verificacoes_especiais = [
    'Usuario Administrador' => "SELECT nome, usuario, email FROM usuarios WHERE id_usuarios = 1",
    'Categorias Principais' => "SELECT id_categorias, nome, id, status FROM categorias WHERE id_categorias IN (1,2,3,4,5)",
    'Módulos Principais' => "SELECT id_modulos, nome, id, status FROM modulos WHERE id_modulos IN (1,2,3,4,5)"
];

foreach ($verificacoes_especiais as $nome => $query) {
    echo "\n$nome:\n";
    $comando = "docker-compose exec mysql mysql -u root -proot123 conn2flow -e \"$query;\" 2>nul";
    $output = shell_exec($comando);
    
    $lines = explode("\n", trim($output));
    if (count($lines) > 1) {
        for ($i = 0; $i < count($lines); $i++) {
            if (!empty(trim($lines[$i]))) {
                echo "  " . trim($lines[$i]) . "\n";
            }
        }
    }
}
?>
