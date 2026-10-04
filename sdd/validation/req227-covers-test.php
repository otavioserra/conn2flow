<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2).'/gestor/modulos/dashboard/dashboard-covers.php';

$root = sys_get_temp_dir().'/req227-'.bin2hex(random_bytes(6));
mkdir($root.'/assets/modulos/covers', 0777, true);
file_put_contents($root.'/assets/modulos/covers/admin-paginas.webp', 'test-fixture');
$checks = 0;
function check227(bool $condition, string $description): void {
	global $checks;
	if(!$condition){ throw new RuntimeException($description); }
	$checks++;
}
try {
	$url = dashboard_capa_modulo_url('admin-paginas', '/painel/distribuido/', $root);
	check227(str_starts_with($url, '/painel/distribuido/modulos/covers/admin-paginas.webp?v='), 'Preserve panel prefix');
	check227(dashboard_capa_modulo_url('not-installed', '/', $root) === '', 'Missing covers use existing icon');
	foreach(['../admin-paginas', 'admin-paginas.webp', 'admin/paginas', 'admin-paginas?x=1', 'admin-paginas"', '', null] as $id){
		check227(dashboard_capa_modulo_url($id, '/', $root) === '', 'Reject unsafe or invalid module id');
	}
	$svg = dashboard_capa_modulo_svg('/assets/a.webp?x="&y=<');
	check227(str_contains($svg, '&quot;&amp;y=&lt;'), 'Escape SVG href attribute');
	check227(str_contains($svg, 'viewBox="0 0 1024 1024"'), 'Square cover with existing SVG sizing');
	check227(str_contains($svg, 'aria-hidden="true"'), 'Decorative image does not repeat module label');
	$before = dashboard_capa_modulo_url('admin-paginas', '/', $root);
	touch($root.'/assets/modulos/covers/admin-paginas.webp', time() + 5);
	clearstatcache();
	check227($before !== dashboard_capa_modulo_url('admin-paginas', '/', $root), 'Updated image invalidates browser cache');
	echo json_encode(['checks' => $checks, 'result' => 'pass'], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
	unlink($root.'/assets/modulos/covers/admin-paginas.webp');
	rmdir($root.'/assets/modulos/covers');
	rmdir($root.'/assets/modulos');
	rmdir($root.'/assets');
	rmdir($root);
}
