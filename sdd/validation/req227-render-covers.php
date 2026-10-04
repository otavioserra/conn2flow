<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2).'/gestor/modulos/dashboard/dashboard-covers.php';
$base = dirname(__DIR__, 3);
$records = json_decode(file_get_contents(__DIR__.'/req227-covers.json'), true, 512, JSON_THROW_ON_ERROR);
foreach($records as &$record){
	$prefix = $record['repository'] === 'conn2flow' ? '/core/' : '/site/';
	$record['url'] = dashboard_capa_modulo_url($record['id'], $prefix, $base.'/'.$record['repository'].'/gestor');
	if($record['url'] === ''){ throw new RuntimeException('Cover not installed: '.$record['id']); }
	$record['svg'] = dashboard_capa_modulo_svg($record['url']);
}
unset($record);
echo json_encode($records, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
