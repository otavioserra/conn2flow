<?php
/**
 * REQ-301 — arnês das bibliotecas mercadopago.php, pagamentos-gateways.php e da ponte em
 * plataforma-gateways.php, executado num processo à parte.
 *
 * Só a E/S é simulada: `banco_select` filtra `$GLOBALS['gateways']` pelas igualdades do WHERE
 * (na ordem de inserção, sem ORDER BY — a ordem canônica tem de sair da biblioteca), o log vai
 * para `$GLOBALS['logs']` e o HTTP do Mercado Pago sai pelo transporte injetável, que consome
 * `$GLOBALS['respostas']` e grava cada requisição em `$GLOBALS['http']`.
 *
 * Globais de injeção (definidas pelo cenário):
 *  - $GLOBALS['gateways']: linhas da tabela gateways_pagamentos.
 *  - $GLOBALS['tem_prioridade']: resposta de banco_campo_existe('prioridade', ...).
 *  - $GLOBALS['filtros']['ns.evento']: callable aplicado por hook_apply_filters.
 *  - $GLOBALS['respostas']: fila de ['http_code' => int, 'body' => string, 'error' => string].
 */

$raiz = dirname(__DIR__, 2) . '/gestor/';

$GLOBALS['avisos'] = [];
$GLOBALS['logs'] = [];
$GLOBALS['sqls'] = [];
$GLOBALS['http'] = [];
$GLOBALS['respostas'] = [];
$GLOBALS['gateways'] = [];
$GLOBALS['tem_prioridade'] = true;
$GLOBALS['filtros'] = [];

set_error_handler(function ($nivel, $mensagem, $arquivo, $linha) {
    $GLOBALS['avisos'][] = $mensagem . ' @ ' . basename($arquivo) . ':' . $linha;
    return true;
});

global $_GESTOR, $_CONFIG;
$_CONFIG = [];
$_GESTOR = [
    'bibliotecas-path' => $raiz . 'bibliotecas/',
    'logs-path' => sys_get_temp_dir() . '/req301-logs-' . getmypid() . '/',
    'url-raiz' => '/',
    'caminho' => [],
];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

function gestor_incluir_biblioteca($nome) {
    if (in_array($nome, ['stripe', 'paypal', 'mercadopago', 'pagamentos-gateways'], true)) {
        require_once $GLOBALS['_GESTOR']['bibliotecas-path'] . $nome . '.php';
    }
}
function log_disco($mensagem, $arquivo = '') { $GLOBALS['logs'][] = [$arquivo, $mensagem]; }
function banco_escape_field($v) { return str_replace("'", "''", (string)$v); }
function banco_campo_existe($campo, $tabela) { return $GLOBALS['tem_prioridade']; }
function hook_apply_filters(string $ns, string $evento, mixed $valor, mixed ...$args): mixed {
    $fn = $GLOBALS['filtros'][$ns . '.' . $evento] ?? null;
    return is_callable($fn) ? $fn($valor, ...$args) : $valor;
}
function banco_select($params = false) {
    $extra = (string)($params['extra'] ?? '');
    $GLOBALS['sqls'][] = $extra;
    preg_match_all("/(\\w+)\\s*=\\s*'((?:[^']|'')*)'/", explode(' ORDER BY ', $extra)[0], $m, PREG_SET_ORDER);
    $linhas = [];
    foreach ($GLOBALS['gateways'] as $linha) {
        foreach ($m as $cond) {
            if ((string)($linha[$cond[1]] ?? '') !== str_replace("''", "'", $cond[2])) continue 2;
        }
        $linhas[] = $linha;
    }
    if (!$linhas) return null;
    return isset($params['unico']) ? $linhas[0] : $linhas;
}

/** Transporte HTTP do Mercado Pago: registra a requisição e devolve a próxima resposta da fila. */
$_GESTOR['biblioteca-mercadopago']['transporte'] = function (array $req) {
    $GLOBALS['http'][] = $req;
    return array_shift($GLOBALS['respostas']) ?? ['http_code' => 500, 'body' => '{}', 'error' => ''];
};

function resposta($codigo, $corpo) { $GLOBALS['respostas'][] = ['http_code' => $codigo, 'body' => json_encode($corpo), 'error' => '']; }

/** Linha de gateway com padrões razoáveis. */
function gateway(array $campos) {
    static $seq = 0;
    $seq++;
    return array_replace([
        'id_gateways_pagamentos' => $seq, 'id' => 'gw-' . $seq, 'nome' => 'Gateway ' . $seq, 'tipo' => 'mercadopago',
        'client_id' => 'TEST-pub', 'client_secret' => 'TEST-token', 'webhook_id' => null, 'webhook_secret' => 'segredo',
        'ambiente' => 'S', 'configuracoes' => null, 'moeda' => 'BRL', 'padrao' => 'N', 'status' => 'A',
    ], $campos);
}

/** Header x-signature do Mercado Pago para o manifesto oficial. */
function assinatura_mp($segredo, $dataId, $requestId, $ts) {
    $manifesto = ($dataId !== null ? 'id:' . strtolower($dataId) . ';' : '') . ($requestId !== '' ? 'request-id:' . $requestId . ';' : '') . 'ts:' . $ts . ';';
    return 'ts=' . $ts . ',v1=' . hash_hmac('sha256', $manifesto, $segredo);
}

/** Header Stripe-Signature. */
function assinatura_stripe($segredo, $payload, $t = null) {
    $t = $t ?? time();
    return 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $payload, $segredo);
}

/** Cabeçalho de autorização de uma requisição registrada. */
function header_http(array $req, $nome) {
    foreach ($req['headers'] as $h) {
        [$k, $v] = array_map('trim', explode(':', $h, 2));
        if (strcasecmp($k, $nome) === 0) return $v;
    }
    return null;
}

gestor_incluir_biblioteca('stripe');
gestor_incluir_biblioteca('paypal');
gestor_incluir_biblioteca('mercadopago');
gestor_incluir_biblioteca('pagamentos-gateways');

define('PLATAFORMA_GATEWAYS_SEM_INICIAR', true);
require_once $raiz . 'controladores/plataforma-gateways/plataforma-gateways.php';
