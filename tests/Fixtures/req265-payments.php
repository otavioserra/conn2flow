<?php
function gestor_incluir_biblioteca($n) {}
function gestor_asset_version($n) { return '1'; }
function banco_escape_field($v) { return str_replace("'", "''", (string)$v); }
function banco_query($sql) { $GLOBALS['queries'][] = $sql; return str_contains($sql,'GET_LOCK') ? ['acquired' => $GLOBALS['lock']] : true; }
function banco_fetch_assoc($r) { return $r; }
function plataforma_gateways_401($m) { throw new RuntimeException('401'); }
function plataforma_gateways_resposta_erro($c, $m) { throw new RuntimeException((string)$c); }
function check265($ok, $m) { if (!$ok) throw new RuntimeException($m); }
$root = dirname(__DIR__, 2);
$_GESTOR = ['bibliotecas-path' => $root.'/gestor/bibliotecas/'];
require_once $root.'/gestor/bibliotecas/stripe.php';
require_once $root.'/gestor/bibliotecas/pagamentos.php';
require_once $root.'/gestor/bibliotecas/comunicacao.php';
require_once $root.'/gestor/bibliotecas/formulario.php';
$GLOBALS['lock'] = 1;
check265(pagamentos_com_trava('stripe', fn() => 42) === 42, 'Callback result lost');
check265(str_contains(end($GLOBALS['queries']), 'RELEASE_LOCK'), 'Lock not released');
try { pagamentos_com_trava('stripe', fn() => throw new RuntimeException('business')); } catch (RuntimeException $e) { check265($e->getMessage() === 'business', 'Unexpected error'); }
check265(str_contains(end($GLOBALS['queries']), 'RELEASE_LOCK'), 'Exceptional lock not released');
$GLOBALS['lock'] = 0;
$called = false;
try { pagamentos_com_trava('stripe', function() use (&$called) { $called = true; }); } catch (RuntimeException $e) {}
check265(!$called, 'Write allowed without lock');
check265(array_column(stripe_transacao_referencias(['id' => 'in_a1','payment_intent' => ['id' => 'pi_b2'],'charge' => 'ch_c3','customer' => 'cus_x','subscription' => 'sub_y']), 'value') === ['in_a1','pi_b2','ch_c3'], 'Financial IDs not normalized');
check265(array_column(stripe_transacao_referencias(['id' => 'inpay_new', 'invoice' => 'in_a1', 'payment' => ['payment_intent' => 'pi_b2']]), 'value') === ['in_a1','pi_b2'], 'Invoice payment event references missing');
$_GESTOR = ['modulo-config' => ['nome-site' => 'Marca & Co'], 'url-full-http' => 'https://test.local/', 'assets-path' => sys_get_temp_dir().'/', 'contents-path' => sys_get_temp_dir().'/'];
$_CONFIG = [];
$html = comunicacao_email_marca('<img src="#brand_logo#" alt="#brand_name# Logo">#brand_name#');
check265(!str_contains($html, '#brand_name#') && str_contains($html, 'Marca &amp; Co'), 'Brand fallback failed');
$r = formulario_email_processar_imagens($html);
check265(str_contains($r['html'], 'https://test.local/images/Logomarca200.png'), 'Absolute logo fallback failed');
$file = tempnam(sys_get_temp_dir(), 'c2f-brand-');
file_put_contents($file, 'image');
try {
    $r = formulario_email_processar_imagens('<img src="@[[pagina#url-raiz]]@'.basename($file).'?v=1">');
    check265(count($r['imagens']) === 1 && str_contains($r['html'], 'cid:'), 'Local logo not embedded');
} finally { unlink($file); }
$source = file_get_contents($root.'/gestor/controladores/plataforma-gateways/plataforma-gateways.php');
preg_match('/function plataforma_gateways_stripe_confirmar_recebimento\(.*?\r?\n}\r?\n/s', $source, $match);
eval($match[0]);
plataforma_gateways_stripe_confirmar_recebimento(['processed' => true]);
foreach ([['reason' => 'subscription-not-found','persisted' => true], ['reason' => 'invalid-signature'], ['reason' => 'receiver-error'], null] as $result) {
    try { plataforma_gateways_stripe_confirmar_recebimento($result); throw new LogicException('Failure acknowledged'); }
    catch (RuntimeException $e) { check265(in_array($e->getMessage(), ['401','503']), 'Wrong webhook failure status'); }
}
echo "REQ-265: aliases, lock lifecycle, brand, logo embedding and webhook acknowledgments passed.\n";
