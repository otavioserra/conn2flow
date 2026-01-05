<?php
// Script to generate temporary authentication for admin-environment tests

echo "=== GENERATING AUTHENTICATION FOR TESTS ===\n";

// Simulate manager environment
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/instalador/';
$_SERVER['HTTP_USER_AGENT'] = 'TestScript/1.0';

// Define paths BEFORE including config.php
$gestorPath = __DIR__;
$_INDEX['sistemas-dir'] = $gestorPath . '/';

echo "Manager path: $gestorPath\n";

// Include config.php which loads everything
require_once $gestorPath . '/config.php';

echo "Config.php loaded\n";

// Include necessary libraries
require_once $gestorPath . '/bibliotecas/banco.php';
require_once $gestorPath . '/bibliotecas/gestor.php';
require_once $gestorPath . '/bibliotecas/ip.php';
require_once $gestorPath . '/bibliotecas/usuario.php';

echo "Libraries loaded\n";

// Check database connection
try {
    echo "Testing database connection...\n";
    $test = banco_select([
        'tabela' => 'usuarios',
        'campos' => ['COUNT(*) as total'],
        'extra' => "WHERE 1=1 LIMIT 1"
    ]);
    echo "Database connection OK\n";
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if administrator user (ID 1) exists
try {
    $usuario = banco_select([
        'unico' => true,
        'tabela' => 'usuarios',
        'campos' => ['id_usuarios', 'nome'],
        'extra' => "WHERE id_usuarios = 1"
    ]);

    if ($usuario) {
        echo "User found: " . $usuario['nome'] . " (ID: " . $usuario['id_usuarios'] . ")\n";

        $tokenFile = __DIR__ . '/.envAITestsToken';
        if (file_exists($tokenFile)) {
            $tokenJWT = trim(file_get_contents($tokenFile));
            echo "Token already exists, loading from file...\n";
        } else {
            // Generate authentication token
            echo "Generating authentication token...\n";

            // Delete old tokens for user 1
            banco_delete
            (
                "usuarios_tokens",
                "WHERE user_agent='".$_SERVER['HTTP_USER_AGENT']."' AND id_usuarios='".$usuario['id_usuarios']."'"
            );
            
            // Simulate JWT token generation as in usuario_gerar_token_autorizacao function
            $expiration = time() + $_CONFIG['cookie-lifetime'];
            $keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
            
            $fp = fopen($keyPublicPath,"r");
            $chavePublica = fread($fp,8192);
            fclose($fp);
            
            $tokenPubId = md5(uniqid(rand(), true));
            $pubIDValidation = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
            
            $tokenJWT = usuario_gerar_jwt(Array(
                'host' => $_SERVER['SERVER_NAME'],
                'expiration' => $expiration,
                'chavePublica' => $chavePublica,
                'pubID' => $tokenPubId,
            ));
            
            // Save to file
            file_put_contents($tokenFile, $tokenJWT);
            
            // Save to database as in original function
            gestor_incluir_biblioteca('ip');
            $ip = ip_get();
            
            $campos = null; $campo_sem_aspas_simples = null;
            $campo_nome = "id_usuarios"; $campo_valor = 1; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
            $campo_nome = "pubID"; $campo_valor = $tokenPubId; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
            $campo_nome = "pubIDValidation"; $campo_valor = $pubIDValidation; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
            $campo_nome = "expiration"; $campo_valor = $expiration; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
            $campo_nome = "ip"; $campo_valor = $ip; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
            $campo_nome = "user_agent"; $campo_valor = $_SERVER['HTTP_USER_AGENT']; $campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
            $campo_nome = "data_criacao"; $campo_valor = 'NOW()'; $campos[] = Array($campo_nome,$campo_valor,true);
            
            banco_insert_name($campos, "usuarios_tokens");
        }
        
        echo "🌎 Server Name: ".$_SERVER['SERVER_NAME']."!\n";
        echo "✅ Authentication token generated/loaded successfully!\n";
        echo "🍪 Cookie set to: " . $_CONFIG['cookie-authname'] . "\n";
        echo "🔑 JWT Token Value: " . $tokenJWT . "\n";
        echo "Now you can access: http://localhost/instalador/\n";
    } else {
        echo "❌ Administrator user (ID 1) not found in database\n";
        echo "Check if database was initialized correctly\n";
    }
} catch (Exception $e) {
    echo "Error checking user: " . $e->getMessage() . "\n";
}
