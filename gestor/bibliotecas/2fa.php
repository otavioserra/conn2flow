<?php
/**
 * Biblioteca de Autenticação de Dois Fatores (2FA)
 *
 * Implementa TOTP (RFC 6238 / RFC 4226) compatível com aplicativos autenticadores
 * (Google Authenticator, Authy, etc.) e o fluxo alternativo de código por e-mail.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-2fa'] = Array(
    'versao' => '1.0.0',
);

// ===== Constantes internas

if(!defined('TWO_FACTOR_BASE32_ALPHABET')){
    define('TWO_FACTOR_BASE32_ALPHABET', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');
}

// ===== Helpers Base32 (sem dependência de extensões externas)

/**
 * Codifica uma string binária em Base32 (RFC 4648, sem padding).
 *
 * @param string $data Dados binários.
 * @return string Texto Base32.
 */
function two_factor_base32_encode($data){
    if($data === '' || $data === null) return '';

    $binary = '';
    $tamanho = strlen($data);
    for($i = 0; $i < $tamanho; $i++){
        $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }

    $saida = '';
    foreach(str_split($binary, 5) as $bloco){
        $bloco = str_pad($bloco, 5, '0', STR_PAD_RIGHT);
        $saida .= TWO_FACTOR_BASE32_ALPHABET[bindec($bloco)];
    }

    return $saida;
}

/**
 * Decodifica uma string Base32 (RFC 4648) para binário.
 *
 * @param string $b32 Texto Base32.
 * @return string Dados binários.
 */
function two_factor_base32_decode($b32){
    $b32 = strtoupper((string)$b32);
    $b32 = preg_replace('/[^A-Z2-7]/', '', $b32);
    if($b32 === '') return '';

    $binary = '';
    $tamanho = strlen($b32);
    for($i = 0; $i < $tamanho; $i++){
        $pos = strpos(TWO_FACTOR_BASE32_ALPHABET, $b32[$i]);
        if($pos === false) continue;
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }

    $saida = '';
    foreach(str_split($binary, 8) as $byte){
        if(strlen($byte) < 8) break; // descarta bits remanescentes incompletos
        $saida .= chr(bindec($byte));
    }

    return $saida;
}

// ===== Funções principais

/**
 * Gera um segredo TOTP aleatório em Base32.
 *
 * @param int $length Quantidade de caracteres Base32 (padrão 16 = 80 bits).
 * @return string Segredo Base32.
 */
function two_factor_generate_secret($length = 16){
    $length = (int)$length;
    if($length < 16) $length = 16;

    $secret = '';
    $max = strlen(TWO_FACTOR_BASE32_ALPHABET) - 1;
    for($i = 0; $i < $length; $i++){
        $secret .= TWO_FACTOR_BASE32_ALPHABET[random_int(0, $max)];
    }

    return $secret;
}

/**
 * Monta a URI otpauth:// para renderização em QR Code.
 *
 * Formato: otpauth://totp/Conn2Flow:[email]?secret=[secret]&issuer=Conn2Flow
 *
 * @param string $email E-mail / conta do usuário (rótulo da conta).
 * @param string $secret Segredo Base32.
 * @return string URI otpauth.
 */
function two_factor_get_qr_code($email, $secret){
    $issuer = 'Conn2Flow';
    $label = rawurlencode($issuer) . ':' . rawurlencode($email);

    return 'otpauth://totp/' . $label
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

/**
 * Calcula um código HOTP (RFC 4226) para um contador específico.
 *
 * @param string $secret Segredo Base32.
 * @param int $counter Contador (no TOTP é floor(time()/30)).
 * @return string Código de 6 dígitos.
 */
function two_factor_hotp($secret, $counter){
    $key = two_factor_base32_decode($secret);
    if($key === '') return '';

    // Contador de 8 bytes big-endian (32 bits altos zerados, 32 baixos = counter).
    $binCounter = pack('N*', 0, (int)$counter);

    $hash = hash_hmac('sha1', $binCounter, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

    $truncated = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    );

    $code = $truncated % 1000000;

    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

/**
 * Valida um código TOTP informado pelo usuário, com tolerância de ±1 ciclo de 30s.
 *
 * @param string $secret Segredo Base32 do usuário.
 * @param string $code Código de 6 dígitos informado.
 * @return bool true se o código for válido na janela de tempo.
 */
function two_factor_validate_code($secret, $code){
    $code = preg_replace('/\D/', '', (string)$code);
    if(strlen($code) !== 6) return false;
    if($secret === '' || $secret === null) return false;

    $timeSlice = (int) floor(time() / 30);

    for($offset = -1; $offset <= 1; $offset++){
        $calculado = two_factor_hotp($secret, $timeSlice + $offset);
        if($calculado !== '' && hash_equals($calculado, $code)) return true;
    }

    return false;
}

/**
 * Gera um código numérico de 6 dígitos, grava-o no usuário com validade de 5
 * minutos e o envia por e-mail HTML.
 *
 * @param int $usuario_id ID do usuário (coluna id_usuarios).
 * @param string $email E-mail de destino.
 * @return bool true se o e-mail foi enviado.
 */
function two_factor_email_send_code($usuario_id, $email){
    global $_GESTOR;

    $usuario_id = (int)$usuario_id;
    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expira = date('Y-m-d H:i:s', time() + 300); // 5 minutos

    // ===== Persistir código e expiração no usuário

    banco_update_campo('two_factor_email_code', $codigo);
    banco_update_campo('two_factor_email_expire', $expira);
    banco_update_executar('usuarios', "WHERE id_usuarios='" . $usuario_id . "'");

    // ===== Montar e enviar o e-mail

    gestor_incluir_biblioteca('comunicacao');

    $assunto = 'Conn2Flow - Código de verificação (2FA)';
    $html = ''
        . '<h2>Código de verificação</h2>'
        . '<p>Use o código abaixo para concluir o seu login. Ele expira em 5 minutos.</p>'
        . '<p style="font-size:28px;font-weight:bold;letter-spacing:6px;">' . $codigo . '</p>'
        . '<p>Se você não tentou acessar a sua conta, ignore este e-mail.</p>';

    $enviado = comunicacao_email(Array(
        'destinatarios' => Array(
            Array('email' => $email),
        ),
        'mensagem' => Array(
            'assunto' => $assunto,
            'html' => $html,
        ),
    ));

    return $enviado ? true : false;
}

/**
 * Valida o código 2FA enviado por e-mail, conferindo valor e prazo de validade.
 * Em caso de sucesso, limpa o código para impedir reutilização.
 *
 * @param int $usuario_id ID do usuário (coluna id_usuarios).
 * @param string $code Código de 6 dígitos informado.
 * @return bool true se o código for válido e estiver dentro do prazo.
 */
function two_factor_email_validate($usuario_id, $code){
    $usuario_id = (int)$usuario_id;
    $code = preg_replace('/\D/', '', (string)$code);
    if(strlen($code) !== 6) return false;

    $usuario = banco_select(Array(
        'unico' => true,
        'tabela' => 'usuarios',
        'campos' => Array(
            'two_factor_email_code',
            'two_factor_email_expire',
        ),
        'extra' => "WHERE id_usuarios='" . $usuario_id . "'",
    ));

    if(!$usuario) return false;

    $armazenado = isset($usuario['two_factor_email_code']) ? (string)$usuario['two_factor_email_code'] : '';
    $expira = isset($usuario['two_factor_email_expire']) ? $usuario['two_factor_email_expire'] : null;

    if($armazenado === '' || empty($expira)) return false;
    if(strtotime($expira) < time()) return false;
    if(!hash_equals($armazenado, $code)) return false;

    // ===== Consumir o código (evita reuso)

    banco_update_campo('two_factor_email_code', '');
    banco_update_executar('usuarios', "WHERE id_usuarios='" . $usuario_id . "'");

    return true;
}

?>
