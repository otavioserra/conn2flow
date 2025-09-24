<?php
// Script de teste do módulo admin-environment

echo "=== TESTANDO MÓDULO ADMIN-ENVIRONMENT ===\n";

// Simular ambiente do gestor
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/instalador/admin-environment/';
$_SERVER['HTTP_USER_AGENT'] = 'TestScript/1.0';

// Definir caminhos
$gestorPath = __DIR__;

// Incluir config.php
require_once $gestorPath . '/config.php';

echo "Config.php carregado\n";

// Incluir bibliotecas necessárias
require_once $gestorPath . '/bibliotecas/banco.php';
require_once $gestorPath . '/bibliotecas/gestor.php';
require_once $gestorPath . '/bibliotecas/ip.php';
require_once $gestorPath . '/bibliotecas/usuario.php';

echo "Bibliotecas carregadas\n";

// Simular autenticação
$_COOKIE['_C2FCID'] = 'eyJhbGciOiJSU0EiLCJ0eXAiOiJKV1QifQ==.eyJpc3MiOiJsb2NhbGhvc3QiLCJleHAiOjE3NTk5NDkwODIsInN1YiI6ImRhMWQxNDVlMjY4MGU3MjVhY2RjNjQ3NzI5ZWUyOWNlIn0=.bml2YUQ1SHpFWTczYU93L3ZGUzBiRUMzRlFIblk5VDErYXUxWWpxZ1p3blZlZU1lNG1KZFFnUHVoUTgrMmk4YTUyNlVuaEk1OVVLT3pPNnI4SjU3RVlZT0NvbnlEYVc3MHFadXQ0YzhHODExSy9YK1NRTjNScXkwaDVQbjFjWW9HTTlsUmNxOWVOelNkaVNFUXk1a3hFNXF4am51N0xiT0ZRS3E5ZEsvemlqZmdNYTVxalpOTzg1NmtUemphR0RCQmd2WVF3YklyU3ZBQS9BTlVDT3FHdWpDTEdoNC8vdG91SEk3MDJZSGVOYmcyVVlyRFZHUW5oV0h4U0lyQ1RqRXFOUmlZMGVKRnJWQ1ZoazE1K1loMll6VGFRUkpwNWN5bEw0ZDI0OGwwY2RuVDhLVkRIalZFem5raEM0YnJkQVl1bzBwdFNYdW52a29vTkRYSDM4UEJBPT0=';

echo "Cookie de autenticação definido\n";

// Simular ambiente AJAX do gestor
$_GESTOR['ajax'] = true;
$_GESTOR['ajax-opcao'] = 'salvar'; // Vamos testar a função de salvar
$_GESTOR['modulo-id'] = 'admin-environment';

echo "Simulando ambiente AJAX com opcao=salvar\n";

// Simular dados de requisição
$_REQUEST['usuario_recaptcha_active'] = 'true';
$_REQUEST['usuario_recaptcha_site'] = 'test-site-key';
$_REQUEST['usuario_recaptcha_server'] = 'test-server-key';

echo "Dados de teste preparados\n";

// Incluir o módulo admin-environment
require_once $gestorPath . '/modulos/admin-environment/admin-environment.php';

echo "Módulo admin-environment incluído\n";

// Primeiro testar a leitura
echo "Testando leitura das configurações...\n";
$envData = admin_environment_env_read();
echo "Configurações lidas: " . print_r($envData, true) . "\n";

echo "=== TESTE CONCLUÍDO ===\n";