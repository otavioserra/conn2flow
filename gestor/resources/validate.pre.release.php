<?php

echo "🔍 VALIDAÇÃO PRÉ-RELEASE - CONN2FLOW SISTEMA MULTILÍNGUE\n";
echo "=========================================================\n\n";

$errors = [];
$warnings = [];
$base_dir = dirname(__DIR__); // Um nível acima para sair da pasta resources

// 1. Verificar sintaxe dos arquivos PHP críticos
echo "📋 Verificando sintaxe PHP...\n";

$critical_files = [
    'db/migrations/20250807210000_create_multilingual_tables.php',
    'resources/generate.multilingual.seeders.php',
    'db/seeds/LayoutsSeeder.php',
    'db/seeds/PagesSeeder.php',
    'db/seeds/ComponentsSeeder.php',
    'resources/resources.map.php',
    'resources/resources.map.pt-br.php',
];

foreach ($critical_files as $file) {
    $filepath = $base_dir . '/' . $file;
    if (file_exists($filepath)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$filepath\" 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "   ✅ $file - OK\n";
        } else {
            $errors[] = "Erro de sintaxe em $file: " . implode("\n", $output);
            echo "   ❌ $file - ERRO\n";
        }
    } else {
        $warnings[] = "Arquivo não encontrado: $file";
        echo "   ⚠️  $file - NÃO ENCONTRADO\n";
    }
}

// 2. Verificar estrutura de recursos
echo "\n📁 Verificando estrutura de recursos...\n";

$language = 'pt-br';
$resources_count = [
    'layouts' => 0,
    'pages' => 0,
    'components' => 0
];

// Recursos globais
$global_resources = $base_dir . "/resources/$language";
if (is_dir($global_resources)) {
    foreach (['layouts', 'pages', 'components'] as $type) {
        $type_dir = $global_resources . "/$type";
        if (is_dir($type_dir)) {
            $items = glob($type_dir . '/*', GLOB_ONLYDIR);
            $resources_count[$type] += count($items);
        }
    }
    echo "   ✅ Recursos globais encontrados\n";
} else {
    $warnings[] = "Pasta de recursos globais não encontrada: $global_resources";
}

// Recursos de módulos
$modulos_dir = $base_dir . '/modulos';
$modulos_com_resources = 0;

if (is_dir($modulos_dir)) {
    $modulos = glob($modulos_dir . '/*', GLOB_ONLYDIR);
    foreach ($modulos as $modulo) {
        $modulo_resources = $modulo . "/resources/$language";
        if (is_dir($modulo_resources)) {
            $modulos_com_resources++;
            foreach (['layouts', 'pages', 'components'] as $type) {
                $type_dir = $modulo_resources . "/$type";
                if (is_dir($type_dir)) {
                    $items = glob($type_dir . '/*', GLOB_ONLYDIR);
                    $resources_count[$type] += count($items);
                }
            }
        }
    }
    echo "   ✅ $modulos_com_resources módulos com recursos\n";
}

echo "   📊 Layouts: {$resources_count['layouts']}\n";
echo "   📊 Páginas: {$resources_count['pages']}\n";
echo "   📊 Componentes: {$resources_count['components']}\n";

// 3. Verificar seeders gerados
echo "\n📄 Verificando seeders...\n";

$seeder_files = [
    'db/seeds/LayoutsSeeder.php',
    'db/seeds/PagesSeeder.php', 
    'db/seeds/ComponentsSeeder.php'
];

foreach ($seeder_files as $seeder) {
    $filepath = $base_dir . '/' . $seeder;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        $lines = substr_count($content, "\n");
        $data_entries = substr_count($content, "'layout_id' =>");
        $data_entries += substr_count($content, "'page_id' =>");
        $data_entries += substr_count($content, "'component_id' =>");
        
        echo "   ✅ $seeder - $lines linhas, $data_entries registros\n";
    } else {
        $errors[] = "Seeder não encontrado: $seeder";
        echo "   ❌ $seeder - NÃO ENCONTRADO\n";
    }
}

// 4. Verificar GitHub Actions workflow
echo "\n⚙️ Verificando GitHub Actions...\n";

$workflow_file = dirname($base_dir) . '/.github/workflows/release-gestor.yml';
if (file_exists($workflow_file)) {
    $workflow_content = file_get_contents($workflow_file);
    
    // Verificar se tem as etapas necessárias
    $required_steps = [
        'Generate multilingual seeders',
        'Remove resource files',
        'Create gestor.zip'
    ];
    
    foreach ($required_steps as $step) {
        if (strpos($workflow_content, $step) !== false) {
            echo "   ✅ Etapa '$step' encontrada\n";
        } else {
            $warnings[] = "Etapa '$step' não encontrada no workflow";
            echo "   ⚠️  Etapa '$step' não encontrada\n";
        }
    }
} else {
    $warnings[] = "Arquivo de workflow não encontrado";
    echo "   ❌ Workflow não encontrado\n";
}

// 5. Verificar migração
echo "\n🗄️ Verificando migração...\n";

$migration_file = $base_dir . '/db/migrations/20250807210000_create_multilingual_tables.php';
if (file_exists($migration_file)) {
    $migration_content = file_get_contents($migration_file);
    
    // Verificar se tem os campos necessários
    $required_fields = ['language', 'user_modified', 'file_version', 'checksum'];
    
    foreach ($required_fields as $field) {
        if (strpos($migration_content, "'$field'") !== false) {
            echo "   ✅ Campo '$field' encontrado\n";
        } else {
            $errors[] = "Campo '$field' não encontrado na migração";
            echo "   ❌ Campo '$field' não encontrado\n";
        }
    }
} else {
    $errors[] = "Arquivo de migração não encontrado";
    echo "   ❌ Migração não encontrada\n";
}

// 6. Relatório final
echo "\n📊 RELATÓRIO FINAL:\n";
echo "==================\n";

if (empty($errors)) {
    echo "✅ VALIDAÇÃO PASSOU! Sistema pronto para release.\n\n";
    
    echo "📈 ESTATÍSTICAS:\n";
    echo "   📋 Layouts: {$resources_count['layouts']}\n";
    echo "   📄 Páginas: {$resources_count['pages']}\n";
    echo "   🧩 Componentes: {$resources_count['components']}\n";
    echo "   📁 Total: " . array_sum($resources_count) . " recursos\n";
    echo "   🧩 Módulos: $modulos_com_resources módulos com resources\n\n";
    
    echo "🚀 PRÓXIMOS PASSOS:\n";
    echo "1. Commit e push das alterações\n";
    echo "2. Criar tag: git tag gestor-v1.8.4+\n";
    echo "3. Push da tag: git push origin gestor-v1.8.4+\n";
    echo "4. GitHub Actions criará release automaticamente\n";
    echo "5. Testar instalação no ambiente Docker\n\n";
    
    $exit_code = 0;
} else {
    echo "❌ VALIDAÇÃO FALHOU! Corrija os erros antes do release:\n\n";
    foreach ($errors as $error) {
        echo "   ❌ $error\n";
    }
    $exit_code = 1;
}

if (!empty($warnings)) {
    echo "\n⚠️  AVISOS:\n";
    foreach ($warnings as $warning) {
        echo "   ⚠️  $warning\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

exit($exit_code);

?>
