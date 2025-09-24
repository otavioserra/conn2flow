<?php
// Script para gerar autenticação temporária para testes do admin-environment

echo "=== GERANDO AUTENTICAÇÃO PARA TESTES ===\n";

// Simular ambiente do gestor
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/instalador/';
$_SERVER['HTTP_USER_AGENT'] = 'TestScript/1.0';

// Definir caminhos ANTES de incluir config.php
$gestorPath = __DIR__;
$_INDEX['sistemas-dir'] = $gestorPath . '/';

echo "Caminho do gestor: $gestorPath\n";

// Incluir config.php que carrega tudo
require_once $gestorPath . '/config.php';

echo "Config.php carregado\n";

// Incluir bibliotecas necessárias
require_once $gestorPath . '/bibliotecas/banco.php';
require_once $gestorPath . '/bibliotecas/gestor.php';
require_once $gestorPath . '/bibliotecas/ip.php';
require_once $gestorPath . '/bibliotecas/usuario.php';

echo "Bibliotecas carregadas\n";

// Verificar conexão com banco
try {
    echo "Testando conexão com banco...\n";
    $test = banco_select([
        'tabela' => 'usuarios',
        'campos' => ['COUNT(*) as total'],
        'extra' => "WHERE 1=1 LIMIT 1"
    ]);
    echo "Conexão com banco OK\n";
} catch (Exception $e) {
    echo "Erro na conexão com banco: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar se existe usuário administrador (ID 1)
try {
    $usuario = banco_select([
        'tabela' => 'usuarios',
        'campos' => ['id_usuarios', 'nome'],
        'extra' => "WHERE id_usuarios = 1"
    ]);

    if ($usuario) {
        echo "Usuário encontrado: " . $usuario['nome'] . " (ID: " . $usuario['id_usuarios'] . ")\n";

        // Gerar token de autenticação
        echo "Gerando token de autenticação...\n";
        
        // Simular a geração do token JWT como na função usuario_gerar_token_autorizacao
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
        
        // Salvar no banco como na função original
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
        
        echo "✅ Token de autenticação gerado com sucesso!\n";
        echo "🍪 Cookie definido para: " . $_CONFIG['cookie-authname'] . "\n";
        echo "🔑 Valor do token JWT: " . $tokenJWT . "\n";
        echo "Agora você pode acessar: http://localhost/instalador/admin-environment/\n";
    } else {
        echo "❌ Usuário administrador (ID 1) não encontrado no banco\n";
        echo "Verifique se o banco foi inicializado corretamente\n";
    }
} catch (Exception $e) {
    echo "Erro ao verificar usuário: " . $e->getMessage() . "\n";
}