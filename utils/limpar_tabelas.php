<?php
require_once '/var/www/sites/localhost/conn2flow-gestor/config.php';

try {
    $pdo = new PDO('mysql:host='.BD_HOST.';dbname='.BD_DATABASE, BD_USUARIO, BD_SENHA);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao banco de dados\n";
    
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('DELETE FROM paginas');
    $pdo->exec('DELETE FROM layouts');
    $pdo->exec('DELETE FROM componentes');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "Tabelas limpas com sucesso!\n";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
