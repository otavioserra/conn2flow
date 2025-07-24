<?php
// Teste do instalador com criação automática de diretórios

// Simular ambiente web
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['step'] = '1';
$_POST['install_path'] = '/home/conn2flow/meu-novo-gestor';

// Incluir o instalador
require_once '/var/www/html/gestor-instalador/src/Installer.php';

echo "=== TESTE DO INSTALADOR ===\n";
echo "Testando criação automática de diretório: /home/conn2flow/meu-novo-gestor\n\n";

// Verificar se diretório existe antes
echo "Diretório existe antes: " . (is_dir('/home/conn2flow/meu-novo-gestor') ? 'SIM' : 'NÃO') . "\n";

// Criar instância e testar
$installer = new Installer();
echo "Instância do instalador criada com sucesso.\n";

try {
    $result = $installer->validateInstallPath('/home/conn2flow/meu-novo-gestor');
    echo "Resultado da validação: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
} catch (Exception $e) {
    echo "Erro durante validação: " . $e->getMessage() . "\n";
}

// Verificar se diretório foi criado
echo "Diretório existe depois: " . (is_dir('/home/conn2flow/meu-novo-gestor') ? 'SIM' : 'NÃO') . "\n";

// Verificar permissões
if (is_dir('/home/conn2flow/meu-novo-gestor')) {
    $perms = substr(sprintf('%o', fileperms('/home/conn2flow/meu-novo-gestor')), -4);
    echo "Permissões do diretório: " . $perms . "\n";
}

// Listar conteúdo do diretório home
echo "\n=== CONTEÚDO DO DIRETÓRIO HOME ===\n";
$files = scandir('/home/conn2flow/');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $path = '/home/conn2flow/' . $file;
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $type = is_dir($path) ? 'DIR' : 'FILE';
        echo "$type $perms $file\n";
    }
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
