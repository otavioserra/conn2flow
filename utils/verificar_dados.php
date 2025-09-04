<?php
require_once '/var/www/sites/localhost/conn2flow-gestor/config.php';

try {
    $pdo = new PDO('mysql:host='.BD_HOST.';dbname='.BD_DATABASE, BD_USUARIO, BD_SENHA);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== RELATÓRIO DE VERIFICAÇÃO ===\n";
    
    $layouts = $pdo->query('SELECT COUNT(*) FROM layouts')->fetchColumn();
    echo "Layouts: $layouts\n";
    
    $paginas = $pdo->query('SELECT COUNT(*) FROM paginas')->fetchColumn();
    echo "Páginas: $paginas\n";
    
    $componentes = $pdo->query('SELECT COUNT(*) FROM componentes')->fetchColumn();
    echo "Componentes: $componentes\n";
    
    $paginas_com_layout = $pdo->query('SELECT COUNT(*) FROM paginas WHERE id_layouts IS NOT NULL')->fetchColumn();
    echo "Páginas com layout: $paginas_com_layout\n";
    
    $paginas_sem_layout = $pdo->query('SELECT COUNT(*) FROM paginas WHERE id_layouts IS NULL')->fetchColumn();
    echo "Páginas sem layout: $paginas_sem_layout\n";
    
    echo "\n=== AMOSTRA DE PÁGINAS ===\n";
    $stmt = $pdo->query('SELECT nome, tipo, caminho, id_layouts FROM paginas LIMIT 5');
    while ($row = $stmt->fetch()) {
        echo "- {$row['nome']} | tipo: {$row['tipo']} | caminho: {$row['caminho']} | layout_id: {$row['id_layouts']}\n";
    }
    
    echo "\n=== TIPOS DE PÁGINAS ===\n";
    $stmt = $pdo->query('SELECT tipo, COUNT(*) as total FROM paginas GROUP BY tipo');
    while ($row = $stmt->fetch()) {
        echo "- {$row['tipo']}: {$row['total']}\n";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
