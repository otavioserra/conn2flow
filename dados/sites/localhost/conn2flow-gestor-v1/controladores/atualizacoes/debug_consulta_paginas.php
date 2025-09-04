<?php
require __DIR__ . '/../../config.php';
$dsn = "mysql:host={$_BANCO['host']};dbname={$_BANCO['nome']};charset=utf8mb4";
$pdo = new PDO($dsn, $_BANCO['usuario'], $_BANCO['senha'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$sql = "SELECT id, tipo, system_updated, LEFT(html_updated,50) AS html_up, LEFT(css_updated,50) AS css_up, user_modified FROM paginas WHERE id IN ('contato','teste-coluna-centralizada','teste-variavel-global')";
foreach ($pdo->query($sql, PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
