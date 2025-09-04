<?php
/**
 * Conn2Flow - Página Principal
 * Simula ambiente cPanel com estrutura de pastas correta
 */

// Exemplo de como seria em uma hospedagem cPanel
// Gestor estará em: /home/conn2flow/gestor/
// Esta página pública está em: /home/conn2flow/public_html/

echo "<h1>🎉 Conn2Flow - Ambiente cPanel Simulado</h1>";
echo "<p><strong>Estrutura de pastas criada com sucesso!</strong></p>";

echo "<h2>📁 Estrutura do Ambiente:</h2>";
echo "<ul>";
echo "<li><strong>Gestor:</strong> /home/conn2flow/gestor/ (fora da web - seguro)</li>";
echo "<li><strong>Public HTML:</strong> /home/conn2flow/public_html/ (acessível via web)</li>";
echo "<li><strong>cPanel:</strong> /home/conn2flow/cpanel/ (fora da web - seguro)</li>";
echo "</ul>";

echo "<h2>🔗 Links Úteis:</h2>";
echo "<ul>";
echo '<li><a href="/gestor-instalador/" target="_blank">📦 Instalador do Gestor</a></li>';
echo '<li><a href="http://localhost:8081" target="_blank">🗄️ phpMyAdmin</a></li>';
echo "</ul>";

echo "<h2>⚙️ Configurações para Instalação:</h2>";
echo "<div style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
echo "<strong>Banco de dados:</strong> <code>conn2flow_system</code><br>";
echo "<strong>Host do banco:</strong> <code>mysql</code><br>";
echo "<strong>Usuário:</strong> <code>conn2flow_user</code><br>";
echo "<strong>Senha:</strong> <code>conn2flow_pass</code>";
echo "</div>";

echo "<hr>";
echo "<small>🐳 Ambiente Docker - Simulando hospedagem cPanel</small>";
?>
