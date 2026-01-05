<?php
// Test script for admin-environment module

echo "=== TESTING ADMIN-ENVIRONMENT MODULE ===\n";

// Simulate manager environment
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/instalador/admin-environment/';
$_SERVER['HTTP_USER_AGENT'] = 'TestScript/1.0';

// Define paths
$gestorPath = __DIR__;

// Include config.php
require_once $gestorPath . '/config.php';

echo "Config.php loaded\n";

// Include necessary libraries
require_once $gestorPath . '/bibliotecas/banco.php';
require_once $gestorPath . '/bibliotecas/gestor.php';
require_once $gestorPath . '/bibliotecas/ip.php';
require_once $gestorPath . '/bibliotecas/usuario.php';

echo "Libraries loaded\n";

// Simulate authentication
$_COOKIE['_C2FCID'] = 'eyJhbGciOiJSU0EiLCJ0eXAiOiJKV1QifQ==.eyJpc3MiOiJsb2NhbGhvc3QiLCJleHAiOjE3NTk5NDkwODIsInN1YiI6ImRhMWQxNDVlMjY4MGU3MjVhY2RjNjQ3NzI5ZWUyOWNlIn0=.bml2YUQ1SHpFWTczYU93L3ZGUzBiRUMzRlFIblk5VDErYXUxWWpxZ1p3blZlZU1lNG1KZFFnUHVoUTgrMmk4YTUyNlVuaEk1OVVLT3pPNnI4SjU3RVlZT0NvbnlEYVc3MHFadXQ0YzhHODExSy9YK1NRTjNScXkwaDVQbjFjWW9HTTlsUmNxOWVOelNkaVNFUXk1a3hFNXF4am51N0xiT0ZRS3E5ZEsvemlqZmdNYTVxalpOTzg1NmtUemphR0RCQmd2WVF3YklyU3ZBQS9BTlVDT3FHdWpDTEdoNC8vdG91SEk3MDJZSGVOYmcyVVlyRFZHUW5oV0h4U0lyQ1RqRXFOUmlZMGVKRnJWQ1ZoazE1K1loMll6VGFRUkpwNWN5bEw0ZDI0OGwwY2RuVDhLVkRIalZFem5raEM0YnJkQVl1bzBwdFNYdW52a29vTkRYSDM4UEJBPT0=';

echo "Authentication cookie set\n";

// Simulate manager AJAX environment
$_GESTOR['ajax'] = true;
$_GESTOR['ajax-opcao'] = 'salvar'; // Let's test the save function (keeping 'salvar' as expected by module)
$_GESTOR['modulo-id'] = 'admin-environment';

echo "Simulating AJAX environment with option=salvar\n";

// Simulate request data
$_REQUEST['usuario_recaptcha_active'] = 'true';
$_REQUEST['usuario_recaptcha_site'] = 'test-site-key';
$_REQUEST['usuario_recaptcha_server'] = 'test-server-key';

echo "Test data prepared\n";

// Include admin-environment module
require_once $gestorPath . '/modulos/admin-environment/admin-environment.php';

echo "admin-environment module included\n";

// First test reading
echo "Testing configuration reading...\n";
$envData = admin_environment_env_read();
echo "Configurations read: " . print_r($envData, true) . "\n";

echo "=== TEST COMPLETED ===\n";
