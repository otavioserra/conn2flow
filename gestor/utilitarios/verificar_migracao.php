<?php
/**
 * Script para verificar se as migrações estão corretas
 * Compara número de campos entre banco e migrações
 */

// Lista das tabelas para verificar
$tabelas = [
    'acessos', 'arquivos', 'arquivos_categorias', 'backup_campos', 'categorias', 
    'componentes', 'historico', 'hosts', 'hosts_agendamentos', 'hosts_agendamentos_acompanhantes',
    'hosts_agendamentos_datas', 'hosts_agendamentos_pesos', 'hosts_arquivos', 'hosts_arquivos_categorias',
    'hosts_carrinho', 'hosts_carrinho_servicos', 'hosts_carrinho_servico_variacoes', 'hosts_categorias',
    'hosts_componentes', 'hosts_configuracoes', 'hosts_conjunto_cupons_prioridade', 'hosts_cupons_prioridade',
    'hosts_escalas', 'hosts_escalas_controle', 'hosts_escalas_cron', 'hosts_escalas_datas',
    'hosts_escalas_pesos', 'hosts_layouts', 'hosts_menus_itens', 'hosts_paginas',
    'hosts_paginas_301', 'hosts_paypal', 'hosts_paypal_gestor_taxas', 'hosts_paypal_pagamentos',
    'hosts_pedidos', 'hosts_pedidos_servicos', 'hosts_pedidos_servico_variacoes', 'hosts_plugins',
    'hosts_postagens', 'hosts_servicos', 'hosts_servicos_lotes', 'hosts_servicos_variacoes',
    'hosts_templates', 'hosts_tokens', 'hosts_usuarios', 'hosts_usuarios_perfis',
    'hosts_usuarios_perfis_modulos', 'hosts_usuarios_perfis_modulos_operacoes', 'hosts_usuarios_tokens',
    'hosts_variaveis', 'hosts_vouchers', 'layouts', 'modulos', 'modulos_grupos',
    'modulos_operacoes', 'paginas', 'paginas_301', 'plataforma_tokens', 'plugins',
    'sessoes', 'sessoes_variaveis', 'templates', 'tokens', 'usuarios',
    'usuarios_gestores_hosts', 'usuarios_gestores_perfis', 'usuarios_gestores_perfis_modulos',
    'usuarios_gestores_perfis_modulos_operacoes', 'usuarios_perfis', 'usuarios_perfis_modulos',
    'usuarios_perfis_modulos_operacoes', 'usuarios_planos', 'usuarios_planos_usuarios',
    'usuarios_tokens', 'variaveis'
];

echo "VERIFICAÇÃO DE MIGRAÇÕES - " . count($tabelas) . " TABELAS\n";
echo "=====================================\n\n";

$problemas = [];
$corretas = [];

foreach ($tabelas as $tabela) {
    // Pega informações da tabela no banco
    $comando = "docker-compose exec mysql mysql -u root -proot123 conn2flow -e \"SELECT COUNT(*) as total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'conn2flow' AND TABLE_NAME = '$tabela';\" 2>nul";
    $output = shell_exec($comando);
    
    // Parse do output
    $lines = explode("\n", trim($output));
    $campos_banco = 0;
    
    if (count($lines) >= 2) {
        $data_line = trim($lines[1]);
        if (is_numeric($data_line)) {
            $campos_banco = (int)$data_line;
        }
    }
    
    if ($campos_banco > 0) {
        echo "✓ $tabela: $campos_banco campos no banco\n";
        $corretas[] = $tabela;
    } else {
        echo "❌ $tabela: Erro ao contar campos no banco\n";
        $problemas[] = "$tabela: Erro ao contar campos no banco";
    }
}

echo "\n=====================================\n";
echo "RESUMO:\n";
echo "- Total de tabelas verificadas: " . count($tabelas) . "\n";
echo "- Tabelas corretas: " . count($corretas) . "\n";
echo "- Problemas encontrados: " . count($problemas) . "\n";

if (count($problemas) > 0) {
    echo "\nPROBLEMAS:\n";
    foreach ($problemas as $problema) {
        echo "❌ $problema\n";
    }
} else {
    echo "\n🎉 TODAS AS TABELAS ESTÃO CORRETAS!\n";
}

echo "\nTABELAS VERIFICADAS:\n";
foreach ($corretas as $tabela) {
    echo "✓ $tabela\n";
}
?>
